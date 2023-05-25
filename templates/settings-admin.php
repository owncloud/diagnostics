<?php

/**
 * @var array $_
 * @var \OCP\IL10N $l
 * @var OC_Defaults $theme
 */
$levelLabels = [
	$l->t('Nothing (collecting but not used)'),
	$l->t('Summary (one report per request)'),
	$l->t('All queries (summary, single queries with their parameters)'),
	$l->t('All events (summary, single events)'),
	$l->t('Everything (summary, single queries with their parameters and events)'),
];
script('diagnostics', 'settings-admin');
style('diagnostics', 'settings-admin');
?>
<div id="ocDiagnosticsSettings" class="section">
	<h2 class="app-name"><?php p($l->t('Diagnostics')); ?></h2>
	<em>
		<?php
		p($l->t('Enabling this ownCloud diagnostic module will result in collecting data '.
			'about all queries and events in the system per request.'));
?>
	</em>

	<br/>
	<br/>
	<div id="diagnosticLog" <?php if ($_['enableDiagnostics']) {
		print_unescaped('class="hidden"');
	} ?>>
		<?php p($l->t('Collect data only after authentication of users:')); ?>
		<br/>
		<input name="diagnosticUserList"
			   id="diagnosticUserList"
			   value="<?php
				// Decode diagnosedUsers to string[] form and implode with '|'
				// The desired form is: {"id":"test1","displayname":"Test, 1"} | {"id":"test2","displayname":"Test, 2"}
				p(\implode("|", \array_map(function ($userData) {
					return \json_encode($userData);
				}, \json_decode($_['diagnosedUsers']))))
?>"
			   style="width: 400px">
		<br/>
		<em>
			<?php p($l->t('Please specify full user name for best search performance')); ?>
		</em>
	</div>
	<p>
		<input type="checkbox" class="checkbox" name="enableDiagnostics" id="enableDiagnostics"
			   value="1" <?php if ($_['enableDiagnostics']) {
			   	print_unescaped('checked="checked"');
			   } ?> />
		<label for="enableDiagnostics"><?php p($l->t('Allow collecting data for all requests in debug mode (all users, unauthenticated requests)'));?></label>
	</p></br>

	<p>
		<input type="checkbox" class="checkbox" name="useLoggingLocks" id="useLoggingLocks"
			value="yes" <?php if ($_['useLoggingLocks']) {
				print_unescaped('checked="checked"');
			} ?> />
		<label for="useLoggingLocks"><?php p($l->t('Lock the diagnostic.log file while writing. Useful for HA setups with NFS for the local storage'));?></label>
	</p></br>

	<h2 id='diagnosticLogLevelText'>
		<?php p($l->t('What to log'));?>
	</h2>

	<select name='diagnosticLogLevel' id='diagnosticLogLevel'>
		<?php for ($i = 0; $i < 5; $i++):
			$selected = '';
			if ($i == $_['diagnosticLogLevel']):
				$selected = 'selected="selected"';
			endif; ?>
			<option value='<?php p($i)?>' <?php p($selected) ?>><?php p($levelLabels[$i])?></option>
		<?php endfor;?>

	</select>
	<br/>
	<em>
		<?php p($l->t('Decide what details should be included in the log file')); ?>
	</em>

	<br/>
	<br/>
	<h2 id='diagnosticLogText'>
		<?php p($l->t('Diagnostic Log'));?>
	</h2>
	<?php if ($_['logFileSize'] > 0): ?>
		<a href="<?php print_unescaped($_['urlGenerator']->linkToRoute('diagnostics.Admin.downloadLog')); ?>" class="button">
			<?php p($l->t('Download logfile (%s)', [\OCP\Util::humanFileSize($_['logFileSize'])]));?>
		</a>
		<a class="button" id='cleanDiagnosticLog'>
			<?php p($l->t('Clean logfile'));?>
		</a>
		<br/>
		<br/>
		<em>
			<?php p($l->t('Log file is located by default in ./data/diagnostic.log')); ?>
		</em>
	<?php endif; ?>
	<?php if ($_['logFileSize'] === 0): ?>
		<em>
			<?php p($l->t('The logfile is empty!')); ?>
		</em>
	<?php endif; ?>
	<?php if ($_['logFileSize'] > (100 * 1024 * 1024)): ?>
		<br>
		<em>
			<?php p($l->t('The logfile is bigger than 100 MB. Downloading it may take some time!')); ?>
		</em>
	<?php endif; ?>
</div>

