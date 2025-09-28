<?php
/******
 * Amadeus' Table Feature
 * v2 - from amadeusweb/code (datatables) - Dec 2024
 * v2.5 - supoprting dossiers (tsv / auto)
 * Trying to support multiple in one page (not tested yet)
 */

//add_foot_hook(featurePath('tables/foot-hook.php'));

variable('calendar-cells', explode(',', '1-1,1-2,1-3,1-4,1-5,1-6,1-7,2-1,'
	. '2-2,2-3,2-4,2-5,2-6,2-7,3-1,3-2,3-3,3-4,3-5,3-6,3-7,'
	. '4-1,4-2,4-3,4-4,4-5,4-6,4-7,5-1,5-2,5-3,5-4,5-5,5-6,5-7'));

DEFINE('IGPOSTFORMAT', '<blockquote class="instagram-media" data-instgrm-permalink="https://www.instagram.com/p/%Instagram%/" data-instgrm-version="14"></blockquote>');
DEFINE('IGREELFORMAT', '<blockquote class="instagram-media" data-instgrm-permalink="https://www.instagram.com/reel/%Instagram%/" data-instgrm-version="14" style="max-width:540px; min-width:326px;"></blockquote>');

function getTableTemplate($nameOrMeta) {
	if (is_object($nameOrMeta))
		$nameOrMeta = $nameOrMeta->values['use-template'];
	return AMADEUSCORE . 'data/table-templates/' . $nameOrMeta . '.tsv';
}

function _table_row_values($item, $cols, $tsv, $values, $template) {
	//TODO: HIGH: use $tsv for sticking with old code path.. seems buggy
	if (!$tsv && $tsv != 'array') { $r = []; foreach ($cols as $c) $r[$c] = !is_int($item) && $item[$c] ? $item[$c] : ''; return $r; }
	$r = [];

	//parameterError('Table Debugger', [$item, $cols], false); die();
	foreach ($cols as $key => $c) {
		if (is_numeric($key)) $key = $c;
		$value = $item[$c];

		if (contains($template, $mr = '%' . $key . '_mr%'))
			$r[$key . '_mr'] = urlize($value);

		$start = startsWith($value, '__');

		if (!$value || (startsWith($key, '__') && !(variable('allow-internal'))))
			$r[$key] = '';
		else if (endsWith($key, '_link') && $key != '_link')
			$r[$key] = _table_link($item, $c, $values, $key, $cols);
		else if (endsWith($key, '_md') || in_array($key, ['about', 'content', 'description']))
			$r[$key] = returnLine($value);
		else if (endsWith($key, '_urlized'))
			$r[$key] = $start ? '' : $value . '/';
		else if (endsWith($key, '_date'))
			$r[$key] = replaceItems((new DateTime($value))->format('D, d-/S/- M Y // h:mA'), ['-/' => '<sup class="plain">', '/-' => '</sup>', '//' => '&mdash;']);
		else if (contains($key, 'tags'))
			$r[$key] = csvToHashtags($value);
		else
			$r[$key] = $value; 

		if (contains($template, $key . '_slug'))
			$r[$key . '_slug'] = urlize($value);

		if (contains($template, $key . '_sluggized'))
			$r[$key . '_sluggized'] = sluggize($value);

		if (endsWith($key, '_urlized')) {
			$wrap = $start ? ['<b>', '</b>'] : ['', ''];
			if ($start) $value = substr($value, 2);
			$hzeKey = str_replace('_urlized', '', $key) . '_humanized';
			$r[$hzeKey] = $wrap[0] . ((isset($item[$hzeKey])) ? $item[$hzeKey] : humanize($value)) . $wrap[1];
		}
	}

	$wantsIG = contains($template, 'Instagram_Embed');
	$wantsYT = contains($template, 'YouTube_Embed');
	$wantsSOC = contains($template, 'Social_Embed');

	if ($wantsYT || ($wantsSOC && isset($cols['YouTube']) && $r['YouTube']))
		$r[$wantsYT ? 'YouTube_Embed' : 'Social_Embed'] = processYouTubeShortcode('[youtube]' . $r['YouTube'] . '[/youtube]', '%');
	else if ($wantsIG || ($wantsSOC && isset($cols['Instagram']) && $r['Instagram']))
		$r[$wantsIG ? 'Instagram_Embed' : 'Social_Embed'] = replaceItems(isset($cols['InstagramReel']) && $cols['InstagramReel'] ? IGREELFORMAT : IGPOSTFORMAT, $r, '%');

	return $r;
}

