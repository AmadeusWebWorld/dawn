<?php
$networkItems = variable('networkItems');
$categories = array_keys(arrayGroupBy($networkItems, 'category'));

$categoryItems = implode(NEWLINE, array_map(function($cat) { return replaceItems(
		'			<li class="activeFilter"><a href="#" data-filter=".article-site-%slug%">%name%</a></li>',
			['slug' => urlize($cat), 'name' => $cat], '%');
	}, $categories));

$template = getThemeBlock('articles');

echo replaceItems($template['start'], [
		'block-title' => 'This Network of Sites',
		'filterButtons' => $categoryItems,
	],'%');

foreach ($networkItems as $item) {
	$variables = [
		'type' => 'site-' . urlize($item['category']),
		'type_r' => $item['category'],
		'link' => $item['url'],
		'image' => $item['img-prefix'] . '-logo.png',
		'title' => $item['name'],
		'content' => $item['byline'] . BRNL . BRNL . returnLine($item['description']) . BRNL . '<hr />Status: ' . $item['status'],
	];

	echo replaceItems($template['item'], $variables, '%');
}

echo $template['end'];
