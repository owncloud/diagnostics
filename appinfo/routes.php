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

$application = new \OCA\Diagnostics\AppInfo\Application();

$application->registerRoutes(
	$this,
	[
		'routes' => [
			[
				'name' => 'Admin#setDiagnosticForUsers',
				'url' => '/setdiagnosticforusers',
				'verb' => 'POST'
			],
			[
				'name' => 'Admin#getDiagnosedUsers',
				'url' => '/getdiagnosedusers',
				'verb' => 'GET'
			],
			[
				'name' => 'Admin#setDebug',
				'url' => '/setdebug',
				'verb' => 'POST'
			],
			[
				'name' => 'Admin#setDiagnosticLogLevel',
				'url' => '/setdiaglevel',
				'verb' => 'POST'
			],
			[
				'name' => 'Admin#setLoggingLocks',
				'url' => '/setlogginglocks',
				'verb' => 'POST'
			],
			[
				'name' => 'Admin#downloadLog',
				'url' => '/log/download',
				'verb' => 'GET'
			],
			[
				'name' => 'Admin#cleanLog',
				'url' => '/log/clean',
				'verb' => 'POST'
			],
		],
	]
);
