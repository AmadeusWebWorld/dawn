<?php
function before_render() {
	addStyle('amadeusweb7', COREASSETS);
	addStyle('amadeus-web-features', COREASSETS);
	addScript('amadeusweb7', COREASSETS);

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

		//NOTE: rewritten in v 7.2 & 8.0
		$baseFol = variable('path') . '/' . $slug . ($slug != $node ? '/' . $node : ''); //no trailing slash

		if (!disk_is_dir($baseFol)) {
			continue;
		}
		if (!empty($innerSlugs)) {
			$reversedVars = [];
			$thisBreadcrumbs = [];
			$folderAbsolute = $baseFol;

			foreach ($innerSlugs as $thisItem) {
				$matchType = false;
				$fileExtension = false;
				$item = $thisItem;

				if ($fileExtension = disk_one_of_files_exist($folderAbsolute . '/' . $item . '/home.', CONTENTFILES)) {
					$matchType = 'file';
					$thisBreadcrumbs[] = $item;
					$item .= '/home';
				} else if ($fileExtension = disk_one_of_files_exist($folderAbsolute . '/' . $item . '.', CONTENTFILES)) {
					$matchType = 'file';
				} else if (disk_is_dir($folderAbsolute . '/' . $item)) {
					$matchType = 'folder';
				}

				if (!$matchType) break;

				if ($matchType == 'folder') {
					$folderAbsolute .=  '/' . $thisItem;
					$thisBreadcrumbs[] = $item;
				}

				$breadcrumbs = $thisBreadcrumbs;
				$reversedVars[] = compact('item', 'matchType', 'fileExtension', 'breadcrumbs', 'folderAbsolute');

				if ($matchType != 'folder')
					$folderAbsolute .=  '/' . $thisItem;
			}

			$reversedVars = array_reverse($reversedVars);

			$fileToTry = 'home';
			foreach ($reversedVars as $vars) {
				extract($vars);

				if ($matchType == 'file')
					variable('file', $folderAbsolute . '/' . $item . '.' . $fileExtension);

				variable('section', $slug);
				variable('breadcrumbs', $breadcrumbs);
				variable('folderGoesUpto', $folderAbsolute);
				afterSectionSet();
				return;
			}

			return; //let it throw a missing file exception
		} else {
			variable('folderGoesUpto', dirname($baseFol));
			if (setFileIfExists($slug, $baseFol . '/home.', false, false)) return;
			continue;
		}
	}

	//lets make it a point to call before render here, assuming either its a "content" page or will throw an error
	afterSectionSet();
}

function setFileIfExists($section, $fwe, $breadcrumbs, $itemToAdd) {
	if ($breadcrumbs) variable('breadcrumbs', $breadcrumbs);

	$ext = disk_one_of_files_exist($fwe, CONTENTFILES);
	if (!$ext) return false;

	variable('file', $fwe . $ext);
	variable('section', $section);
	variable('folderGoesUpto', dirname($fwe));
	if ($itemToAdd) $breadcrumbs[] = $itemToAdd;
	if ($breadcrumbs) variable('breadcrumbs', $breadcrumbs);

	afterSectionSet();
	return true;
}

function afterSectionSet() {
	//TODO: include _folder.php on $file if it exists
	if (function_exists('site_before_render')) site_before_render();

	$leafFolder = variable('file') ? dirname(variable('file')) : variable('folderGoesUpto');
	if (disk_file_exists($inc2File = $leafFolder . '/_include.php'))
		disk_include_once($inc2File);

	read_seo();
}

function did_render_page() {
	if (function_exists('did_site_render_page') && did_site_render_page()) return true;
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
