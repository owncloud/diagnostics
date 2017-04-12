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

namespace OCA\Diagnostics;

use OCP\IConfig;
use OCP\AppFramework\Http\StreamResponse;
use OCA\Diagnostics\Log\OwncloudLog;

/**
 * This class provides access to system and application configuration, is also responsible for 
 * storing the data in the required destination as specified by the administrator
 *
 * @package OCA\Diagnostics
 */
class Diagnostics {
	/** Nothing (collecting but not used) */
	const LOG_NOTHING = '0';

	/** Summary (one report per request) */
	const LOG_SUMMARY = '1';

	/** Queries (summary, single queries with their parameters) */
	const LOG_QUERIES = '2';

	/** Events (summary, single events) */
	const LOG_EVENTS = '3';
	
	/** Everything (summary, single queries with their parameters and events) */
	const LOG_ALL = '4';

	const EVENT_TYPE = 'EVENT';
	const QUERY_TYPE = 'QUERY';
	const SUMMARY_TYPE = 'SUMMARY';

	/** @var \OCP\IConfig */
	private $config;
	
	/** @var \OCA\Diagnostics\Log\OwncloudLog */
	private $diagnosticLogger;
	
	/**
	 * @param \OCP\IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
		$this->diagnosticLogger = new OwncloudLog($config);
	}

	/**
	 * @return bool
	 */
	public function isDebugEnabled() {
		return $this->config->getSystemValue('debug', false);
	}

	/**
	 * @param bool $enable
	 */
	public function setDebug($enable) {
		$this->config->setSystemValue('debug', $enable);
	}
	
	/**
	 * @return string
	 */
	public function getDiagnosticLogLevel() {
		return $this->config->getAppValue('diagnostics', 'diagnosticLogLevel', Diagnostics::LOG_NOTHING);
	}

	/**
	 * @param string $logLevel
	 */
	public function setDiagnosticLogLevel($logLevel) {
		$this->config->setAppValue('diagnostics', 'diagnosticLogLevel', $logLevel);
	}

	/**
	 * @param string $sqlStatement
	 * @param array $sqlParams
	 * @param float $sqlQueryDurationmsec
	 */
	public function recordQuery($sqlStatement, $sqlParams, $sqlQueryDurationmsec) {
		$sqlStatement = str_replace("\"", "", str_replace("\t", "", str_replace("\n", " ", $sqlStatement)));
		$sqlParams = str_replace("\n", " ", var_export($sqlParams, true));
		$entry = compact(
			'sqlStatement',
			'sqlParams',
			'sqlQueryDurationmsec'
		);
		$this->diagnosticLogger->write(self::QUERY_TYPE, $entry);
	}

	/**
	 * @param string $eventDescription
	 * @param float $totalSQLDurationmsec
	 */
	public function recordEvent($eventDescription, $eventDurationmsec) {
		$entry = compact(
			'eventDescription',
			'eventDurationmsec'
		);

		$this->diagnosticLogger->write(self::EVENT_TYPE, $entry);
	}
	
	/**
	 * @param int $totalSQLQueries
	 * @param float $totalSQLDurationmsec
	 * @param int $totalSQLParams
	 * @param int $totalEvents
	 * @param int $totalEventsDurationmsec
	 */
	public function recordSummary($totalSQLQueries, $totalSQLDurationmsec, $totalSQLParams, $totalEvents, $totalEventsDurationmsec) {
		$entry = compact(
			'totalSQLQueries',
			'totalSQLDurationmsec',
			'totalSQLParams',
			'totalEvents',
			'totalEventsDurationmsec'
		);

		$this->diagnosticLogger->write(self::SUMMARY_TYPE, $entry);
	}
	
	/**
	 * get logfile filesize
	 *
	 * @return int
	 */
	public function getLogFileSize() {
		$logFilePath = $this->diagnosticLogger->getLogFilePath();
		clearstatcache(true, $logFilePath);
		$doesLogFileExist = file_exists($logFilePath);
		
		if($doesLogFileExist) {
			return filesize($logFilePath);
		}
		return 0;
	}

	/**
	 * download logfile
	 *
	 * @return StreamResponse
	 */
	public function downloadLog() {
		$resp = new StreamResponse($this->diagnosticLogger->getLogFilePath());
		$resp->addHeader('Content-Type', 'application/octet-stream');
		$resp->addHeader('Content-Disposition', 'attachment; filename="diagnostic.log"');
		return $resp;
	}
	
	/**
	 * clean logfile
	 */
	public function cleanLog() {
		$this->diagnosticLogger->clean();
	}
}
