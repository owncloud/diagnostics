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
use OCP\IUserSession;
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
	public const LOG_NOTHING = '0';

	/** Summary (one report per request) */
	public const LOG_SUMMARY = '1';

	/** Queries (summary, single queries with their parameters) */
	public const LOG_QUERIES = '2';

	/** Events (summary, single events) */
	public const LOG_EVENTS = '3';

	/** Everything (summary, single queries with their parameters and events) */
	public const LOG_ALL = '4';

	/** @var \OCP\IConfig */
	private $config;

	/** @var \OCP\IUserSession */
	private $session;

	/** @var \OCA\Diagnostics\Log\OwncloudLog */
	private $diagnosticLogger;

	/** string */
	private $diagnosticLevel = null;

	/**
	 * string[] - each string is in JSON format e.g. "{\"id\":\"zombie12\",\"displayname\":\"Borowski, Gretl\"}"
	 */
	private $diagnosticForUsers = null;

	/** bool */
	private $debug = null;

	/**
	 * @param \OCP\IConfig $config
	 */
	public function __construct(IConfig $config, IUserSession $session) {
		$this->config = $config;
		$this->session = $session;
		$this->diagnosticLogger = new OwncloudLog($config);
	}

	/**
	 * Function accepts as parameters JSON formated string e.g. ["{\"id\":\"test\",\"displayname\":\"User, Test\"}", ..].
	 * JSON contains array of JSON objects with following keys:
	 *  id - e.g. test
	 *  displayname - e.g.
	 *
	 * @param string $userData
	 */
	public function setDiagnosticForUsers($userData) {
		$this->config->setAppValue('diagnostics', 'diagnosedUsers', $userData);
		$this->diagnosticForUsers = $userData;
	}

	/**
	 * Function returns JSON formated string e.g. ["{\"id\":\"test\",\"displayname\":\"User, Test\"}", ..].
	 * JSON contains array of JSON objects with following keys:
	 *  id - e.g. test
	 *  displayname - e.g.
	 *
	 * @return string -
	 */
	public function getDiagnosedUsers() {
		if ($this->diagnosticForUsers === null) {
			$this->diagnosticForUsers = $this->config->getAppValue('diagnostics', 'diagnosedUsers', "[]");
		}
		return $this->diagnosticForUsers;
	}

	/**
	 * @return bool
	 */
	public function isDiagnosticActivatedForSession() {
		if ($this->isDebugEnabled() && ($this->getDiagnosticLogLevel() !== self::LOG_NOTHING)) {
			// If in debug mode, always enabled
			return true;
		} elseif ($this->getDiagnosticLogLevel() !== self::LOG_NOTHING) {
			// If diagnostic level is set, lets check if diagnostic is enabled for this user
			$user = $this->session->getUser();
			if ($user) {
				$userUID = $user->getUID();
				$diagnosedUsers = \json_decode($this->getDiagnosedUsers());
				foreach ($diagnosedUsers as $userData) {
					if ($userData && isset($userData->{'id'}) && $userUID === $userData->{'id'}) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Sets whether the logging funtionality should use locks or not
	 * @param bool $state
	 */
	public function setLoggingLocks($state) {
		$value = 'no';
		if ($state) {
			$value = 'yes';
		}
		$this->config->setAppValue('diagnostics', 'loggingLocks', $value);
	}

	/**
	 * Gets whether the logging funtionality should use locks or not
	 * @return bool
	 */
	public function getLoggingLocks() {
		$value = $this->config->getAppValue('diagnostics', 'loggingLocks', 'no');
		return \filter_var($value, FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * @return bool
	 */
	public function isDebugEnabled() {
		if ($this->debug === null) {
			$this->debug = $this->config->getSystemValue('debug', false);
		}
		return $this->debug;
	}

	/**
	 * @param bool $enable
	 */
	public function setDebug($enable) {
		$this->config->setSystemValue('debug', $enable);
		$this->debug = $enable;
	}

	/**
	 * @return string
	 */
	public function getDiagnosticLogLevel() {
		if ($this->diagnosticLevel === null) {
			$this->diagnosticLevel = $this->config->getAppValue('diagnostics', 'diagnosticLogLevel', Diagnostics::LOG_NOTHING);
		}
		return $this->diagnosticLevel;
	}

	/**
	 * @param string $logLevel
	 */
	public function setDiagnosticLogLevel($logLevel) {
		$this->config->setAppValue('diagnostics', 'diagnosticLogLevel', $logLevel);
		$this->diagnosticLevel = $logLevel;
	}

	/**
	 * @param string $sqlStatement
	 * @param array $sqlParams
	 * @param float $sqlQueryDurationmsec
	 * @param float $sqlTimestamp
	 *
	 * @return bool $success
	 */
	public function recordQuery($sqlStatement, $sqlParams, $sqlQueryDurationmsec, $sqlTimestamp) {
		if ($this->getDiagnosticLogLevel() === Diagnostics::LOG_QUERIES || $this->getDiagnosticLogLevel() === Diagnostics::LOG_ALL) {
			$sqlStatement = \str_replace("\"", "", \str_replace("\t", "", \str_replace("\n", " ", $sqlStatement)));
			$sqlParams = \str_replace("\n", " ", \var_export($sqlParams, true));
			/** @var array $entry */
			$entry = \compact(
				'sqlStatement',
				'sqlParams',
				'sqlQueryDurationmsec',
				'sqlTimestamp'
			);
			$this->diagnosticLogger->write(OwnCloudLog::QUERY_TYPE, $entry);
			return true;
		}
		return false;
	}

	/**
	 * @param string $eventDescription
	 * @param float $eventDurationmsec
	 *
	 * @return bool $success
	 */
	public function recordEvent($eventDescription, $eventDurationmsec, $eventTimestamp) {
		if ($this->getDiagnosticLogLevel() === Diagnostics::LOG_EVENTS || $this->getDiagnosticLogLevel() === Diagnostics::LOG_ALL) {
			/** @var array $entry */
			$entry = \compact(
				'eventDescription',
				'eventDurationmsec',
				'eventTimestamp'
			);

			$this->diagnosticLogger->write(OwnCloudLog::EVENT_TYPE, $entry);
			return true;
		}
		return false;
	}

	/**
	 * @param int $totalSQLQueries
	 * @param float $totalSQLDurationmsec
	 * @param int $totalSQLParams
	 * @param int $totalEvents
	 * @param int $totalEventsDurationmsec
	 *
	 * @return bool $success
	 */
	public function recordSummary($totalSQLQueries, $totalSQLDurationmsec, $totalSQLParams, $totalEvents, $totalEventsDurationmsec) {
		if ($this->getDiagnosticLogLevel() !== Diagnostics::LOG_NOTHING) {
			/** @var array $entry */
			$entry = \compact(
				'totalSQLQueries',
				'totalSQLDurationmsec',
				'totalSQLParams',
				'totalEvents',
				'totalEventsDurationmsec'
			);

			$this->diagnosticLogger->write(OwnCloudLog::SUMMARY_TYPE, $entry);
			return true;
		}
		return false;
	}

	/**
	 * get logfile filesize
	 *
	 * @return int
	 */
	public function getLogFileSize() {
		$logFilePath = $this->diagnosticLogger->getLogFilePath();
		\clearstatcache(true, $logFilePath);
		$doesLogFileExist = \file_exists($logFilePath);

		if ($doesLogFileExist) {
			return \filesize($logFilePath);
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
