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

namespace OCA\Diagnostics\Tests\Panels;

use OCA\Diagnostics\Panels\Admin;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUserSession;

/**
 * @package OCA\Diagnostics\Tests\Panels
 */
class AdminTest extends \Test\TestCase {

	/** @var IConfig */
	private $config;
	/** @var IUserSession */
	private $session;
	/** @var IURLGenerator */
	private $logger;
	/** @var Admin */
	private $panel;

	public function setUp(): void {
		parent::setUp();

		$this->config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()->getMock();
		$this->config
			->method('getAppValue')
			->willReturn("[]");

		$this->logger = $this->getMockBuilder(IURLGenerator::class)->getMock();
		$this->session = $this->getMockBuilder(IUserSession::class)->getMock();

		$this->panel = new Admin(
			$this->config,
			$this->session,
			$this->logger);
	}

	public function testGetSection() {
		$this->assertEquals('diagnostics', $this->panel->getSectionID());
	}

	public function testGetPriority() {
		$this->assertTrue(\is_integer($this->panel->getPriority()));
	}

	public function testGetPanel() {
		$templateHtml = $this->panel->getPanel()->fetchPage();
		$this->assertStringContainsString('<div id="ocDiagnosticsSettings" class="section">', $templateHtml);
	}
}
