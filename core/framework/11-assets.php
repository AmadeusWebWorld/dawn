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

function assetMeta($location = 'site', $setValueOr = false) {
	$key = '__assetmanager_meta_' . $location; //cache it to prevent long manipulations/file reads below

	if (is_array($setValueOr)) {
		variable($key, $setValueOr);
		return;
	}

	//dont do early return as get could be for one item of array alone
	if (!($result = variable($key))) {
		$bits = explode('--', $location);
		$twoBits = explode('-', $bits[0], 2);

		$mainFol = variableOr('site-static-folder', SITEPATH . '/');
		$mainUrl = variableOr('site-static', fileUrl('assets/'));
		if ($twoBits[0] == 'app') {
			$mainFol = AMADEUSROOT . $twoBits[1] . '/';
			$mainUrl = variable($bits[0]);
		} else if ($twoBits[0] == 'network' && isset($twoBits[1])) {
			$mainFol = variable('network-static-folder') .  $twoBits[1] . '/';
			$mainUrl = variable('network-static');
		} else if ($twoBits[0] == 'node') {
			$mainFol = variable('node-static-folder') .  $twoBits[1] . '/';
			$mainUrl = variable('node-static');
		}

		$middlePath = (in_array($twoBits[0], ['network', 'app'])
			&& isset($bits[1])) ? $bits[1] . '/' : '';

		$versionFile = $mainFol . $middlePath .'_version.txt';
		$version = disk_file_exists($versionFile) ? '?' . disk_file_get_contents($versionFile) : '';
		$location = $mainUrl . $middlePath;

		$result = ['location' => $location, 'version' => $version];

		//print_r($result); debug_print_backtrace();
		variable($key, $result);
	}

	if ($setValueOr == 'version')
		return $result['version'];
	//TODO: not yet implemented for url!

	return $result;
}

function siteOrNetworkOrAppStatic($relative, $assertSite = false) {
	$nodeStatic = hasVariable('node-static') && $assertSite == false;
	$where = $nodeStatic ? 'node-static' : variableOr('site-static', 'app-static');
	$subFol = $nodeStatic || hasVariable('site-static') ? '' : variable('safeName') . '/';

	return assetUrl($subFol . $relative, $where);
}

//TODO: support for network-static and site-static
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

function addStyle($name, $location = 'site') {
	_addAssets($name, $location, 'styles');
}

function addScript($name, $location = 'site') {
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
