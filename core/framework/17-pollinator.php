<?php
DEFINE('PINICONS', 'icons');
DEFINE('PINTEXT',  'text');
DEFINE('PINEMBED', 'embed');

function pollenAt($where, $exclude = 'me-only, business') {
	if (true || variable('dont-pollinate')) return;

	$sheet = getSheet(AMADEUSROOT . 'admin/public-interest-network/pollen.tsv', 'where');

	$exclude = explode(', ', $exclude);
	$noBusiness = in_array('business', $exclude);
	$noMe = in_array('me', $exclude);

	$items = [];
	foreach ($sheet->group[$where] as $row) {
		$thisIsBiz = parseAnyType($sheet->getValue($row, 'business'), TYPEBOOLEAN);
		$thisSite = subVariable('networkItems', sluggize($site = $sheet->getValue($row, 'site')));

		if ($noBusiness && $thisIsBiz) continue;
		if ($noMe && $thisSite['safeName'] == variable('safeName')) continue;

		if (!disk_is_dir(ALLSITESROOT . '/' . $site)) {
			//if (variable('local')) parameterError('Nope!', $site . ' not found', false, false);
			continue;
		}

		$items[] = $sheet->asObject($row);
	}

	if (count($items) == 0) return;

	shuffle($items);
	$items = array_splice($items, 0, 5);
	$item = $items[0];
	parameterError('30 @' . $where, $items, false);
}
