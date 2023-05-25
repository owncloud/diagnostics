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

namespace OCA\Diagnostics\Controller;

use OCA\Diagnostics\Diagnostics;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\AppFramework\Http\StreamResponse;

class AdminController extends Controller {
	/** @var  Diagnostics */
	private $diagnostics;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param Diagnostics $diagnostics
	 */
	public function __construct(
		$AppName,
		IRequest $request,
		Diagnostics $diagnostics
	) {
		parent::__construct($AppName, $request);
		$this->diagnostics = $diagnostics;
	}

	/**
	 * @param string $users - JSON format e.g. ["{\"id\":\"zombie12\",\"displayname\":\"Borowski, Gretl\"}", ..]
	 */
	public function setDiagnosticForUsers($users) {
		$this->diagnostics->setDiagnosticForUsers($users);
	}

	/**
	 * @return string - JSON format e.g. ["{\"id\":\"zombie12\",\"displayname\":\"Borowski, Gretl\"}", ..]
	 */
	public function getDiagnosedUsers() {
		return $this->diagnostics->getDiagnosedUsers();
	}

	/**
	 * @param bool $enable
	 */
	public function setLoggingLocks($enable) {
		$this->diagnostics->setLoggingLocks($enable);
	}

	/**
	 * @return bool
	 */
	public function getLoggingLocks() {
		return $this->diagnostics->getLoggingLocks();
	}

	/**
	 * @return bool
	 */
	public function isDebugEnabled() {
		return $this->diagnostics->isDebugEnabled();
	}

	/**
	 * @param bool $enable
	 */
	public function setDebug($enable) {
		$this->diagnostics->setDebug($enable);
	}

	/**
	 * @return string
	 */
	public function getDiagnosticLogLevel() {
		return $this->diagnostics->getDiagnosticLogLevel();
	}

	/**
	 * @param string $logLevel
	 */
	public function setDiagnosticLogLevel($logLevel) {
		$this->diagnostics->setDiagnosticLogLevel($logLevel);
	}
	
	/**
	 * @NoCSRFRequired
	 *
	 * @return StreamResponse
	 */
	public function downloadLog() {
		return $this->diagnostics->downloadLog();
	}

	/**
	 * @NoCSRFRequired
	 */
	public function cleanLog() {
		$this->diagnostics->cleanLog();
	}
}
