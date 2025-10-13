<?php
$onlyCloned = getQueryParameter('cloned');
$onlyToClone = getQueryParameter('to-clone');
$isMobile = variable('is-mobile');

$all = (new linkBuilder('all', nodeValue()))->btnOrOutline('info', $onlyCloned)->make(false);
$cloned = (new linkBuilder('only cloned', nodeValue() . '/?cloned=true'))->btnOrOutline('success', !$onlyCloned)->make(false);
$cloningNeedeed = (new linkBuilder('only to clone', nodeValue() . '/?to-clone=true'))->btnOrOutline('danger', !$onlyToClone)->make(false);

contentBox('git', 'container');

$reposLink = (new linkBuilder('per this', 'repositories'))->btnOutline()->make(false);
h2('Repositories ' . $reposLink . ' &mdash;&gt; ' . $all . ' ' . $cloned . ' ' . $cloningNeedeed);

$sheet = getSheet(__DIR__ . '/repositories.tsv', false);
$paths = getSheet(__DIR__ . '/clone-paths.tsv', 'Name');
$items = [];

DEFINE('LOCATIONNOTSET', 'not-set');

$yes = '<span class="btn btn-success">yes</span>';
$no = '<span class="btn btn-warning">no</span>';
$notSet = '<span class="btn btn-danger">not set</span>';
$clonePaths = ' &mdash; <span class="btn btn-outline-danger"><abbr title="D:\AmadeusWeb\amadeus\dawn\devs\clone-paths.tsv">set path</abbr></span>';

$rows = [];

foreach ($sheet->rows as $repo) {
	$item = $sheet->asObject($repo);

	$nameLookup = $paths->firstOfGroup($item['name']);
	$location = $nameLookup ? $paths->getValue($nameLookup, 'Location') . $item['name'] : LOCATIONNOTSET;
	$exists = $location != LOCATIONNOTSET && disk_is_dir(ALLSITESROOT . $location);

	if ($onlyCloned && !$exists) continue;
	if ($onlyToClone && $exists) continue;

	$actions = '';
	if ($isMobile && $location == LOCATIONNOTSET) {
		$actions = linkBuilder::factory('Clone URL', $item['clone_url'], linkBuilder::copyUrl)
			. ' ' . linkBuilder::factory('Relative Path', $location, linkBuilder::copyRelUrl);
	}

	$row = [
		'name' => returnLine($item['repo_link_md']),
		'owner' => returnLine($item['owner_link_md']),
		'location' => $location == LOCATIONNOTSET ? $notSet . $clonePaths
			: linkBuilder::factory($location, $location, linkBuilder::localhostLink),
		'exists' => ($exists ? $yes : $no) . (!$exists && $location != LOCATIONNOTSET ? ' &mdash; ' . _clone($location, $item) : ''),
		'actions' => $exists && $location != LOCATIONNOTSET ? _pull_and_log($location) : $actions,
		'description' => returnLine($item['description']),
	];

	$rows[$item['name']] = $row; //needed to sort
	continue;
}

ksort($rows);

runFeature('tables');
(new tableBuilder('repo', $rows))->render();

contentBox('end');

function _pull_and_log($location) {
	return _getGuiLink($location, 'pull', 'outline-success') . NEWLINE
		. ' ' . _getGuiLink($location, 'log', 'outline-info') . NEWLINE;
}

function _clone($location, $item) {
	return _getGuiLink($location, 'clone', 'outline-primary', '&git-url=' . $item['clone_url']);
}

function _getGuiLink($site, $action, $classSuffix, $optional = '') {
	$script = 'http://localhost/git-web-ui.php';
	$qs = '?git-action=' . $action . '&site=' . $site . $optional;
	return getLink($action, $script . $qs, 'btn btn-' . $classSuffix, true);
}
