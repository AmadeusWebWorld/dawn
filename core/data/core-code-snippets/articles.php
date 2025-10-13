<?php
$sheetName = nodeIs(SITEHOME) ? 'articles' : relatedDataFile('articles');

if (!sheetExists($sheetName)) return h2('No articles found.', 'text-danger', true) . '<p>Please add articles in the "' . $sheetName . '" file.</p>';

$sheet = getSheet($sheetName, false);

$op = ['</section><div class="container articles">' . NEWLINE];
$format = '<section class="content-box"><sup>%sno%</sup> %title%
<br /><br />%excerpt%</section>';

foreach ($sheet->rows as $item) {
	$site = $sheet->hasColumn('site') ? $sheet->getValue($item, 'site') : '';
	$path = $sheet->getValue($item, 'path');

	$relPath = str_replace('/home', '', $path);
	$url = replaceHtml(DEFINED('NETWORKPATH') && $site ? '%' . OTHERSITEPREFIX . $site . '%' : '%url%');

	$link = $url . $relPath . '/';

	$base = $site && DEFINED('NETWORKPATH')
		? NETWORKPATH . '/' . $site . '/'
		: SITEPATH . '/';

	$file = $base
		. $sheet->getValue($item, 'section') . '/'
		. $path
		. $sheet->getValue($item, 'extension');

	$title = $sheet->getValue($item, 'title');

	$itm = replaceItems($format, [
		'sno' => $sheet->getValue($item, 'sno'),
		'title' => getLink($title, $link, 'btn btn-outline-info'),
		'excerpt' => renderExcerpt($file, $link, '', false),
	], '%');

	$op[] = $itm;
}

$op[] = '<section>' . NEWLINES2;

return implode(NEWLINES2, $op);
