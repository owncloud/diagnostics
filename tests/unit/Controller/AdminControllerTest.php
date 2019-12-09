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

namespace OCA\Diagnostics\Tests\Controller;

use OCA\Diagnostics\Controller\AdminController;
use OCA\Diagnostics\DataSource;
use OCA\Diagnostics\Diagnostics;
use OCP\IRequest;
use OCP\IL10N;
use OCP\AppFramework\Http;
use Test\TestCase;
use OCP\AppFramework\Http\StreamResponse;

class AdminControllerTest extends TestCase {

	/** @var AdminController */
	private $controller;

	/** @var Diagnostics */
	private $diagnostics;

	/** @var DataSource */
	private $datasource;

	/** @var \OCP\IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $requestMock;

	/** @var \OCP\IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l10nMock;

	protected function setUp(): void {
		parent::setUp();

		$this->requestMock = $this->createMock(IRequest::class);

		$this->l10nMock = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()->getMock();

		$this->l10nMock->expects($this->any())
			->method('t')
			->will($this->returnCallback(function ($message) {
				return $message;
			}));

		$this->diagnostics = $this->getMockBuilder(Diagnostics::class)
			->disableOriginalConstructor()
			->setMethods([
				'isDebugEnabled',
				'setDebug',
				'getDiagnosticLogLevel',
				'setDiagnosticLogLevel',
				'downloadLog',
				'cleanLog',
				'getDiagnosedUsers',
				'setDiagnosticForUsers',
			])->getMock();

		$this->datasource = $this->getMockBuilder(DataSource::class)
			->disableOriginalConstructor()->getMock();

		$this->controller = new AdminController(
			'diagnostics',
			$this->requestMock,
			$this->l10nMock,
			$this->diagnostics,
			$this->datasource
		);
	}

	public function testSetDiagnosticLogLevel() {
		$this->diagnostics->expects($this->once())
			->method('setDiagnosticLogLevel');
		$this->controller->setDiagnosticLogLevel('1');
	}

	public function testGetDiagnosticLogLevel() {
		$this->diagnostics->expects($this->once())
			->method('getDiagnosticLogLevel')
			->willReturn('1');
		$response = $this->controller->getDiagnosticLogLevel();
		$this->assertSame('1', $response);
	}

	public function testGetDiagnosedUsers() {
		$diagUsersJson = "[{\"id\":\"admin\",\"displayname\":\"Admin, Test\"},{\"id\":\"user100\",\"displayname\":\"User, 100\"}]";
		$this->diagnostics->expects($this->once())
			->method('getDiagnosedUsers')
			->willReturn($diagUsersJson);
		$response = $this->controller->getDiagnosedUsers();
		$this->assertSame($diagUsersJson, $response);
	}

	public function testSetDiagnosticForUsers() {
		$diagUsersJson = "[{\"id\":\"admin\",\"displayname\":\"Admin, Test\"},{\"id\":\"user100\",\"displayname\":\"User, 100\"}]";
		$this->diagnostics->expects($this->once())
			->method('setDiagnosticForUsers');
		$this->controller->setDiagnosticForUsers($diagUsersJson);
	}

	public function testSetDebug() {
		$this->diagnostics->expects($this->once())
			->method('setDebug');
		$this->controller->setDebug(true);
	}

	public function testDebugEnabled() {
		$this->diagnostics->expects($this->once())
			->method('isDebugEnabled')
			->willReturn(true);
		$response = $this->controller->isDebugEnabled();
		$this->assertSame(true, $response);
	}

	public function testCleanLog() {
		$this->diagnostics->expects($this->once())
			->method('cleanLog');
		$this->controller->cleanLog();
	}

	public function testDownloadLog() {
		$streamResponse = $this->getMockBuilder(StreamResponse::class)
			->disableOriginalConstructor()->getMock();
		$this->diagnostics->expects($this->once())
			->method('downloadLog')
			->willReturn($streamResponse);

		$response = $this->controller->downloadLog();
		$this->assertInstanceOf(StreamResponse::class, $response);
	}
}
