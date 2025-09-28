<?php
//removed renderAnyFile, _renderSingleFile in 8.5, extensions like jpg & pdf can be done in before/after file

function renderExcerpt($file, $link, $prefix = '', $echo = true) {
	$prefix = $prefix ? renderMarkdown($prefix) : '';

	$meta = read_seo($file);
	if ($meta AND isset($meta['excerpt'])) $raw = $meta['excerpt']; else
	$raw = renderAny($file, ['excerpt' => true, 'echo' => false, 'markdown' => endsWith($file, '.md')]);

	if ($meta && isset($meta['meta']['Date'])) $raw .= BRTAG . '<i>on ' . $meta['meta']['Date'] . '</i>';

	$result = $prefix . _excludeFromGoogleSearch($raw)
		. BRTAG . '<a class="read-more" href="' . $link . '">Read More&hellip;</a>';

	if (!$echo) return $result;
	echo $result;
}

DEFINE('GOOGLEOFF', '<!--googleoff: all-->');
DEFINE('GOOGLEON', '<!--googleon: all-->');

function _excludeFromGoogleSearch($raw) {
	return GOOGLEOFF
		. NEWLINE . $raw
		. NEWLINE . GOOGLEON
		. NEWLINES2;
}

function renderOnlyMarkdownOrRaw($raw, $wantsMD, $settings = []) {
	return $wantsMD ? renderSingleLineMarkdown($raw, $settings) : $raw; //so we can use inline in code
}

function renderMarkdown($raw, $settings = []) {
	$settings['markdown'] = true;
	return _renderImplementation($raw, $settings);
}

function returnLines($raw) {
	return renderMarkdown($raw, ['echo' => false]);
}

function returnLinesNoParas($raw) {
	return renderSingleLineMarkdown($raw, ['echo' => false, 'strip-paragraph-tag' => true]);
}

function returnLine($raw) {
	return renderSingleLineMarkdown($raw, ['echo' => false]);
}

function renderSingleLineMarkdown($raw, $settings = []) {
	return renderMarkdown($raw, array_merge($settings, ['strip-paragraph-tag' => true]));
}

function renderAny($file, $settings = []) {
	if (endsWith($file, '.php'))
		return disk_include_once($file);
	else
		return _renderImplementation($file, $settings);
}

DEFINE('FIRSTSECTIONONLY', 'FirstSectionOnly');
DEFINE('FULLACCESSNOTICE', 'FullAccessNotice');

