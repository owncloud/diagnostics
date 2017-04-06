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

use OCP\Diagnostics\IEventLogger;
use OCP\Diagnostics\IQueryLogger;
use OCP\IRequest;

/**
 * This class provides access to gathered diagnostic data
 *
 * @package OCA\Diagnostics
 */
class DataSource {
	/**
	 * This const can be used to strpos() in the sql statement for these operations and
	 * aggregate operations with their summary durations and number of parameters
	 */
	const relevantSqlOperations = [
		'COMMIT',
		'SELECT',
		'INSERT',
		'UPDATE',
		'INSERT',
		'JOIN',
		'DELETE'
		];
	/**
	 * Gives access to all queries with their parameters for the current request
	 *
	 * @var \OCP\Diagnostics\IQueryLogger
	 */
	private $queryLogger;

	/**
	 * Gives access to all event their decsriptions for the current request
	 *
	 * @var \OCP\Diagnostics\IEventLogger
	 */
	private $eventLogger;

	/**
	 * @var  DataSource
	 */
	private $diagnostics;

	/**
	 * Gives access to parameters of the request for data mining
	 *
	 * @var  IRequest
	 */
	private $request;

	/**
	 * @param string $appName
	 * @param \OCP\Diagnostics\IQueryLogger $queryLogger
	 * @param \OCP\Diagnostics\IEventLogger $eventLogger
	 * @param \OCP\IRequest $request
	 * @param \OCA\Diagnostics\Diagnostics $diagnostics
	 */
	public function __construct(IQueryLogger $queryLogger, IEventLogger $eventLogger, IRequest $request, Diagnostics $diagnostics) {
		$this->queryLogger = $queryLogger;
		$this->eventLogger = $eventLogger;
		$this->request = $request;
		$this->diagnostics = $diagnostics;
	}

	/**
	 * @return array
	 */
	public function diagnoseRequest() {
		$diagnoseLevel = $this->diagnostics->getDiagnosticLogLevel();
		if ($diagnoseLevel === Diagnostics::LOG_NOTHING) {
			return;
		}

		$totalEvents = 0;
		$totalEventsDuration = 0;
		$events = $this->eventLogger->getEvents();
		foreach($events as $event) {
			$eventDescription = $event->getDescription();
			$eventDuration = $event->getDuration() * 1000;  //msec
			$totalEventsDuration += $eventDuration;
			$totalEvents++;

			if ($diagnoseLevel === Diagnostics::LOG_EVENTS || $diagnoseLevel === Diagnostics::LOG_ALL) {
				$this->diagnostics->recordEvent($eventDescription, $eventDuration);
			}
		}

		$totalDBQueries = 0;
		$totalDBDuration = 0;
		$totalDBParams = 0;
		$queries = $this->queryLogger->getQueries();
		foreach($queries as $query) {
			$sqlStatement = $query->getSql();
			$totalDBQueries++;

			$sqlQueryDuration = $query->getDuration() * 1000;  //msec
			$totalDBDuration += $sqlQueryDuration;

			$sqlParams = $query->getParams();
			$totalDBParams += count($sqlParams);

			if ($diagnoseLevel === Diagnostics::LOG_QUERIES || $diagnoseLevel === Diagnostics::LOG_ALL) {
				$this->diagnostics->recordQuery($sqlStatement, $sqlParams, $sqlQueryDuration);
			}
		}

		if ($diagnoseLevel !== Diagnostics::LOG_NOTHING) {
			$this->diagnostics->recordSummary($totalDBQueries, $totalDBDuration, $totalDBParams, $totalEvents, $totalEventsDuration);
		}
	}
}
