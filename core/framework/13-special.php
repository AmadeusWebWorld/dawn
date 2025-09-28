<?php
variables([
	'special-folder-extensions' => $sfe = [
		'articles' => 'md',
		'in-memoriam' => 'md',
		'blurbs' => 'txt',
		'code' => 'php',
		'decks' => 'md',
		'dossiers' => 'tsv',
		'rich-pages' => 'tsv',
		'tables' => 'md',
	],
	'exclude-folders' => ['home', 'assets', 'data', 'engage', 'home', 'images', 'thumbnails'],
]);

DEFINE('CONTENTFILES', 'php, md, tsv, txt, html');
DEFINE('CONTENTFILEEXTENSIONS', explode(', ', CONTENTFILES));
DEFINE('ENGAGEFILES', 'md, tsv');
DEFINE('FILESWITHMETA', 'md, tsv');

function isContentFile($fileOrRaw) {
	foreach (CONTENTFILEEXTENSIONS as $extn)
		if (endsWith($fileOrRaw, '.' . $extn)) return true;
	return false;
}

function autoRender($file, $type = false, $useHeading = true) {
	if (endsWith($file, '.php')) {
		renderAnyFile($file);
		pageMenu($file);
		return;
	}

	$raw = disk_file_exists($file) ? disk_file_get_contents($file) : '[RAW]';
	$embed = hasPageParameter('embed');
	$pageName = title(FORHEADING);

	//cannot use startsWith as edit in vs-code wouldnt work
	$detectedEngage = contains($raw, '|is-engage') || contains($raw, '<!--is-engage-->');
	if ($type != 'engage' && $detectedEngage) $type = 'engage';

	if ($type == 'engage') {
		$md = !endsWith($file, '.tsv');

		runFeature('engage');

		if ($detectedEngage)
			sectionId('special-form' . ($ix = variableOr('special-form', 1)), 'container');

		if ($md)
			renderEngage($pageName, $raw);
		else
			runEngageFromSheet(getPageName(), $file);

		if ($detectedEngage) {
			variableOr('special-form', ++$ix);
			section('end');
		}

		pageMenu($file);
		return;
	}

	if (endsWith($file, '.md')) {
		sectionId('special-md', 'container');
		if (startsWith($raw, '<!--is-blurbs-->')) {
			_renderedBlurbs($file);
		} else if (startsWith($raw, '<!--is-deck-->')) {
			_renderedDeck($file, $pageName);
		} else {
			$settings = ['use-content-box' => true];
			if ($useHeading) $settings['heading'] = $pageName;
			if (variable(FIRSTSECTIONONLY)) $settings[FIRSTSECTIONONLY] = true;
			renderAny($file, $settings);
		}

		section('end');
		pageMenu($file);
		return;
	}

	if (endsWith($file, '.tsv')) {
		runFeature('tables');

		$meta = getSheet($file, false);
		$istwt = contains($raw, '|is-table-with-template');
		if ($meta && isset($meta->values['use-template']))
			$meta->values = array_merge($meta->values, getSheet(getTableTemplate($meta), false)->values);

		$noCB = $istwt && $meta ? valueIfSet($meta->values, 'no-content-box') : false;
		if ($istwt) h2(title(FORHEADING) . currentLevel(), 'amadeus-icon');

		$isDeck = contains($raw, '|is-deck');
		$notRendering = !hasPageParameter('embed') && !hasPageParameter('expanded');

		if ($noCB) sectionId('special-table', 'container'); else
		if (!$embed) sectionId('special-table', _getCBClassIfWanted('container' . ($isDeck && !$notRendering ? ' deck deck-from-sheet' : '')));

		if ($isDeck)
			renderSheetAsDeck($file, variableOr('all_page_parameters', nodeValue()) . '/');
		else if (startsWith($raw, '|is-rich-page'))
			renderRichPage($file);
		else if (contains($raw, '|is-table'))
			add_table(pathinfo($file, PATHINFO_FILENAME), $file, valueIfSet($meta->values, 'head-columns', 'auto'), valueIfSet($meta->values, 'row-template', 'auto'), $meta->values);
		else if ($istwt)
			add_table(pathinfo($file, PATHINFO_FILENAME), $file, $meta->values['head-columns'], $meta->values['row-template'], $meta->values);
		else
			parameterError('unsupported tsv file - see line 1 for type definition', $file);

		if (!$embed) section('end');
		pageMenu($file);
		return;
	}

	$siteTheme = variable('site-has-theme') || variable('skip-container-for-this-page');
	if (!$siteTheme) sectionId('file', _getCBClassIfWanted('container'));
	renderAny($file);
	if (!$siteTheme) section('end');
	pageMenu($file);
}

