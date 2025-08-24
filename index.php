<?php
define('SITEPATH', __DIR__);

include_once 'entry.php';

DEFINE('SITENETWORK', OURNETWORK);

runFrameworkFile('site');
