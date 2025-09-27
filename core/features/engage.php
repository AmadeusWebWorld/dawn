<?php
addStyle('engage', COREASSETS);
addScript('engage', COREASSETS);

//TODO: Make a toggle-more when the md contains <!--more-->
function renderEngage($name, $raw, $echo = true, $meta = []) {
	//if (!$open) echo engageButton($name, $class);

	$salutation = variableOr('salutation', 'Dear ' . variable('name')) . ',';
	$addressee = '';
	$additionalCC = '';
	$whatsapp = variable('whatsapp-txt-start');

	if ($meta) {
		$mailSpacer = ',';
		if (isset($meta['Salutation'])) $salutation = $meta['Salutation'];
		if (isset($meta['Email To'])) $addressee = $meta['Email To'] . $mailSpacer;
		if (isset($meta['Email Cc'])) $additionalCC = $meta['Email Cc'] . $mailSpacer;
		if (isset($meta['WhatsApp To'])) $whatsapp = _whatsAppME($meta['WhatsApp To']);
	}

	$result = '	<div id="engage-' . urlize($name) . '" class="' . _getCBClassIfWanted('engage') . '" ' .
		//($open ? '' : 'style="display: none" ') .
		'data-to="' . ($email = $addressee . variable('email')) .
		'" data-cc="' . $additionalCC .
			variableOr('assistantEmail', variable('systemEmail')) .
		'" data-whatsapp="' . $whatsapp .
		'" data-site-name="' . variable('name') .
		'" data-salutation="' . $salutation . '">' . variable('nl');

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

function runEngageFromSheet($pageName, $sheetName) {
	$sheet = getSheet($sheetName);
	$contentIndex = $sheet->columns['content'];
	$introIndex = $sheet->columns['section-intro'];
	$varsIndex = valueIfSet($sheet->columns, 'item_vars');
	$introduction = valueIfSet($sheet->values, 'introduction', 'Welcome to <b>' . humanize($pageName) . '</b> page of <b>' . variable('name') . '</b>.');

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
	renderEngage($pageName, implode(variable('nl'), $raw));
	section('end');
}
