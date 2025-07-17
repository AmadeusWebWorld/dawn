<?php
$tpl = getThemeSnippet('countdown');
$message = hasVariable('below-countdown') ? variable('below-countdown')
	: returnLine('A proprietary system, AW Dawn is not for the faint of heart!<br />Launching [Aug 10th](%imran-url%writing/_proxy/?go=all/for/msa/BTNSITE)');
return replaceItems($tpl, [
	'left-hand-side'   => markdown('**COMING**'),
	'right-hand-side'  => markdown('**SOOOON**'),
	'countdown-params' => variableOr('countdown-params', 'data-year="2025" data-month="8" data-day="10"  data-hour="4" data-format="dHMS"'),
	'below-countdown'  => $message,
], '%');
