<?php
if (!variable('local')) peDie('Git Tools', ['message' => 'Nice Try!']);

contentBox('git', 'container');
echo '<h1>Searching In Repos:</h1>';

$sheet = getSheet(NODEPATH . '/view/git-accounts.tsv', false);
$sources = [];

DEFINE('CARDTEMPLATE', '<span class="float-right">%owner_link_md%</span>|%repo_link_md% &mdash;> %website_link_md%|%description%|');
DEFINE('GITHUBORGLINK',  '[%name%](https://github.com/orgs/%name%/repositories)');
DEFINE('GITHUBUSERLINK', '[%name%](https://github.com/%name%?tab=repositories)');

foreach ($sheet->rows as $item) {
	$item = rowToObject($item, $sheet);

	h2('Repos Of: ' . getLink($item['Slug'], _urlOf($item), '', true));

	if ($item['Provider'] == 'GitHub')
		$repos = _gitHubToOurs(_urlOf($item, true));

	$sources[] = $repos;
	echo 'Found: ' . count($repos) . ' repos with names:';
	echo implode(NEWLINE . '<hr style="margin: 6px" />', array_map(function ($repo) { return BRNL . returnLine(pipeToBR(replaceItems(CARDTEMPLATE, $repo, '%'))); }, $repos));

	echo cbCloseAndOpen('container') . NEWLINE;
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
file_put_contents(__DIR__ . '/../view/repositories.tsv', implode(NEWLINE, $op));
echo 'Written ' . (count($op) - 2) . ' lines to ' . getLink('repo list', '../../view/repositories/');

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

		$owner = $item['owner'];
		$org = $owner['type'] == 'Organization';
		$ownerLink = replaceItems($org ? GITHUBORGLINK : GITHUBUSERLINK, [ 'name' => $owner['login'] ], '%');

		$op[$item['name']] = [
			//'key' => $item['full_name'],
			'id' => $item['id'],

			'owner_link_md' => $ownerLink,
			'repo_link_md' => '[' . $item['name'] . '](' . $item['html_url'] . ')',

			'description' => $item['description'],

			'website_link_md' => !!$item['homepage'] ? '--empty--' : '[' . $item['homepage'] . '](' . $item['homepage'] . ')',

			'created' => $item['created_at'],
			'updated' => $item['updated_at'],
		];
	}

	ksort($op);
	return $op;
}
