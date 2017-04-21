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

namespace OCA\Diagnostics\AppInfo;

use OCP\App;
use OCA\Diagnostics\Controller\AdminController;
use OCA\Diagnostics\DataSource;
use OCA\Diagnostics\Diagnostics;
use OCP\AppFramework\IAppContainer;

class Application extends \OCP\AppFramework\App {

	/**
	 * @param array $urlParams
	 */
	public function __construct($urlParams = []) {
		parent::__construct('diagnostics', $urlParams);
		$this->registerServices();
	}

	private function registerServices() {
		$container = $this->getContainer();
		
		$container->registerService('Diagnostics', function(IAppContainer $c) {
			$server = $c->getServer();
			return new Diagnostics(
				$server->getConfig(),
				$server->getUserSession()
			);
		});

		$container->registerService('DataSource', function(IAppContainer $c) {
			$server = $c->getServer();
			return new DataSource(
				$server->getQueryLogger(),
				$server->getEventLogger(),
				$server->getRequest(),
				$c->query('Diagnostics')
			);
		});

		$container->registerService('AdminController', function(IAppContainer $c) {
			$server = $c->getServer();
			return new AdminController(
				$c->getAppName(),
				$server->getRequest(),
				$server->getL10N($c->getAppName()),
				$c->query('Diagnostics'),
				$c->query('DataSource')
			);
		});

	}

	public function finalizeRequest() {
		$container = $this->getContainer();

		/** @var DataSource $datasource */
		$datasource = $container->query('DataSource');

		// Retrieve the data, process it and store
		$datasource->diagnoseRequest();
	}
}
