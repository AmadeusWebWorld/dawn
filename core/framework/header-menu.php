<?php
extract(variable('menu-settings'));
_menuULStart();

$append = variable('scaffold') ? array_merge(['----'], variable('scaffold')) : false;
menu('/' . variable('folder'), [
	'link-to-home' => variable('link-to-site-home'),
	'files-to-append' => $append,
	'a-class' => $anchorClass,
	'ul-class' => $ulClass,
]);
echo '</li>' . NEWLINE;

if ($groups = variable('section-groups')) {
	foreach ($groups as $group => $items) {
		$isGroup = true;
		if (is_string($items)) {
			$group = $items;
			$items = [$items];
			$isGroup = false;
		}

		$name = humanize($group);
		if ($wrapTextInADiv) $name = '<div>' . $name . $topLevelAngle . '</div>';

		if ($isGroup) echo '<li class="' . $itemClass . ' ' . $subMenuClass . '"><a class="' . $anchorClass . '">' . $name . '</a>' . NEWLINE;
		if ($isGroup) echo '	<ul class="' . $ulClass . '">' . NEWLINE;

		foreach ($items as $slug) {
			//if (cannot_access($slug)) continue;
			if ($slug[0] == '_') continue;
			renderHeaderMenu($slug);
		}

		if ($isGroup) echo '	</ul>' . variable('2nl');
		if ($isGroup) echo '</li>' . NEWLINE;
	}
} else {
	foreach (variable('sections') as $slug) {
		if ($slug[0] == '_') continue;
		//if (cannot_access($slug)) continue;
		renderHeaderMenu($slug);
	}
}

if (function_exists('after_menu')) after_menu();
if (function_exists('network_after_menu')) network_after_menu();
if (!$noOuterUl) _menuULStart('site');

function renderHeaderMenu($slug, $node = '', $name = false) {
	$parentSlug = $node ? $node : $slug;

	if ($name) ; //noop
	else if (contains($node, '/'))  { $bits = explode('/', $node); $name = humanize(array_pop($bits)) . ' (' . humanize(array_pop($bits)) . ')'; }
	else if ($node) { $name = humanize($node) . ' (' . humanize($slug) . ')'; }
	else { $name = humanize($parentSlug); }

	extract(variable('menu-settings'));

	$files = false; $tiss = false;
	$standalones = variableOr('standalone-sections', []);
	if (in_array($slug, $standalones)) {
		$tiss = true;
		$files = disk_include(variable('path') . '/' . $slug . '/menu.php', ['callingFrom' => 'header-menu', 'limit' => 5]);
		if ($tsmn = variable(getSectionKey($slug, MENUNAME)))
			$name = $tsmn;
	}

	$homeNA = variable(getSectionKey($slug, MENUNAME) . '_home') == 'off';
	if ($wrapTextInADiv) $name = '<div>' . $name . $topLevelAngle . '</div>';

	echo '<li class="' . $itemClass . ' ' . $subMenuClass . '"><a class="' . $anchorClass . '">' . $name . '</a>';

	if ($node) $slug .= '/' . $node;
	menu('/' . $slug . '/', [
		'a-class' => $anchorClass,
		'ul-class' => $ulClass . ($node ? ' of-node node-' . $node : ''),
		'files' => $files, 'this-is-standalone-section' => $tiss,
		'list-only-folders' => $node == '',
		'list-only-files' => variable('sections-have-files'),
		'link-to-home' => variable('link-to-section-home') && !$homeNA,
		'parent-slug-for-home-link' => $parentSlug . '/',
		'parent-slug' => $node ? $node . '/' : '',
	]);
	echo '</li>' . NEWLINE;
}