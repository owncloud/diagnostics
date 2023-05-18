<?php
/**
 * @author Jörn Firedrich Dreyer <jfd@butonic.de>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Diagnostics\Command\Db;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCP\Files\Folder;
use OCP\IDBConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Stress extends Command {
	protected function configure() {
		$this
			->setName('db:stress')
			->setDescription('Start a process to constantly query the db. Run multiple times (~3-MaxRequestWorkers) on all frontend servers.')
			->addOption(
				'rwSplit',
				's', // split
				InputOption::VALUE_NONE,
				'UPDATE and SELECTs a value from the appconfig table. Each process will use its own row.'
			)
			->addOption(
				'readCommited',
				'r', // readCommited
				InputOption::VALUE_NONE,
				'UPDATES and SELECTs a value from the appconfig table inside a transaction. Each process updates the same row.'
			)
			->addOption(
				'filecachePut',
				'f', // filecache put
				InputOption::VALUE_NONE,
				'test putting entries in the filecache, uses a the root storage and creates dirs like \'/diagnostics/fci-[timestamp]\''
			)
			->addOption(
				'cleanup',
				'c',
				InputOption::VALUE_NONE,
				'removes the stress related rows from the appconfig and filecache tables'
			)
		;
	}

	protected function testRWSplit(IDBConnection $connection, OutputInterface $output) {
		$key = 'rwsplit-'.\uniqid();
		$i = 0;

		// add initial value
		$insert = $connection->getQueryBuilder();
		$insert->insert('appconfig')
			->values([
				'appid' => '?',
				'configkey' => '?',
				'configvalue' => '?',
			])
			->setParameters([
				0 => 'diagnostics',
				1 => $key,
				2 => $i
			]);
		$insert->execute();

		$update = $connection->getQueryBuilder();
		$update->update('appconfig')
			->where('appid = ?')
			->andWhere('configkey = ?')
			->setParameters([
				0 => 'diagnostics',
				1 => $key
			]);

		$select = $connection->getQueryBuilder();
		$select->select('configvalue')
			->from('appconfig')
			->where('appid = ?')
			->andWhere('configkey = ?')
			->setParameters([
				0 => 'diagnostics',
				1 => $key
			]);

		// start update loop

		$p = new ProgressBar($output);
		$p->start();
		do {
			$i++;
			$update->set('configvalue', $update->expr()->literal($i));
			$update->execute();

			$result = $select->execute();
			/* @phan-suppress-next-line PhanDeprecatedFunction */
			$value = $result->fetch();

			$p->advance();
		} while ((int)$value['configvalue'] === $i);

		$p->finish();

		$output->writeln("<error>Updated configvalue to $i but read $value</error>");
	}

	protected function testReadCommited(IDBConnection $connection, OutputInterface $output) {
		$i = 0;

		// try adding initial value
		$insert = $connection->getQueryBuilder();
		$insert->insert('appconfig')
			->values([
				'appid' => '?',
				'configkey' => '?',
				'configvalue' => '?',
			])
			->setParameters([
				0 => 'diagnostics',
				1 => 'rwsplit-readcommited',
				2 => $i
			]);
		try {
			$insert->execute();
		} catch (UniqueConstraintViolationException $e) {
			//ignore
		}

		$update = $connection->getQueryBuilder();
		$update->update('appconfig')
			->set('configvalue', $update->createParameter('value'))
			->where('appid = :appid')
			->andWhere('configkey = :configkey')
			->setParameters([
				'appid' => 'diagnostics',
				'configkey' => 'rwsplit-readcommited'
			]);

		$select = $connection->getQueryBuilder();
		$select->select('configvalue')
			->from('appconfig')
			->where('appid = ?')
			->andWhere('configkey = ?')
			->setParameters([
				0 => 'diagnostics',
				1 => 'rwsplit-readcommited'
			]);

		// start update loop

		$p = new ProgressBar($output);
		$p->start();
		do {
			$connection->beginTransaction();

			// read current value
			$result = $select->execute();
			/* @phan-suppress-next-line PhanDeprecatedFunction */
			$value = $result->fetch();
			$i = (int)$value['configvalue'];

			// update
			$i++; // yes, this could be done in plain SQL, however we are trying to test read committed
			$update->setParameter('value', $i);
			$update->execute();

			// read current value
			$result = $select->execute();
			/* @phan-suppress-next-line PhanDeprecatedFunction */
			$value = $result->fetch();

			$connection->commit();

			$p->advance();
		} while ((int)$value['configvalue'] === $i);

		$p->finish();

		$output->writeln("<error>Updated configvalue to $i but read $value</error>");
	}

	protected function testFilecachePut(Folder $folder, OutputInterface $output) {
		$cache = $folder->getStorage()->getCache();

		$i = 0;
		$oldTime = 0;
		$etag = 'diag-fci-pid:'.\getmypid().'-i:';

		$output->writeln("etag prefix of this process is '$etag'");

		// start update loop

		$p = new ProgressBar($output);
		$p->start();
		$insertsPerSec=0;
		do {
			$time = \time();
			if ($oldTime < $time) {
				$insertsPerSec = $i;
				$i = 0;
			}
			$path = 'diagnostics/fci-'.$time;
			$id = $cache->put($path, [
				'size' => 0,
				'mtime' => $time,
				'mimetype' => 'httpd/directory',
				'etag' => $etag.$i++
			]);
			$folder->getStorage()->getPropagator()->propagateChange($path, $time);
			$p->advance();
			if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
				$output->write(" $insertsPerSec inserts/s");
			}
			if ($oldTime < $time && $output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
				$output->writeln("");
			}
			$oldTime = $time;
		} while ($id > -1);

		$p->finish();

		$output->writeln("<error>$id < 0</error>");
	}

	protected function cleanup(IDBConnection $connection) {
		$root = \OC::$server->getRootFolder();
		if ($root->nodeExists('diagnostics')) {
			$folder = $root->get('diagnostics');
			$folder->getStorage()->getCache()->remove('diagnostics');
		}

		$delete = $connection->getQueryBuilder();
		$delete->delete('appconfig')
			->where($delete->expr()->eq('appid', $delete->createNamedParameter('diagnostics')))
			->andWhere($delete->expr()->like('configkey', $delete->createNamedParameter('rwsplit-%')));
		$delete->execute();
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$options = $input->getOptions();

		if (\count(\array_intersect_assoc($options, ['rwSplit' => true, 'readCommited' => true, 'filecachePut' => true, 'cleanup' => true])) > 1) {
			$output->writeln('<error>Only one option of rwSplit, readCommited, filecachePut and cleanup can be used at the same time</error>');
		}

		try {
			$connection = \OC::$server->getDatabaseConnection();
			if ($options['rwSplit']) {
				$this->testRWSplit($connection, $output);
			} elseif ($options['readCommited']) {
				$this->testReadCommited($connection, $output);
			} elseif ($options['filecachePut']) {
				$root = \OC::$server->getRootFolder();
				if ($root->nodeExists('/diagnostics')) {
					/** @var \OCP\Files\Folder $folder */
					$folder = $root->get('/diagnostics');
				} else {
					/** @var \OCP\Files\Folder $folder */
					$folder = $root->newFolder('/diagnostics');
				}
				$this->testFilecachePut($folder, $output);
			} elseif ($options['cleanup']) {
				$this->cleanup($connection);
			}
		} catch (\Exception $e) {
			// we always want a stacktrace
			$output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
			throw $e;
		}
		return 0;
	}
}
