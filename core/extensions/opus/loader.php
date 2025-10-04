<?php
DEFINE('AMADEUSOPUS', __DIR__ . DIRECTORY_SEPARATOR);

function runOpusItem($name) {
	disk_include_once(AMADEUSOPUS . $name . '.php');
}

runOpusItem('o1-site');
runOpusItem('o2-auth');

before_bootstrap();

runFrameworkFile('site');

render();
