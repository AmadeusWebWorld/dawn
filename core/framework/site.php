<?php
getSiteUrlKey(); //get only needed for testing which should come soon

function __testSiteVars($array) {
	return; //comment to test
	print_r($array);
}

$sheet = getSheet('site', false);
$cols = $sheet->columns;

$siteVars = [];
foreach ($sheet->rows as $row) {
	$key = $row[$cols['key']];
	if (!$key || $key[0] == '|') continue;
	$siteVars[$key] = $row[$cols['value']];
}

variable('site-vars', $siteVars);

if (contains($url = $siteVars[variable(SITEURLKEY)], 'localhost')) {
	$url = replaceItems($url, ['localhost' => 'localhost' . variable('port')]);
	__testSiteVars(['url-for-localhost' => $url]);
}

variable(assetKey(SITEASSETS, ASSETFOLDER), SITEPATH . '/assets/');
variable(assetKey(SITEASSETS), $url . 'assets/');

function parseSectionsAndGroups($siteVars, $return = false, $forNetwork = false) {
	if (variable('sections') && !$forNetwork) return;
	$sections = isset($siteVars['sections']) ? $siteVars['sections'] : false;
	if (!$sections) {
		$sections = [];
		if (!$forNetwork) variable('sections', $sections);
		__testSiteVars(['sections' => $sections]);
		return $sections;
	}

	$vars = [];
	//Eg.: research, causes, solutions, us: programs+members+blog
	if (contains($sections, ':')) {
		$swgs = explode(', ', $sections); //sections wtih groups
		$items = []; $groups = [];

		foreach ($swgs as $item) {
			if (contains($item, ':')) {
				$bits = explode(': ', $item, 2);
				$subItems = explode('+', $bits[1]);
				$groups[$bits[0]] = $subItems;
				$items = array_merge($items, $subItems);
			} else {
				$items[] = $item;
				$groups[] = $item;
			}
		}

		$vars['sections'] = $items;
		$vars['section-groups'] = $groups;
	} else {
		$vars['sections'] = explode(', ', $sections);
	}

	if ($return) return $vars;

	__testSiteVars($vars);
	variables($vars);
}

parseSectionsAndGroups($siteVars);

//valueIfSetAndNotEmpty
function _visane($siteVars) {
	//defaults are given, hence guaranteed and site is the only way
	$guarantees = [
		['home-link-to-section', false, 'bool'],
		['ChatraID', '--use-amadeusweb'],
		['google-analytics', '--use-amadeusweb'],

		['email', 'imran@amadeusweb.com'],
		['phone', '+91-9841223313'],
		['whatsapp', '919841223313'],
		['address', 'Chennai, India'],
		//['address-url', '#address'], //not here as needed for social too

		['description', false],
	];

	if (!hasVariable('theme')) {
		$guarantees[] = ['theme', 'canvas'];
		$guarantees[] = ['sub-theme', variableOr('sub-theme', 'business')];
	}

	$op = [];
	foreach ($guarantees as $cfg) {
		if (hasVariable($cfg[0])) continue;
		$op[$cfg[0]] = valueIfSetAndNotEmpty($siteVars, $cfg[0], $cfg[1], isset($cfg[2]) ? $cfg[2] : 'no-change');
	}

	__testSiteVars($op);
	variables($op);
}

function _always($siteVars) {
	$op = [];
	$always = [
		'name',
		'byline',
		'safeName',
		'footer-message',
		'siteMenuName',
	];
	foreach ($always as $item)
		$op[$item] = $siteVars[$item];

	$op['start_year'] = $siteVars['year'];

	__testSiteVars($op);
	variables($op);
}

_visane($siteVars);
_always($siteVars);

$safeName = $siteVars['safeName'];
$network = variable('network');

variables($op = [
	'folder' => 'content/',
	'siteHumanizeReplaces' => siteHumanize(),
	'scaffold' => isset($siteVars['scaffold']) ? explode(', ', $siteVars['scaffold']) : [],

	'path' => SITEPATH,
	'assets-url' => $url,
	'page-url' => scriptSafeUrl($url),
]);

__testSiteVars($op);

//TODO: add_foot_hook(AMADEUSTHEMEFOLDER . 'media-kit.php');

if ($network) setupNetwork($network);

function setupNetwork($network) {
	if (DEFINED('NETWORKPATH')) {
		if (disk_file_exists($nw = NETWORKPATH . 'network.php'))
			disk_include_once($nw);

		$siteNames = textToList(disk_file_get_contents(NETWORKPATH . 'sites.txt'));
		peDie('149', $siteNames);
	} else {
		if (!($at = variable('network-at')))
			peDie('Setup', 'Expected Variable: "network-at" is missing', true);

		DEFINE('NETWORKPATH', ALLSITESROOT);
		$sites = getSheet($at . '/data/sites.tsv', 'Network');

		$siteNames = [];
		foreach(explode(', ', $network) as $slug) {
			$items = $sites->group[$slug];
			foreach ($items as $row)
				$siteNames[] = $row[$sites->columns['Path']];
		}
	}

	$newTab = true ? 'target="_blank" ' : '';

	$networkItems = [];
	$networkUrls = [];

	foreach ($siteNames as $site) {
		$sheetFile = NETWORKPATH . $site . '/data/site.tsv';
		if (!sheetExists($sheetFile)) { continue; }

		$sheet = getSheet($sheetFile, 'key');
		$val = $sheet->columns['value'];

		$item = $sheet->group;

		//expects all to follow the same principle
		if (contains($url = $item[variable(SITEURLKEY)][0][$val], 'localhost'))
			$url = replaceItems($url, ['localhost' => 'localhost' . variable('port')]);

		$site = str_replace('/', '--', $site); //NOTE: escape char
		$link = sprintf('<a class="site-icon" href="%s" %stitle="%s &mdash; %s"><img src="'
				. $url . $item['safeName'][0][$val] . '-icon.png" height="30px" />  %s</a>', $url, $newTab,
			$name = $item['name'][0][$val],
			$byline = $item['byline'][0][$val],
			$item['iconName'][0][$val]);

		$networkUrls[$site . '-url'] = $url;
		$networkItems[] = ['url' => $url, 'name' => $name, 'icon-link' => $link];
	}

	$networkUrls['network-assets'] = variable(assetKey(NETWORKASSETS));
	variable('networkItems', $networkItems);
	variable('networkUrls', $networkUrls);
}

runFrameworkFile('cms');

if (disk_file_exists($cms = SITEPATH . '/cms.php'))
	disk_include_once($cms);

if (hasPageParameter('embed')) variable('embed', true);

render();
