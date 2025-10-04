<?php
function getGoogleItems($type) {
	$sheet = getSheet(OPUSSITE . '/data/google' . '.tsv', 'type');
	$result = [];
	foreach ($sheet->group[$type] as $item)
		$result[] = $sheet->asObject($item);
	return $result;
}

function printMenuAndRender($items, $format) {
	$currentItem = false;
	contentBox('items', 'container text-center');
	$result = ['<span class="btn btn-outline-danger">Calendars &mdash;&gt;</span>'];
	$name = getQueryParameter('name');

	$baseUrl = nodeValue() . '/' . getPageParameterAt(1) . '/?' . TESTABLE . 'name=';

	foreach ($items as $item) {
		if (!appliesToCurrentUser($item['users'])) continue;
		if ($name == $item['name']) $currentItem = $item;
		$btn = $name == $item['name'] ? 'btn-secondary' : 'btn-success';
		$result[] = linkBuilder::factory($item['name'],  $baseUrl . $item['name'], 'humanize margins ' . $btn);
	}
	echo implode(NEWLINE, $result);
	contentBox('end');

	if (!$currentItem) return;

	contentBox('items', 'container');
	h2(SPACERSTART . humanize($currentItem['name']) . SPACEREND);
	echo replaceItems($format, $currentItem);
	contentBox('end');
}
