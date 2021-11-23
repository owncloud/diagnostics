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
	public const relevantSqlOperations = [
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
	 * @var Diagnostics
	 */
	private $diagnostics;

	/**
	 * Gives access to parameters of the request for data mining
	 *
	 * @var  IRequest
	 */
	private $request;

	/**
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

	public function activateDataSources() {
		if ($this->diagnostics->isDiagnosticActivatedForSession()) {
			// Activate data sources
			$this->queryLogger->activate();
			$this->eventLogger->activate();
		}
	}
	
	/**
	 * @return array|void|null
	 */
	public function diagnoseRequest() {
		if (!$this->diagnostics->isDiagnosticActivatedForSession()) {
			return;
		}

		$totalEvents = 0;
		$totalEventsDuration = 0;
		$events = $this->eventLogger->getEvents();
		foreach ($events as $event) {
			$eventDescription = $event->getDescription();
			$eventTimestamp = $event->getStart();
			$eventDuration = $event->getDuration() * 1000;  //msec
			$totalEventsDuration += $eventDuration;
			$totalEvents++;

			$this->diagnostics->recordEvent($eventDescription, $eventDuration, $eventTimestamp);
		}

		$totalDBQueries = 0;
		$totalDBDuration = 0;
		$totalDBParams = 0;
		$queries = $this->queryLogger->getQueries();
		foreach ($queries as $query) {
			$sqlTimestamp = $query->getStart();
			$sqlStatement = $query->getSql();
			$totalDBQueries++;

			$sqlQueryDuration = $query->getDuration() * 1000;  //msec
			$totalDBDuration += $sqlQueryDuration;

			$sqlParams = $query->getParams();

			if (\is_array($sqlParams)) {
				$totalDBParams += \count($sqlParams);
			}

			$this->diagnostics->recordQuery($sqlStatement, $sqlParams, $sqlQueryDuration, $sqlTimestamp);
		}

		if ($totalDBQueries>0 && $totalEvents>0) {
			$this->diagnostics->recordSummary($totalDBQueries, $totalDBDuration, $totalDBParams, $totalEvents, $totalEventsDuration);
		}
	}
}
