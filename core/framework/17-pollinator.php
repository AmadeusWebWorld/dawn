<?php
DEFINE('PINICONS', 'icons');
DEFINE('PINTEXT',  'text');
DEFINE('PINEMBED', 'embed');

function wantsPollen() {
	return hasPageParameter('slider') || hasPageParameter('content');
}

function pollenAt($where, $exclude = 'me, business') {
	return;
	if (variable('live') || variable('dont-pollinate')) return;
	if (wantsPollen()) return;

	$sheet = getSheet(AMADEUSROOT . 'admin/public-interest-network/pollen.tsv', 'where');

	$exclude = explode(', ', $exclude);
	$noBusiness = in_array('business', $exclude);
	$noMe = in_array('me', $exclude);

	$items = [];
	foreach ($sheet->group[$where] as $row) {
		$thisIsBiz = parseAnyType($sheet->getValue($row, 'business'), TYPEBOOLEAN);
		$thisSite = subVariable('networkItems', sluggize($site = $sheet->getValue($row, 'site')));

		if (!$thisSite) continue;
		if ($noBusiness && $thisIsBiz) continue;
		if ($noMe && $thisSite['safeName'] == variable('safeName')) continue;

		if (!disk_is_dir(ALLSITESROOT . '/' . $site)) {
			//if (variable('local')) parameterError('Nope!', $site . ' not found', false, false);
			continue;
		}

		$obj = $sheet->asObject($row);
		$obj['url'] = $thisSite['url'];
		$obj['safeName'] = $thisSite['safeName'];

		$items[] = $obj;
	}

//	parameterError('36', $items, false);
	if (count($items) == 0) return;
	shuffle($items);

	$counts = [
		PINICONS => 5,
		PINTEXT => 3,
		PINEMBED => 1,
	];

	$items = array_splice($items, 0, $counts[$where]);
	renderPollen($where, $items);
}

function renderPollen($where, $items) {
	$op = [];
	
	if ($where == PINICONS) {
		foreach ($items as $item)
			$op[] = NEWLINE . '		<li>' . getLink(_iconImage($item['url'] . $item['safeName'] . '-icon.png'), $item['goesTo']) . '</li>';
		echo implode(NEWLINE, $op);
	}
}
