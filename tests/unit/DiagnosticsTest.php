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
use OCA\Diagnostics\Log\OwncloudLog;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\IUser;
use OCP\AppFramework\Http\StreamResponse;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @package OCA\Diagnostics\Tests
 * @group DB
 */
class DiagnosticsTest extends \Test\TestCase {
	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var IUserSession | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $session;

	/** @var Diagnostics */
	private $diagnostics;

	public function setUp(): void {
		parent::setUp();

		@\mkdir(\OC::$SERVERROOT.'/data-autotest');
		$eventDispatcher = $this->createMock(EventDispatcher::class);
		$mainConfig = new \OC\Config(\OC::$SERVERROOT . '/config/');
		$systemConfig = new \OC\SystemConfig($mainConfig);
		$this->config = new \OC\AllConfig($systemConfig, $eventDispatcher);
		$this->config->setSystemValue('datadirectory', \OC::$SERVERROOT . '/data-autotest');
		$this->config->setSystemValue('logdateformat', 'c');
		$this->config->setSystemValue('logtimezone', 'UTC');
		$this->session = $this->getMockBuilder(IUserSession::class)->getMock();

		$this->diagnostics = new Diagnostics(
			$this->config,
			$this->session
		);
	}

	public function tearDown(): void {
		$this->config->deleteSystemValue('datadirectory');
		$this->config->deleteSystemValue('datadirectory');
		$this->config->deleteSystemValue('logdateformat');
		parent::tearDown();
	}

	/**
	 * @return array
	 */
	public function diagnostedUsers() {
		return [
			['[]'],
			['["{"id":"admin","displayname":"Admin, Test"}"]'],
			['["{"id":"admin","displayname":"Admin, Test"}","{"id":"user100","displayname":"User, 100"}"]'],
		];
	}

	/**
	 * @dataProvider diagnostedUsers
	 */
	public function testSetDiagnosticForUsers($diagnostedUsersString) {
		$this->config->deleteAppValue('diagnostics', 'diagnosedUsers');
		$diagnosedUsers = $this->diagnostics->getDiagnosedUsers();
		$this->assertSame('[]', $diagnosedUsers);

		$this->diagnostics->setDiagnosticForUsers($diagnostedUsersString);
		$diagnosedUsers = $this->diagnostics->getDiagnosedUsers();
		$this->assertSame($diagnostedUsersString, $diagnosedUsers);
	}

	/**
	 * @return array
	 */
	public function activationConditionsUsers() {
		return [
			['[{"id":"diagnosedUser1","displayname":"diagnosedUser1"},{"id":"diagnosedUser2","displayname":"diagnosedUser2"}]', 'diagnosedUser1', true],
			['[{"id":"diagnosedUser1","displayname":"diagnosedUser1"},{"id":"diagnosedUser2","displayname":"diagnosedUser2"}]', '', false],
			['[{"id":"diagnosedUser1","displayname":"diagnosedUser1"},{"id":"diagnosedUser2","displayname":"diagnosedUser2"}]', null, false],
			['[{"id":"diagnosedUser","displayname":"diagnosedUser"}]', 'notDiagnosedUser', false],
			['[]', 'notDiagnosedUser', false],
		];
	}

	/**
	 * @dataProvider activationConditionsUsers
	 *
	 * @param bool $debugValue
	 * @param string $diagnosticLevel
	 * @param bool $isActivatedExpected
	 */
	public function testIsDiagnosticActivatedForSessionWithUsers($userString, $loggedUser, $isActivatedExpected) {
		// Set value and check if correct
		$this->diagnostics->setDiagnosticLogLevel(Diagnostics::LOG_ALL);
		$diagnosticLevelReturn = $this->diagnostics->getDiagnosticLogLevel();
		$this->assertSame(Diagnostics::LOG_ALL, $diagnosticLevelReturn);

		// Set value and check if correct
		$this->diagnostics->setDebug(false);
		$isDebugEnabled = $this->diagnostics->isDebugEnabled();
		$this->assertFalse($isDebugEnabled);

		// Set diagnosed users in DB
		$this->diagnostics->setDiagnosticForUsers($userString);

		$user = $this->getMockBuilder(IUser::class)->getMock();
		$user->expects($this->once())
			->method('getUID')
			->willReturn($loggedUser);
		$this->session->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$isActivated = $this->diagnostics->isDiagnosticActivatedForSession();
		$this->assertSame($isActivatedExpected, $isActivated);
	}

	/**
	 * @return array
	 */
	public function activationConditions() {
		return [
			[ true , Diagnostics::LOG_ALL, true ],
			[ false, Diagnostics::LOG_ALL, false ],
			[ true , Diagnostics::LOG_NOTHING, false ],
			[ false, Diagnostics::LOG_NOTHING, false ],
		];
	}