function hasSpecial() {
	if (_isScaffold()) return true;
	if (_isLinks() || nodeIs('search')) return true;

	if (isset($_GET['share'])) return _setAndReturn(['sub-theme' => 'go']);

	return false;
}

function _setAndReturn($vars) {
	variables($vars);
	return true;
}

function renderedSpecial() {
	if (variable('site-lock')) { doSiteLock(); return true; }

	if (nodeIs('search')) { echo getSnippet('search', CORESNIPPET); return true; }
	//share done at top of entry's render()
	if (nodeIs('gallery')) { runFeature('gallery'); return true; }
	if (_renderedLink()) return true;
	if (_renderedScaffold()) return true;

	return false;

	$special = variable('special-folder');
	if (!$special) return false;

	$file = variable('file');

	if ($special == 'blurbs') {
		_renderedBlurbs($file);
	} else if ($special == 'code') {
		_renderedCode($file);
	} else if ($special == 'decks') {
		_renderedDeck($file);
	} else if ($special == 'dossiers') {
		_renderedDossiers($file);
	} else if ($special == 'rich-pages') {
		variable('home', getSheet($file));
		renderThemeFile('home');
	}

	return true;
}

// ************************************ Region: Private (Internal) Functions

function _setupBlurbs($fwe, $page) {
	$blurb = $fwe . '.txt';
	variable('blurb-file', $blurb);
	if (hasPageParameter('embed'))
		variable('embed', true);
}

function _renderedBlurbs($blurb, $name = false) {
	if (!$name) $name = variable('special-filename');

	if (hasPageParameter('embed')) {
		runFeature('blurbs');
		return;
	}

	$url = currentUrl();
	$embedUrl = $url . '?embed=1';
	echo '<section class="blurb-container" style="text-align: center;">BLURBS: '
		. makeLink($name, $embedUrl, false) . ' (opens in new tab)<hr>' . variable('nl');
	echo '<iframe style="height: 80vh; width: 100%; border-radius: 30px;" src="' . $embedUrl . '"></iframe>' . variable('nl');
	echo '</section>' . variable('2nl');
}

function _setupCode($fwe, $name) {
	variable('file', $fwe . '.php');
}

function _renderedCode($code) {
	disk_include_once($code);
}

function _setupDeck($fwe, $name) {
	if (hasPageParameter('expanded')) return false;

	$file = $fwe . '.md';

	variable('deck-name', $name);
	variable('file', $file);

	if (!hasPageParameter('embed')) {
		return false;
	}

	variable('no-permanent-link', true);
	variable('no-detail-link', true);
	variable('embed', true);
}

function renderInPageDeck($section, $node, $name) {
	$deck = concatSlugs([variable('path'), $section, $node . '.md']);
	$title = humanize($node) . ' &raquo; ' . $name;
	variable('embed', true);
	_renderedDeck($deck, $title, pageUrl($node . '/'));
}

function renderSheetAsDeck($deck, $link) {
	$title = title(FORHEADING);
	if (!hasPageParameter('embed') && !hasPageParameter('expanded')) {
		_renderedDeck($deck, $title);
		return;
	}

	$sheet = getSheet($deck, false);
	$op = [];
	foreach ($sheet->rows as $item) {
		$type = $item[$sheet->columns['type']];
		$text = $item[$sheet->columns['text']];
	
		if ($type == 'slide') {
			if (count($op)) { $op[] = ''; $op[] = '----'; $op[] = ''; }
			$op[] = '<input type="hidden" value="' . $text . '" />';
			$op[] = '';
		} else if ($type == 'heading') {
			$op[] = '## ' . $text;
			$op[] = '';
		} else if ($type == 'sub-heading') {
			$op[] = '### ' . $text;
			$op[] = '';
		} else if ($type == 'paragraph') {
			$op[] = $text;
			$op[] = '';
		} else if ($type == 'image') {
			$op[] = replaceHtml($text);
			$op[] = '';
		} else if ($type == 'style-file') {
			variable('style-file', replaceHtml($text));
		} else if ($type == 'print-config') {
			variable('print-config', $text);
		} else if ($type == 'item') {
			if (end($op) != '') $op[] = '';
			$op[] = '* ' . $text;
		}
	}

	variable('nodeLink', $link);
	$op = implode(variable('nl'), $op);
	_renderedDeck($op, $title);
}

function __parseDeck($deck) {
	$deck = renderMarkdown($deck, [ 'echo' => false ]);
	return $deck;
}

