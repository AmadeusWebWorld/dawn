<?php
variable('upiFormat', 'upi://pay?pa=%id%&amp;pn=%name%&amp;cu=INR');

function runAllMacros($html) {
	if (contains($html, '-snippet%'))
		$html = replaceSnippets($html);

	if (contains($html, '-coresnippet%'))
		$html = replaceSnippets($html, false, CORESNIPPET);

	if (contains($html, '-codesnippet%'))
		$html = replaceCodeSnippets($html);

	if (contains($html, '-corecodesnippet%'))
		$html = replaceCodeSnippets($html, false, CORESNIPPET);

	if (contains($html, '#upi') || contains($html, '%upi'))
		$html = replaceUPIs($html);

	/*
	TODO: reinstate after engage-v3 / beta?
	if (contains($html, '%engage-btn'))
		$html = replaceEngageButtons($html);
	*/

	if (contains($html, '[youtube]'))
		$html = processYouTubeShortcode($html);

	if (contains($html, '[audio]'))
		$html = processAudioShortcode($html);

	if (contains($html, '[spacer]'))
		$html = processSpacerShortcode($html);

	return $html;
}

DEFINE('CORESNIPPET', 'use-core');

function _getSnippetPath($fol, $type = 'plain') {
	if ($fol && $fol != CORESNIPPET) return $fol;
	if ($type == 'plain') {
		return $fol == CORESNIPPET ? (AMADEUSCORE . 'data/core-snippets/')
			: (SITEPATH . '/data/snippets/');
	}

	return $fol == CORESNIPPET ? (AMADEUSCORE . 'data/core-code-snippets/')
		: (SITEPATH . '/data/code-snippets/');
}

function getSnippet($name, $fol = false) {
	$core = $fol == CORESNIPPET ? '-core' : '-';

	$fileFol = $fol;
	$fileFol = _getSnippetPath($fol); //plain

	$ext = disk_one_of_files_exist($fileFol . $name . '.', 'html, md');
	if (!$ext) return '';
	
	return replaceSnippets('%' . $name . $core . 'snippet%', [$name . '.' . $ext], $fol);
}

function replaceSnippets($html, $files = false, $fol = false) {
	$core = $fol == CORESNIPPET ? '-core' : '-';
	if (!$fol || $fol == CORESNIPPET) $fol = _getSnippetPath($fol); //plain	

	if (!$files) $files = disk_scandir($fol);

	foreach ($files as $file) {
		if ($file[0] == '.') continue;

		$fwoe = replaceItems($file, ['.md' => '', '.html' => '']);
		$ext = disk_one_of_files_exist($fol . $fwoe . '.', 'html, md');
		$key = '%' . $fwoe . $core . 'snippet%';

		if (!contains($html, $key)) continue;
		$op = renderMarkdown($fol . $file, [
			'echo' => false,
			'strip-paragraph-tag' => true,
			'raw' => $ext == 'html',
		]);

		if ($ext == 'html')
			$op = replaceHtml($op);

		$html = str_replace($key, $op, $html);
	}

	return $html;
}

function getCodeSnippet($name, $fol = false) {
	$core = $fol == CORESNIPPET ? '-core' : '-';
	return replaceCodeSnippets('%' . $name . $core . 'codesnippet%', [$name . '.php'], $fol);
}

function replaceCodeSnippets($html, $files = false, $fol = false) {
	$core = ($fol == CORESNIPPET ? '-core' : '-');
	$fol = _getSnippetPath($fol, 'code');

	if (!$files) $files = disk_scandir($fol);

	foreach ($files as $file) {
		if ($file[0] == '.' || getExtension($file) != 'php') continue;

		$fwoe = replaceItems($file, ['.php' => '']);
		$key = '%' . $fwoe . $core . 'codesnippet%';
		if (!contains($html, $key)) continue;

		$html = str_replace($key, include $fol . $file, $html);
	}

	return $html;
}

function replaceEngageButtons($html) {
	$engage = subVariable('node-vars', 'engage');

	if (!$engage) {
		if (variable('local') || isset($_GET['debug'])) parameterError('Node Variable **engage** missing', ['html' => $html]);
		return $html;
	}

	foreach ($engage as $where => $array) {
		$class = $where == 'all' ? ENGAGENODE : ENGAGENODEITEM;
		foreach ($array as $id => $name) {
			$html = str_replace('%engage-btn-' . $id . '%', engageButton($id, $name, $class, true), $html);
		}
	}

	return $html;
}

function replaceUPIs($html) {
	$items = variableOr('upi', []);

	if (empty($items)) {
		if (variable('local') || isset($_GET['debug'])) parameterError('Amadeus Variable for **upi** missing', ['html' => $html]);
		return $html;
	}

	foreach ($items as $key => $item) {
		$replaces = ['id' => $item['id'], 'name' => urlencode($item['name'])];
		$html = replaceItems($html, [
			'#upi-' . $key => replaceVariables(variable('upiFormat'), $replaces),
			'%upi-' . $key . '%' => $item['id'],
			'%upi-' . $key . '-textbox%' => textBoxWithCopyOnClick('UPI ID for Indian Money Transfer (GPay / PhonePe etc):', $item['id']),
		]);
	}

	return $html;
}

function textBoxWithCopyOnClick($lineBefore, $value, $lineAfter = 'Text Copied!', $label = false) {
	$bits = [];
	$icon = $lineBefore;
	$group = 'fa-brands bg-' . $icon;
	if ($lineBefore == 'tracker without source') { $group = 'fa-classic bg-info'; $icon = 'file-lines'; }
	if ($lineBefore == 'email') { $group = 'fa-classic bg-success'; $icon = 'envelope'; }
	$bits[] = '<div>' . ($label ? '<label><i style="width: 64px;" class="text-light si-mini rounded-circle fa-2x ' . $group . ' fa-' . $icon . '"></i> ' : '') . $lineBefore . '<br>';
	//https://css-tricks.com/auto-growing-inputs-textareas/
	$bits[] = '<textarea onfocus="this.select(); document.execCommand(\'copy\'); this.parentNode.parentNode.classList.add(\'text-copied\'); this.parentNode.nextElementSibling.style.display = \'block\';" rows="3" readonly>' . $value . '</textarea>';

	if ($label) $bits[] = '</label>';
	$bits[] = '<span style="display: none;">' . $lineAfter . '</span></div>';
	$bits[] = ''; $bits[] = ''; //extra blank lines

	return implode(variable('nl'), $bits);
}

function processYouTubeShortcode($html) {
	return replaceItems($html, [
		'[youtube]' => '<div class="video-container"><iframe width="560" height="315" src="https://www.youtube.com/embed/',
		'[/youtube]' => '" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>',
	]);
}

function processAudioShortcode($html) {
	return replaceItems($html, [
		'[audio]' => '<audio style="width: 100%" height="27" preload="none" controls><source src="',
		'[/audio]' => '" type="audio/mp3"></audio>',
	]);
}

function processSpacerShortcode($html) {
	return replaceItems($html, [
		'[spacer]' => cbCloseAndOpen() . '<div class="divider divider-center" style="margin: 0"><h1>',
		'[/spacer]' => NEWLINE . '</h1></div>' . cbCloseAndOpen(),
	]);
}
