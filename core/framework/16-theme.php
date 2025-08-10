<?php
function getThemeBaseUrl() {
	$themeName = variable('theme');
	$themeUrl = variable('app-themes') . "$themeName/assets/";
	variable('themeUrl', $themeUrl);
	return $themeUrl;
}

function getThemeFile($file, $folder = false) {
	$themeName = variable('theme');
	return concatSlugs([($folder ? $folder : AMADEUSTHEMESFOLDER) . $themeName, $file]);
}

function setSubTheme($name) {
	variable('sub-theme', $name);
}

function renderThemeFile($file, $themeName = false) {
	if (variable('site-has-theme')) {
		disk_include_once(SITEPATH . '/theme/' . $file . '.php');
		return;
	}

	if (!$themeName) $themeName = variable('theme');
	$variables = [
		/*
		'theme' => variable('app') . "themes/$themeName/",
		'themeFol' => $themeFol = concatSlugs([AMADEUSTHEMESFOLDER, $themeName, '']),
		*/
	];

	$themeFol = concatSlugs([AMADEUSTHEMESFOLDER, $themeName, '']);
	disk_include_once($themeFol . $file . '.php', $variables);
}

function getThemeTemplate($end = '-rich-page.php') {
	return getThemeFile(variable('sub-theme') . $end);
}

function getTemplateFrom($file) {
	$file = getThemeFile($file . '.html');
	$bits = explode('##content##', disk_file_get_contents($file));
	return ['header' => $bits[0], 'footer' => $bits[1]];
}

function getThemeBlock($name, $location = false) {
	$file = getThemeFile('blocks/' . $name . '.html', $location);
	$bits = explode('<!--part-separator-->', disk_file_get_contents($file));
	return ['start' => $bits[0], 'item' => $bits[1], 'end' => $bits[2]];
}

function getThemeSection($name, $section, $location = false) {
	$file = getThemeFile('rich-pages/' . $name . '/' . $section . '.html', $location);
	return disk_file_get_contents($file);
}

function getThemeSnippet($name, $location = false) {
	$file = getThemeFile('snippets/' . $name . '.html', $location);
	$html = renderAny($file, ['echo' => false, 'strip-paragraph-tag' => true]);
	$vars = [
		'##theme##' => getThemeBaseUrl(),
		'<br />' => '',
	];
	return NEWLINES2 . replaceItems($html, $vars) . NEWLINE;
}

