<?php
class CanvasTheme {
	static function HeadCssFor($page, $css) {
		$base = getThemeBaseUrl();
		$demo = $base . 'demos/' . $page . '/';
		if ($page == 'spa') {
			//$demo = '//canvastemplate.com/demo/spa/';
			$css[] = sprintf(CSSTAG, $demo . 'spa.css');
			$css[] = sprintf(CSSTAG, $demo . 'css/fonts/spa-icons.css');
		}
		return $css;
	}
}