//_ denotees its not to be called from outside - see flavours above + remove deprecated
function _renderImplementation($fileOrRaw, $settings) {
	if (endsWith($fileOrRaw, 'family-tree.md')) {
		runFeature('family-tree');
		renderFamilyTree($fileOrRaw); //only echoes for now
		return;
	}

	$endsWithMd = false;
	$raw = $fileOrRaw; $fileName = '[RAW]';
	$treatAsMarkdown = valueIfSet($settings, 'markdown');

	if ($wasFile = isContentFile($fileOrRaw)) {
		$fileName = $fileOrRaw;
		$endsWithMd = endsWith($fileOrRaw, '.md');
		$raw = disk_file_get_contents($fileOrRaw);
	}
	if (valueIfSet($settings, FIRSTSECTIONONLY)) {
		$raw = explode('---', $raw, 2)[0];
		if ($fan = variable(FULLACCESSNOTICE))
			$raw .= '---' . NEWLINE . $fan;
	}

	$echo = valueIfSet($settings, 'echo', true);
	$excerpt = valueIfSet($settings, 'excerpt', false);
	$no_processing = valueIfSet($settings, 'raw', false) || contains($raw, '<!--no-processing-->') || do_md_in_parser($raw);
	if (contains($raw, '<!--no-p-tags-->')) $settings['strip-paragraph-tag'] = true;

	if ($excerpt) $raw = explode('<!--more-->', $raw)[0];

	if (function_exists('site_render_content')) $raw = site_render_content($raw);

	$replacesParams = isset($settings['replaces']) ? $settings['replaces'] : [];
	$plainReplaces = isset($settings['plainReplaces']) ? $settings['plainReplaces'] : [];
	$builtinReplaces = [
		'site-assets' => variable(assetKey(SITEASSETS)),
		'site-assets-images' => variable(assetKey(SITEASSETS)) . 'images/',
		'app' => variable('app'),
		'app-assets' => variable(assetKey(COREASSETS)),
	];

	$raw = replaceItems($raw, $replacesParams, '%');
	$raw = replaceItems($raw, $plainReplaces, '');
	$raw = replaceItems($raw, $builtinReplaces, '##');

	if ($wasFile && !variable('dont-autofix-encoding')) $raw = simplify_encoding($raw);

	if ($svars = variable('siteReplaces')) $raw = replaceItems($raw, $svars, '%', true);

	$markdownStart = variable('markdownStartTag');
	$autopStart = variable('autopStart');
	$param1IsPageTag = '<!--node-item-is-page-->';
	$param1IsPage = contains($raw, $param1IsPageTag);

	$autop = $raw != '' && startsWith($raw, $autopStart);
	$md = $raw != '' && ($raw[0] == '#' || startsWith($raw, $markdownStart));
	$engageContent = false;

	if ($rawVars = variable('rawReplaces'))
		$raw = replaceItems($raw, $rawVars, '%');

	if ($no_processing) {
		$output = $raw;
	} else if ($autop || ($endsWithMd && variable('autop-for-markdown'))) {
		//TODO: @<team> temp for Sarath site which should use txt (autop) ideally
		$output = wpautop($raw);
	} else {
		$inProgress = '<!--render-processing-->';
		if (engage_until_eof($raw)) {
			$engageBits = explode(ENGAGESTART, $raw);
			$raw = $engageBits[0];
			$engageContent = $engageBits[1];
		}

		if (is_engage($raw) && !contains($raw, $inProgress)) {
			runFeature('engage');
			$settings['use-content-box'] = false;
			$meta = $wasFile ? variable('meta_' . $fileName) : [];
			$output = renderEngage(getPageName(), $raw . $inProgress, false, $meta);
		} else {
			$ai = contains($raw, FROM_GEMINI_AI);
			if ($ai) {
				runFrameworkFile('parser');
				$raw = processAI($raw, 'gemini');
			}

			$output = $md || $endsWithMd || $treatAsMarkdown ? markdown($raw) : wpautop($raw);
		}
	}

	$output = runAllMacros($output);

	if (contains($raw, '<!--composite-work-->') && !(variable('is-in-directory'))) {
		runFrameworkFile('parser');
		$prepend = getWorkSettings($fileName);
		$param1IsPage = $param1IsPage || contains($prepend, $param1IsPageTag);
		$output = parseCompositeWork($prepend . $output, $param1IsPage);
	}

	$output = replaceHtml($output);

	if (!isset($settings['dont-prepare-links']))
		$output = prepareLinks($output); //if doing before markdown then this gets messed up

	if (isset($settings['strip-paragraph-tag']))
		$output = strip_paragraph($output);

	if (contains($output, '%fileName%'))
		$output = replaceItems($output, ['%fileName%' => '<u>EDIT FILE:</u> ' .
			replaceItems($fileName, [SITEPATH => '', '//' => '/'])]);

	if (isset($settings['wrap-in-section']))
		$output = '<section>' . variable('nl') . $output . variable('nl') . '</section>' . variable('2nl');

	if (isset($settings['use-content-box']) && $settings['use-content-box'])
		$output = cbWrapAndReplaceHr($output);

	if (isset($settings['heading'])) $output = h2($settings['heading'] . currentLevel(), 'amadeus-icon', true) . NEWLINES2 . $output;

	if ($engageContent) {
		runFeature('engage');
		$settings['use-content-box'] = false;
		$meta = $wasFile ? read_seo($fileName) : [];
		$output .= renderEngage(getPageName(), $engageContent . $inProgress, false, $meta);
	}

	if ($wasFile)
		$output .= _txtInfo('File Rendered', $fileName);

	if (!$echo) return $output;
	echo $output;
}

function _txtInfo($msg, $info) {
	if (true || !variable('local')) return '';
	return textBoxWithCopyOnClick($msg, _makeSlashesConsistent($info), 'Link Copied');
}

DEFINE('FROM_GEMINI_AI', '<!--exported-from-gemini-ai-->');

function peekAtMainFile($file) {
	$raw = disk_file_get_contents($file);
	$ai = contains($raw, FROM_GEMINI_AI);
	if (!$ai) return;
	
	add_body_class('with-ai has-gemini-ai has-prompts');
}

function renderRichPage($sheetFile, $groupBy = 'section', $templateName = 'home') {
	variable('home', getSheet($sheetFile, $groupBy));
	$call = variable('theme_folder') . $templateName . '.php';
	disk_include_once($call);
}

function is_engage($raw) {
	return contains($raw, ' //engage-->') || contains($raw, '<!--ENGAGE-->');
}

DEFINE('ENGAGESTART', '<!--start-engage-->');
function engage_until_eof($raw) {
	return contains($raw, ENGAGESTART);
}

function do_md_in_parser($raw) {
	return contains($raw, '<!--markdown-when-processing-->');
}
