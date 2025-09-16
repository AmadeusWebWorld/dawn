<?php
$sheetName = nodeIs(SITEHOME) ? 'articles' : relatedDataFile('articles');

if (!sheetExists($sheetName)) return h2('No articles found.', 'text-danger', true) . '<p>Please add articles in the "' . $sheetName . '" file.</p>';

$sheet = getSheet($sheetName, false);

$op = ['<ul class="large-list">' . NEWLINE];
$format = '<li class="mb-3">%sno% %title%<blockquote class="after-content mt-2 ms-4">%excerpt%</blockquote><hr class="m-2" /></li>';

foreach ($sheet->rows as $item) {
	$site = $sheet->getValue($item, 'site');
	$path = $sheet->getValue($item, 'path');

	$relPath = str_replace('/home', '', $path);
	$link = replaceHtml('%' . OTHERSITEPREFIX . $site . '%') . $relPath . '/';

	$file = NETWORKPATH . '/'
		. ($site == NETWORKMAIN ? 'main/' : $path . '/')
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

$op[] = '</ul>' . NEWLINES2;

return implode(NEWLINES2, $op);
