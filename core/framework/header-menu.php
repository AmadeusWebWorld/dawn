<?php
if (variable('under-construction')) return;

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

subsitesMenu();
if (function_exists('pollenAt')) pollenAt(PINICONS);
if (function_exists('after_menu')) after_menu();
if (function_exists('network_after_menu')) network_after_menu();
if (!$noOuterUl) _menuULStart('site');

function subsitesMenu() {
	$items = variableOr('subsiteItems', []);
	if (count($items) == 0) return;

	$home = variable('subsiteHome');
	if (!$home) return;

	$name = humanize($home['Name']) . '++';

	extract(variable('menu-settings'));
	if ($wrapTextInADiv) $name = '<div>' . $name . $topLevelAngle . '</div>';

	echo '<li class="' . $itemClass . ' ' . $subMenuClass . '"><a class="' . $anchorClass . '">' . $name . '</a>' . NEWLINE;
	echo '	<ul class="' . $ulClass . '">' . NEWLINE;

	$all = variable('allSiteItems');
	$homePath = $home['Path'] . '/' . (isset($home['Folder']) ? $home['Folder'] : '') . $home['Subsite']['Site'];

	$name = _siteOf($home, $all, $wrapTextInADiv, $anchorClass);
	echo '<li class="' . $itemClass . ' ' . $subMenuClass . '">' . $name . '</li>';

	foreach ($items as $siteAt => $item) {
		//if ($item['Path'] != $home['Path']) continue; //skip other networks
		if ($siteAt == $homePath) continue;
		$name = _siteOf($item, $all, $wrapTextInADiv, $anchorClass);
		if (!$name) continue;
		echo '<li class="' . $itemClass . ' ' . $subMenuClass . '">' . $name . '</li>';
	}

	echo '	</ul>' . variable('2nl');
	echo '</li>' . NEWLINE;
}

function _siteOf($item, $items, $wrap, $anchorClass) {
	$key = $item['Path'] . '/' . (isset($item['Folder']) ? $item['Folder'] : '') . $item['Subsite']['Site'];
	if (!isset($items[$key])) return false;
	$site = $items[$key];
	$name = str_replace('site-icon', $anchorClass, $site['icon-link']);
	if ($wrap) $name = '<div>' . $name . '</div>';
	return $name;
}

function _headerMenuItem($name, $link, $target = false) {
	extract(variable('menu-settings'));
	if ($wrapTextInADiv) $name = '<div>' . $name . '</div>';
	echo '<li class="' . $itemClass . ' ' . $subMenuClass . '">' . getLink($name, $link, $anchorClass, $target) . '</li>' . NEWLINE;
}

function renderHeaderMenu($slug, $node = '', $name = false) {
	$parentSlug = $node ? $node : $slug;

	if ($name) ; //noop
	else if (contains($node, '/'))  { $bits = explode('/', $node); $name = humanize(array_pop($bits)) . ' (' . humanize(array_pop($bits)) . ')'; }
	else if ($node) { $name = humanize($node) . ' (' . humanize($slug) . ')'; }
	else { $name = humanize($parentSlug); }

	extract(variable('menu-settings'));
	$parentSlugForMenuItem = function_exists('getParentSlugForMenuItem') ? getParentSlugForMenuItem($slug, $node) : ($node ? $node . '/' : '');
	if (function_exists('getParentSlug')) $parentSlug = getParentSlug($parentSlug);

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
		'parent-slug' => $parentSlugForMenuItem,
	]);
	echo '</li>' . NEWLINE;
}