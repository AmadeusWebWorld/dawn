<?php
return; //TODO: later in v1.2

DEFINE('LOCALRUNSAT', 'http://localhost/');
DEFINE('OPUSRUNSAT', LOCALRUNSAT . 'teams/');

$domain = $_SERVER['HTTP_HOST']; //peDie('4', $domain);
$local = startsWith($domain, 'localhost');

if (!$local && !startsWith($domain, ''))
	peDie('Nice Try!', $domain);

if (!(defined('OPUSSITEFOLDER')))
	peDie('Nice Try!', 'define: OPUSSITEFOLDER');

if (!startsWith($domain, OPUSRUNSAT))
	peDie('Nice Try!', 'define: OPUSRUNSAT');

$localDomain = substr($domain, strlen($OPUSRUNSAT), strlen($domain) + strlen($LOCALRUNSAT));
peDie('14', $localDomain);

$sites = getSheet(OPUSENGINE . 'hashes.tsv', 'domain');
//if (!isset($sites->group['$']));

//die(OPUSSITEFOLDER);


//if ($local && )
//OPUSDOMAIN
