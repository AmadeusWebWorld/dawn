<?php
define('SITEPATH', __DIR__);

include_once 'entry.php';

variables(['network-at' => ALLSITESROOT . '/amadeus8', 'network' => OURNETWORK]);

runFrameworkFile('site');