	/**
	 * @dataProvider activationConditions
	 *
	 * @param bool $debugValue
	 * @param string $diagnosticLevel
	 * @param bool $isActivatedExpected
	 */
	public function testIsDiagnosticActivatedForSessionWithDebugAndLevel($debugEnabled, $diagnosticLevel, $isActivatedExpected) {
		$this->config->deleteAppValue('diagnostics', 'diagnosticLogLevel');
		$this->config->deleteSystemValue('debug');
		// Check that isDebugEnabled will return default variable
		$isDebugEnabled = $this->diagnostics->isDebugEnabled();
		$this->assertFalse($isDebugEnabled);

		// Check that getDiagnosticLogLevel will return default variable
		$diagnosticLevelReturn = $this->diagnostics->getDiagnosticLogLevel();
		$this->assertSame(Diagnostics::LOG_NOTHING, $diagnosticLevelReturn);

		// Set value and check if correct
		$this->diagnostics->setDiagnosticLogLevel($diagnosticLevel);
		$diagnosticLevelReturn = $this->diagnostics->getDiagnosticLogLevel();
		$this->assertSame($diagnosticLevel, $diagnosticLevelReturn);

		// Set value and check if correct
		$this->diagnostics->setDebug($debugEnabled);
		$isDebugEnabled = $this->diagnostics->isDebugEnabled();
		$this->assertSame($debugEnabled, $isDebugEnabled);

		$isActivated = $this->diagnostics->isDiagnosticActivatedForSession();
		$this->assertSame($isActivatedExpected, $isActivated);
	}

	private function initRecords() {
		$this->diagnostics->recordQuery('SELECT', ['some params'], 100.1, 1492118966.034);
		$this->diagnostics->recordQuery('DELETE', ['some params'], 200.899, 1492118966.100);
		$this->diagnostics->recordEvent('APPLoad', 0.1, 1492118966.234);
		$this->diagnostics->recordEvent('mountFS', 10.1, 1492118966.854);
		$this->diagnostics->recordSummary(2, 300.999, 2, 2, 10.2);
	}

	public function testLogging() {
		// Check total size of log
		$this->diagnostics->cleanLog();
		$contentSize = $this->diagnostics->getLogFileSize();
		$this->assertSame(0, $contentSize);

		$logFile = \OC::$SERVERROOT . '/data-autotest'.'/diagnostic.log';
		$handle = @\fopen($logFile, 'w');
		\fclose($handle);

		$this->diagnostics->setDiagnosticLogLevel(Diagnostics::LOG_ALL);
		$this->initRecords();

		$handle = @\fopen($logFile, 'r');
		$content = [];
		$logFileSize = 0;
		while (($line = @\fgets($handle)) !== false) {
			$logFileSize += \strlen($line);
			$content[] = \json_decode($line);
		}
		\fclose($handle);

		// Check total size of log
		$this->assertSame(5, \count($content));
		$contentSize = $this->diagnostics->getLogFileSize();
		$this->assertSame($logFileSize, $contentSize);

		// Check if query log contains correct parameters
		$this->assertSame(OwncloudLog::QUERY_TYPE, $content[0]->{'type'});
		$this->assertSame('SELECT', $content[0]->{'diagnostics'}->{'sqlStatement'});
		$this->assertStringContainsString('some params', $content[0]->{'diagnostics'}->{'sqlParams'});
		$this->assertSame(100.1, $content[0]->{'diagnostics'}->{'sqlQueryDurationmsec'});

		// Check if event log contains correct parameters
		$this->assertSame(OwncloudLog::EVENT_TYPE, $content[2]->{'type'});
		$this->assertSame('APPLoad', $content[2]->{'diagnostics'}->{'eventDescription'});
		$this->assertSame(0.1, $content[2]->{'diagnostics'}->{'eventDurationmsec'});

		// Check if summary log contains correct parameters
		$this->assertSame(OwncloudLog::SUMMARY_TYPE, $content[4]->{'type'});
		$this->assertSame(2, $content[4]->{'diagnostics'}->{'totalSQLQueries'});
		$this->assertSame(300.999, $content[4]->{'diagnostics'}->{'totalSQLDurationmsec'});
		$this->assertSame(2, $content[4]->{'diagnostics'}->{'totalSQLParams'});
		$this->assertSame(2, $content[4]->{'diagnostics'}->{'totalEvents'});
		$this->assertSame(10.2, $content[4]->{'diagnostics'}->{'totalEventsDurationmsec'});

		// Clean log and expect it to be cleaned
		$this->diagnostics->cleanLog();
		$handle = @\fopen($logFile, 'r');
		$contents = \fread($handle, 8192);
		\fclose($handle);
		$this->assertSame('', $contents);
	}

	public function testLoggingWithNotingLoggLevel() {
		// Check total size of log
		$this->diagnostics->cleanLog();
		$contentSize = $this->diagnostics->getLogFileSize();
		$this->assertSame(0, $contentSize);

		$logFile = \OC::$SERVERROOT . '/data-autotest'.'/diagnostic.log';
		$handle = @\fopen($logFile, 'w');
		\fclose($handle);

		$this->diagnostics->setDiagnosticLogLevel(Diagnostics::LOG_NOTHING);
		$this->initRecords();

		$handle = @\fopen($logFile, 'r');
		$content = [];
		$logFileSize = 0;
		while (($line = @\fgets($handle)) !== false) {
			$logFileSize += \strlen($line);
			$content[] = \json_decode($line);
		}
		\fclose($handle);

		// Check total size of log
		$this->assertSame(0, \count($content));
		$contentSize = $this->diagnostics->getLogFileSize();
		$this->assertSame($logFileSize, $contentSize);

		$this->assertSame([], $content);
	}

	public function testDownloadLog() {
		$response = $this->diagnostics->downloadLog();

		$this->assertInstanceOf(StreamResponse::class, $response);
		$headers = $response->getHeaders();
		$this->assertEquals('application/octet-stream', $headers['Content-Type']);
		$this->assertEquals('attachment; filename="diagnostic.log"', $headers['Content-Disposition']);
	}
}
