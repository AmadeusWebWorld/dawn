<?php
startDiv('health', 'container');
startDiv('', 'row');

$sites = variable('allSiteItems');

foreach ($sites as $site) {
	startDiv($site['safeName'], 'col-4 p-3');
	contentBox($site['safeName']);
	$item = replaceItems(pipeToBR('%icon-link% <i>%name%</i>|%byline%|at: "%siteAt%"||status: %status%||%description%' . NEWLINE . '<hr />'), $site, '%');
	echo replaceItems($item, variable('networkUrls'), '%');
	echo '<iframe src="' . $site['url'] . '?health=1" style="height: 50px; width: 100%"></iframe>';
	contentBox('end');
	endDiv();
}

endDiv();
endDiv();