function _renderedDeck($deck, $title, $goesTo = false) {
	if (hasPageParameter('embed')) {
		$deck = __parseDeck($deck);
		variable('deck', $deck);
		runModule('revealjs');
		return true;
	}

	$expanded = hasPageParameter('expanded');
	$url = $goesTo ? $goesTo : currentUrl();

	$embedUrl = $url .'?embed=1';

	sectionId('deck-toolbar', 'text-center');
	h2($title . currentLevel(), 'amadeus-icon', true);
	contentBox('deck', 'toolbar');
	echo 'PRESENTATION: ' . variable('nl');
	$links = [];

	//TODO: UI FIX: if (!$expanded) $links[] = '<a class="toggle-deck-fullscreen" href="javascript: $(\'.deck-container\').show();"><span class="text">maximize</span> ' . getIconSpan('expand', 'normal') . '</a>';
	if ($expanded) $links[] = makeLink('open deck page', $url, false);
	$links[] = makeLink('open deck fully', $embedUrl, false);
	$links[] = makeLink('print', $embedUrl . '&print=1', false); //TODO: wip - make this on demand
	$links[] = $expanded ? 'expanded deck below' : makeLink('open deck expanded', $url . '?expanded=1', false);
	//TODO: get this working and support multi decks
	//$(this).closest(\'.deck-toolbar\').next(\'.deck-container\').toggle();
	if (!$expanded) $links[] = makeLink('toggle deck below', 'javascript: $(\'.deck-container\').toggle();', false);

	echo implode(' &nbsp;&nbsp;&mdash;&nbsp;&nbsp; ' . variable('nl'), $links);
	contentBox('end');
	section('end');

	if ($expanded) {
		$deck = __parseDeck($deck);
		$deck = cbWrapAndReplaceHr($deck, 'container'); //in revealjs we will use plain sections
		echo $deck;
	} else {
		echo sprintf('<section class="deck-container container">'
			. '<iframe src="%s&iframe=1"></iframe></section>', $embedUrl);
		addScript('presentation-toolbar', COREASSETS);
	}
}

function _setupDossiers($fwe, $name) {
	$data = $fwe . '.tsv';
	if (!disk_file_exists($data)) return false;

	$data = dirname($fwe) . '/' . $name . '.tsv';

	$folder = SITEPATH . '/data/dossier-templates/';
	$node = nodeValue();

	$templates = [
		'node-item' => $folder . $node . '-' . $name . '.html',
		'node' => $folder . $node . '.html',
		'default' => $folder . 'default.html',
	];

	foreach($templates as $type => $item) {
		if (disk_file_exists($item)) {
			variables([
				'file' => $data,
				'template' => $item,
				'template-type' => $type,
			]);
			return true;
		};
	}

	parameterError('Dossier Template Resolver', ['found-data' => $data, 'searched-templates' => $templates], false);
	die(); //this is before render and violating the contract with the isSpecial which calls it

	return false;
}

function _renderedDossiers($data) {
	$page = variable('special-filename-websafe');
	$html = variable('template');
	$type = variable('template-type');

	sectionId($page . '-intro', 'feature-table'); //NOTE: dbc heads up: section nesting will be a problem when using html in dossiers!
	h2('Dossiers or Records');

	//later this can be resolved from multiple filenames as needed
	echo replaceItems(getSnippet('dossier'), [
		'pageName' => humanize($page),
		'nodeName' => humanize(nodeValue()),
		'sectionName' => humanize(variable('section')),
		'siteName' => variable('name'),
	], '%');

	section('end');

	add_table($page, false, $data, 'auto', disk_file_get_contents($html));
}

function _isLinks() {
	if (nodeIs('go'))
		runFeature('links'); //will just do a redirect

	return nodeIs('links');
}

function _renderedLink() {
	if (nodeIsNot('links')) return false;

	runFeature('links'); //will list them
	return true;
}

function _isScaffold() {
	$scaffold = variableOr('scaffold', []);
	//NOTE: sitemap always needed
	$always = variable('local') && nodeIs('sitemap');
	if (!$always && !nodeIsOneOf($scaffold))
		return false;

	if (hasPageParameter('embed')) variable('embed', true);
	variable('scaffoldCode', nodeValue());
	return true;
}

function _renderedScaffold() {
	$code = variable('scaffoldCode');
	if (!$code) return false;

	runFeature($code);
	return true;
}

//scaffolded features
function do_updates() {
	if (!sheetExists('updates') || variable('no-updates')) return;

	runFeature('updates');
}
