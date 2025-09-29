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

	contentBox('related', 'container text-center');
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

	$links = [];

	if (disk_file_exists($fol . ($item = '_deep-dive.md')))
		$links[] = linkBuilder::factory($item, $url, linkBuilder::openFileBlock);

	foreach ($files as $item) {
		if (disk_is_dir($fol . $item)) {
			$links[] = '<div class="btn btn-outline-secondary p-1">';
			$links[] = '<span class="d-block mb-1">' . $item . '</span>';
			$pages = _skipNodeFiles(scandir($fol . $item));
			natsort($pages);
			foreach ($pages as $page)
				$links[] = linkBuilder::factory($page, $url . $item, linkBuilder::openFileInline);
			$links[] = '</div>';
			continue;
		}
		$links[] = linkBuilder::factory($item, $url, linkBuilder::openFileBlock);
	}

	echo implode(NEWLINE, $links);
	contentBox('end');
}