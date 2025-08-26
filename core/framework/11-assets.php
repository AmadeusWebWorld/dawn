<?php
DEFINE('SCRIPTTAG', NEWLINE . '	<script src="%s" type="text/javascript"></script>');

function scriptTag($url) {
	echo sprintf(SCRIPTTAG, $url);
}

DEFINE('CSSTAG', NEWLINE . '	<link href="%s" rel="stylesheet" type="text/css">');
function cssTag($url) {
	echo sprintf(CSSTAG, $url);
}

function getPageName($tailOnly = true) {
	if ($tailOnly) {
		$tail = explode('/', variableOr('all_page_parameters', nodeValue()));
		return end($tail);
	}
	//todo - alternatives??
}

DEFINE('TITLEONLY', 'title-only');
DEFINE('FORHEADING', 'for-heading');

function title($what = 'default') {
	if (hasVariable('custom-title')) {
		return variable('custom-title') . ' | ' . variable('name');
	}

	$node = nodeValue();
	if ($what === TITLEONLY) return humanize($node);

	$siteRoot = $node == 'index' || variable('under-construction');

	if ($siteRoot)
		return variable('name') . ' | ' . variable('byline');

	$forHeading = $what == FORHEADING;
	$result = $forHeading ? [humanize($node)] : [];

	$exclude = ['print', 'embed'];
	$items = variableOr('page_parameters', []);
	if (!$forHeading) $items = array_reverse($items);

	foreach($items as $slug)
		if (!in_array($slug, $exclude)) $result[] = humanize($slug);

	if (!$forHeading) $result[] = humanize($node);

	return implode($forHeading ? ' &mdash;&gt; ' : ' &lt; ', $result) . ($forHeading ? '' : ' | ' . variable('name'));
}

//locations
DEFINE('LEAFNODEASSETS', 'LEAFNODE');
DEFINE('NODEASSETS', 'NODE');
DEFINE('NETWORKASSETS', 'NETWORK');
DEFINE('SITEASSETS', 'SITE');
DEFINE('COREASSETS', 'CORE');
DEFINE('THEMEASSETS', 'THEME');

DEFINE('ASSETFOLDER', '-folder');

function assetKey($type, $suffix = '') {
	return 'ASSETSOF' . $type . $suffix;
}

function assetMeta($location = SITEASSETS, $setValueOr = false) {
	$key = '__assetmanager_meta_' . $location; //cache it to prevent long manipulations/file reads below

	if (is_array($setValueOr)) {
		variable($key, $setValueOr);
		return;
	}

	//dont do early return as get could be for one item of array alone
	if (!($result = variable($key))) {
		$mainFol = variable(assetKey($location, ASSETFOLDER));
		$versionFile = $mainFol . '_version.txt';
		$version = disk_file_exists($versionFile) ? '?' . disk_file_get_contents($versionFile) : '';

		$result = ['location' => variable(assetKey($location)), 'version' => $version];

		//print_r($result); debug_print_backtrace();
		variable($key, $result);
	}

	if ($setValueOr == 'version')
		return $result['version'];
	//TODO: not yet implemented for url!

	return $result;
}

//what == logo | icon
//which = site | node (falls back to site)
function getLogoOrIcon($what, $which = 'site') {
	$suffix = ($what == 'icon' ? '-icon' : '-logo') . '.png';

	$netWorkManaged = variable('network-manages-site-assets');
	$prefix = $netWorkManaged && DEFINED('SITENAME') ? SITENAME . '/' : '';

	if (is_array($which)) {
		$inNode = true;
		$name = $which['nodeSafeName'] . $suffix;
		$where = $which[assetKey(NODEASSETS)];
	} else {
		$inNode = $which == 'node' && hasVariable('nodeSafeName') && DEFINED('NODEPATH');
		$name = variable($inNode ? 'nodeSafeName' : 'safeName') . $suffix;

		$where = $inNode ? STARTATNODE : ($netWorkManaged ? STARTATNETWORK : STARTATSITE);
	}

	return _resolveFile($prefix . $name, $where, $inNode);
}

DEFINE('STARTATNODE', 0);
DEFINE('STARTATNETWORK', 1);
DEFINE('STARTATSITE', 2);
DEFINE('STARTATCORE', 3);

function _resolveFile($file, $where = 0, $includeAssets = true) {
	if (is_integer($where)) {
		$hierarchy = [NODEASSETS, NETWORKASSETS, SITEASSETS, COREASSETS];
		while (true) { if (hasVariable( assetKey($hierarchy[$where]))) break; else $where++; }
		$result = assetUrl($file, $hierarchy[$where]);
	} else {
		$result = $where . $file;
	}
	if (!$includeAssets) $result = str_replace('/assets/', '/', $result);
	return $result;
}

function assetUrl($file, $location) {
	if (startsWith($file, 'http') || startsWith($file, '//'))
		parameterError('ASSETMANAGER: direct urls not supported in beta', $file, DOTRACE, DODIE);

	$meta = assetMeta($location);
	return $meta['location'] . $file . (contains($file, '.') ? $meta['version'] : '');
}

variables(['styles' => [], 'scripts' => []]);
//NODE: This breaks the offline mode...
variables(['3pStyles' => [], '3pScripts' => []]);

function add3pStyle($url) {
	$array = variable('3pStyles'); $array[] = $url; variable('3pStyles', $array);
}

function add3pScript($url) {
	$array = variable('3pScripts'); $array[] = $url; variable('3pScripts', $array);
}

function addStyle($name, $location = SITEASSETS) {
	_addAssets($name, $location, 'styles');
}

function addScript($name, $location = SITEASSETS) {
	_addAssets($name, $location, 'scripts');
}

function _addAssets($names, $location, $type) {
	$existing = variable($type);

	if (!is_array($names)) $names = [$names]; //magic - single or array. location can be defined only once
	foreach ($names as $name) {
		$key = concatSlugs([$type, $location, $name]);
		if (isset($existing[$key])) return;

		$existing[$key] = [ 'name' => $name, 'location' => $location ];
	}
	variable($type, $existing);
}

function styles_and_scripts() {
	foreach (variable('styles') as $item)
		cssTag(assetUrl($item['name'] . '.css', $item['location']));
	foreach (variable('scripts') as $item)
		scriptTag(assetUrl($item['name'] . '.js', $item['location']));

	foreach (variable('3pStyles') as $url) cssTag($url);
	foreach (variable('3pScripts') as $url) scriptTag($url);

	if (variable('mediakit'))
		cssTag(variable('app') . 'assets/mediakit.php' . variable('mediakit'));
}
