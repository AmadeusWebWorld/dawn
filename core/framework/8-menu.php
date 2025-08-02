<?php
variable('toggle-list', 'toggle-list-below');

DEFINE('MENUPADLEFT', '							');
DEFINE('NOPAGESTART', '--page-start--');
function _menuULStart($endAndName = false) {
	if ($endAndName && $endAndName != NOPAGESTART) { echo MENUPADLEFT . '</ul><!-- #end ' . $endAndName . ' menu -->' . NEWLINES2; return; }

	extract(variable('menu-settings'));
	if (!isset($groupOuterUlClass) && $endAndName != NODEPAGESTART) $groupOuterUlClass = $outerUlClass;
	if (!$noOuterUl) echo NEWLINE . MENUPADLEFT . '<ul class="' . $groupOuterUlClass . '">' . NEWLINE;

	if ($endAndName == NOPAGESTART) return;

	$mainMenu = variable($isPageMenu ? 'nodeSiteName' : 'siteMenuName') . $topLevelAngle;
	if ($wrapTextInADiv) $mainMenu = '<div>' . $mainMenu . '</div>';
	echo MENUPADLEFT . '	<li class="' . $itemClass . '"><a class="' . $anchorClass . '" href="javascript: void(0);">' . $mainMenu . '</a>' . NEWLINE;
}

function _handleSlashes($file, $handle, $useMDash) {
	if (!$handle || contains($file, '#') || !contains($file, '/'))
		return $file;

	$test = humanize($file);
	if (!contains($test, '/'))
		return $test;

	$bits = explode('/', $file);
	return $useMDash ? join(' &mdash; ', $bits) : array_pop($bits);
}

function _skipNodeFiles($files) {
	return _skipExcludedFiles($files, variable('exclude-folders'), '', true);
}

function _skipExcludedFiles($files, $excludeNames = 'home', $excludeExtensions = 'jpg, png', $stripExtension = false) {
	$op = [];

	if (!is_array($excludeNames))
		$excludeNames = explode(', ', $excludeNames);
	$checkNames = count($excludeNames) > 0;

	$excludeExtensions = explode(', ', $excludeExtensions);
	$checkExtensions = count($excludeExtensions) > 0 && $excludeExtensions[0] != '';

	foreach($files as $item) {
		if ($item[0] == '.' OR $item[0] == '_')
			continue;

		if ($checkNames && in_array(stripExtension($item), $excludeNames))
			continue;

		if ($checkExtensions && in_array(getExtension($item), $excludeExtensions))
			continue;

		if ($stripExtension)
			$item = stripExtension($item);

		$op[] = $item;
	}

	return $op;
}

function pageMenu($file) {
	print_seo();

	if (variable('no-page-menu') || !variable('section') || hasPageParameter('embed')) return;

	$breadcrumbs = variable('breadcrumbs');

	if (!$breadcrumbs) {
		if (variable('section') == variable('node')) return;

		//happens when: 'sections-have-files' (Ag)
		if (!disk_is_dir(concatSlugs([variable('path'), variable('section'), variable('node')]))) return;

		variable('in-node', true);

		variable('directory_of', variable('section') . '/' . variable('node'));
		runFeature('directory');
		return;
	}

	variable('directory_of', variable('section') . '/' . variable('node') . '/' . concatSlugs($breadcrumbs));
	runFeature('directory');
}

DEFINE('ABSOLUTEPATHPREFIX', 'ABSOLUTE=');