function runThemePart($what) {

	if (!($content = variable('theme-template'))) {
		$file = getThemeFile(variable('sub-theme') . '.html');
		$bits = explode('##content##', disk_file_get_contents($file));
		$content = ['header' => $bits[0], 'footer' => $bits[1]];
		$content['footer-widgets'] = disk_file_get_contents(getThemeFile('footer/' . variableOr('footer-variation', 'default') . '.html'));
		variable('theme-template', $content);
	}

	$vars = [
		'theme' => getThemeBaseUrl(), //TODO: /version can be maintained on the individual file?
		'optional-page-menu' => '',
		'optional-slider' => '', //this could be a page title too
		'optional-right-button' => '',
		'header-align' => '', //an addon class needed if video page title has an image and wants content on right
		'search-url' => searchUrl(contains(variable('node'), 'search')),
		'app-static' => assetMeta(COREASSETS)['location'],
	];

	$siteIcon = getLogoOrIcon('icon', 'site');
	$nodeIcon = getLogoOrIcon('icon', 'node');

	if ($what == 'header') {

		$icon = '<link rel="icon" href="' . $nodeIcon . '" sizes="192x192">';

		$vars['head-includes'] = '<title>' . title(true) . '</title>' . NEWLINE . '	' . $icon . NEWLINE . main::runAndReturn();
		$vars['seo'] = seo_tags(true);
		$vars['body-classes'] = body_classes(true);

		//TODO: icon link to node home, should have 2nd menu & back to home
		$baseUrl = hasVariable('nodeSafeName') ? pageUrl(variable('node')) : pageUrl();
		$logo2x = getLogoOrIcon('logo', 'node');
		$vars['logo'] = concatSlugs(['<a href="', $baseUrl . variableOr('nodeChildSlug', ''), '">' . NEWLINE
			. '								<img src="', $logo2x, '" class="img-fluid img-max-',
			variableOr('footer-logo-max-width', '500'), '" alt="', variableOr('nodeSiteName', variable('name')), '">' . NEWLINE
			. '							</a><br>'], '');

		$vars['optional-page-menu'] = _page_menu($siteIcon, $nodeIcon);

		$header = _substituteThemeVars($content, 'header', $vars);

		$bits = explode('##menu##', $header);

		echo _renderRaw($bits[0]);
		if (isset($bits[1])) {
			setMenuSettings();
			runFrameworkFile('header-menu');
			echo _renderRaw($bits[1]);
		}
		setMenuSettings(true);
	} else if ($what == 'footer') {
		if (!variable('footer-widgets-in-enrich')) {
			$logo2x = getLogoOrIcon('logo', 'site');
			$logo = NEWLINE . '			' . concatSlugs(['<a href="', pageUrl(), '">' . NEWLINE .
				'				<img src="', $logo2x, '" style="border-radius: 20px;" class="img-fluid img-logo" alt="', variable('name'), '">' . NEWLINE . '			</a><br>'], '');

			$message = !variable('footer-message') ? '' : (NEWLINE . '			<span class="btn btn-secondary mb-2">' . returnLine(variable('footer-message')) . '</span>' . NEWLINE);

			$contact = getSnippet('contact');
			if (!$contact) $contact = getSnippet('contact', CORESNIPPET);

			$nodeName = hasVariable('nodeSiteName') ? NEWLINE . '				<span class="btn btn-light" style="letter-spacing: 2px;">&#10148; ' . variable('nodeSiteName') . '</span>' . NEWLINE : '';

			$fwVars = [
				'footer-logo' => $logo . NEWLINE . '			<div class="text-center">'
					. NEWLINE . '			<h4 class="mt-sm-4 mb-0">' . variable('name') . '</h4>' . $nodeName .'</div>',
				'site-widgets' => siteWidgets(),
				'footer-message' => '<p class="text-align-center p-3 pt-4">' . $message . '</p>',
				'footer-contact' => $contact,
				'copyright' => _copyright(true),
				'credits' => _credits('', true),
			];

			$vars['footer-widgets'] = _substituteThemeVars($content, 'footer-widgets', $fwVars);
		}

		$footer = _substituteThemeVars($content, 'footer', $vars);

		$atBody = !contains($footer, '##footer-includes##');
		$bits = explode($atBody ? '</body>' : '##footer-includes##', $footer);

		if ($after = variable('after-wrapper')) {
			if (!contains($bits[0], $sep = '<!-- #wrapper end -->'))
				peDie('expected template to have a wrapper close comment!', $after);

			$wabbits = explode($sep, $bits[0]);
			echo _renderRaw($wabbits[0]);
			$tpl = getTemplateFrom($after['template']);
			echo _renderRaw($tpl['header']);
			autoRender($after['file']);
			echo _renderRaw($tpl['footer']);
			echo $sep . _renderRaw($wabbits[1]);
		} else {
			echo _renderRaw($bits[0]);
		}

		print_stats(); //returns if not needed
		if (function_exists('before_footer_assets')) before_footer_assets();
		styles_and_scripts();
		if (function_exists('after_footer_assets')) after_footer_assets();

		if ($atBody) echo '</body>';
		echo _renderRaw($bits[1]);
	}
}

function site_and_node_icons($siteIcon = null, $nodeIcon = null, $nodeSuffix = '') {
	if (!$siteIcon) $siteIcon = getLogoOrIcon('icon', 'site');
	if (!$nodeIcon) $nodeIcon = getLogoOrIcon('icon', 'node' . $nodeSuffix);

	return '<a href="' . pageUrl() . '">' . NEWLINE . '		<img height="40" src="' . $siteIcon . '" /></a>&nbsp;&nbsp;&nbsp;' . NEWLINE
	. '<a href="' . pageUrl(variable('node')) . '">' . NEWLINE . '		<img height="40" src="' . $nodeIcon . '" /></a>&nbsp;&nbsp;&nbsp;' . NEWLINE;
}

function _page_menu($siteIcon, $nodeIcon) {
	if (!variable('submenu-at-node')) return '<!--no-page-menu-->';

	$menuFile = getThemeFile('snippets/page-menu.html');
	$menuContent = disk_file_get_contents($menuFile);

	$menuVars = [
		'menu-title' => NEWLINE . site_and_node_icons($siteIcon, $nodeIcon)
			 . variable('nodeSiteName') . NEWLINE,
	];
	$menuContent = replaceItems($menuContent, $menuVars, '##');

	$menuBits = explode('##page-menu##', $menuContent);

	doToBuffering(1);

	echo _renderRaw($menuBits[0]);
	setMenuSettings('page-menu');
	runFrameworkFile('node-menu');
	echo _renderRaw($menuBits[1]);

	$result = doToBuffering(2);
	doToBuffering(3);
	return $result;
}

