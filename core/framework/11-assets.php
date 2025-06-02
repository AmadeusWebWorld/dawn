<?php
function scriptTag($url) {
	echo PHP_EOL . '	<script src="' . $url . '" type="text/javascript"></script>';
}

function cssTag($url) {
	echo PHP_EOL . '	<link href="' . $url . '" rel="stylesheet" type="text/css"> ';
}

function getPageName($tailOnly = true) {
	if ($tailOnly) {
		$tail = explode('/', variableOr('all_page_parameters', variable('node')));
		return end($tail);
	}
	//todo - alternatives??
}

function title($return = false) {
	if (variable('custom-title') || variable('node-alias')) {
		$r = variableOr('custom-title', humanize(variable('name')) . ' | ' . variable('byline'));
		if ($return) return $r;
		echo $r;
		return;
	}

	$page = variableOr('page-name', variable('node'));
	$siteRoot = $page == 'index' || variable('under-construction');

	if ($return === 'title-only') return $page;
	$r = [];

	if ($return !== 'params-only')
		$r[] = (!$siteRoot ? humanize($page) . ' - ' : '') . variable('name') . ($siteRoot ? ' | ' . variable('byline') : '');

	if ($return !== true) {
		$exclude = ['print', 'embed'];
		foreach(array_merge([variable('node')], variableOr('page_parameters', [])) as $slug)
			if (!in_array($slug, $exclude)) $r[] = humanize($slug);
	}

	$r = implode(' &mdash;&gt; ', $r);
	if ($return) return $r;
	echo $r;
}

//locations
DEFINE('NODEASSETS', 'NODE');
DEFINE('NETWORKASSETS', 'NETWORK');
DEFINE('SITEASSETS', 'SITE');
DEFINE('COREASSETS', 'CORE');

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

	$nameVar = 'safeName';
	if ($which == 'node1' && hasVariable('node1SafeName')) $nameVar = 'node1SafeName';
	if ($which == 'node' && hasVariable('nodeSafeName')) $nameVar = 'nodeSafeName';

	$name = variable($nameVar) . $suffix;

	$node = ($which == 'node' || $which == 'node1') && DEFINED('NODEPATH');
	$prefix = ($node ? (variable('network') ? SITENAME . '/' : '') . (variable('section') ? variable('section') . '/' : '') : '');
	$where = $what == 'icon' && !$node ? STARTATSITE : (variable('network') ? STARTATNETWORK : STARTATNODE);
	return _resolveFile($prefix . $name, $where, $node);
}

DEFINE('STARTATNODE', 0);
DEFINE('STARTATNETWORK', 1);
DEFINE('STARTATSITE', 2);
DEFINE('STARTATCORE', 3);

function _resolveFile($file, $where = 0, $includeAssets = true) {
	$hierarchy = [NODEASSETS, NETWORKASSETS, SITEASSETS, COREASSETS];
	while (true) { if (hasVariable( assetKey($hierarchy[$where]))) break; else $where++; }
	$result = assetUrl($file, $hierarchy[$where]);
	if (!$includeAssets) $result = str_replace('/assets/', '/', $result);
	return $result;
}

function assetUrl($file, $location) {
	if (startsWith($file, 'http') || startsWith($file, '//'))
		parameterError('ASSETMANAGER: direct urls not supported in beta', $file, DOTRACE, DODIE);

	$meta = assetMeta($location);
	return $meta['location'] . $file . $meta['version'];
}

variables([
	'styles' => [],
	'scripts' => [],
]);

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
}
