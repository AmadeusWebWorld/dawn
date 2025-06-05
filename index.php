<?php
define('SITEPATH', __DIR__);

if (is_dir($nw = __DIR__ . '/../amadeusweb/')) {
	DEFINE('NETWORKPATH', $nw);
	include_once($nw . 'loader.php');
	return;
}

include_once 'entry.php';
runFrameworkFile('site');
