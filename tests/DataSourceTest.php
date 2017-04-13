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
use OCA\Diagnostics\DataSource;
use OC\Diagnostics\QueryLogger;
use OC\Diagnostics\EventLogger;
use OCP\IRequest;

/**
 * @package OCA\Diagnostics\Tests
 */
class DataSourceTest extends \Test\TestCase {
	/** @var DataSource */
	private $datasource;

	/** @var QueryLogger */
	private $querylogger;

	/** @var EventLogger */
	private $eventlogger;

	/** @var Diagnostics */
	private $diagnostics;

	/** @var IRequest */
	private $request;

	public function setUp() {
		parent::setUp();
		$this->request = $this->createMock(IRequest::class);

		$this->diagnostics = $this->getMockBuilder(Diagnostics::class)
			->disableOriginalConstructor()
			->setMethods([
				'recordEvent',
				'recordQuery',
				'recordSummary',
				'isDiagnosticActivatedForSession'
			])->getMock();

		$this->querylogger = new QueryLogger();
		$this->eventlogger = new EventLogger();

		$this->datasource = new DataSource(
			$this->querylogger,
			$this->eventlogger,
			$this->request,
			$this->diagnostics
		);
	}

	private function initQueriesEvents(){
		$this->datasource->activateDataSources();

		$this->eventlogger->start("test", "testevent");
		$this->eventlogger->end("test");
		$this->querylogger->startQuery("SELECT", ["testuser", "count"], ["string", "int"]);
		$this->querylogger->stopQuery();
	}

	public function testDiagnoseWithLogNothing() {
		$this->diagnostics->expects($this->any())
			->method('isDiagnosticActivatedForSession')
			->willReturn(false);

		$this->initQueriesEvents();
		
		$this->diagnostics->expects($this->never())
			->method('recordSummary');

		$this->diagnostics->expects($this->never())
			->method('recordQuery');

		$this->diagnostics->expects($this->never())
			->method('recordEvent');

		$this->datasource->diagnoseRequest();
	}

	public function testDiagnoseWithLogging() {
		$this->diagnostics->expects($this->any())
			->method('isDiagnosticActivatedForSession')
			->willReturn(true);

		$this->initQueriesEvents();
		
		$this->diagnostics->expects($this->once())
			->method('recordQuery')
			->with(
				'SELECT',
				["testuser", "count"],
				$this->anything()
			);

		$this->diagnostics->expects($this->once())
			->method('recordEvent')
			->with(
				'testevent',
				$this->anything()
			);

		$this->diagnostics->expects($this->once())
			->method('recordSummary')
			->with(
				1, // 1 query
				$this->anything(),
				2, // 2 query params
				1, // 1 event
				$this->anything()
			);

		$this->datasource->diagnoseRequest();
	}

	public function testDiagnoseWithLoggingWithoutActivation() {
		$this->diagnostics->expects($this->any())
			->method('isDiagnosticActivatedForSession')
			->willReturn(true);

		// This time, event and query logger were not activated,
		// supplied data should be ignored
		$this->eventlogger->start("test", "testevent");
		$this->eventlogger->end("test");
		$this->querylogger->startQuery("SELECT", ["testuser", "count"], ["string", "int"]);
		$this->querylogger->stopQuery();

		$this->diagnostics->expects($this->never())
			->method('recordQuery');

		$this->diagnostics->expects($this->never())
			->method('recordEvent');

		$this->diagnostics->expects($this->never())
			->method('recordSummary');

		$this->datasource->diagnoseRequest();
	}
}