function _table_link($item, $c, $values, $key, $cols) {
	$link = $item[$c];

	if (isset($values[$key . '_template'])) {
		$lookup = []; foreach ($cols as $ix => $c) $lookup[$ix] = $item[$c];
		$format = trim($values[$key . '_template'], '	'); //comes from vscode tsv editor
		$text = str_replace('_link', '', $key);
		$link = replaceHtml(replaceItems($format, $lookup, '%'));
		$class = isset($values[$key . '_class']) ? trim($values[$key . '_class'], '	') : ''; //comes from vscode tsv editor
		return getLink($text, $link, $class);
	}

	$text = 'open';

	if (contains($link, 'docs.google.com'))
		$text = 'document';
	else if (contains($link, '/folders/'))
		$text = 'folder';

	return makeLink($text, $link, 'external');
}

class tableBuilder extends builderBase {
	private $id, $data, $cols, $template;

	function __construct($id, $data, $cols = 'auto', $template = 'auto', $settings = [])
	{
		$this->id = $id;
		$this->data = $data;
		$this->cols = $cols;
		$this->template = $template;
		$this->settings = $settings;
		$this->setDefault('dont-treat-array', true);
		$this->setDefault('use-datatables', true);
	}

	function render() {
		if (!isset($this->data[0]) && count($this->data))
			$this->data = array_values($this->data);

		if ($this->cols == 'auto') { $this->cols = array_keys($this->data[0]); } //onus on caller to have rows if cols are auto

		if ($this->template == 'auto') $this->template = '<tr><td>%' . (implode('%</td><td>%', $this->cols)) . '%</td></tr>' . NEWLINE;

		add_table(
			$this->id,
			$this->data,
			$this->cols,
			$this->template,
			$this->settings,
		);
	}
}

//TEST: http://localhost/amadeus8/code/

function add_table($id, $dataFile, $columnList, $template, $values = []) {
	variable('allow-internal', variable('local') || isset($_GET['internal']));
	$tsv = is_string($dataFile) && endsWith($dataFile, '.tsv');
	$json = is_string($dataFile) && endsWith($dataFile, '.json');
	$dontTreat = valueIfSetAndNotEmpty($values, 'dont-treat-array', false);
	$wantsBSRow = isset($values['use-a-bootstrap-row']) && $values['use-a-bootstrap-row'];
	$lineTemplateForBS = isset($values['bs-template']) ? $values['bs-template'] : false;

	if ($dontTreat) {
		$rows = $dataFile;
		if (is_array($columnList))
			$headings = implode('</th>' . variable('nl') . '			<th>', $columnList);
	} else if (is_array($dataFile)) {
		$tsv = 'array';
		$rows = $dataFile;
		if (is_string($columnList)) $columnList = [$columnList];
		$headingNames = array_map('humanize', array_map('first_of_underscore', explode(', ', $columnList[0])));
		$columns = explode(', ', implode(', ', $columnList));
	} else if (!$tsv && !$json) {
		$rows = $dataFile;
		$columns = explode(', ', $columnList);
		$headingNames = array_map('humanize', $columns);
	} else if ($columnList == 'auto' || is_string($columnList)) {
		if (!$tsv) { parameterError('TSV Expected', $dataFile, false); die(); }
		$sheet = getSheet($dataFile, false);
		$cols = $columnList == 'auto' ? array_keys($sheet->columns) : explode(', ', $columnList);

		$headingNames = [];
		foreach ($cols as $item) {
			if (startsWith($item, '_')) continue;
			$headingNames[] = humanize(explode('_', $item)[0]);
		}

		if ($template == 'auto') $template = '<tr><td>%' . (implode('%</td><td>%', $cols)) . '%</td></tr>' . NEWLINE;
		$rows = $sheet->rows;
		$columns = $sheet->columns;
	} else if (!$wantsBSRow) {
		//NOTE: Magic columnList can be array of csvs where 2nd is additional cols needed, but not the headers
		$headingNames = is_string($columnList) ? $columnList : $columnList[0];
		$headingNames = explode(', ', $headingNames);

		$columnNames = explode(', ', is_string($columnList) ? $columnList : implode(', ', $columnList));
		$columns = array_map('strtolower', $columnNames);

		$rows = $json ? jsonToArray($dataFile) : $dataFile;
	} else {
		$sheet = getSheet($dataFile, false);
		$rows = $sheet->rows;
	}

	if (!$dontTreat AND !$wantsBSRow)
		$headings = implode('</th>' . variable('nl') . '			<th>', $headingNames);

	$isInList = variable('is-in-directory');
	$useDatatables = valueIfSetAndNotEmpty($values, 'use-datatables');
	$allowCards = valueIfSetAndNotEmpty($values, 'allow-cards', false, TYPEBOOLEAN);

	$datatableClass = ($isInList ? '' : 'amadeus-table ') . ($useDatatables ? ' amadeus-data-table ' : '');
	$datatableParams = '';
	if ($useDatatables && $rg = valueIfSetAndNotEmpty($values, 'row-group')) $datatableParams .= 'data-row-group="' . $rg . '" ';

	$skipItemFn = isset($values['skipItem']) ? $values['skipItem'] : false;

	if ($beforeContent = valueIfSetAndNotEmpty($values, 'before-content')) echo returnLine(pipeToBR($beforeContent));
	if ($allowCards) echo '<div class="text-center"><button data-table-id="amadeus-table-' . $id . '" class="amadeus-table-' . $id . '-card-view">toggle card view</button></div>' . BRNL;

	if ($wantsBSRow) echo '<div id="amadeus-bs-row-' . $id . '" class="row">'; else
	echo '
	<table id="amadeus-table-' . $id . '" class="' . $datatableClass . 'table table-striped table-bordered" ' . $datatableParams . 'cellspacing="0" width="100%">
	<thead>
		<tr class="align-text-top amadeus-header-row">
			<th>' . $headings . '</th>
		</tr>
	</thead>
	<tbody>
';
	foreach ($rows as $item) {
		if ($dontTreat) {
			$row = $item;
		} else if($wantsBSRow && $lineTemplateForBS) {
			$row = $sheet->asObject($item);
		} else {
			$more = isset($item[0]) && $item[0] == '<!--more-->';
			if ($more) { if (variable('is-in-directory')) break; else continue; }
			$row = _table_row_values($item, $columns, $tsv, $values, $template);
			if ($skipItemFn && $skipItemFn($row)) continue;
		}
		$line = replaceHtml(prepareLinks(replaceItems($template, $row, '%')));

		if ($wantsBSRow && $lineTemplateForBS)
			$line = replaceItems($lineTemplateForBS, ['line' => returnLine(pipeToBR($line))], '%');

		echo NEWLINE . $line;
	}
	if ($wantsBSRow) echo '</div><!-- end #' . $id . ' -->' . NEWLINES2; else
	echo '
	</tbody>
</table>
';
	if (contains($template, '_Embed')) echo NEWLINES2 . '<script async src="//www.instagram.com/embed.js"></script>';
	if ($useDatatables) _includeDatatables($rg);
	if ($useDatatables) _includeTableV2($template);
}

