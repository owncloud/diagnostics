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
namespace OCA\Diagnostics\Panels;

use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\Template;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use OCP\IURLGenerator;

class Admin implements ISettings {

	/** @var IConfig */
	protected $config;
	/** @var IURLGenerator  */
	protected $urlGenerator;

	public function __construct(IConfig $config,
								IURLGenerator $urlGenerator) {
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
	}

	public function getPriority() {
		return 98;
	}

	public function getSectionID() {
		return 'diagnostics';
	}

	public function getPanel() {
		$template = new Template('diagnostics', 'settings-admin');
		$diagnostics = new \OCA\Diagnostics\Diagnostics(
			$this->config
		);

		$template->assign('enableDiagnostics', $diagnostics->isDebugEnabled());
		$template->assign('diagnosticLogLevel', $diagnostics->getDiagnosticLogLevel());
		$template->assign('urlGenerator', $this->urlGenerator);
		$template->assign('logFileSize', $diagnostics->getLogFileSize());
		return $template;
	}

}
