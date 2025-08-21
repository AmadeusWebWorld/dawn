<?php
$tpl = getThemeSnippet('countdown');
$message = hasVariable('below-countdown') ? variable('below-countdown')
	: returnLine('A proprietary system, AW Dawn is not for the faint of heart!<br />Launching [Oct 15th](%imran-url%for/msa/BTNSITE)');
return replaceItems($tpl, [
	'left-hand-side'   => markdown('**COMING**'),
	'right-hand-side'  => markdown('**SOOOON**'),
	'countdown-params' => variableOr('countdown-params', 'data-year="2025" data-month="10" data-day="15"  data-hour="12" data-minute="37" data-format="dHMS"'),
	'below-countdown'  => $message,
], '%');
