<?php
function relatedDataFile($name) {
	$file = variable('file');
	if (!$file) peDie('"file" not set', 'function: relatedDataFile', true);
	return pathinfo($file, PATHINFO_DIRNAME) . '/data/' . $name . '.tsv';
}

function relatedMetaFile($file) {
	return pathinfo($file, PATHINFO_DIRNAME) . '/_/' . pathinfo($file, PATHINFO_FILENAME) . '/_meta.md';
}

function printRelatedPages($file) {
	$fol = pathinfo($file, PATHINFO_DIRNAME) . '/_/' . pathinfo($file, PATHINFO_FILENAME) . '/';
	if (!disk_is_dir($fol)) return;
	$files = _skipNodeFiles(scandir($fol));
	if (!COUNT($files)) return;

	contentBox('related', 'container');
	h2('Related Pages');

	$extn = pathinfo($file, PATHINFO_EXTENSION);
	$section = variable('section');
	$leaf = contains($file, '/home') ? 'home' : lastParam();
	$url = pageUrl(replaceItems($file, [
		SITEPATH . '/' => '',
		'.' . $extn => '',
		$section . '/' => '',
		'/' . $leaf => '',
	]) . '/_/' . $leaf . '/');

	//peDie('19', [$url, $leaf, $fol]);
	$links = [];
	if (disk_file_exists($fol . ($item = '_deep-dive.md'))) {
		$links[] = getLink(humanize($name = pathinfo($item, PATHINFO_FILENAME)), $url . $name . '/', 'btn btn-outline-info', true);
	}

	foreach ($files as $item)
		$links[] = getLink(humanize($name = pathinfo($item, PATHINFO_FILENAME)), $url . $name . '/', 'btn btn-outline-info me-3 mb-3', true);

	echo implode(BRNL, $links);
	contentBox('end');
}