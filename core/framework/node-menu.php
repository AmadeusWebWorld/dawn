<?php
//D:\AmadeusWeb\html\Canvas 7 Files\js\modules\menus.js
//D:\AmadeusWeb\html\Canvas 7 Files\page-submenu.html
renderNodeMenu();

function renderNodeMenu() {
	renderBreadcrumbsMenu();
	if (variable('skip-node-folders')) return;

	extract(variable('menu-settings'));

	$hasFiles = variable('nodes-have-files'); //yay now we support both sections with files & folders in same site
	if (($order = NODEPATH . '/_menu-items.txt') && disk_file_exists($order))
		$files = textToList(disk_file_get_contents($order)); else
	$files = _skipNodeFiles(disk_scandir(NODEPATH));

	_menuULStart(NOPAGESTART);

	if (variable('link-to-node-home'))
		echo '<li class="' . $itemClass . '"><a href="' . pageUrl(variable(SAFENODEVAR)) . '" class="' . $anchorClass . '">' . getHtmlVariable('nodeName') . '</a>';

	foreach ($files as $page) {
		//if (cannot_access($slug)) continue;
		$page_r = humanize($page);
		$page_r = $wrapTextInADiv ? '<div>' . $page_r . '</div>' : $page_r;

		$files = []; $tiss = false;
		$standalones = variableOr('standalone-pages', []);
		if (in_array($page, $standalones)) {
			variable('page_parameter1_safe', $page);
			$tiss = true;
			$menuFile = concatSlugs([variable('path'), variable('section'), variable(SAFENODEVAR), $page, 'menu.php']);
			$files = disk_include($menuFile, ['callingFrom' => 'header-page-menu', 'limit' => 5]);
			if ($tsmn = variable(getSectionKey($page, MENUNAME)))
				$page_r = $tsmn;
		}

		if (disk_is_dir(NODEPATH . '/' . $page)) {
			echo '<li class="' . $itemClass . '"><a class="' . $anchorClass . '">' . $page_r . '</a>';
			menu('/' . variable('section') . '/' . variable(SAFENODEVAR) . '/' . $page . '/', [
				'link-to-home' => variable('link-to-sub-node-home'),
				'files' => $files, 'this-is-standalone-section' => $tiss,
				'ul-class' => $ulClass,
				'li-class' => $itemClass,
				'a-class' => $anchorClass,
				'parent-slug' => $tiss ? '' : variable(SAFENODEVAR) . '/' . $page . '/',
			]);
		} else if ($hasFiles) {
			echo '<li class="' . $itemClass . '"><a href="' . pageUrl(variable(SAFENODEVAR) . '/' . $page) . '" class="' . $anchorClass . '">' . $page_r . '</a>';
		}
		echo '</li>' . NEWLINES2;
	}

	if ($social = variable('node-social')) {
		//echo '<li class="' . $itemClass . ' ms-sm-3">social: </li>';
		foreach ($social as $item) {
			extract(specialLinkVars($item));

			echo '<li class="d-inline-block my-2"><a target="_blank" href="' . $url . '" class="mt-2 text-white">'
				. '	<i class="social-icon si-mini text-light rounded-circle ' . $class . '"></i> <span class="d-sm-none btn-light">' . $text . '</span></a></li>';
		}
	}

	_menuULStart('page');
}

function renderBreadcrumbsMenu() {
	$items = _getBreadcrumbs();
	if (count($items) == 0) return;

	extract(variable('menu-settings'));
	_menuULStart(NOPAGESTART);

	$section = variable('section');

	$ix = 0;
	foreach ($items as $relativeFol => $nodeSlug) {
		$menuName = '<abbr title="level ' . ++$ix . '">' . $ix . '</abbr> ' . humanize($nodeSlug);
		if ($wrapTextInADiv) $menuName = '<div>' . $menuName . $topLevelAngle . '</div>';

		//echo NEWLINE . '<ul class="' . $ulClass . '">';

		echo MENUPADLEFT . '		  <li class="' . $itemClass . '"><a class="' . $anchorClass . ' breadcrumb-item">' . $menuName . '</a>';

		menu('/' . $section . '/' . $relativeFol, [
			'ul-class' => $ulClass . (false ? ' of-node node-' . $nodeSlug : ''),
			'li-class' => $itemClass,
			'a-class' => $anchorClass,
			'link-to-home' => true,
			'parent-slug-for-home-link' => $relativeFol,
			'parent-slug' => $relativeFol,
			'indent' => '			',
		]);

		echo MENUPADLEFT . '		  </li>' . NEWLINES2;
	}

	_menuULStart('breadcrumbs');
}

function _getBreadcrumbs() {
	//TODO: if (cannot_access(variable('section'))) return;

	$breadcrumbs = variable('breadcrumbs');
	if (empty($breadcrumbs)) return [];

	$result = [];
	$node = nodeValue();

	$base = $node . '/';

	foreach ($breadcrumbs as $item) {
		$base .= $item . '/';
		$result[$base] = $item;
	}

	return $result;
}
