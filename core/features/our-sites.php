<?php
$subsiteItems = variable('allSiteItems');
$categories = array_keys(arrayGroupBy($subsiteItems, 'category'));

$categoryItems = implode(NEWLINE, array_map(function($cat) { return replaceItems(
		'			<li class="activeFilter"><a href="#" data-filter=".article-site-%slug%">%name%</a></li>',
			['slug' => urlize($cat), 'name' => humanize($cat)], '%');
	}, $categories));

$template = getThemeBlock('articles');

echo replaceItems($template['start'], [
		'block-title' => 'This Network of Sites',
		'filterButtons' => $categoryItems,
	],'%');

//peDie('17', $subsiteItems);

foreach ($subsiteItems as $item) {
	$variables = [
		'type' => 'site-' . urlize($item['category']),
		'type_r' => $item['category'],
		'link' => $item['url'],
		'image' => $item['img-prefix'] . '-logo.png',
		'title' => $item['name'],
		'content' => $item['byline'] . BRNL . BRNL . returnLine($item['description']),
	];

	echo replaceItems($template['item'], $variables, '%');
}

echo $template['end'];
