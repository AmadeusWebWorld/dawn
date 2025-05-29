<?php
///Tag Helpers

function currentUrl() {
	return pageUrl(variable('all_page_parameters'));
}

function currentLevel($wrap = true) {
	if (hasVariable('page_parameter3'))
		$level = 'Sub Sub Page';
	if (hasVariable('page_parameter2'))
		$level = 'Sub Page';
	else if (hasVariable('page_parameter1'))
		$level = 'Page';
	else
		$level = variable('node') == variable('section') ? 'Section' : 'Site';

	return $wrap ? ' (' . $level . ')' : $level;
}

function pageUrl($relative = '') {
	if ($relative == '') return variable('page-url');
	$hasQuerysting = contains($relative, '?');
	$hasHash = contains($relative, '#');
	if (!endsWith($relative, '/') && !$hasHash && !$hasQuerysting)
		$relative .= '/';
	return variable('page-url') . $relative;
}

function scriptSafeUrl($url) {
	return $url . variableOr('scriptNameForUrl', '');
}

function fileUrl($relative = '') {
	return variable('assets-url') . $relative;
}

function searchUrl($all = false) {
	if (contains(variable('node'), 'search'))
		return variable('page-url') . ($relative = $all ? variable('all_page_parameters') : variable('node')) . '/';
	return variable('page-url') . 'search/';
}

function cssClass($items) {
	if (!count($items)) return '';
	return ' class="' . implode(' ', $items) . '"';
}

//TODO: section tag cleanup!
function section($what = 'start', $h1 = '', $feature = false) {
	if ($h1) $h1 = sprintf('<h1%s>' . $h1 . '</h1>', $feature ? ' class="amadeus-icon"' : ''); 
	echo $what == 'start' ? variable('nl') . '<section>' . $h1 . variable('nl') : '</section>' . variable('2nl');
}

function sectionId($id, $class = '') {
	$attrs = '';
	if ($id) $attrs .= ' id="' . $id . '"';
	if ($class) $attrs .= ' class="' . $class . '"';
	echo variable('nl') . '<section' . $attrs . '>' . variable('nl');
}

function iframe($url, $wrapContainer = true) {
	if ($wrapContainer) echo '<div class="video-container">';
	echo '<iframe src="' . $url . '" style="width: 100%; height: 90vh;"></iframe>';
	if ($wrapContainer) echo '</div>';
}

function cbWrapAndReplaceHr($raw) {
	if (variable('no-content-boxes')) return $raw;

	$closeAndOpen = ($end = contentBox('end', '', true)) . ($start = contentBox('', '', true));
	//TODO: asap! if (substr_count($raw, HRTAG) > 3) runFeature('page-menu');
	return $start . str_replace(HRTAG, $closeAndOpen, $raw) . $end;
}

function cbCloseAndOpen() {
	return contentBox('end', '', true) . contentBox('', '', true);
}

function _getCBClassIfWanted($additionalClass) {
	$no = variable('no-content-boxes');
	if ($no && $additionalClass == '') return '';
	$classes = [];
	if ($additionalClass) $classes[] = $additionalClass;
	if (!$no) $classes[] = 'content-box';
	return implode(' ', $classes);
}

function contentBox($id, $class = '', $return = false) {
	if ($id == 'end') {
		$result = variable('nl') . '</div>' . variable('2nl');
		if ($return) return $result;
		echo $result;
		return;
	}

	$attrs = '';
	if ($id) $attrs .= ' id="' . $id . '"';

	$all = _getCBClassIfWanted($class);
	if ($all) $attrs .= ' class="' . $all . '"';

	$result = variable('nl') . '<div' . $attrs . '>' . variable('nl');
	if ($return) return $result;
	echo $result;
}

function div($what = 'start', $h1 = '', $class = 'video-container') {
	if ($h1) $h1 = '<h1>' . $h1 . '</h1>';
	echo $what == 'start' ? '<div class="' . $class . '">' . $h1 . variable('nl') : '</div>' . variable('2nl');
}

function h2($text, $class = '', $return = false) {
	if ($class) $class = ' class="' . $class . '"';
	$result = '<h2'.$class.'>';
	$result .= renderSingleLineMarkdown($text, ['echo' => false]);
	$result .= '</h2>' . variable('nl');
	if ($return) return $result;
	echo $result;
}

function listItem($html) {
	return '	<li>' . $html . '</li>' . variable('nl');
}

