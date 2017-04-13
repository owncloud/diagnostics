<?php
/**
 * @author Piotr Mrowczynski <piotr@owncloud.com>
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

namespace OCA\Diagnostics\Tests\Log;

use OCA\Diagnostics\Log\OwncloudLog;
use Test\TestCase;
use OCP\IConfig;

class LogTest extends TestCase {

	/** @var OwncloudLog */
	private $logger;

	/** @var IConfig */
	private $config;

	protected function setUp() {

		parent::setUp();
		@mkdir(\OC::$SERVERROOT.'/data-autotest');

		$this->config = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()->getMock();


		// Create a map of arguments to return values.
		$map = [
			['datadirectory', \OC::$SERVERROOT.'/data', \OC::$SERVERROOT.'/data-autotest'],
			['logdateformat', 'c', 'c'],
			['logtimezone', 'UTC', 'UTC']
		];
		$this->config
			->method('getSystemValue')
			->will($this->returnValueMap($map));

		$this->logger = new OwncloudLog(
			$this->config
		);
	}

	public function testGetLogFilePath() {
		$logFile = $this->logger->getLogFilePath();
		$this->assertContains('data-autotest/diagnostic.log', $logFile);
	}

	public function testWriteLog() {
		$logFile = $this->logger->getLogFilePath();
		$this->assertContains('data-autotest/diagnostic.log', $logFile);

		// Clean log
		$handle = @fopen($logFile, 'w');
		fclose($handle);

		// Now write to log
		$this->logger->write("SUMMARY", []);

		$handle = @fopen($logFile, 'r');
		$contents = fread($handle, 8192);
		fclose($handle);
		$parsedContents = json_decode($contents);
		$this->assertSame("SUMMARY", $parsedContents->{'type'});
		$this->assertSame(0, sizeof($parsedContents->{'diagnostics'}));
	}

	public function testCleanLog() {
		$logFile = $this->logger->getLogFilePath();
		$this->assertContains('data-autotest/diagnostic.log', $logFile);

		$handle = @fopen($logFile, 'w');
		fwrite($handle, "test");
		fclose($handle);
		$handle = @fopen($logFile, 'r');
		$contents = fread($handle, 8192);
		fclose($handle);
		$this->assertSame("test", $contents);

		// Now clean log
		$this->logger->clean();

		$handle = @fopen($logFile, 'r');
		$contents = fread($handle, 8192);
		fclose($handle);
		$this->assertSame("", $contents);
	}
}
