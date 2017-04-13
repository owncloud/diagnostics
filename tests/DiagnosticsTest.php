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

namespace OCA\Diagnostics\Tests;

use OCA\Diagnostics\Diagnostics;
use OCP\IConfig;

/**
 * @package OCA\Diagnostics\Tests
 * @group DB
 */
class DiagnosticsTest extends \Test\TestCase {
	/**
	 * @var IConfig
	 */
	private $config;

	/** @var Diagnostics */
	private $diagnostics;

	public function setUp() {
		parent::setUp();

		@mkdir(\OC::$SERVERROOT.'/data-autotest');
		$mainConfig = new \OC\Config(\OC::$SERVERROOT . '/config/');
		$this->config = new \OC\AllConfig(new \OC\SystemConfig($mainConfig));
		$this->config->setSystemValue("datadirectory", \OC::$SERVERROOT . '/data-autotest');
		$this->config->setSystemValue('logdateformat', 'c');
		$this->config->setSystemValue('logtimezone', 'UTC');

		$this->diagnostics = new Diagnostics(
			$this->config
		);
	}

	public function tearDown() {
		$this->config->deleteSystemValue("datadirectory");
		$this->config->deleteSystemValue("logdateformat");
		$this->config->deleteSystemValue("logtimezone");
		parent::tearDown();
	}

	/**
	 * @return array
	 */
	public function diagnosticLevels() {
		return [
			[ Diagnostics::LOG_ALL ],
			[ Diagnostics::LOG_EVENTS ],
			[ Diagnostics::LOG_QUERIES ],
			[ Diagnostics::LOG_SUMMARY ],
			[ Diagnostics::LOG_NOTHING ],
		];
	}

	/**
	 * @dataProvider diagnosticLevels
	 */
	public function testDiagnosticLogLevel($diagnosticLevel) {
		$this->diagnostics->setDiagnosticLogLevel($diagnosticLevel);
		$diagnosticLevelReturn = $this->diagnostics->getDiagnosticLogLevel();
		$this->assertSame($diagnosticLevel, $diagnosticLevelReturn);
	}

	/**
	 * @return array
	 */
	public function enableDebugData() {
		return [
			[ true ],
			[ false ],
		];
	}

	/**
	 * @dataProvider enableDebugData
	 *
	 * @param bool $debugValue
	 */
	public function testEnableDebug($debugValue) {
		$this->diagnostics->setDebug($debugValue);
		$isDebugEnabled = $this->diagnostics->isDebugEnabled();
		$this->assertSame($debugValue, $isDebugEnabled);
	}

	public function testLogging() {
		// Check total size of log
		$contentSize = $this->diagnostics->getLogFileSize();
		$this->assertSame(0, $contentSize);
		
		$logFile = \OC::$SERVERROOT . '/data-autotest'.'/diagnostic.log';
		$handle = @fopen($logFile, 'w');
		fclose($handle);
		
		$this->diagnostics->recordQuery("SELECT", ["some params"], 100.1);
		$this->diagnostics->recordQuery("DELETE", ["some params"], 200.899);
		$this->diagnostics->recordEvent("mountFS", 10.1);
		$this->diagnostics->recordEvent("APPLoad", 0.1);
		$this->diagnostics->recordSummary(2, 300.999, 2, 2, 10.2);

		$handle = @fopen($logFile, 'r');
		$content = [];
		$logFileSize = 0;
		while (($line = @fgets($handle)) !== false) {
			$logFileSize += strlen($line);
			$content[] = json_decode($line);
		}
		fclose($handle);

		// Check total size of log
		$this->assertSame(5, sizeof($content));
		$contentSize = $this->diagnostics->getLogFileSize();
		$this->assertSame($logFileSize, $contentSize);

		// Check if query log contains correct parameters
		$this->assertSame(Diagnostics::QUERY_TYPE, $content[0]->{'type'});
		$this->assertSame("SELECT", $content[0]->{'diagnostics'}->{'sqlStatement'});
		$this->assertSame(1, sizeof($content[0]->{'diagnostics'}->{'sqlParams'}));
		$this->assertContains("some params", $content[0]->{'diagnostics'}->{'sqlParams'});
		$this->assertSame(100.1, $content[0]->{'diagnostics'}->{'sqlQueryDurationmsec'});

		// Check if event log contains correct parameters
		$this->assertSame(Diagnostics::EVENT_TYPE, $content[2]->{'type'});
		$this->assertSame("mountFS", $content[2]->{'diagnostics'}->{'eventDescription'});
		$this->assertSame(10.1, $content[2]->{'diagnostics'}->{'eventDurationmsec'});

		// Check if summary log contains correct parameters
		$this->assertSame(Diagnostics::SUMMARY_TYPE, $content[4]->{'type'});
		$this->assertSame(2, $content[4]->{'diagnostics'}->{'totalSQLQueries'});
		$this->assertSame(300.999, $content[4]->{'diagnostics'}->{'totalSQLDurationmsec'});
		$this->assertSame(2, $content[4]->{'diagnostics'}->{'totalSQLParams'});
		$this->assertSame(2, $content[4]->{'diagnostics'}->{'totalEvents'});
		$this->assertSame(10.2, $content[4]->{'diagnostics'}->{'totalEventsDurationmsec'});

		// Clean log and expect it to be cleaned
		$this->diagnostics->cleanLog();
		$handle = @fopen($logFile, 'r');
		$contents = fread($handle, 8192);
		fclose($handle);
		$this->assertSame("", $contents);
	}

	public function testDownloadLog() {
		$response = $this->diagnostics->downloadLog();

		$this->assertInstanceOf('\OCP\AppFramework\Http\StreamResponse', $response);
		$headers = $response->getHeaders();
		$this->assertEquals('application/octet-stream', $headers['Content-Type']);
		$this->assertEquals('attachment; filename="diagnostic.log"', $headers['Content-Disposition']);
	}
}