///Internal Variables & its replacements

function pipeToBR($raw) {
	$replaces = [
		'|' => BRNL,
	];
	return replaceItems($raw, $replaces);
}

function csvToHashtags($raw) {
	$begin = '<a class="hashtag fs-4">#';
	$end = '</a>';
	$replaces = [
		', ' => $end . ' ' . $begin,
	];
	return $begin . replaceItems($raw, $replaces) . $end;
}

function replaceSpecialChars($html) {
	$replaces = [
		'|' => variable('nl'),
		'–' => ' &mdash; ',
		'’' => '\'',
		'“' => '"',
		'”' => '"',
		'®' => '&reg;',
	];
	return replaceItems($html, $replaces);
}

function getHtmlVariable($key) {
	return subVariable('htmlSitewideReplaces', '%' . $key . '%');
}

function replaceHtml($html) {
	//TODO: MEDIUM: Warning if called before bootstrap!
	$key = 'htmlSitewideReplaces';
	$replaces = variable($key);
	if (!$replaces) {
		$section = variable('section');
		$node = variable('node');
		$safeUrl = variable('page-url');
		variable($key, $replaces = [
			//Also, we should incorporate dev tools like w3c & broken link checkers
			'%url%' => variable('page-url'),
			'%assets%' => variableOr('assets-override', variable('assets-url') . 'assets/'),
			'%node%' => variable('node'),

			'%core-url%' => scriptSafeUrl(variable('app')),
			'%amadeus-url%' => scriptSafeUrl(variable('main')),
			'%world-url%' => scriptSafeUrl(variable('world')),
			'%network-url%' => variableOr('network-static', '##no-network-url') . basename(SITEPATH) . '/',

			'%phone%' => variableOr('phone', ''),
			'%email%' => variableOr('email', ''),
			'%whatsapp-number%' => $wa = variableOr('whatsapp', '##no-number-specified'),
			'%whatsapp%' => $wame = 'https://wa.me/'. $wa . '?text=',

			'%address%' => variableOr('address', '[no-address]'),
			'%address-url%' => variableOr('address-url', '#no-link'),

			'%siteName%' => $sn = variable('name'),
			'%safeName%' =>  variable('safeName'),
			'%section%' => $section, //let archives break!
			'%section_r%' => humanize($section),
			'%site-engage-btn%' => engageButton('site', 'Engage With Us', 'inline'),

			'%node-url%' => $section ? $safeUrl . $node . '/' : '##not-in-a-node',
			'%node-assets%' => $section && $section != $node ? variable('assets-url') . $section . '/' . $node . '/assets/' : '##not-in-a-node',
			'%nodeSiteName%' => variableOr('nodeSiteName', '##not-in-a-node'),

			'%page-url%' => variable('page_parameter1') ? $safeUrl . $node . '/' . variable('page_parameter1') . '/' : '##not-in-a-page',
			'%sub-page-url%' => variable('page_parameter2') ? $safeUrl . $node . '/' . variable('page_parameter1') . '/'  . variable('page_parameter2') . '/' : '##not-in-a-sub-page',
			'%page-location%' => $loc = title('params-only'),

			'%enquiry%' => str_replace(' ', '+', 'enquiry (for) ' . $sn . ' (at) ' . $loc),
			'%optional-content-box-class%' => _getCBClassIfWanted(''),
			'<marquee>' => variable('_marqueeStart'),
		]);
		variable('whatsapp-txt-start', $wame);
	}

	return replaceItems($html, $replaces);
}

variable('_marqueeStart', '<marquee onmouseover="this.stop();" onmouseout="this.start();">');
variable('_errorStart', '<div style="padding: 20px; font-size: 130%; font-weight: bold; background-color: #fee; margin-top: 20px;">');

function togglingH2($text, $initialArrow = 'down') {
	return '<h2 class="amadeus-icon toggle-parent-panel mb-3">'
		. '<span class="heading-text">' . $text . '</span><span class="toggle-icon icofont-arrow-' . $initialArrow . '"></h2>';
}