function menu($folderRelative = false, $settings = []) {
	if (variable('menu-settings')) $settings = array_merge(variable('menu-settings'), $settings);

	$useSections = valueIfSetAndNotEmpty($settings, 'sections-not-list');
	$itemTag = $useSections ? 'section' : 'li';
	$noul = $useSections || (isset($settings['no-ul']) && $settings['no-ul']);
	$indent = MENUPADLEFT . ($indentGiven = valueIfSetAndNotEmpty($settings, 'indent', ''));

	$class_li = arrayIfSetAndNotEmpty($settings, 'li-class');
	$class_active = arrayIfSetAndNotEmpty($settings, 'li-active-class', 'selected');
	$class_link = arrayIfSetAndNotEmpty($settings, 'a-class');
	$class_ul = arrayIfSetAndNotEmpty($settings, 'ul-class');

	//NOTE: needed for can_access
	$what = valueIfSetAndNotEmpty($settings, 'what');
	$where = valueIfSetAndNotEmpty($settings, 'where', '');

	$backToHome = valueIfSet($settings, 'back-to-home', '');
	$menuLevel = valueIfSetAndNotEmpty($settings, 'menu-level', 1);

	$result = NEWLINE;
	if (!$noul) $result .= $indent . '<ul' . cssClass($class_ul) . '>' . NEWLINE;

	$isAbsolute = startsWith($folderRelative, ABSOLUTEPATHPREFIX);
	$folderPrefix = $isAbsolute ? '' : variable('path');
	if ($isAbsolute) $folderRelative = substr($folderRelative, strlen(ABSOLUTEPATHPREFIX));
	$folder = $folderPrefix. ($folderRelative ? $folderRelative : (variable('folder') ? '/' . variable('folder') : '/'));

	$filesGiven = false;
	$couldHaveSlashes = isset($settings['could-have-slashes']) && $settings['could-have-slashes'];
	$givenFiles = valueIfSetAndNotEmpty($settings, 'files');
	$standalone = valueIfSetAndNotEmpty($settings, 'this-is-standalone-section');
	$inHeader = valueIfSetAndNotEmpty($settings, 'in-header');

	$namesOfFiles = false;
	if ($standalone) {
		$namesOfFiles = $givenFiles;
		$files = array_keys($givenFiles);
		$filesGiven = true;
	} else if ($givenFiles) {
		$files = $givenFiles;
		$filesGiven = true;
	} else {
		if (disk_file_exists($itemsTsv = $folder . '_menu-items.tsv')) {
			$itemsSheet = getSheet($itemsTsv, 'slug');
			$filesGiven = true;
			$files = [];

			$hasSNo = isset($itemsSheet->columns['sno']);
			if ($hasSNo) { $namesOfFiles = []; $snoIndex = $itemsSheet->columns['sno']; }
			//later we can make it sno and text as optional

			foreach ($itemsSheet->group as $thisFile => $thisItem) {
				$files[] = $thisFile;
				if ($hasSNo)
					$namesOfFiles[$thisFile] = $thisItem[0][$snoIndex] . '. ' . humanize($thisFile);
			}
		} else {
			$files = disk_scandir($folder);
			natsort($files);
			$files = _skipExcludedFiles($files);
		}

		$config = getConfigValues($folder . '_menu-config-values.txt'); //for some reason, . in the filename doesnt work - does for .template.html though
		if($config) {
			if (isset($config['reverse']) && $config['reverse'] == 'yes')
				$files = array_reverse($files);

			if (isset($config['limit']))
				$files = getRange($files, intval($config['limit']));
		}
	}

	$exclude = valueIfSet($settings, 'exclude-files', []);
	$exclude = array_merge(variable('exclude-folders'), $exclude);
	$breaks = valueIfSetAndNotEmpty($settings, 'breaks', []); //NOTE: needed for immersive education node
	$prefix = isset($settings['prefix']) ? $settings['prefix'] . ' ' : '';
	$wrapInDiv = ($wrapInDivVO = valueIfSetAndNotEmpty($settings, 'wrapTextInADiv'));
	$onlySlugForSectionMenu = valueIfSet($settings, 'humanize');

	//If neither specified, returns mixed.
	$onlyFiles = valueIfSet($settings, 'list-only-files');
	$onlyFolders = valueIfSet($settings, 'list-only-folders');

	$excludeExtensions = valueIfSet($settings, 'exclude-extensions', []);

	$base = valueIfSet($settings, 'parent-slug', '');
	$noLinks = valueIfSet($settings, 'no-links');
	$blogHeading = valueIfSet($settings, 'blog-heading');

	$section = explode('/', $folderRelative)[1];
	$last = false;

	if (isset($settings['link-to-home']) && $settings['link-to-home']) {
		$homeBase = $base;
		if (isset($settings['parent-slug-for-home-link'])) $homeBase = $settings['parent-slug-for-home-link'];

		$mainNode = ($section == variable('node')) || startsWith($folderRelative, '/' . variable('section'));
		$result .= replaceItems($indent . '	<li%li-classes%><a href="%url%"%a-classes%><%wrap-in%>%text%</%wrap-in%></a>' . NEWLINE, [
			'li-classes' => cssClass(array_merge($class_li, $mainNode ? ['selected'] : [], ['home-link'])),
			'a-classes' => cssClass($class_link),
			'wrap-in' => $wrapInDivVO ? 'div' : 'u',
			'url' => pageUrl() . $homeBase,
			//%style% - 'style' => $mainNode ? ' style="background-color: var(--amw-home-link-color);"' : '',
			'text' => 'Home'
		], '%');
	}

	if ($append = valueIfSetAndNotEmpty($settings, 'files-to-append', []))
		$files = array_merge($files, $append);

	$files = isset($settings['reorderItems']) ? $settings['reorderItems']($files) : $files;

	foreach ($files as $file) {
		if ($file == 'index') continue; //scaffolded but not in menu

		//skip these checks when there is a whitelist
		if (!$filesGiven && !in_array($file, $append)) {
			if ($onlyFolders != $onlyFiles) {
				if ($onlyFolders && !is_dir($folder . $file)) continue;
				if ($onlyFiles && is_dir($folder . $file)) continue;
			}

			$info = pathinfo($file);
			$bits = [$info['filename']]; //TODO: move to files.php
			if (isset($info['extension'])) $bits[] = $info['extension'];

			$extension = getExtension($file);
			$file = stripExtension($file);
			$isDir = disk_is_dir($folder . $file);
			if ($isDir) $extension = '';

			if ($file && $file[0] != '~' && !$extension && !$isDir) {
				if (variable('local')) {
					parameterError('$settings', $settings);
					parameterError('file with no extension - skipping', [ 'folder' => $folder, 'file' => $file]);
				}
				continue;
			}

			if ($extension && in_array($extension, $excludeExtensions)) continue;
		} else {
			$extension = 'none';
		}

		$indented = '';
		if (startsWith($file, '~')) {
			if (variable('thisSection') && !$indented) { $result .= '<hr>'; variable('hadMenuSection', true); }
			$result .= $indent . '	<' . $itemTag . ' class="menu-section">' . substr($file, 1) . '</' . $itemTag . '>' . NEWLINE;
			$indented = 'indented';
			continue;
		} else if ($file == '----') {
			$result .= $indent . '	<' . $itemTag . ' class="menu-break"><hr></' . $itemTag . '>' . NEWLINE;
			continue;
		}

		if (!$filesGiven) {
			if (in_array($file, $exclude)) continue;
			$isNotValidFile = disk_is_dir($folder . $file) && !isset($bits[1]);
			if ($file == 'index' || substr($file, 0, 1) == '_' || $last == $file) continue;
		}

		if (isset($settings['visible']) && !$settings['visible']($file)) continue;
		$last = $file;

		//note removed the $extensions - guess used in archives for jpg linking to jpg or something..
		$url = pageUrl($base . $file); //new method will autoadd trailing slash

		$file = _handleSlashes($file, $filesGiven || $couldHaveSlashes, $couldHaveSlashes);
		/*
		TODO: when to reinstate?
		if ($what == 'page') { if (cannot_access_page($file)) continue; }
		else { if (cannot_access($file, 'page')) continue; }
		*/

		$text = $namesOfFiles && isset($namesOfFiles[$file]) ? $namesOfFiles[$file] : humanize($file, $onlySlugForSectionMenu);

		//TODO: HIGH: LOOK FOR USAGE:

		if (isset($settings['innerHtml'])) {
			$innerHtml = $settings['innerHtml']($file, compact('extension', 'url', 'folder'));
		} else {
			if ($wrapInDivVO) $text = '<div>' . $text . '</div>';
			$innerHtml = getLink($text, $url, cssClass(array_merge($class_link)));
		}

		if ($blogHeading) $innerHtml = blog_heading($file, variable('node'));

		if ($noLinks) {
			$result .= $indent . '	<' . $itemTag . cssClass($class_li) . '>' . $innerHtml . '</' . $itemTag . '>' . NEWLINE;
		} else {
			if ($inHeader) {
				$result .= $indent . '<hr>' . variable('2nl') . '<h2 class="' . variable('toggle-list') . '">' . humanize($file) .'</h2>' . NEWLINE;
				$result .= menu($folderRelative . $file . '/', [
					'parent-slug' => variable('node') . '/',
					'menu-level' => $menuLevel + 1,
					'return' => true,
					'indent' => $indentGiven . '	',
				]) . variable('2nl');
			} else {
				$thisClass = array_merge($class_li);
				if ($file == variable('node') || in_array($file, variableOr('page_parameters', [])))
					$thisClass = array_merge($thisClass, $class_active);

				if ($indented) $thisClass[] = $indented;
				$result .= $indent . '	<' . $itemTag . cssClass($thisClass) . '>'
					. $innerHtml . '</' . $itemTag . '>' . NEWLINE;
			}
		}

		if (in_array($file, $breaks))
			$result .= $indent . '	<' . $itemTag . ' class="menu-break"><hr></' . $itemTag . '>' . NEWLINE;
	}

	if ($backToHome) {
		$thisClass = array_merge($class_li, ['back-to-home-link']);
		$thisAClass = array_merge($class_link);
		$result .= sprintf($indent . '<li%s><a href="%s"%s>%s</a>',
			cssClass($thisClass),
			pageUrl(),
			cssClass($thisAClass),	
			'** Back to ' . variable('abbr'));
	}

	if (!$noul) $result .= $indent . '</ul>' . NEWLINE;

	$return = isset($settings['return']) ? $settings['return'] : false;
	if ($return) return $result;
	echo $result;
}
