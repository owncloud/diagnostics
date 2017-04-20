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
use OCA\Diagnostics\DataSource;
use OCP\AppFramework\Controller;
use OCP\IL10N;
use OCP\IRequest;
use OCP\AppFramework\Http\StreamResponse;

class AdminController extends Controller {

	/** @var  Diagnostics */
	private $diagnostics;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param IL10N $l10n
	 * @param Diagnostics $diagnostics
	 */
	public function __construct($AppName,
								IRequest $request,
								IL10N $l10n,
								Diagnostics $diagnostics,
								DataSource $dataSource
	) {
		parent::__construct($AppName, $request);
		$this->diagnostics = $diagnostics;
	}

	/**
	 * @param string $uids - e.g. "["admin","user1000"]" in JSON format
	 */
	public function setDiagnosticForUsers($uids) {
		$this->diagnostics->setDiagnosticForUsers($uids);
	}

	/**
	 * @return string[] $uid
	 */
	public function getDiagnosedUsers() {
		return $this->diagnostics->getDiagnosedUsers();
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
