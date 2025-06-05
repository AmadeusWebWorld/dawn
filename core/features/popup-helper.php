<?php
DEFINE('ENGAGETAB', 'engage');
DEFINE('SOCIALTAB', 'social');
DEFINE('CONTACTTAB', 'contact-us');
DEFINE('SITEWIDETAB', 'site-form');

function getPopupTabs($tabs = [ENGAGETAB, SITEWIDETAB, CONTACTTAB, SOCIALTAB]) {
	$output = [];
	foreach ($tabs as $tab) {
		if ($tab == ENGAGETAB)
			$output['engage'] = getEngageTab(ENGAGETAB);
		if ($tab == SOCIALTAB)
			$output['social'] = getSocialTab();
		if ($tab == SITEWIDETAB)
			$output['site-form'] = getEngageTab(SITEWIDETAB);
		if ($tab == CONTACTTAB)
			$output['contact-form'] = getEngageTab(CONTACTTAB);
	}

	$opNav = [];
	$opNav[] = '<ul class="nav canvas-tabs tabs nav-tabs justify-content-center mb-3" role="tablist">';
	$fmtNav = '<li class="nav-item" role="presentation"><button class="nav-link%active%" id="popup-%slug%-tab" data-bs-toggle="pill" data-bs-target="#tab-popup-%slug%" type="button" role="tab" aria-controls="popup-home" aria-selected="%selected%">%text%</button></li>';

	$opTabs = [];
	$opTabs[] = '<div class="tab-content">';
	$fmtTab = '<div class="tab-pane fade%active%" id="tab-popup-%slug%" role="tabpanel" aria-labelledby="popup-%slug%-tab" tabindex="0"><div id="%id%">%content%</div></div>';


	$ix = 0;
	foreach ($output as $key => $content) {
		if ($content == false) continue;
		$selected = $ix++ == 0;

		$opNav[] = replaceItems($fmtNav, $vars = [
			'id' => 'panel-' . $key,
			'active' => $selected ? ' active' : '',
			'selected' => $selected ? ' true' : 'false',
			'slug' => $key, 'text' => humanize($key),
			'content' => $content,
		], '%');

		if ($selected) $vars['active'] .= ' show';
		$opTabs[] = replaceItems($fmtTab, $vars, '%');
	}

	$opNav[] = '</ul>';
	$opTabs[] = '</div>';

	return implode(NEWLINE, array_merge($opNav, $opTabs));
}

function getEngageTab($what) {
	if ($what == SITEWIDETAB || $what == CONTACTTAB) {
		$file = SITEPATH . '/data/' . ($what == CONTACTTAB ? 'contact' : 'engage') . '.';
		$extension = disk_one_of_files_exist($file, ENGAGEFILES);
		if (!$extension) return false;
		$file .= $extension;
	} else {
		$file = resolveEngage();
		if (!$file) return false;
	}

	doToBuffering(1);

	autoRender($file);

	$result = doToBuffering(2);
	doToBuffering(3);

	return $result;
}

//similar method in cms
function resolveEngage() {
	$fol = variableOr('leafFolder', variable('folderGoesUpto'));
	while (startsWith($fol, SITEPATH) && $fol != SITEPATH) {
		$extension = disk_one_of_files_exist($file = $fol . '/_engage.', ENGAGEFILES);
		if ($extension) return $file . $extension;

		$fol = dirname($fol);
	}
	return false;
}

function getSocialTab() {
	$op = [];
	$op[] = contentBox('social', 'container', true);
	appendSocial(variableOr('social', main::defaultSocial()), $op);
	$op[] = contentBox('end', '', true);
	return implode(NEWLINE . BRNL, $op);
}