function _tableHeadingsOnLeft($id, $data) {
	$css = 'amadeus-plain-table headings-on-left table table-striped table-bordered table-reponsive';
	if (is_array($id)) {
		if (isset($id['class'])) $css .= ' ' . $id['class'];
		$id = $id['id'];
	}
	echo variable('nl') . '<table id="amadeus-table-' . $id . '" class="' . $css . '" cellspacing="0">' . variable('nl');

	$header = '	<thead><tr class="header"><th>%th%</th><th class="left">%td%</th></tr></thead><tbody>' . variable('nl');
	$row = '	<tr><th>%th%</th><td>%td%</td></tr>' . variable('nl');

	foreach ($data as $th => $td) {
		if (in_array($th, ['Author', 'Page Custodian', 'Prompted By', 'Published', 'Meta Author']))
			$td = returnLine($td);

			echo replaceItems(($hdg = startsWith($th, '+')) ? $header : $row, ['th' => $hdg ? substr($th, 1) : $th, 'td' => $td], '%');
	}

	echo '</tbody></table>' . variable('2nl');
}

function _includeDatatables($rg) {
	//https://datatables.net/download/
	//DataTables' default styling.
	add3pStyle ('https://cdn.datatables.net/v/dt/dt-2.3.2/b-3.2.4/b-colvis-3.2.4/b-html5-3.2.4/b-print-3.2.4/r-3.0.5/rg-1.5.2/sp-2.3.4/datatables.css');
	//
	add3pScript('https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.js');
	add3pScript('https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js');
	add3pScript('https://cdn.datatables.net/v/dt/dt-2.3.2/b-3.2.4/b-colvis-3.2.4/b-html5-3.2.4/b-print-3.2.4'
		. ($rg !== false || true ? '/r-3.0.5' : '')
		. '/rg-1.5.2/sp-2.3.4/datatables.js');
}

function _includeTableV2($template) {
	//D:\AmadeusWeb\bhava\amadeus\features\tables\tables-loader.js
	addScript('tables-v2', COREASSETS);
	//contains($template, 'data-price')
}
