<?php

/**
 * @var array $_
 * @var \OCP\IL10N $l
 * @var OC_Defaults $theme
 */
$levelLabels = [
	$l->t( 'Nothing (collecting but not used)' ),
	$l->t( 'Summary (one report per request)' ),
	$l->t( 'All queries (summary, single queries with their parameters)' ),
	$l->t( 'All events (summary, single events)' ),
	$l->t( 'Everything (summary, single queries with their parameters and events)' ),
];
script('diagnostics', 'settings-admin');
style('diagnostics', 'settings-admin');
?>
<div id="ocDiagnosticsSettings" class="section">
	<h2 class="app-name"><?php p($l->t('Diagnostics')); ?></h2>
	<em><?php
		p($l->t('Enabling this ownCloud diagnostic module will result in collecting data '.
			'about all queries and events in the system per request.' )
		);
		?></em>

	<p>
		<input type="checkbox" class="checkbox" name="enableDiagnostics" id="enableDiagnostics"
			   value="1" <?php if ($_['enableDiagnostics']) print_unescaped('checked="checked"'); ?> />
		<label for="enableDiagnostics"><?php p($l->t('Enable diagnostic data collection'));?></label>
	</p></br>
	<span class="msg"></span>

	<?php if ($_['logFileSize'] > 0): ?>
		<a href="<?php print_unescaped($_['urlGenerator']->linkToRoute('diagnostics.Admin.downloadLog')); ?>" class="button">
			<?php p($l->t('Download logfile (%s)', [\OCP\Util::humanFileSize($_['logFileSize'])]));?>
		</a>
		<a class="button" id='cleanDiagnosticLog'>
			<?php p($l->t('Clean logfile'));?>
		</a>
	<?php endif; ?>
	<?php if ($_['logFileSize'] > (100 * 1024 * 1024)): ?>
		<br>
		<em>
			<?php p($l->t('The logfile is bigger than 100 MB. Downloading it may take some time!')); ?>
		</em>
	<?php endif; ?>

	<br/>
	<br/>
	<div id="diagnosticLog" <?php if (!$_['enableDiagnostics']) print_unescaped('class="hidden"'); ?>>
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
	</div>
</div>

