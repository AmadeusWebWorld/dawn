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

function autoRender($file, $type = false) {
	if (endsWith($file, '.php')) {
		renderAnyFile($file);
		pageMenu($file);
		return;
	}

	$raw = disk_file_exists($file) ? disk_file_get_contents($file) : '[RAW]';
	$embed = hasPageParameter('embed');
	$pageName = title('params-only');

	//cannot use startsWith as edit in vs-code wouldnt work
	$detectedEngage = contains($raw, '|is-engage') || contains($raw, '<!--is-engage-->');
	if ($type != 'engage' && $detectedEngage) $type = 'engage';

	if ($type == 'engage') {
		$md = !endsWith($file, '.tsv');

		runFeature('engage');

		if ($detectedEngage)
			sectionId('special-form' . ($ix = variableOr('special-form', 1)), 'container');

		if ($md)
			_renderEngage($pageName, $raw, true);
		else
			_runEngageFromSheet(getPageName(), $file);

		if ($detectedEngage) {
			variableOr('special-form', ++$ix);
			section('end');
		}

		pageMenu($file);
		return;
	}

	if (endsWith($file, '.md')) {
		sectionId('special-md', 'container');
		if (startsWith($raw, '<!--is-blurbs-->'))
			_renderedBlurbs($file);
		else if (startsWith($raw, '<!--is-deck-->'))
			_renderedDeck($file, $pageName);
		else
			renderAny($file, ['use-content-box' => true, 'heading' => $pageName]);

		section('end');
		pageMenu($file);
		return;
	}

	if (endsWith($file, '.tsv')) {
		$istwt = contains($raw, '|is-table-with-template') && $meta = getSheet($file, false);
		if ($istwt) h2(title('params-only') . currentLevel(), 'amadeus-icon');

		$isDeck = contains($raw, '|is-deck');
		$notRendering = !hasPageParameter('embed') && !hasPageParameter('expanded');
		if (!$embed) sectionId('special-table', _getCBClassIfWanted('container' . ($isDeck && !$notRendering ? ' deck deck-from-sheet' : '')));
		runFeature('tables');

		if ($isDeck)
			renderSheetAsDeck($file, variableOr('all_page_parameters', variable('node')) . '/');
		else if (startsWith($raw, '|is-rich-page'))
			renderRichPage($file);
		else if (startsWith($raw, '|is-table'))
			add_table(pathinfo($file, PATHINFO_FILENAME), $file, 'auto', disk_file_get_contents(dirname($file) . '/.template.html'));
		else if ($istwt)
			add_table(pathinfo($file, PATHINFO_FILENAME), $file, $meta->values['head-columns'], $meta->values['row-template'], $meta->values);
		else
			parameterError('unsupported tsv file - see line 1 for type definition', $file);

		if (!$embed) section('end');
		pageMenu($file);
		return;
	}

	$siteTheme = variable('site-has-theme');
	if (!$siteTheme) sectionId('file', _getCBClassIfWanted('container'));
	renderAny($file);
	if (!$siteTheme) section('end');
	pageMenu($file);
}

function hasSpecial() {
	if (_isScaffold()) return true;
	$node = variable('node');
	if (_isLinks($node) || $node == 'search') return true;

	if (isset($_GET['share'])) return _setAndReturn(['sub-theme' => 'go']);

	return false;
}

function _setAndReturn($vars) {
	variables($vars);
	return true;
}

function renderedSpecial() {
	if (variable('site-lock')) { doSiteLock(); return true; }
	$node = variable('node');
	if ($node == 'search') { echo getSnippet('search', CORESNIPPET); return true; }
	//share done at top of entry's render()
	if ($node == 'gallery') { runFeature('gallery'); return true; }
	if (_renderedLink($node)) return true;
	if (_renderedScaffold($node)) return true;

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
	$title = title('params-only');
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
	$node = variable('node');

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
		'nodeName' => humanize(variable('node')),
		'sectionName' => humanize(variable('section')),
		'siteName' => variable('name'),
	], '%');

	section('end');

	add_table($page, false, $data, 'auto', disk_file_get_contents($html));
}

function _isLinks($node) {
	if ($node == 'go')
		runFeature('links'); //will just do a redirect

	return $node == 'links';
}

function _renderedLink($node) {
	if ($node != 'links') return false;

	runFeature('links'); //will list them
	return true;
}

function before_section_or_file($section) {
	$node = variable('node');

	if ($node == $section) {
		variable('section', $section);
		return true;
	}

	$fol = SITEPATH . '/' . $section . '/';
	$files = disk_scandir($fol);

	foreach ($files as $fil) {
		if ($fil[0] == '.') continue;
		if ($node == $fil) {
			variable('fwk-section', $section);
			return true;
		} else if (disk_is_dir($fol . $node . '/')) {
			variable('fwk-section', $section);
			variable('fwk-folder', $section . '/' . $node . '/');
			return true;
		} else if ($ext = disk_one_of_files_exist($fwe = $fol . $fil . '.','txt, md')) {
			variable('fwk-section', $section);
			variable('fwk-file', $fwe . $ext);
			return true;
		}
	}

	return false;
}

function did_render_section_or_file() {
	$section = variable('fwk-section');
	$dir = variable('fwk-folder');
	$file = variable('fwk-file');

	if ($file) {
		renderAny($file);
		return true;
	} else if ($section || $dir) {
		runFeature('blog'); //TODO: merge this with directory and use section type if not blog/wiki/sitemap
		return true;
	}

	return false;
}

function _isScaffold() {
	$node = variable('node');
	$scaffold = variableOr('scaffold', []);
	//NOTE: sitemap always needed
	$always = variable('local') && $node == 'sitemap';
	if (!$always && !in_array($node, $scaffold))
		return false;

	if (hasPageParameter('embed')) variable('embed', true);
	variable('scaffoldCode', $node);
	return true;
}

function _renderedScaffold() {
	$code = variable('scaffoldCode');
	if (!$code) return false;

	runFeature($code, false);
	return true;
}

//scaffolded features
function do_updates() {
	if (!sheetExists('updates') || variable('no-updates')) return;

	runFeature('updates');
}
