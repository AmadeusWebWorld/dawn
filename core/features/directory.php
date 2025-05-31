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

if (disk_file_exists($home = $folder . 'home.md')) {
	$breadcrumbs = variable('breadcrumbs');

	if (!$breadcrumbs && !variable('in-node'))
		h2(humanize($where) . currentLevel(), 'amadeus-icon');

	$isIndex = variable('node') == 'index';
	$isSection = variable('section') == variable('node');
	if ($isIndex || $isSection) {
		contentBox('home');
		renderFile($home);
		contentBox('end');
	}

	echo GOOGLEOFF;
	contentBox('nodes', 'after-content');

	if (!$breadcrumbs)
		_sections($where);

	variable('seo-handled', false);


	if ($breadcrumbs || variable('in-node')) {
		//TODO: develop asap!
		$sectionItems = [];
	} else {
		$sectionItems = [getFolderMeta($folder, false, $where)];
	}

	$files = disk_scandir($folder);
	natsort($files);
	$nodes = _skipNodeFiles($files);

	foreach ($nodes as $fol) {
		$sectionItems[] = getFolderMeta($folder, $fol);
	}

	$relativeUrl = $breadcrumbs ? variable('node') . '/' . implode($breadcrumbs) . '/' : '';

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
		add_table('sections-table', $sectionItems,
			$sectionItems ? ['site, about, tags', 'name_urlized'] : 'site-name, about, tags',
			'<tr><td><a href="%url%' . $relativeUrl . '%name_urlized%">%name_humanized%</a></td><td>%about%</td><td>%tags%</td></tr>');
	}

	contentBox('end');
	echo GOOGLEON;
}

section('end');
