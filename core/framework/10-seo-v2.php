<?php
function read_seo($file, $inContent = false) {
	if (variable('seo-handled')) return;

	$fileGiven = $file != variable('file');
	if (!$file) return;

	$meta = false;
	if (endsWith($file, '.md')) {
		$raw = disk_file_get_contents($file);
		$meta = parseMeta($raw);
	} else if (endsWith($file, '.tsv')) {
		$meta = getSheet($file, false)->values;
	}

	if (!$meta) {
		$altFile = relatedMetaFile($file);
		if (!disk_file_exists($altFile)) return;
		$raw = disk_file_get_contents($altFile);
		$meta = parseMeta($raw);
	}

	if ($meta) {
		$aboutFields = ['About', 'about'];
		$descriptionFields = ['Description', 'description'];
		$excerptFields = ['Excerpt', 'excerpt'];
		$titleFields = ['Title', 'title'];

		if (variable('omit-long-keywords'))
			$keywordsFields = ['Primary Keyword', 'Related Keywords', 'Keywords', 'keywords'];
		else
			$keywordsFields = ['Primary Keyword', 'Related Keywords', 'Long-Tail Keywords', 'Localized Keywords', 'Keywords', 'keywords'];

		$about = false;
		$description = false; //if meta exists, this is mandatory (but only single)
		$excerpt = false;
		$title = false;
		$keywords = []; //can be multiple

		foreach ($meta as $key => $value) {
			if (contains($value, '%siteName%'))
				$value = replaceItems($value, ['siteName' => variable('name')], '%');

			if (in_array($key, $aboutFields)) {
				$about = $value;
			} else if (in_array($key, $descriptionFields)) {
				$description = $value;
			} else if (in_array($key, $excerptFields)) {
				$excerpt = $value;
			} else if (in_array($key, $keywordsFields)) {
				$keywords[] = $value;
			/* TODO: DECIDE and REMOVE after seeing how seo reacts!
			} else if ($key == SINGLEFILECONTENT) {
				variable(SINGLEFILECONTENT, $value);
			*/
			} else if (in_array($key, $titleFields)) {
				$title = $value;
			}
		}

		$keywords = count($keywords) ? implode(', ', $keywords) : '';

		variable('meta_' . $file, $meta);
		if ($fileGiven && !$inContent) return compact('about', 'title', 'description', 'excerpt', 'keywords', 'meta');

		if ($description) {
			variable('description', $description);
			variable('og:description', $description);
			if ($title) variable('custom-title', $title);
			variable('keywords', $keywords);
			variable('seo-handled', true);
			variable('meta_' . $file, $meta);
			//TODO: do we need to consume singlefilecontent in render? I think not
		}
	}
}

function print_seo() {
	if (variable('meta-rendered') || variable('no-seo-info')) return;
	$file = variable('file');
	if (!$file) return;

	$meta = variable('meta_' . $file);
	if (!$meta) return;

	$show = [
		'Title', 'About', 'Excerpt', 'Description',
		'Salutation', 'Email To', 'Email Cc', 'WhatsApp To',
		'Primary Keyword', 'Keywords', 'Related Keywords', 'Long-Tail Keywords',
		'Date', 'Author', 'Page Custodian', 'Prompted By', 'Published', 'Meta Author',
		'Born', 'Died',
	];

	$info = [];

	foreach ($show as $col) {
		if (!isset($meta[$col])) continue;
		$val = $meta[$col];
		if (contains($col, 'key'))
			$val = csvToHashtags($val);
		$info[$col] = $val;
	}

	echo GOOGLEOFF;
	contentBox('meta', 'container');
	h2('About This Page / SEO Information');
	runFeature('tables');
	_tableHeadingsOnLeft(['id' => 'piece'], $info);
	contentBox('end');
	echo GOOGLEON;
}

function inlineMeta($meta) {
	$show = ['Date', 'Primary Keyword', 'Page Custodian', 'Prompted By', 'Meta Author', 'Author'];
	$info = [];

	foreach ($show as $col) {
		if (!isset($meta[$col])) return;
		$val = $meta[$col];
		if (contains($col, 'key'))
			$val = 'Tagged As: #<b>' . $val . '</b>';
		if (contains($col, 'Author') || contains($col, 'Prompt'))
			$val = '<span title="' . $col . '">' . $val . '</span>';
		$info[$col] = $val;
	}
	return empty($info) ? '<i>No Inline Info Found</i>' : '<hr>' . implode(' / ', $info);
}

function getFolderMeta($folder, $fol, $folName = false, $index = '') {
	$home = $folder . ($fol ? $fol . '/' : ''). 'home.';
	$page = $folder . ($fol ? $fol : ''). '.';

	$name = $folName ? $folName : $fol;
	$about = 'No About Set';
	$tags = 'No Tags Set';
	$inline = '';
	$title = humanize($name);

	$homeExtension = disk_one_of_files_exist($home, FILESWITHMETA);
	$pageExtension = !$homeExtension ? disk_one_of_files_exist($page, FILESWITHMETA) : false;

	if ($homeExtension || $pageExtension) {
		$file = $homeExtension ? $home . $homeExtension : $page . $pageExtension;
		$vars = read_seo($file);

		if ($vars) {
			if (isset($vars['about']))
				$about = pipeToBR($vars['about']);
			else if (isset($vars['description']))
				$about = pipeToBR($vars['description']);

			if (isset($vars['title']) && $vars['title'])
				$title = $vars['title'];

			if (isset($vars['keywords']))
				$tags = hasPageParameter('generate-index') ? $vars['keywords'] : csvToHashtags($vars['keywords']);

			$inline = hasPageParameter('generate-index') ? '' : inlineMeta($vars['meta']);
		}
	}

	return [
		'name_urlized' => $name,
		'name_humanized' => $title,
		'about' => $about . $inline,
		'tags' => $tags,
		'size' => isset($file) ? size_r(filesize($file)) : '-',
		'index' => $index,
	];
}

function seo_info() {
	$item = variable('current_page');
	if (!$item) return;

	echo '<section id="seo-info" class="container" style="padding-top: 30px;">' . NEWLINE;
	echo featureHeading('seo');

	$fmt = '<p><h4>%s</h4>%s</p>' . NEWLINE;

	$cols = ['about', 'description', 'keywords'];
	foreach ($cols as $col) {
		$field = isset($item[$col]) ? $item[$col] : false;
		if ($field) echo sprintf($fmt, ($col != 'about' ? 'SEO ' : '') . humanize($col), $field);
	}

	echo NEWLINE . '</section>' . NEWLINE;
}

function seo_tags($return = false) {
	$fmt = '	<meta name="%s" content="%s">';
	$ogFmt = '	<meta property="%s" content="%s">';

	variable('generator', 'Amadeus Web Builder / CMS at amadeusweb.com');
	$op = [];

	foreach (['generator', 'description', 'keywords', 'og:image', 'og:title', 'og:description', 'og:keywords', 'og:url', 'og:type', 'fb:app_id'] as $key)
		if ($val = variable($key)) $op[] = sprintf(startsWith($key, 'og:') || startsWith($key, 'fb:') ? $ogFmt : $fmt, $key, replaceVariables($val));

	$op = implode(NEWLINE, $op);
	if ($return) return $op;
	echo $op;
}