function featureHeading($id, $return = 'full', $text = false) {
	$bits = explode('-', $id, 2);
	$whitelabelled = variable('whitelabelled-features');
	$link = $whitelabelled ? '' : ' &mdash; ' . makeLink('?', variable('old-main') . 'features/' . $bits[0] . '/', false);
	if ($return == 'link-only') return $link;

	if (!$text) $text = '';
	if ($bits[0] == 'site') $what = 'site';
	else if ($bits[0] == 'statistics') $what = 'statistics';
	else $what = $id;

	switch ($what) {
		case 'engage': $text = 'Send a message to ' . variable('name'); break;
		case 'seo': $text = 'SEO Info for ' . variable('name'); break;
		case 'share-form': $text = 'Share Link (with Google tracking)'; break;
		case 'assistant': $text = ''; break;
		case 'assistant-voice': $text = 'Voice Controls'; break;
		case 'tree': $text = 'Family Tree of ' . humanize(variable('node')); break;
		case 'links': $text = 'Quick Links'; break;
		case 'site': if (!$text) $text = $bits[1]; break;
		case 'statistics': $text = 'Statistics ' . humanize($bits[1]); break;
	}

	if ($return == 'text') return $text;
	if ($text) $text .= $link;

	$class = $whitelabelled ? ' whitelabelled' : '';
	$class .= in_array($what, ['statistics', 'site', 'links', 'assistant-toc']) ? ' ' . variable('toggle-list') : '';
	return '	<h2 id="amadeus-' . $id . '" class="amadeus-icon' . $class . '">'
		 . ($return == 'h2-start' ? '' : $text . '</h2>' . variable('nl'));
}

variable('_engageButtonFormat', '<a href="javascript: void(0);" class="btn btn-primary btn-%class% toggle-engage" data-engage-target="engage-%id%">%name%</a>');

function engageButton($id, $name, $class, $scroll = false) {
	if ($scroll) $class .= ' engage-scroll';
	$class .= ' btn-fill';
	return replaceItems(variable('_engageButtonFormat'), ['id' => $id, 'name' => $name, 'class' => $class], '%') . variable('nl');
}

///Other Amadeus Stuff
function makePLImages($prefix, $echo = true) {
	$prefix = fileUrl($prefix);
	$format = '<img src="%s-%s.jpg" class="img-fluid show-in-%s">' . variable('nl');
	$result =
		sprintf($format, $prefix, 'portrait', 'portrait') .
		sprintf($format, $prefix, 'landscape', 'landscape');
	if (!$echo) return $result;
	echo $result;
}

/// Expects the whole link(s) html to be provided so href to target blank and mailto can be substituted.
function prepareLinks($output) {
	$output = str_replace(pageUrl(), '%url%', $output); //so site urls dont open in new tab. not sure when this became a problem. maybe a double call to prepareLinks as the render methods got more complex.
	$output = str_replace('href="http', 'target="_blank" href="http', $output); //yea, baby! no need a js solution!
	$output = str_replace('href="mailto', 'target="_blank" href="mailto', $output); //if gmail in chrome is the default, it will hijack current window
	$output = str_replace('%url%', pageUrl(), $output);

	//undo wrongly added blanks
	$output = str_replace('rel="preconnect" target="_blank" ', 'rel="preconnect" ', $output); //new nuance
	$output = str_replace('target="_blank" href="https://fonts.googleapis.com', 'href="https://fonts.googleapis.com', $output);
	$output = str_replace('target="_blank" target="_blank" ', 'target="_blank" ', $output);

	$output = str_replace('href="https://wa.me/', 'rel="nofollow" href="https://wa.me/', $output); //throws errorcode=429, too many requests while crawling
	$output = str_replace('target="_blank" rel="nofollow" target="_blank" rel="nofollow" ', 'target="_blank" rel="nofollow" ', $output); //multiple calls :(

	//TODO: " class="analytics-event" data-payload="{clickFrom:'%safeName%' //leave end " as a hack to pile on attributes
	$campaign = isset($_GET['utm_campaign']) ? '&utm_campaign=' . $_GET['utm_campaign'] : '';
	$output = str_replace('#utm', '?utm_source=' . variable('safeName') . $campaign, $output);

	return $output;
}

function specialLinkVars($item) {
	extract($item);
	//$url sent
	$text = $name;

	if ($type == 'email') $classType = 'fa-classic bg-info fa-envelope';
	if ($type == 'phone') $classType = 'fa-classic bg-info fa-solid fa-phone';

	$class = isset($classType) ? $classType : 'fa-brands fa-'. $type . ' bg-' . $type;

	if ($type == 'phone') {
		$url = 'tel:' . $url;
	}

	if ($type == 'whatsapp') {
		$url = 'https://wa.me/' . replaceItems($url, ['+' => '', '-' => '', '.' => '']);
	}

	if ($type == 'email') {
		$url = 'mailto:' . $url . '?subject=' . replaceItems($text, [' ' => '+']);
	}

	return compact('text', 'url', 'class');
}

