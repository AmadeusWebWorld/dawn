<?php
/**
 * This php framework is proprietary, Source-available software!
 * It is licensed for distribution at the sole discretion of it's owner Imran.
 * Copyright Oct 2019 -> 2025, AmadeusWeb.com, All Rights Reserved!
 * Author: Imran Ali Namazi <imran@amadeusweb.com>
 * Website: https://dawn.amadeusweb.com/
 * Source:  https://github.com/AmadeusWebInAction/amadeus8
 * Note: AmadeusWeb v8.4 is based on 25 years of Imran's programming experience:
 * https://imran.wiseowls.life/about-me/amadeusweb-dawns/
 */

DEFINE('AMADEUSROOT', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
DEFINE('ALLSITESROOT', dirname(AMADEUSROOT) . DIRECTORY_SEPARATOR);
DEFINE('AMADEUSFRAMEWORK', __DIR__ . DIRECTORY_SEPARATOR);
DEFINE('AMADEUSCORE', dirname(__DIR__) . DIRECTORY_SEPARATOR);
DEFINE('AMADEUSFEATURES', AMADEUSCORE . 'features/');
DEFINE('AMADEUSMODULES', AMADEUSCORE . 'modules/');
DEFINE('AMADEUSTHEMESFOLDER', AMADEUSROOT . 'themes/');
DEFINE('AMADEUSEXTENSIONS', AMADEUSCORE . 'extensions/');

include_once AMADEUSFRAMEWORK . '2-stats.php'; //start time, needed to log disk load in files.php
include_once AMADEUSFRAMEWORK . '3-files.php'; //disk_calls, needed first to measure include times

function runFrameworkFile($name) {
	disk_include_once(AMADEUSFRAMEWORK . $name . '.php');
}

function runModule($name) {
	disk_include_once(AMADEUSMODULES . $name . '.php');
}

function runFeature($name, $variables = []) {
	disk_include_once(AMADEUSFEATURES . $name . '.php', $variables);
}

function runExtension($name) {
	disk_include_once(AMADEUSEXTENSIONS . $name . '/loader.php');
}

runFrameworkFile('4-array');
runFrameworkFile('5-vars');
runFrameworkFile('6-text'); //needs vars
runFrameworkFile('7-html');
runFrameworkFile('8-menu');

//New in 4.1
runFrameworkFile('9-render');
runFrameworkFile('10-seo-v2');
runFrameworkFile('11-assets');

//fresh
runFrameworkFile('12-macros');

//v6.2 - renamed helper to special
runFrameworkFile('13-special');

//v6.5
runFrameworkFile('14-main');

//v7.1
runFrameworkFile('15-routing');

//v8.1
runFrameworkFile('16-theme');

//v8.3
runFrameworkFile('17-pollinator');

//v8.5
runFrameworkFile('18-related');

function before_bootstrap() {
	$port = $_SERVER['SERVER_PORT'];

	$testMobile = 80; //80 for normal, 8000 to simulate mobile/no-url-rewrite
	$isMobile = $testMobile != 80 || startsWith(__DIR__, '/storage/');
	
	variable('port', $port != $testMobile ? ':' . $port : '');
	variable('is_mobile_server', $isMobile);

	variable('local', $local = startsWith($_SERVER['HTTP_HOST'], 'localhost'));
	variable('live', contains(SITEPATH, 'live'));

	variable('app', $url = ($local && !$isMobile ? replaceVariables('http://localhost%port%/dawn/', 'port') : '//dawn.amadeusweb.com/'));

	if (DEFINED('AMADEUSURL')) variable('app', AMADEUSURL);

	variable('app-themes', $url . 'themes/');

	variable(assetKey(COREASSETS, ASSETFOLDER), AMADEUSROOT . 'assets/');
	variable(assetKey(COREASSETS), variable('app') . 'assets/');

	$php = contains($_SERVER['DOCUMENT_ROOT'], 'magique') || contains($_SERVER['DOCUMENT_ROOT'], 'Magique');
	variable('no_url_rewrite', $isMobile || $php);
	if ($isMobile || $php) variable('scriptNameForUrl', 'index.php/'); //do here so we can simulate usage in site.php

	runModule('markdown');
	runModule('wordpress');
}

if (!DEFINED('AMADEUSPRODUCT'))
	before_bootstrap();

//Now this only sets up the node and page parameters - rest moved to before_bootstrap()
function bootstrap($config) {
	variables($config);

	$noRewrite = variable('no_url_rewrite');
	if ($noRewrite) $node = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
	else $node = isset($_GET['node']) && $_GET['node'] ? $_GET['node'] : '';

	if (endsWith($node, '/')) $node = substr($node, 0, strlen($node) - 1);
	if (startsWith($node, '/')) $node = substr($node, 1);

	if ($node == '') $node = SITEHOME;
	variable('all_page_parameters', $node); //let it always be available

	if (strpos($node, '/') !== false) {
		$slugs = explode('/', $node);
		$node = array_shift($slugs);
		variable('page_parameters', $slugs);
		foreach ($slugs as $ix => $slug) variable('page_parameter' . ($ix + 1), $slug);
		variable(LASTPARAM, $ix + 1);
	}

	variable(NODEVAR, variableOr('node-alias', $node));
}

function getPageParameterAt($index = 1, $or = false) {
	return variableOr('page_parameter' . $index, $or);
}

function hasPageParameter($param) {
	return in_array($param, variableOr('page_parameters', [])) || isset($_GET[$param]);
}

function getQueryParameter($param) {
	return isset($_GET[$param]) ? $_GET[$param] : false;
}

function render() {
	if (function_exists('before_render')) before_render();
	ob_start();

	$theme = variable('theme') ? variable('theme') : 'default';
	$embed = variable('embed');

	$folder = variable('path') . '/' . (variable('folder') ? variable('folder') : '');
	$contentExt = disk_one_of_files_exist($contentFWE = $folder . nodeValue() . '.', CONTENTFILES);
	if ($contentExt) {
		read_seo($contentFWE . $contentExt, true);
	}

	if (!$embed) {
		renderThemeFile('header', $theme);
		if (function_exists('before_file')) before_file();
	}

	if (variable('under-construction')) {
		runFeature('under-construction');
		$rendered = true;
	} else if (isset($_GET['share'])) {
		runFeature('share');
		$rendered = true;
	} else if (hasPageParameter('slider')) {
		$rendered = true; //dont want to render content. and needed here as it shouldnt support "content" menu pages
	} else if (variable('skip-content-render')) {
		$rendered = false;
	} else {
		$rendered = false;
		if ($contentExt) {
			$rendered = true;
			autoRender($file = $contentFWE . $contentExt);
			pageMenu($file);
		}
	}

	if (isset($_GET['debug']) || isset($_GET['stats'])) {
		variable('stats', true);
	}

	if (!$rendered) {
		if (function_exists('did_render_page') && did_render_page()) {
			//noop
		} else {
			//NOTE: Uses output buffering magic methods to delay sending of output until 404 header is sent 
			header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
			ob_flush();

			if (isset($_GET['debug'])) {
				echo 'NOTE: Turning on stats so you can additionally see what files are included! This appears below the footer' . variable('brnl') . variable('brnl');

				$verbose = $_GET['debug'] == 'verbose';
				if ($verbose) {
					global $cscore;
					parameterError('ALL AMADEUS VARS - global $cscore;', $cscore);
				}
			}

			$breadcrumbs = variable('breadcrumbs');
			$file = nodeValue(); $message = 'at level 1 (content / section / node)';
			if ($breadcrumbs) {
				$file = BRNL . variableOr('all_page_parameters', 'node/section missing?');
				$message = 'found only these valid params:' . BRNL . BRNL . '<strong>' . implode(' &mdash; ', $breadcrumbs) . '</strong>';
			}
			error('<h1 class="alert alert-danger rounded-pill mt-3 mb-0">Couldn\'t find page:</h1>'
				. '<h2 class="mt-3 mb-3">' . $file . '</h2>' . BRNL . NEWLINE . '<p class="rounded-pill alert alert-secondary">' . $message . '</p>');
		}
	}

	ob_end_flush();

	if (!$embed) {
		if (function_exists('pollenAt')) pollenAt('embed');
		if (function_exists('after_file')) after_file();
		renderThemeFile('footer', $theme); //theme.php is now responsible for calling stats before styles+scipts as the table feature requires its usage and it will be before </body>
	}

	if (function_exists('after_render')) after_render();
}

function copyright_and_credits($separator = '<br>', $return = false) {
	$copy = _copyright(true);
	$cred = _credits('', true);
	$result = $copy . $separator . $cred;
	if ($return) return $result;
	echo $result;
}

function _copyright($return = false) {
	if (variable('dont_show_copyright')) return '';

	$year = date('Y');
	$start = variable('start_year');
	$from = ($start && ($start != $year)) ? $start . ' - ' : '';

	$before = variable('owned-by') ? '<strong>' . variable('name') . '</strong>, ' : '';
	$after = variable('owned-by') ? variable('owned-by') : variable('name');

	$result = '&copy; ' . $before . 'Copyright <strong><span>' . $after . '</span></strong>. ' . $from . $year . ' All Rights Reserved.';
	if ($return) return $result; else echo $result;
}

function _credits($pre = '', $return = false) {
	if (variable('dont_show_amadeus_credits')) return '';
	$world = replaceHtml('%urlOf-world%');

	$url = $world . '?utm_content=site-credits&utm_referrer=' . variable('safeName');
	$result = $pre . sprintf('<a href="%s" target="_blank" class="amadeus-credits" title="Built With AmadeusWeb.com" style="display: inline-block;">' .
		NEWLINE . '			<img src="%s" height="50" alt="%s" style="border-radius: 12px; vertical-align: middle;"></a>',
		$url, $world . 'amadeuswebworld-logo.png', 'Amadeus Web World');

	if ($return) return $result; else echo $result;
}
