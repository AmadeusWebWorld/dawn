<?php
header("Content-type: text/css");
include_once '../core/framework/4-array.php';
DEFINE('NEWLINE', "\r\n");
DEFINE('NEWLINES2', NEWLINE . NEWLINE);

if ($raw = valueIfSetAndNotEmpty($_GET, 'raw')) { echo $raw; return; }

$palette = $fonts = $_GET;
$moreVars = [''];

$cursive = valueIfSetAndNotEmpty($fonts, 'cursive');
$menu = valueIfSetAndNotEmpty($fonts, 'menu');

if ($cursive || $menu) {
	$unique = implode('&family=', array_unique([$cursive, $menu]));
	echo '@import url(\'https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $unique) . '&display=swap\');' . NEWLINES2;
}

//TODO: rewrite with all as optional!
$op = ':root {
	--cnvs-header-bg: %header%;
		--cnvs-header-sticky-bg: %sticky-header%;
	--cnvs-footer-bg: %footer%;
	--cnvs-link-color: %link%;
	--amadeus-heading-bgd: %heading%;
	--amadeus-after-content-bgd: %paler%;%MOREROOTVARS%
}' . NEWLINES2;

function _color($palette, $key, $default) {
	$val = valueIfSetAndNotEmpty($palette, $key, $default);
	if (is_bool($val)) return $val;
	return $val == 'no' ? 'transparent' : '#' . $val;
}

$content = _color($palette, 'content', false);

echo replaceItems($op, [
	'header' => _color($palette, 'header', 'no'),
	'sticky-header' => $content ? $content : '#fff',
	'footer' => _color($palette, 'footer', valueIfSetAndNotEmpty($palette, 'theme', 'no')),
	'body' => _color($palette, 'body', 'bee6f9'),
	'link' => _color($palette, 'link', '5BDCFF'),
	'heading' => _color($palette, 'heading', 'E1F2FF'),
	'paler' => _color($palette, 'paler', 'C8D9F8'),
	'MOREROOTVARS' => implode(NEWLINE . '	', $moreVars),
], '%');

if (valueIfSetAndNotEmpty($palette, 'dont-round-logo', false, TYPEBOOLEAN))
	echo '.img-logo { border-radius: 0px!important; }' . NEWLINES2;

if ($node = _color($palette, 'node', false))
	echo '#page-menu-wrap { background-color: ' . $node . ' }' . NEWLINES2;

if ($content)
	echo '#content { background-color: ' . $content . '; }' . NEWLINES2;

if ($cursive) echo '.cursive { font-family: "' . $cursive . '", serif; }' . NEWLINES2;

if ($menu) {
	$menuSize = valueIfSetAndNotEmpty($fonts, 'menu-size');
	$menuSize = $menuSize ? '--cnvs-primary-menu-font-size: ' . $menuSize . '; ' : '';
	echo '#header { --cnvs-primary-menu-font: "' . $menu . '", serif; ' . $menuSize . '}' . NEWLINES2;
}