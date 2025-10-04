<?php
if (variable('skip-directory')) return;
$where = variableOr('directory_of', variable('section'));

$folder = sectionBaseOrSitePath() . '/' . $where . '/';
if (disk_file_exists($php = $folder . 'home.php')) {
	disk_include_once($php);
	return;
}

variable('omit-long-keywords', true);

sectionId('directory', 'container');
function _sections($current) {
	if (nodeIsNot(variable('section'))) return;

	contentBox('', 'toolbar text-align-left');
	echo 'Section: ' . variable('nl');
	foreach (variable('sections') as $item) {
		//TODO: reinstate - if (cannot_access($item)) continue;
		echo sprintf(variable('nl') . '<a class="btn btn-%s" href="%s">%s</a> ',
			$item == $current ? 'primary' : 'secondary',
			pageUrl($item),
			humanize($item)
		);
	}
	contentBox('end');
}

_renderMenu(variable('file') ? false : $folder . 'home.md', $folder, $where);

function _renderMenu($home, $folder, $where) {
	$breadcrumbs = variable('breadcrumbs');

	if (!$breadcrumbs && !variable('in-node'))
		h2(humanize($where) . currentLevel(), 'amadeus-icon');

	if ($home) {
		contentBox('home');
		renderAny($home);
		contentBox('end');
	}

	echo GOOGLEOFF;
	contentBox('nodes', 'after-content mb-5');

	if (!$breadcrumbs)
		_sections($where);

	variable('seo-handled', false);


	$ix = 1;
	$sectionItems = [];

	if ($breadcrumbs) {
		$clone = array_merge($breadcrumbs);
		if (count($clone) > 1)
			$first = array_shift($clone);
		$last = end($clone);
		$sectionItems[] = getFolderMeta($folder, false, '__' . $last, $ix++);
	}

	$files = disk_scandir($folder);
	natsort($files);
	$nodes = _skipNodeFiles($files);

	foreach ($nodes as $fol) {
		$sectionItems[] = getFolderMeta($folder, $fol, '', $ix++);
	}

	$relativeUrl = (nodeIsNot(variable('section')) ? nodeValue() . '/' : '') . ($breadcrumbs ? implode('/', $breadcrumbs) . '/' : '');

	if (hasPageParameter('generate-index')) {
		addScript('engage', 'app-static--common-assets'); //TODO: better way than against DRY?	
		echo '<textarea class="autofit">' . NEWLINE;
		echo '<!--use-blocks-->' . NEWLINES2;
		foreach ($sectionItems as $item) {
			echo '## ' . humanize($item['name_urlized']) . NEWLINE;
			echo 'Keyworkds ' . $item['tags'] . NEWLINES2;
			echo $item['about'] . NEWLINES2;
		}

		echo '</textarea>' . NEWLINE;
	} else {
		runFeature('tables');
		$template = '<tr><td>%index%</td><td><a href="%url%' . $relativeUrl .
			'%name_urlized%">%name_humanized%</a></td><td>%about%</td><td>%tags%</td><td>%size%</td></tr>';
		$params = ['use-datatables' => count($sectionItems) > 5];
		add_table('sections-table', $sectionItems, 'index, name_urlized, about, tags, size', $template, $params);
	}

	contentBox('end');
	echo GOOGLEON;
}

section('end');
