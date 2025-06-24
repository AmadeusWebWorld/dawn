<?php
/**
 * This "AmadeusWeb Core's Opus" feature framework is proprietary, Source-available software!
 * It is licensed for distribution at the sole discretion of it's owner Imran Ali Namazi.
 * v 1.1
 */

//$page, $slug, $pageDir, $listCols, $listTemplate
function renderOpusListOrSingle($vars) {
	extract($vars);
	$page_hze = humanize($page);
	contentBox($slug, 'container my-4');

	if ($name = getPageParameterAt(1)) {
		$name_hze = humanize($name);
		echo makeLink('BACK TO ' . strtoupper($page) . ' LIST', '../BTNSITE');

		h2($page_hze . ' :: ' . $name_hze);
		$data = getSheet($slug, 'name');
		if (!isset($data->group[$name_hze])) {
			peDie($page_hze . ' Not Found', ['slug' => $name, 'searched' => $name_hze, 'file' => _sheetPath($slug)], false, true);
		}

		$item = $data->group[$name_hze][0];
		$op = [];
		foreach ($data->columns as $key => $ix) {
			//todo - hidden fields
			$op[humanize($key)] = $item[$ix];
		}

		$forms = disk_scandir($path = $pageDir . '/_opus/forms/');
		$forms = _skipExcludedFiles($forms, '', '', false);
		variable('opus-vars', [ 'path' => $path, 'forms' => $forms]);

		runFeature('tables');
		_tableHeadingsOnLeft($page . '-' . $name, $op);
	} else {
		h2($page_hze . ' dB of ' . variable('name'));
		$data = _sheetPath($slug);
		runFeature('tables');
		add_table($slug, $data, $listCols, $listTemplate);
	}

	contentBox('end');

}