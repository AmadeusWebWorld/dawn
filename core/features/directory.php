<?php
$where = variableOr('directory_of', variable('section'));
variable('omit-long-keywords', true);

sectionId('directory', 'container');
function _sections($current) {
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

$folder = SITEPATH . '/' . $where . '/';

_renderMenu(variable('file') ? false : $folder . 'home.md', $folder, $where);

function _renderMenu($home, $folder, $where) {
	$breadcrumbs = variable('breadcrumbs');

	if (!$breadcrumbs && !variable('in-node'))
		h2(humanize($where) . currentLevel(), 'amadeus-icon');

	if ($home) {
		contentBox('home');
		renderFile($home);
		contentBox('end');
	}

	echo GOOGLEOFF;
	contentBox('nodes', 'after-content mb-5');

	if (!$breadcrumbs)
		_sections($where);

	variable('seo-handled', false);


	$sectionItems = [];
	if ($breadcrumbs) {
		$clone = array_merge($breadcrumbs);
		if (count($clone) > 1)
			$first = array_shift($clone);
		$last = end($clone);
		$sectionItems[] = getFolderMeta($folder, false, '__' . $last);
	}

	$files = disk_scandir($folder);
	natsort($files);
	$nodes = _skipNodeFiles($files);

	foreach ($nodes as $fol) {
		$sectionItems[] = getFolderMeta($folder, $fol);
	}

	$relativeUrl = (variable('node') != variable('section') ? variable('node') . '/' : '') . ($breadcrumbs ? implode('/', $breadcrumbs) . '/' : '');

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
		add_table('sections-table', $sectionItems, 'name_urlized, about, tags',
			'<tr><td><a href="%url%' . $relativeUrl . '%name_urlized%">%name_humanized%</a></td><td>%about%</td><td>%tags%</td></tr>');
	}

	contentBox('end');
	echo GOOGLEON;
}

section('end');
