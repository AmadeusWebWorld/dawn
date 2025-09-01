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

if (hasPageParameter('health')) die('<span style="background-color: #cbfecb; padding: 10px;">Works!: ' . $url . '</span>');

variable(assetKey(SITEASSETS, ASSETFOLDER), SITEPATH . '/assets/');
variable(assetKey(SITEASSETS), $url . 'assets/');

function parseSectionsAndGroups($siteVars, $return = false, $forNetwork = false) {
	if (variable('sections') && !$forNetwork) return;
	$sections = isset($siteVars['sections']) ? $siteVars['sections'] : false;
	if (isset($siteVars['sections_local'])) $sections = $siteVars['sections_local'];

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
		['footer-name', null], //needs null as uses !== in variableOr
		['link-to-site-home', true, 'bool'],
		['link-to-section-home', false, 'bool'],
		['ChatraID', '--use-amadeusweb'],
		['google-analytics', 'none', 'bool'], //'--use-amadeusweb'

		['email', 'imran@amadeusweb.com'],
		['phone', '+91-9841223313'],
		['whatsapp', '919841223313'],
		['address', 'Chennai, India'],
		['timings', 'Mon - Sat 11am to 7pm'],
		//['address-url', '#address'], //not here as needed for social too
		['owned-by', false], //in _copyright

		['mediakit', '?palette=default'],
		['fonts', []], //used in mediakit.php
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

	if (!empty($op['fonts']))
		$op['mediakit'] .= '&' . $op['fonts'];

	__testSiteVars($op);
	variables($op);

	variable(assetKey(THEMEASSETS), getThemeBaseUrl());
}

function _always($siteVars) {
	$op = [];
	$always = [
		'name',
		'byline',
		'safeName',
		'iconName',
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

variables($op = [
	'folder' => 'content/',
	'siteHumanizeReplaces' => siteHumanize(),
	'scaffold' => isset($siteVars['scaffold']) ? explode(', ', $siteVars['scaffold']) : [],

	'path' => SITEPATH,
	'assets-url' => $url,
	'page-url' => scriptSafeUrl($url),
]);

__testSiteVars($op);

setupNetwork($url);

function setupNetwork($thisUrl) {
	$networkUrls = [];
	$networkHome = false;

	$sites = getSheet(AMADEUSROOT . '/data/sites.tsv', false);

	$sitePaths = [];
	$networks = explode(', ', DEFINED('SITENETWORK') ? SITENETWORK : '');
	$mainNetwork = !empty($networks) ? $networks[0] : false;

	foreach ($sites->rows as $siteRow) {
		$showIn = explode(', ', $sites->getValue($siteRow, 'Network'));

		$matched = false;
		foreach ($showIn as $allow) {
			if (in_array($allow, $networks)) {
				$matched = true;
				break;
			}
		}

		$thisPath = $sites->getValue($siteRow, 'Path');
		$siteObj = $sites->asObject($siteRow);
		$siteObj['Matched'] = $matched;

		if ($siteObj['HomeOf'] == $mainNetwork)
			$networkHome = $thisPath;

		$sitePaths[$thisPath] = $siteObj;
	}

	$networkItems = [];
	$allSiteItems = [];
	$local = variable('local');
	$sansPreview = _getUrlKeySansPreview();

	foreach ($sitePaths as $siteAt => $siteObj) {
		$sheetFile = ALLSITESROOT . $siteAt . '/data/site.tsv';
		if (!sheetExists($sheetFile)) {
			if ($local) echo '<!--missing tsv for: ' . $siteAt . '-->' . NEWLINE;
			continue;
		}

		$sheet = getSheet($sheetFile, 'key');
		$valueIndex = $sheet->columns['value'];

		$item = $sheet->group;

		$urlRows = isset($item[variable(SITEURLKEY)]) ? $item[variable(SITEURLKEY)] : $item[$sansPreview];
		if (contains($url = $urlRows[0][$valueIndex], 'localhost'))
			$url = replaceItems($url, ['localhost' => 'localhost' . variable('port')]);

		$site = sluggize($siteAt);
		$networkUrls[OTHERSITEPREFIX . $site] = $url;

		$status = variable('local') ? "\r\n\r\nstatus: " . $siteObj['Status'] : '';
		$imgPrefix = $url . ($slug = $item['safeName'][0][$valueIndex]);

		$link = replaceItems('<a class="site-icon" href="%href%" target="_blank" title="%name% &mdash; %byline%">' .
				'<img src="%src%" height="30px" />  %text%</a>', [
			'href' => $url,
			'name' => $name = $item['name'][0][$valueIndex],
			'byline' => ($byline = $item['byline'][0][$valueIndex]) . $status,
			'src' => $imgPrefix . '-icon.png',
			'text' => $item['iconName'][0][$valueIndex],
			], '%');

		$allSiteItems[] = $thisItem = [
			'url' => $url,
			'siteAt' => $siteAt,
			'safeName' => $slug,
			'name' => $name,
			'byline' => $byline,
			'description' => $item['footer-message'][0][$valueIndex],
			'icon-link' => $link,
			'img-prefix' => $imgPrefix,
			'status' => $siteObj['Status'],
			'category' => $siteObj['Category'],
		];

		if (!$siteObj['Matched']) continue;

		$networkItems[$siteAt] = $thisItem;

		if ($networkHome == $siteAt) {
			variable('networkHome', $thisItem);
			if ($thisUrl == $url) {
				$scaffold = variableOr('scaffold', []);
				$scaffold[] = 'our-network';
				variable('scaffold', $scaffold);
			}
		}
	}

	$networkUrls['network-assets'] = variable(assetKey(NETWORKASSETS));
	variable('networkItems', $networkItems);
	variable('allSiteItems', $allSiteItems);
	variable('networkUrls', $networkUrls);
}

runFrameworkFile('cms');

if (disk_file_exists($cms = SITEPATH . '/cms.php'))
	disk_include_once($cms);

if (hasPageParameter('embed')) variable('embed', true);

if (function_exists('after_framework_config')) after_framework_config();
render();
