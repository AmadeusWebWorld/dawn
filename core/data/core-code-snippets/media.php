<?php
$sheetFile = defined('NETWORKDATA') && disk_file_exists($file = NETWORKDATA . 'media.tsv') ? $file : false;
if (!$sheetFile) {
	if (!sheetExists('media')) return h2('No media found.', 'text-danger', true) . '<p>Please add items in the "data/media.tsv" file.</p>';
	$sheetFile = 'media';
}

$sheet = getSheet($sheetFile, false);
$allItems = $sheet->rows;

if (isset($sheet->columns['site']) && $thisHome = variable('subsiteHome')) {
	$thisName = $thisHome['Path'];

	$bySite = arrayGroupBy($sheet->rows, $sheet->columns['site']);
	$bySiteAndName = valueIfSet($bySite, $thisName, []);
	$bySiteAndAll = count($bySiteAndName) ? [] : valueIfSet($bySite, '*', []);
	$allItems = array_merge($bySiteAndName, $bySiteAndAll);
}

if (count($allItems)) {
	$byNode = arrayGroupBy($allItems, $sheet->columns['node']);
	$byNodeAndName = valueIfSet($byNode, nodeValue(), []);
	$byNodeAndAll = count($byNodeAndName) ? [] : valueIfSet($byNode, '*', []);
	$allItems = array_merge($byNodeAndName, $byNodeAndAll);
}

if (!count($allItems)) return !variable('local') ? ''
	: h2('No items resolved for current page.', 'text-danger', true) . '<p>Please check sufficient data in the "data/media.tsv" file.</p>';

$items = getShuffledItems($allItems, 2);
$html = '';
foreach ($items as $item)
	$html .= replaceItems('[%type%]%id%[/%type%]' . NEWLINES2, $sheet->asObject($item) , '%');

return runAllMacros($html);

//echo '<pre>' . print_r($allItems, true) . '</pre>';
//return '<pre>' . print_r($allItems, true) . '</pre>';
