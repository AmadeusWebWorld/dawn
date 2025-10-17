<?php
DEFINE('MORETAG', '<!--more-->');
DEFINE('EXCERPTSTART', '<!--start-excerpt-->');

//added in 8..5
abstract class builderBase {
	public $settings;

	//called in constructor, check so doesnt override
	protected function setDefault($key, $value) {
		if (!isset($this->settings[$key]))
			$this->set([$key => $value]);
		return $this;
	}

	protected function settingIs($key, $value = true) {
		return isset($this->settings[$key]) ? $this->settings[$key] == $value : false;
	}

	function setValue($key, $value) {
		return $this->set([$key => $value]);
	}

	function set($override = []) {
		foreach ($override as $key => $value)
		$this->settings[$key] = $value;
		return $this;
	}

	function unset($keys = []) {
		if (is_string($keys))
			$keys = [$keys];

		foreach ($keys as $key)
			unset($this->settings[$key]);

			return $this;
	}
}

///Tag Helpers
function currentUrl() {
	return pageUrl(variable('all_page_parameters'));
}

function printNodeHeading($noEnd = false) {
	sectionId('node', 'container text-center');
	h2(humanize(nodeValue()), 'amadeus-icon');
	if (!$noEnd)
		section('end');
}

function currentLevel($wrap = true) {
	return '';
	$nodeIsSection = nodeIsSection();
	$isPageWhenSHF = !$nodeIsSection && variable('sections-have-files');
	if (hasVariable('page_parameter3'))
		$level = 'Sub Sub Page';
	if (hasVariable('page_parameter2'))
		$level = 'Sub Page';
	else if (hasVariable('page_parameter1') || $isPageWhenSHF)
		$level = 'Page';
	else
		$level = $nodeIsSection ? 'Section' : 'Site';

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

function searchUrl() {
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

function cbWrapAndReplaceHr($raw, $class = '') {
	if (variable('no-content-boxes')) return $raw;

	$closeAndOpen = ($end = contentBox('end', '', true)) . ($start = contentBox('', $class, true));
	//TODO: asap! if (substr_count($raw, HRTAG) > 3) runFeature('page-menu');
	return $start . str_replace(HRTAG, $closeAndOpen, $raw) . $end;
}

function cbCloseAndOpen($class = '') {
	return contentBox('end', '', true) . contentBox('', $class, true);
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

function standoutH2InCenter($text) {
	contentBox('', 'container text-center standout');
	echo returnLine('## ' . $text);
	contentBox('end');
}

function startDiv($id, $class = '') {
	$attrs = '';
	if ($id) $attrs .= ' id="' . $id . '"';
	if ($class) $attrs .= ' class="' . $class . '"';
	echo variable('nl') . '<div' . $attrs . '>' . variable('nl');
}

function endDiv() {
	echo '</div>';
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
	return replaceItems($raw, [ '|' => BRNL, 'NEWLINE' => NEWLINE ]);
}

function pipeToNL($raw) {
	return replaceItems($raw, [ '|' => NEWLINE ]);
}

function csvToHashtags($raw) {
	if (!contains(',', $raw)) $raw = ', ' . $raw;

	$begin = '<a class="hashtag">#';
	$end = '</a>';
	$replaces = [
		', ' => $end . NEWLINE . ' ' . $begin,
	];

	$offset = strlen('#">' . $end);
	$result = substr(replaceItems($raw, $replaces), $offset);
	if (startsWith($result, $begin . $begin)) $result = substr($result, strlen($begin));
	return $result;
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
		$node = nodeValue();
		variable($key, $replaces = [
			//Also, we should incorporate dev tools like w3c & broken link checkers
			'%url%' => variable('page-url'),
			'%' . OTHERSITEPREFIX . 'core%' => variable('app'),

			'%node-assets%' => _resolveFile('', STARTATNODE),
			'%site-assets%' => _resolveFile('', STARTATSITE),
			'%core-assets%' => _resolveFile('', STARTATCORE),
			'##theme##' => getThemeBaseUrl(),

			'%cdn%' => variableOr('cdn', variable('assets-url') . 'assets/cdn/'),

			'%nodeSlug%' => $node,
			'%nodeName%' => humanize($node),
			'%nodeUrl%' => pageUrl($node),
			'%nodeItem%' => getPageParameterAt(1, ''),
			'%nodeItem2%' => getPageParameterAt(2, ''),
			'%nodeFullUrl%' => pageUrl(variableOr('nodeSlug', '##no-nodeSlug')),
			'%leafNodeAssets%' => variableOr(assetKey(LEAFNODEASSETS), ''),

			'%admin-email%' => variableOr('systemEmail', variableOr('assistantEmail', '#error--no-email-configured')),
			'%email%' => variableOr('email', ''),
			'%phone%' => variableOr('phone', ''),
			'%phone2%' => variableOr('phone2', ''),
			'%whatsapp-number%' => ($wa = variableOr('whatsapp', '##no-number-specified')),
			'%whatsapp%' => $wame = _whatsAppME($wa),
			'%whatsapp2-number%' => ($wa2 = variableOr('whatsapp2', '##no-number2-specified')),
			'%whatsapp2%' => _whatsAppME($wa2),

			'%address%' => variableOr('address', '[no-address]'),
			'%address2%' => variableOr('address2', '[no-address2]'),
			'%timings%' => variableOr('timings', '[no-timings]'),
			'%address-url%' => variableOr('address-url', '#no-link'),

			'%network-link%' => networkLink('btn btn-success', '<hr class="mt-5" />'),
			'%siteName%' => $sn = variable('name'),
			'%safeName%' =>  variable('safeName'),
			'%section%' => $section, //let archives break!
			'%section_r%' => humanize($section),
			'%site-engage-btn%' => engageButton('Engage With Us', 'btn btn-lg btn-site'),

			'%nodeUrlUptoLeaf%' => $loc = variable('all_page_parameters'), //experimental
			'%enquiry%' => str_replace(' ', '+', 'enquiry (for) ' . $sn . ' (at) ' . $loc),
			'%optional-content-box-class%' => _getCBClassIfWanted(''),
			'<marquee>' => variable('_marqueeStart'),

			'--large-list--' => cbCloseAndOpen('large-list'),
		]);
		variable('whatsapp-txt-start', $wame);
	}

	if ($hr = variable('htmlReplaces'))
		$html = replaceItems($html, $hr, '%');

	if ($nw = variable('networkUrls'))
		$html = replaceItems($html, $nw, '%');

	return replaceItems($html, $replaces);
}

variable('_marqueeStart', '<marquee onmouseover="this.stop();" onmouseout="this.start();">');
variable('_errorStart', '<div class="container mt-4 p-5 alert alert-warning text-center" style="border-radius: 25px;">');

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
		case 'tree': $text = 'Family Tree of ' . humanize(nodeValue()); break;
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

function engageButton($name, $class, $scroll = false) {
	if ($scroll) $class .= ' engage-scroll';
	//$class .= ' btn-fill';

	return replaceItems(variable('_engageButtonFormat'), [
		'id' => urlize($name),
		'name' => $name,
		'class' => $class],
	'%') . NEWLINE;
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
	$output = str_replace('~~TARGETNEW', '" target="_blank', $output); //pdf links

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

	/*
	'BTNPHONE' => '~class~btnNBSPbtn-danger~/class~',
	'BTNWHATSAPP' => '~class~btnNBSPbtn-warning~/class~',
	'BTNEMAIL' => '~class~btnNBSPbtn-info~/class~',
	'MORELINK' => '~class~more-link~/class~',
	*/
	$output = bootstrapAndUX::toButtons($output);

	$output = replaceItems($output, [
		'DIV|LARGELIST' => '<div class="large-list">',
		'DIV-CONTAINER' => '<div class="video-container">',
		'DIV-MAX-500-CENTER' => '<div class="m-auto img-max-500">',
		'DIV-CENTER' => '<div class="text-center">',
		'DIV-RIGHT' => '<div class="float-right">',
		'DIV-ROW' => '<div class="row">',
		'DIV-CELL3' => '<div class="col-md-3 col-sm-12">',
		'DIV-CELL4' => '<div class="col-md-4 col-sm-12">',
		'DIV-CELL6' => '<div class="col-md-6 col-sm-12">',
		'DIV-CELL8' => '<div class="col-md-8 col-sm-12">',
		'DIV-CELL9' => '<div class="col-md-9 col-sm-12">',
		'DIV-CLOSE' => '</div>',
	]);

	$output = replaceItems($output, ['/class' => '', 'class' => '" class="', ], '~');
	$output = str_replace('NBSP', ' ', $output);

	return $output;
}

function url_r($url, $domainOnly = false) {
	$url = replaceItems($url, [
		'preview.' => '',
		'https://' => '',
		'http://' => '',
		'www.' => '',
		'//' => '',
	]);
	if (endsWith($url, '/')) $url = substr($url, 0, strlen($url) - 1);
	return $domainOnly ? explode('/', $url)[0] : $url;
}

function _whatsAppME($mob, $txt = '?text=') {
	return 'https://wa.me/' . replaceItems($mob, ['+' => '', '-' => '', '.' => '']) . $txt;
}

function specialLinkVars($item) {
	extract($item);
	//$url sent
	$text = $name;

	if ($type == 'email') $classType = 'fa-classic amadeus-2x-icon rounded-circle bg-info fa-envelope';
	if ($type == 'phone') $classType = 'fa-classic amadeus-2x-icon rounded-circle bg-info fa-solid fa-phone';

	$class = isset($classType) ? $classType : 'amadeus-2x-icon rounded-circle fa-brands fa-'. $type . ' bg-' . $type;

	if ($type == 'phone') {
		$url = 'tel:' . $url;
	}

	if ($type == 'whatsapp') {
		$url = _whatsAppME($url);
	}

	if ($type == 'email') {
		$url = 'mailto:' . $url . '?subject=' . replaceItems($text, [' ' => '+']);
	}

	return compact('text', 'url', 'class');
}

class bootstrapAndUX {
	const colors = ['primary', 'secondary', 'info', 'success', 'warning', 'danger'];

	const namedButtons = [
		'DOWNLOAD' => 'btn btn-lg btn-primary" target="_blank',
		'SITE' => 'btn btn-info',
		'PHONE' => 'danger fa fa-phone',
		'WHATSAPP' => 'warning fab fab-whatsapp',
		'EMAIL' => 'info bi bi-email',
	];

	static $buttonVars = []; //static on demand for optimizing

	private static function buttonVars() {
		if (count(self::$buttonVars) == 0) {
			$btn = 'BTN'; $bigBtn = 'BTNLARGE';
			$start = '" class="m-1 ';
			foreach (self::colors as $color) {
				$colorUpper = strtoupper($color);
				self::$buttonVars[$btn . $colorUpper] = $start . 'btn btn-' . $color;
				self::$buttonVars[$bigBtn . $colorUpper] = $start . 'btn btn-lg btn-' . $color;
			}

			foreach (self::namedButtons as $name => $class)
				self::$buttonVars[$btn . $name] = $start . $class;
		}

		return self::$buttonVars;
	}

	//NOTE: for now, supports single css class only
	static function toButtons($html) {
		if (!contains($html, 'BTN')) return $html;
		return replaceItems($html, self::buttonVars());
	}
}

class linkBuilder extends builderBase {
	//NOTE: cant keep this in buttonsInText as thats an only when needed static class
	const usePageUrl = 'usePageUrl';
	const content = '/?content=1';

	const openFile = 'strip-extension text-suffix humanize outline-info margins lightbox noPageUrl';
	const openFileInline = self::openFile . ' inline';
	const openFileBlock = self::openFile . ' block';
	const localhostLink = 'new-tab localhost btn-secondary noPageUrl';
	const copyOnClick = 'copy noPageUrl btn btn-lg';
	const copyUrl = self::copyOnClick . ' outline-primary';
	const copyRelUrl = self::copyOnClick . ' outline-danger';

	static function factory($text, $href, $setting, $echo = false) {
		$do = explode(' ', $setting);

		if (in_array('strip-extension', $do))
			$text = pathinfo($text, PATHINFO_FILENAME);

		if (in_array('text-suffix', $do))
			$href = $href . '/' . $text;

		if (in_array('localhost', $do))
			$href = 'http://localhost/' . $text . '/';

		if (in_array('humanize', $do))
			$text = humanize($text);

		$attrs = '';
		if (in_array('copy', $do)) {
			$attrs = ' onclick="
			const val = new Blob([this.getAttribute(\'href\')], { type: \'text/plain\' });
			navigator.clipboard.write([new ClipboardItem({\'text/plain\': val})]);
			this.classList.add(\'text-decoration-underline\'); this.classList.add(\'fw-bolder\');
			return false;"';
		}

		$result = new linkBuilder($text, $href);

		if ($attrs)
			$result->attrs = $attrs;

		if (in_array('new-tab', $do))
			$result->target = true;

		foreach (bootstrapAndUX::colors as $color) {
			$break = true;
			if (in_array('btn-' . $color, $do))
				$result->btn($color);
			else if (in_array('outline-' . $color, $do))
				$result->btnOutline($color);
			else
				$break = false;
			if ($break) break;
		}

		if (in_array('margins', $do))
			$result->addClass('m-2');

		if (in_array('inline', $do))
			$result->addClass('d-inline-block');

		if (in_array('block', $do))
			$result->addClass('d-block me-3 mb-3');

		if (in_array('lightbox', $do)) {
			$result->attrs .= ' data-lightbox="iframe"';
			$result->href .= self::content;
		}

		if (in_array('noPageUrl', $do))
			$result->unset(self::usePageUrl);

		return $result->make($echo);
	}

	private $text, $href, $class, $target, $attrs = '';

	function __construct($text, $href, $class = '', $target = false, $settings = [])
	{
		$this->text = $text;
		$this->href = $href;
		$this->class = $class;
		$this->target = $target;
		$this->settings = $settings;
		$this->setDefault(self::usePageUrl, true);
	}

	function btn($color = 'success') {
		$this->addClass('btn btn-' . $color);
		return $this;	
	}

	function btnOutline($color = 'success') {
		$this->addClass('btn btn-outline-' . $color);
		return $this;	
	}

	function btnOrOutline($color = 'success', $outline = false) {
		if ($outline) $this->btnOutline($color);
		else $this->btn($color);
		return $this;	
	}

	private function addClass($class) {
		$this->class .= ($this->class ? ' ' : '') . $class;
		return $this;
	}

	function make($echo = true, $settings = []) {
		$this->set($settings);

		if ($this->settingIs(self::usePageUrl))
			$this->href = pageUrl($this->href);

		$result = getLink(
			$this->text,
			$this->href,
			$this->class,
			$this->target,
			$this->attrs,
		);

		if (!$echo) return $result;
		echo $result;
	}
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

function urlFromSlugs() {
	return pageUrl(urlize(concatSlugs(func_get_args())));
}

function getLink($text, $href, $class = '', $target = false, $attrs = '') {
	$target = $target ? ' target="' . (is_bool($target) ? '_blank' : $target) . '"' : '';
	if ($class && !contains($class, 'class="')) $class = ' class="' .  $class . '"';
	$params = compact('text', 'href', 'class', 'target', 'attrs');
	return replaceItems('<a href="%href%"%class%%target%%attrs%>%text%</a>', $params, '%');
}

function getLinkWithCustomAttr($text, $href, $attr) {
	$href = ' href="' .  $href . '"';
	$params = compact('text', 'href', 'attr');
	return replaceItems('<a %href%%attr%>%text%</a>', $params, '%');
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

DEFINE('BODYCLASSES', 'custom-body-classes');

function add_body_class($name) {
	$items = variableOr(BODYCLASSES, []);
	$items[] = $name;
	variable(BODYCLASSES, $items);
}

function body_classes($return = false) {
	$loadingPollen = wantsPollen();
	$op = [];

	$op[] = 'site-' . variable('safeName');
	$op[] = 'theme-' . variable('theme');
	if (hasVariable('sub-theme')) $op[] =  'sub-theme-' . variable('sub-theme');

	$op[] = 'node-' . nodeValue();
	$op[] = 'page-' . (isset($_GET['share']) ? 'share' : str_replace('/', '_', variable('all_page_parameters')));

	$op[] = 'mobile-click-to-expand'; //TODO: configurable!

	if (hasVariable('ChatraID') && variable('ChatraID') != 'none' && !variable('local') && !$loadingPollen) $op[] = 'has-chatra';

	if (hasVariable(BODYCLASSES)) $op[] = implode(' ', variable(BODYCLASSES));

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
