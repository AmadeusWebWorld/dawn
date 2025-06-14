<?php
function imageOrText($fol, $prefix, $name, $large = false) {
	$img = $jpg = 'assets/banners/'. $prefix . urlize($name) . '.jpg';
	$img = disk_file_exists(variable('path') . '/' . $img) ? _resolveFile('', STARTATSITE, false) . $img : false;

	$text = $name;
	if ($prefix == 'collection-') {
		$fols = variable('all_collections');
		$text = $fols[$name]['title'];
	}
	$text = '<span class="image-caption">' . $text . '</span>';

	$dims = $prefix == 'category' ? ($large ? [280, 210] : [140, 105]) : ($large ? [600, 240] : [300, 120]);

	if ($img) return sprintf('<img class="img-fluid img-max-300" src="%s" alt="%s" title="%s" />%s', $img . '?version=123', $name, $name, $text);
	return $text;
}

//TODO: Not in use... move to fwk?
function page_banner($url = false) {
	if (!$url && is_array(variable('video-banners')) && array_key_exists(variable('node'), variable('video-banners'))) {
		$video = variable('video-banners')[variable('node')];
		echo '<div class="video-bgd"><div class="container"><div class="video-container"><iframe title="' . $video['title'] . '" src="https://www.youtube.com/embed/' . $video['id'] . '?feature=oembed" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div></div></div>';
		return;
	}

	$imgPath = variable('path') . '/assets/banners/%s.jpg';
	$img = sprintf($imgPath, variable('node'));
	$banner = variable('node');

	if (!disk_file_exists($img) ) {
		if (variable('folderName')) {
			$parent = variable('currentParentMenuRow');
			$banner = variable('folderName');
			$img = sprintf($imgPath, $banner);
			if ($parent) {
				$cols = variable('currentMenuColumns');
				$parentImg = sprintf($imgPath, $parent[$cols->Page]);
				if (disk_file_exists($parentImg)) {
					$img = $parentImg;
					$banner = $parent[$cols->Page];
				}
			}
			if (!disk_file_exists($img) ) return false;
		}
		else
		{
			return false;
		}
	}

	if ($url) return variable('url') . 'assets/banners/' . $banner . '.jpg';
	echo '<img class="banner-img" src="' . (variable('node') == 'index' ? '' : '../') . 'assets/banners/' . $banner . '.jpg" alt="' . $banner . '" />';
}

