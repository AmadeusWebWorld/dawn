<?php
startDiv('git', 'container');
startDiv('', 'row');

$boxStart = '<div class="col-md-4 col-sm-6 col-12 p-3"><div class="content-box">';

$sheet = getSheet(__DIR__ . '/../view/repositories.tsv', false);
$items = [];

foreach ($sheet->rows as $repo) {
	$item = $sheet->asObject($repo);

	$location = $item['location'];
	$exists = disk_is_dir(ALLSITESROOT . $location);
	$res = returnLine($item['repo_link_md'] . BRTAG . ' &mdash;> <small>' . $item['description'] . '</small>' . BRTAG . ' @ ' . $location . BRNL);

	$res .= ($exists ? '<span class="btn btn-outline-primary">Exists</span>'
		: '<span class="btn btn-outline-secondary">Missing</span>') . NEWLINE;

	if ($exists) $res .= _getGuiLink($location, 'pull', 'outline-success') . NEWLINE
		. ' ' . _getGuiLink($location, 'log', 'outline-info') . NEWLINE;
	else $res .= _getGuiLink($location, 'clone', 'outline-warning', '&git-url=' . $item['clone_url']) . NEWLINE;

	$items[$item['name']] = $res;
}

ksort($items);

echo $boxStart;
echo implode('</div></div>' . $boxStart . NEWLINES2, $items);
endDiv(); endDiv(); //boxes

endDiv();
endDiv();

function _getGuiLink($site, $action, $classSuffix, $optional = '') {
	$script = 'http://localhost/git-web-ui.php';
	$qs = '?git-action=' . $action . '&site=' . $site . $optional;
	return getLink($action, $script . $qs, 'btn btn-' . $classSuffix);
}