function makeRelativeLink($text, $relUrl) {
	return '<a href="' . pageUrl($relUrl) . '">' . $text . '</a>';
}

DEFINE('EXTERNALLINK', 'external');

function makeLink($text, $link, $relative = true, $noLink = false) {
	if ($noLink) return $text; //Used when a variable needs to control this, else it will be a ternary condition, complicating things
	if ($relative == EXTERNALLINK) $link .= '" target="_blank'; //hacky - will never 
	else if ($relative) $link = pageUrl($link);
	return prepareLinks('<a href="' . $link . '">' . $text . '</a>');
}

function getLink($text, $href, $class = '', $target = false) {
	$target = $target ? ' target="' . (is_bool($target) ? '_blank' : $target) . '"' : '';
	if ($class && !contains($class, 'class="')) $class = ' class="' .  $class . '"';
	$params = compact('text', 'href', 'class', 'target');
	return replaceItems('<a href="%href%"%class%%target%>%text%</a>', $params, '%');
}

function getIconSpan($what = 'expand', $size = 'large') {
	$theme = variable('theme');
	if ($theme == 'biz-land') {
		$classes = [
			'expand' => 'icofont-expand',
			'expand-swap' => 'icofont-collapse',
			'toggle' => 'icofont-toggle-on',
			'toggle-swap' => 'icofont-toggle-off',
		];
		$sizes = ['large' => 'icofont-2x', 'normal' => 'icofont'];
		return '<span data-add="' . $classes[$what . '-swap'] . '" data-remove="' . $classes[$what] . '" class="icon ' . $classes[$what] . ' ' . $sizes[$size] . '"></span>';
	}
}

function getThemeIcon($id, $size = 'normal')  {
	return '<span class="icofont-1x icofont-' . $id . '"></span>';
}

function body_classes($return = false) {
	$op = [];

	$op[] = 'site-' . variable('safeName');
	$op[] = 'theme-' . variable('theme');
	if (hasVariable('sub-theme')) $op[] =  'sub-theme-' . variable('sub-theme');

	$op[] = 'node-' . variable('node');
	$op[] = 'page-' . (isset($_GET['share']) ? 'share' : str_replace('/', '_', variable('all_page_parameters')));

	$op[] = 'mobile-click-to-expand'; //TODO: configurable!

	if (hasVariable('ChatraID')) $op[] = 'has-chatra';

	$op = implode(' ', $op);
	if ($return) return $op;
	echo $op;
}

function isMobile() {
	//CREDITS: https://stackoverflow.com/a/48385715

	//-- Very simple way
	$useragent = $_SERVER['HTTP_USER_AGENT']; 
	$iPod = stripos($useragent, "iPod"); 
	$iPad = stripos($useragent, "iPad"); 
	$iPhone = stripos($useragent, "iPhone");
	$Android = stripos($useragent, "Android"); 
	$iOS = stripos($useragent, "iOS");
	//-- You can add billion devices 

	return ($iPod||$iPad||$iPhone||$Android||$iOS);
}

function error($html, $renderAny = false, $settings = []) {
	$settings['echo'] = false;
	if ($renderAny) $html = renderAny($html, $settings);
	echo variable('_errorStart') . $html . '</div>';
}

function debug($function, $vars) {
	if (is_debug()) echo variable('2nl') . '<!--FUNCTION CALLED: ' . $function . ' - ' . print_r($vars, true) . '-->';
}

function raiseParameterError($message, $first, $later = []) {
	foreach ($later as $key => $value) $first[$key] = $value;
	parameterError($message, $first);
}

DEFINE('DODIE', true);
DEFINE('DOTRACE', true);
DEFINE('DONTTRACE', false);

function peDie($msg, $param, $trace = false) {
	parameterError($msg, $param, $trace, DODIE);
}

function parameterError($msg, $param, $trace = true, $die = false) {
	if (startsWith($msg, '$')) $msg = 'PARAMETER ERROR: ' . $msg;
	echo variable('_errorStart') . $msg . '<hr><pre>' . print_r($param, 1);
	if ($trace) { echo '</pre><br>STACK TRACE:<hr><pre>'; debug_print_backtrace(); }
	echo '</pre></div>';
	if ($die) die();
}
