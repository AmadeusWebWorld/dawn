<?php
addStyle('engage', COREASSETS);
addScript('engage', COREASSETS);

//TODO: Make a toggle-more when the md contains <!--more-->
function _renderEngage($name, $raw, $open = false, $echo = true) {
	//deprecating the name - heading of form should do
	$id = variableOr('all_page_parameters', variable('node'));
	if (!$open) echo engageButton($id, $name, $class);

	$result = '	<div id="engage-' . $id . '" class="' . _getCBClassIfWanted('engage') . '" ' .
		($open ? '' : 'style="display: none" ') .
		'data-to="' . ($email = variable('email')) . '" data-cc="' .
		variableOr('assistantEmail', variable('systemEmail')) .
		'" data-whatsapp="' . variable('whatsapp-txt-start') . '"' .
		'" data-site-name="' . variable('name') . '">' . variable('nl');

	$replaces = [];
	if (disk_file_exists($note = (AMADEUSCORE . 'data/engage-note.md'))) {
		$replaces['engage-note'] = '<div id="engage-note" class="d-none"><hr>' . renderMarkdown($note, ['echo' => false]) . '</div>';
		if (disk_file_exists($note2 = (AMADEUSCORE . 'data/engage-note-above.md')))
			$replaces['engage-note-above'] = renderMarkdown($note2, ['echo' => false]);
		$replaces['email'] = $email;
		$replaces['whatsapp'] = getHtmlVariable('whatsapp') . getHtmlVariable('enquiry');
	}

	$result .= renderMarkdown($raw, ['replaces' => $replaces, 'echo' => false]);

	$result .= getSnippet('engage-toolbox', CORESNIPPET);
	
	$result .= '</div>' . variable('nl');
	if (!$echo) return $result;
	echo $result;
}

function _runEngageFromSheet($pageName, $sheetName) {
	$pageName = humanize($pageName);
	$sheet = getSheet($sheetName);
	$contentIndex = $sheet->columns['content'];
	$introIndex = $sheet->columns['section-intro'];
	$varsIndex = valueIfSet($sheet->columns, 'item_vars');
	$introduction = valueIfSet($sheet->values, 'introduction', 'Welcome to <b>' . $pageName . '</b> page of <	b>' . variable('name') . '</b>.');

	//TODO: use faq by category like canvas' FAQ?
	//$items = []; //trying to make as pills in a later version
	$raw = ['<!--engage: SITE //engage--><!--render-processing-->', $introduction, ''];

	$firstSection = true;

	$customEngageNotes = variable('custom-engage-notes');
	if (!$customEngageNotes)
		$raw[] = '%engage-note-above%';

	foreach ($sheet->group as $name => $rows) {
		$raw[] = '## ' . $name;
		$raw[] = '';

		$firstRow = true;
		foreach ($rows as $row) {
			if ($firstRow) {
				$raw[] = $row[$introIndex];
				$raw[] = '';
				$firstRow = false;
			}
	
			$line = $row[$contentIndex];

			if ($varsIndex) {
				$vars = $row[$varsIndex];
				if ($vars) {
					$vbits = explode(', ', $vars);
					if (in_array('open', $vbits))
						$line .= '<!--open-->';
					if (in_array('large', $vbits))
						$line .= '<!--large-->';
				}
			}

			$raw[] = '* '  . $line;
			//$content[] = ;
		}
	
		$raw[] = '';
	}

	$raw[] = '';
	if (!$customEngageNotes)
		$raw[] = '%engage-note%';

	//$raw = print_r($items, 1); //$raw = renderPills($items); //todo: LATER!
	sectionId('engage-' . urlize($pageName));
	_renderEngage($pageName, implode(variable('nl'), $raw), true);
	section('end');
}