function _substituteThemeVars($content, $what, $vars) {
	if (function_exists('enrichThemeVars'))
		$vars = enrichThemeVars($vars, $what);

	if ($what == 'header') {
		if ($vars['optional-slider'] == '')
			$vars['body-classes'] = $vars['body-classes'] . ' no-slider';
	}
	return replaceItems($content[$what], $vars, '##');
}

function _renderRaw($html) {
	return renderAny($html, ['raw' => true, 'echo' => false]);
}

function setMenuSettings($after = false) {
	if ($after === true) {
		variable('menu-settings', false);
		return;
	}

	$pm = $after == 'page-menu';
	$prefix = $pm ? 'page-' : '';
	//same as non-profit header
	variable('menu-settings', [
		'isPageMenu' => $pm,
		'noOuterUl' => false,
		'groupOuterUlClass' => $prefix . 'menu-container',
		'outerUlClass' => 'menu-container',
		'ulClass' => $pm ? 'page-menu-sub-menu' : 'sub-menu-container',
		'itemClass' => $prefix . 'menu-item',
		'subMenuClass' => $pm ? 'page-menu-sub-menu' : 'sub-menu',
		'itemActiveClass' => 'current',
		'anchorClass' => $pm ? '' : 'menu-link',
		'wrapTextInADiv' => true,
		'topLevelAngle' => $pm ? '<i class="sub-menu-indicator fa-solid fa-caret-down"></i>' : '<i class="icon-angle-down"></i>',
	]);
}

function siteWidgets() {
	//Do Better - if (variable('node-alias')) return '';

	$colsInUse = 0;

	$showSections = variable('link-to-section-home') && !variable('no-sections-in-footer');
	if ($showSections) $showSections = count($sections = variableOr('sections', []));
	if ($showSections) $colsInUse += 1;

	$showNetwork = !variable('no-network-in-footer') && !variable('not-a-network');
	if ($showNetwork) $showNetwork = count($sites = variableOr('networkItems', []));
	if ($showNetwork) $colsInUse += 1;

	$showSocial = !variable('no-social-in-footer');
	if ($showSocial) $showSocial = count($social = variableOr('social', main::defaultSocial()));
	if ($showSocial) $colsInUse += 1;

	if ($colsInUse == 0) return '';

	//adjust
	$grid = [1 => 12, 2 => 6, 3 => 4];
	$colspan = $grid[$colsInUse];

	$start = sprintf('<div id="footer-[WHAT]" class="col-md-%s mt-sm-2 pt-xs-3"><hr class="d-sm-none">', $colspan) . NEWLINE;

	//TODO: Showcase + Misc
	$op = [];

	if ($showSections) {
		$op[] = str_replace('[WHAT]', 'sections', $start);
		$op[] = '<h4>Sections</h4>';
		foreach ($sections as $slug)
			$op[] = makeRelativeLink(humanize($slug), $slug) . BRNL;
		$op[] = '</div>'; $op[] = '';
	}

	if ($showNetwork) {
		$op[] = str_replace('[WHAT]', 'network', $start);
		$op[] = '<h4>' . makeLink('Our Network', subVariableOr('networkHome', 'url', '#todo/') . 'our-network/') . '</h4>';
		foreach ($sites as $site)
			$op[] =  $site['icon-link'] . BRNL;
		$op[] = '</div>'; $op[] = '';
	}

	if ($showSocial) {
		$op[] = str_replace('[WHAT]', 'social', $start);
		$op[] = '<h4>Social</h4>';
		appendSocial($social, $op);
		$op[] = '</div>'; $op[] = '';
	}

	return implode(variable('nl'), $op);
}

function appendSocial($social, &$op) {
	if (empty($social)) return;
	foreach($social as $item) {
		$op[] = '<a target="_blank" href="' . $item['url'] . '" class="social-link">';
		$op[] = '	<i class="social-icon text-light si-mini rounded-circle ' . (contains($item['type'], ' ')
			? $item['type'] : 'fa-brands fa-'. $item['type'] . ' bg-' . $item['type']) . '"></i> ' . $item['name'] . '</a><hr style="visibility: hidden; margin: 0;" />';
		$op[] = '';
	}
}

function getBreadcrumbs($items) {
	$op = [];
	foreach ($items as $slug => $text)
		$op[] = '<li class="breadcrumb-item">' . getLink($text, replaceHtml($slug)) . '</li>';
	return implode(NEWLINE . '			', $op);
}
