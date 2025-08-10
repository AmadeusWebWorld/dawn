<?php
header("Content-type: text/css");
include_once '../core/framework/4-array.php';
DEFINE('NEWLINES2', "\r\n\r\n");

if ($raw = valueIfSetAndNotEmpty($_GET, 'raw')) { echo $raw; return; }

$palette = $_GET;

$op = '
:root {
	--cnvs-themecolor: %theme%;
		--cnvs-footer-bg: %footer%;
	--cnvs-header-bg-override: %header%;
	--cnvs-body-bg: %body%;
	--cnvs-link-color: %link%;
	--amadeus-heading-bgd: %heading%;
	--after-content-background: %paler%;
}' . NEWLINES2;

function _color($palette, $key, $default) {
	$val = valueIfSetAndNotEmpty($palette, $key, $default);
	if (is_bool($val)) return $val;
	return $val == 'no' ? 'transparent' : '#' . $val;
}

echo replaceItems($op, [
	'theme' => _color($palette, 'theme', '9FC7DA'),
	'header' => _color($palette, 'header', 'fff'),
	'footer' => _color($palette, 'footer', valueIfSetAndNotEmpty($palette, 'theme', '9FC7DA')),
	'body' => _color($palette, 'body', 'bee6f9'),
	'link' => _color($palette, 'link', '5BDCFF'),
	'heading' => _color($palette, 'heading', 'E1F2FF'),
	'paler' => _color($palette, 'paler', 'C8D9F8'),
], '%');

if (valueIfSetAndNotEmpty($palette, 'dont-round-logo', false, TYPEBOOLEAN))
	echo '.img-logo { border-radius: 0px!important; }' . NEWLINES2;

if ($node = _color($palette, 'node', false))
	echo '#page-menu { background-color: ' . $node . ' }' . NEWLINES2;

