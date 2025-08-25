<section class="content-box container">
<h2>Icon Explorer</h2>
Here, we show all icons available from font libraries available in the theme.<br /><br />

Start by clicking the theme you are developing with!<br /><br />

<hr />

<h2>Pick Theme => Library</h2>
<?php
$libs = [
	'canvas' => ['file' => 'css/font-icons.css', 'startsWith' => 'oneof: fa, bi, uil + -', 'magnify' => 'icon-3x' ],
	'spa' => ['file' => 'demos/spa/css/fonts/spa-icons.css', 'startsWith' => 'spa-', 'magnify' => '' ],
];

$key = isset($_GET['lib']) ? $_GET['lib'] : array_keys($libs)[0];
$lib = $libs[$key];

addScript('icon-explorer');
$fontFamily = '';
if ($key != 'canvas') {
	includeThemeManager();
	$css = CanvasTheme::IconsFor($key);
	addStyle($css, THEMEASSETS);
	$fontFamily = 'font-family: ' . $key . '!important; '; //else gets overridden
}

foreach ($libs as $k => $v) {
	$text = humanize($k . ' &mdash; ' . $v['file']);
	$url = './?lib=' . $k;
	$sel = $k == $key;
	echo '<a href="' . $url . '">' . $text . '</a><br />';
}

cbCloseAndOpen('container');

echo '<h2>Icon Search By Name</h2>' . NEWLINES2;

echo '<label for="magnify">Magnify:</label> <input id="magnify" value="' . $lib['magnify'] . '" /><br />';
echo '<label for="prefix">Prefix:</label> <input id="prefix" value="' . $lib['startsWith'] . '" /><br />';
echo '<label for="search">Search:</label> <input id="search" /><br />';
echo '<label for="counts">Counts:</label> <input id="counts" /><br />';

cbCloseAndOpen('container');
echo '<div id="icons" class="row">';
echo '</div>';
section('end');
?>

<style type="text/css">
#icons div { text-align: center; padding-bottom: 8px; margin-bottom: 15px; }
#icons div span { text-wrap: pretty; overflow-wrap: break-word; }
label { width: 70px; margin-bottom: 6px; }
#icons span { <?php echo $fontFamily;?> font-size: 250%; }
</style>
