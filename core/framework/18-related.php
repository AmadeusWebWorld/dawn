<?php
function relatedDataFile($name) {
	$file = variable('file');
	if (!$file) peDie('"file" not set', 'function: relatedDataFile', true);
	$data = pathinfo($file, PATHINFO_DIRNAME) . '/data/' . $name . '.tsv';
}
