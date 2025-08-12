<?php
$files = _skipNodeFiles(disk_scandir(NODEPATH));
$items = [];
foreach ($files as $file) {
	$meta = read_seo(NODEPATH . '/' . $file . '/home.md'); //always md
	$item = $meta['meta'];
	$item['file'] = $file;
	$items[] = $item;
}

$categories = array_keys(arrayGroupBy($items, $groupBy));

$categoryItems = implode(NEWLINE, array_map(function($cat) { return replaceItems(
		'			<li class="activeFilter"><a href="#" data-filter=".article-site-%slug%">%name%</a></li>',
			['slug' => urlize($cat), 'name' => $cat], '%');
	}, $categories));

$template = getThemeBlock('articles');

echo replaceItems($template['start'], [
		'block-title' => $title,
		'filterButtons' => $categoryItems,
	],'%');

foreach ($items as $item) {
	$variables = [
		'type' => 'site-' . urlize($item[$groupBy]),
		'type_r' => $item[$groupBy],
		'link' => getHtmlVariable('nodeUrl') . $item['file'] . '/',
		'image' => pageUrl(variable('section') . '/' . variable('node') . '/' . $item['file']) . 'assets/' . $item['file'] . '.jpg',
		'title' => $item['Title'],
		'content' => $item['About'],
	];

	echo replaceItems($template['item'], $variables, '%');
}

echo $template['end'];
