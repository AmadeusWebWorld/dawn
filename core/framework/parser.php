<?php
function getWorkSettings($file) {
	$config = dirname($file) . '/_work-settings.txt';
	return disk_file_exists($config) ? disk_file_get_contents($config) : '';
}

function parseCompositeWork($raw, $param1IsPage) {
	//$called = variableOr('pcw-called', 0) + 1; variable('pcw-called', $called); parameterError('parseCompositeWork - call:', $called, false); if ($called > 3) return 'USELESS IMRAN!';

	$pieces = explode('## ', $raw);
	$noBeginning = contains($raw, '<!--no-beginning-->');
	$noCategory = contains($raw, '<!--no-category-->');
	$noAllPieces = contains($raw, '<!--no-all-pieces-->');
	$withMore = contains($raw, '<!--pieces-with-more-->');

	$start = $noBeginning ? false : explode(variable('safeNL'), $pieces[0], 2)[1]; //cannot start with a level 2 heading, expects all variables on the first line

	$assetsUrl = variableOr('assetsUrl', '');
	$assetsFol = variableOr('assetsFol', '');
	$wantsMD = do_md_in_parser($raw);
	$wantsItemMD = contains($raw, '<!--markdown-in-item-when-processing-->');
	$autop = contains($raw, '<!--autop-->');
	$separators = $wantsMD ? [variable('safeNL')] : [];
	$separators = array_merge($separators, $autop ? ['</p>', '|'] : ['|']);
	$doSkip = //true || 
		!variable('local') && !isset($_GET['dont-skip']);

	$sorted = [];
	$printSortKeys = isset($_GET['sort-keys']);
	$sortBy = contains($raw, '<!--sort-by-name-->') ? 'name' :
		(contains($raw, '<!--sort-by-category-->') ? 'category' :
			(contains($raw, '<!--sort-by-number-->') ? 'number' : false));

	$result = false;
	$noCount = contains($raw, '<!--no-count-->');
	$noHeader = variable('no-print-header') || contains($raw, '<!--no-print-header-->');
	$noFooter = variable('no-print-footer') || contains($raw, '<!--no-print-footer-->');
	$printOnly = hasPageParameter('print');
	$noLinking = $printOnly && variable('no-linking-in-print') || contains($raw, '<!--no-linking-in-print-->');
	$paramToUse = contains($raw, '<!--node-item-is-param2-->') ? '2' : '1';
	$index = false;

	$pieceOnly = $param1IsPage ? (isset($_GET['item']) ? $_GET['item'] : false) : 
	(variable('page_parameter' . $paramToUse) && variable('page_parameter' . $paramToUse) != 'print' ? variable('page_parameter' . $paramToUse) : false);

	$base = pageUrl(variable('node')) . ($paramToUse == 2 ? variable('page_parameter1') . '/' : '') . ($param1IsPage ? variable('page_parameter' . $paramToUse) . '/' : '');

	if (!$noCategory) {
		$categoryStart = 'Category: ';
		$categories = [];
		$categoryOnly = false;

		$wantsSingleCategory = $param1IsPage ? isset($_GET['category']) : $pieceOnly == 'category';
		if ($wantsSingleCategory) {
			$categoryOnly = $param1IsPage ? $_GET['category'] : variable('page_parameter' . ($paramToUse == '2' ? '3' : '2'));
			$pieceOnly = false;
		}
		$categoryBase = $base . ($param1IsPage ? '?category=' : 'category/');
	} else {
		$linksToPieces = [];
	}

	$pieceBase = $base . ($param1IsPage ? '?item=' : '');
	$urlSuffix = $param1IsPage ? '' : '/';
	$contentIndex = $noCategory ? 2 : 3;

	foreach ($pieces as $piece) {
		if ($index === false) {
			$index = 0;
			continue;
		}

		$bits = explodeByArray($separators, $piece, 4);
		//parameterError('BITS', $bits, false);
		if ($doSkip && $bits[1] == 'SKIP') continue;

		$title = trim($bits[0]);

		if (!$noCategory) {
			$category = trim(str_replace($categoryStart, '', $bits[2]));
			if (!$category) $category = 'Uncategorized';

			$count = isset($categories[$category]) ? $categories[$category] + 1 : 1;
			$categories[$category] = $count;
			if ($categoryOnly && urlize($category) != $categoryOnly) continue;
		}

		$index += 1;
		//if ($index <= 170) continue; if ($index == 181) break;

		$slug = urlize($title);
		$isThisTheCurrentPiece = urlize($title) == $pieceOnly;
		$titleLink = $isThisTheCurrentPiece ? $title : (makeLink($title, $pieceBase . $slug . $urlSuffix, false, $noLinking));

		if ($noCategory) $linksToPieces[] = $titleLink;
		if ($noAllPieces && !$pieceOnly) continue;
		if ($pieceOnly && !$isThisTheCurrentPiece) continue;

		$thisResult = '<section>' . variable('nl') . '	<h2 class="piece">' . $titleLink . ' <span class="number">#' .
			$bits[1] . ($noCount ? '' : ' &mdash; (' . $index . ' / TOTAL)') . '</span></h2>' . variable('nl');
		
		if (disk_file_exists($assetsFol . $slug . '.jpg'))
			$thisResult .= '<img src="' . $assetsUrl . $slug . '.jpg' . '" class="img-piece img-max-200">' . variable('nl');

		$thisResult .= renderOnlyMarkdownOrRaw($bits[$contentIndex], $wantsItemMD, ['echo' => false, 'strip-paragraph-tag' => true]) . variable('nl') . '</section>' . variable('2nl');

		if ($sortBy) {
			$key = $sortBy == 'number' ? $bits[1] : ($sortBy == 'category' ? $category . '--' . $slug : $slug); //defaults to title ($slug)
			if ($printSortKeys) $thisResult = $key . $thisResult;
			$sorted[$key] = $thisResult;
		} else {
			$result .= $thisResult;
		}
	}

	if ($sortBy) {
		ksort($sorted, SORT_NATURAL);
		$result .= implode(variable('2nl'), $sorted);
	}

	$result = str_replace('TOTAL', $index, $result);
	$result = str_replace('<p>- - -</p>', '<div class="break-page' .
		($printOnly ? ' printing' : '') . '"><i>Continued&hellip;</i><hr></div>'
		. ($printOnly ? '</section><section>&hellip;Continuing<hr>' : ''), $result);

	if ($withMore) $result = str_replace(
		'<!--more--><br>', '<a href="javascript: void(0);" class="read-piece">Read More</a>' . variable('nl') .
		'<div class="piece-content">', $result) . '</div>';

	if ($param1IsPage) echo '<h2>' . humanize(variable('node')) . '</h2>' . variable('2nl');
	$page = $param1IsPage || $paramToUse == '2' ? variable('page_parameter' . ($paramToUse == '2' ? '1' : '2')) : variable('node');
	$home = '<h3 class="home"><u>' . makeLink(humanize($page), $base, false, $noLinking) . '</u></h3>' . variable('2nl');
	$links = '';

	if (!$noCategory) {
		$links = [];
		foreach ($categories as $item => $count) {
			$slug = urlize($item);
			$img = !disk_file_exists($assetsFol . '/categories/' . $slug . '.jpg') ? '' :
				variable('nl') . '<br><img src="' . $assetsUrl . 'categories/' . $slug . '.jpg' . '" class="img-category img-max-200" alt="' . $item . '">' . variable('nl');
			$links[$slug] = makeLink(humanize($item) . ' (' . $count . ')' . $img, $categoryBase . $slug . $urlSuffix, false, $noLinking);
		}

		ksort($links, SORT_NATURAL);
		$div = '<div class="col-lg-3 col-md-4 col-sm-12">';
		$links = '<section class="navigation">' . $home . '<h3>Categories</h3><div class="row">' . variable('nl') . $div
			. implode('</div>' . variable('nl') . $div, $links) . '</div>' . variable('nl') . '</div></section>' . variable('2nl');
	} else {
		$links = '<section class="navigation">' . $home . '<h3>Pieces</h3>' . variable('nl') . '<ol><li>' . variable('nl')
			. implode('</li><li>' . variable('nl'), $linksToPieces) . '</li></ol>' . variable('nl') . '</section>' . variable('2nl');
	}

	if (variable('flavour') == 'yieldmore')
		$result .= '<style>#file { background-color: var(--amw-site-background, #A8D4FF)!important; }</style>';

	$start = $start ? renderOnlyMarkdownOrRaw($start, $wantsMD, ['wrap-in-section' => true, 'echo' => false]) : '';
	if (contains($raw, '<!--no-print-->'))
		return $start . variable('2nl') . $links . $result;

	//NOTE: Print "inner" pdf (w/o covers) combine with: https://pdfjoiner.com/ (no margins for images)
	$front = getSnippet(variable('node') . '-front');
	$front = $front ? '<section class="first-page">' . $front . '</section>' : '';

	$printBtn = !$printOnly ? '' : '<div class="print-button" style="text-align: center"><a class="btn btn-primary" '
		. 'href="javascript: print();">PRINT THIS WORK</a></div>';

	$back = getSnippet(variable('node') . '-back');
	if ($back) $back = '<section class="last-page">' . $back . '</section>';

	$printHeader = $noHeader ? '' : replaceItems('<div id="print-header"><h3>"%node%" by <i>%name%</i></h3></div>',
		['node' => humanize(variable('node')), 'name' => variable('name')], '%') . variable('2nl');

	$printFooter = $noFooter ? '' : replaceItems('<div id="print-footer">' . variable('nl')
		.'<img src="%imgUrl%" style="height: 40px;" alt="footer logo"> <span>&copy; %year% &mdash; by <u>%name%</u></span></div>',
		['year' => date('Y'), 'node' => humanize(variable('node')), 'name' => variable('name'),
			'imgUrl' => fileUrl() . variable('safeName') . '-logo.png'], '%') . variable('2nl');

	$result = $printBtn . $start . $front . variable('2nl') . $links . $result . $back . $printHeader . $printFooter;

	return $result;
}

