<?php
function before_render() {
	addStyle('amadeusweb7', COREASSETS);
	addStyle('amadeusweb8', COREASSETS);
	addStyle('amadeus-web-features', COREASSETS);
	addScript('amadeusweb7', COREASSETS);

	if (function_exists('beforeSectionSet') && beforeSectionSet()) return;

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
	if (function_exists('network_before_render')) network_before_render();
	if (function_exists('site_before_render')) site_before_render();

	$file = variable('file');

	if ($file && endsWith($file, '.md'))
		peekAtMainFile($file);

	$leafFolder = $file ? dirname($file) . '/' : variable('folderGoesUpto');
	variable('leafFolder', $leafFolder);

	$fol = $leafFolder;
	while (startsWith($fol, SITEPATH) && $fol != SITEPATH) {
		if (disk_file_exists($incFile = $fol . '/_include.php'))
			disk_include_once($incFile); //its in include once so no worry
		$fol = dirname($fol);
	}

	if (variable('auto-set-node')) autoSetNode(0, SITEPATH);

	ensureNodeVar();
	if (function_exists('node_before_render')) node_before_render();
	read_seo($file);
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

variable('specialHumanizeReplaces', [
	'with ai' => 'With AI',
	'aop' => 'AO Projects',
	'2025 02' => 'Feb 2025',
	'2025 04' => 'Apr 2025',
	'2025 05' => 'May 2025',
	'2025 06' => 'Jun 2025',
	'2025 07' => 'Jul 2025',
	'2025 08' => 'Aug 2025',
	'2025 09' => 'Sep 2025',
]);

function site_humanize($txt, $field = 'title', $how = false) {
	$arrays = [
		variableOr('siteHumanizeReplaces', []),
		variableOr('nodeHumanizeReplaces', []),
		variableOr('nodeItemHumanizeReplaces', []),
		variableOr('specialHumanizeReplaces', []),
	];

	$key = strtolower($txt);
	foreach ($arrays as $list)
		if (array_key_exists($key, $list))
			return $list[$key];

	return $txt;
}

bootstrap([]);
