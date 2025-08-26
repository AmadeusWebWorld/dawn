<?php
if (!variable('local')) peDie('Git Tools', ['message' => 'Nice Try!']);

contentBox('git', 'container');
echo '<h1>Searching In Repos:</h1>';

$sheet = getSheet(NODEPATH . '/view/git-accounts.tsv', false);
$sources = [];

DEFINE('LOCATIONPREFIX', 'location: ./');

DEFINE('CARDTEMPLATE', '%repo_link_md% &mdash;> %website_link_md%|%description%');
DEFINE('GITHUBORGLINK',  '[%name%](https://github.com/orgs/%name%/repositories)');
DEFINE('GITHUBUSERLINK', '[%name%](https://github.com/%name%?tab=repositories)');

foreach ($sheet->rows as $item) {
	$item = rowToObject($item, $sheet);

	h2('Repos Of: ' . getLink($item['Slug'], _urlOf($item), '', true));

	if ($item['Provider'] == 'GitHub')
		$repos = _gitHubToOurs(_urlOf($item, true));

	$sources[] = $repos;
	echo 'Found: ' . count($repos) . ' repos with names:' . BRNL . BRNL;
	echo implode(NEWLINE . '<hr style="margin: 6px" />', array_map(function ($repo) { return NEWLINE . returnLine(pipeToBR(replaceItems(CARDTEMPLATE, $repo, '%'))); }, $repos));

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
	$excludeContaining = ['non-amadeus, ', 'amadeus-util, ', 'is-inactive, '];

	foreach ($raw as $item) {
		if ($skip && !$item['homepage']) continue;

		$name = $item['name'];
		$owner = $item['owner'];
		$org = $owner['type'] == 'Organization';
		$ownerLink = replaceItems($org ? GITHUBORGLINK : GITHUBUSERLINK, [ 'name' => $owner['login'] ], '%');

		$location = '--not-set--';
		$description = $item['description'];
		if (startsWith($description, LOCATIONPREFIX)) {
			$bits = explode(', ', $description, 2);
			$location = substr($bits[0], strlen(LOCATIONPREFIX));
			$location .= ($location ? '/' : '') . $name;
			$description = $bits[1];
		}

		$exclude = false;
		foreach ($excludeContaining as $toMatch)
			if (contains($description, $toMatch)) $exclude = true;
		if ($exclude) continue;

		$op[$name] = [
			'id' => $item['id'],
			'name' => $name,

			'owner_link_md' => $ownerLink,
			'repo_link_md' => '[' . $name . '](' . $item['html_url'] . ')',

			'location' => $location,
			'clone_url' => $item['clone_url'],
			'description' => $description,

			'website_link_md' => !$item['homepage'] ? '--empty--' : '[' . $item['homepage'] . '](' . $item['homepage'] . ')',

			'created' => $item['created_at'],
			'updated' => $item['updated_at'],
		];
	}

	ksort($op);
	return $op;
}
