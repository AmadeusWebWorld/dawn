<?php
if (!variable('local')) peDie('Git Tools', ['message' => 'Nice Try!']);

contentBox('git', 'container');
echo '<h1>Searching In Repos:</h1>';

$sheet = getSheet(NODEPATH . '/view/git-accounts.tsv', false);
$sources = [];

foreach ($sheet->rows as $item) {
	$item = rowToObject($item, $sheet);

	h2('Repos Of: ' . getLink($item['Slug'], _urlOf($item), '', true));

	if ($item['Provider'] == 'GitHub')
		$repos = _gitHubToOurs(_urlOf($item, true));

	$sources[] = $repos;
	echo 'Found: ' . count($repos) . ' repos with names:';
	echo implode(NEWLINE, array_map(function ($repo) { return BRNL . ' &bull; ' . getLink($repo['repo_name'], $repo['repo_url'], '', true) . ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ' . 
		($repo['website'] ? getLink($repo['website'], $repo['website'], '', true) : '(empty)' ). BRNL . '<i>' . $repo['description'] . '</i>' ; }, $repos));

	//break;
	echo '<hr />' . NEWLINE;
}

$op = [];
foreach ($sources as $repos) {
	foreach ($repos as $repo) {
		if (count($op) == 0)
			$op[] = '#' . implode('	', array_keys($repo))
				. NEWLINE . '|is-table-with-template'
				. NEWLINE . '||use-template: for-repositories';
		$op[] = implode('	', array_values($repo));
	}
}
$op[] = '';
file_put_contents(__DIR__ . '/repo-list.tsv', implode(NEWLINE, $op));
echo 'Written ' . (count($op) - 2) . ' lines to ' . getLink('repo list', '../repo-list/');

contentBox('end');

function _urlOf($item, $forAPI = false) {
	if ($item['Provider'] == 'GitHub')
		return 'https://' . ($forAPI ? 'api.' : '') . 'github.com/' . $item['Type'] . '/' . $item['Slug'] . ($forAPI ? '/repos' : '');

	peDie('Unsupported Git Provider', $item);
}

function _gitHubToOurs($url) {
	$raw = getJsonFromUrl($url);
	$op = [];
	$skip = hasPageParameter('skip');

	foreach ($raw as $item) {
		if ($skip && !$item['homepage']) continue;

		$op[$item['name']] = [
			'key' => $item['full_name'],
			'id' => $item['id'],

			'owner' => $item['owner']['login'],
			'owner_url' => $item['owner']['html_url'],
			'owner_type' => $item['owner']['type'],

			'repo_name' => $item['name'],
			'repo_url' => $item['html_url'],
			'repo_git_url' => $item['git_url'],

			'description' => $item['description'],

			'created' => $item['created_at'],
			'updated' => $item['updated_at'],

			'website' => $item['homepage'],
		];
	}

	ksort($op);
	return $op;
}
