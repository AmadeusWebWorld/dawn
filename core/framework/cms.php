<?php
function before_render() {
	addStyle('amadeusweb7', 'app-static');
	addStyle('amadeus-web-features', 'app-static');
	addScript('amadeusweb7', 'app-static');
	/* NEEDED?
	if (contains(SITEPATH, 'awe'))
		addStyle('amadeusweb-network', 'app-static');
	*/

	if (!variable('dont-use-site-static')) 
	variable('site-static',
		assetMeta(variable('network') ? 'network-static' : 'site-static')['location']);

	if (hasSpecial()) { afterSectionSet(); return; }

	$canHaveFiles = variable('sections-have-files');
	$node = variable('node');
	$innerSlugs = variable('page_parameters');

	foreach (variable('sections') as $slug) {
		if (disk_file_exists($incFile = variable('path') . '/' . $slug . '/' . $node . '/_include.php')) {
			variable('section', $slug);
			disk_include_once($incFile);
			if (hasVariable('is-standalone-section')) {
				afterSectionSet();
				return;
			}
		}

		if (function_exists('before_render_section')){
			if (before_render_section($slug)) {
				afterSectionSet();
				return;
			}
		}

		if ($slug == $node && empty($innerSlugs)) {
			variable('directory_of', $node);
			variable('section', $slug);
			afterSectionSet();
			return;
		}

		if ($canHaveFiles) {
			if ($slug == $node) {
				$level0 = [$slug == $node ? variable('path') . '/' . $slug . '/home.' :
					variable('path') . '/' . $slug . '/' . $node . '.'];
				if (setFileIfExists($slug, $level0, false, false)) return;
			}

			$page1 = variable('page_parameter1') ? variable('page_parameter1') : 'home';
			$folUptoNode = variable('path') . '/' . $slug . '/' . $node;

			if (setFileIfExists($slug, $folUptoNode . '/' . $page1 . '.', false, false)) return;
			if (setFileIfExists($slug, $folUptoNode . '.', false, false)) return;

			//die('Coulnt Find File in v7.1'); //let it fall back
		}

		//NOTE: rewritten in v 7.2
		$baseFol = variable('path') . '/' . $slug . '/' . ($slug != $node ? $node . '/' : '');

		if (!disk_is_dir($baseFol)) {
			continue;
		}

		if (!empty($innerSlugs)) {
			$innerReverse = [];
			$breadcrumbs = [];
			$thisRelative = '';
			$relative = '';
			foreach ($innerSlugs as $item) {
				$thisRelative = $relative;
				$thisFol = $baseFol . $relative;

				$exists = disk_is_dir($thisFol . $item);
				if ($exists) $breadcrumbs[] = $item;
				$thisBreadcrumbs = $breadcrumbs;
				$innerReverse[] = compact('item', 'thisFol', 'thisBreadcrumbs', 'thisRelative');
				//for next
				$relative .= $item . ($relative ? '' : '/');
			}

			$innerReverse = array_reverse($innerReverse);

			$fileToTry = 'home';
			foreach ($innerReverse as $vars) {
				extract($vars);
				if (disk_is_dir($thisFol)) {
					$fileToTry = $item;
					if (setFileIfExists($slug, $baseFol . $thisRelative . $item . '.', $thisBreadcrumbs, false)) { return; }
					if (setFileIfExists($slug, $thisFol . 'home.', $thisBreadcrumbs, false)) return;
					break;
				}
			}

			if (setFileIfExists($slug, $baseFol . 'home.', [], false)) return;
		} else {
			if (setFileIfExists($slug, $baseFol . 'home.', false, false)) return;
			continue;
		}
	}

	//lets make it a point to call before render here, assuming either its a "content" page or will throw an error
	afterSectionSet();
}

function setFileIfExists($section, $fwe, $breadcrumbs, $itemToAdd) {
	if ($breadcrumbs) variable('breadcrumbs', $breadcrumbs);

	$ext = disk_one_of_files_exist($fwe, 'php, md, tsv, html');
	if (!$ext) return false;

	variable('file', $fwe . $ext);
	variable('section', $section);
	if ($itemToAdd) $breadcrumbs[] = $itemToAdd;
	if ($breadcrumbs) variable('breadcrumbs', $breadcrumbs);

	afterSectionSet();
	return true;
}

function afterSectionSet() {
	//TODO: include _folder.php on $file if it exists
	if (function_exists('site_before_render')) site_before_render();
	read_seo();
}

function did_render_page() {
	if (renderedSpecial()) return true;

	if (variable('directory_of')) {
		runFeature('directory');
		return true;
	}

	if ($file = variable('file')) {
		autoRender($file);
		return true;
	}
}

function site_humanize($txt, $field = 'title', $how = false) {
	$pages = variableOr('siteHumanizeReplaces', []);
	if (array_key_exists($key = strtolower($txt), $pages))
		return $pages[$key];

	return $txt;
}

bootstrap([]);
