<?php
header("Content-type: text/css");
include_once '../core/framework/4-array.php';

if ($raw = valueIfSetAndNotEmpty($_GET, 'raw')) { echo $raw; return; }

$palette = $_GET;

$op = '
:root {
	--cnvs-themecolor: #%theme%;
		--cnvs-footer-bg: #%theme%;
	--cnvs-header-bg-override: #%header%;
	--cnvs-body-bg: #%body%;
	--amadeus-site-h2-bgd: #%heading%;
	--after-content-background: #%paler%;
}';

echo replaceItems($op, [
	'theme' => valueIfSetAndNotEmpty($palette, 'theme', '9FC7DA'),
	'header' => valueIfSetAndNotEmpty($palette, 'header', 'fff'),
	'body' => valueIfSetAndNotEmpty($palette, 'body', 'bee6f9'),
	'heading' => valueIfSetAndNotEmpty($palette, 'heading', 'E1F2FF'),
	'paler' => valueIfSetAndNotEmpty($palette, 'paler', 'C8D9F8'),
], '%');
