<?php
DEFINE('SITEURLKEY', 'site-url-key'); //typo proof

function _getUrlKeySansPreview() {
	return (variable('local') ? 'local' : 'live') . '-url';
}

function getSiteUrlKey() {
	$usePreview = variableOr('use-preview', false);
	$local = variable('local'); //this is now in before_bootstrap

	//tests preview urls locally
	//$local = false; $preview = true;

	if (!$usePreview) {
		$result = ($local ? 'local' : 'live') . '-url';
		variable(SITEURLKEY, $result);
		return $result;
	}

	$live = variable('live');
	$testSafeHost = variableOr('testingHost', $_SERVER['HTTP_HOST']);
	$preview = hasVariable('preview') ? variable('preview') :
		($local ? !$live : contains($testSafeHost, 'preview'));

	$result = ($local ? 'local-' : 'live-') . ($preview ? 'preview-' : '') . 'url';
	//parameterError('ROUTING', ['key' => $result, 'live' => $live, 'local' => $local, 'preview' => $preview ]);

	variable('preview', $preview);
	variable(SITEURLKEY, $result);
	return $result;
}

DEFINE('MENUNAME', 'menu_name');
DEFINE('FILELOOKUP', 'file_lookup');
DEFINE('MENUITEMS', 'menu_items');
function getSectionKey($slug, $for) {
	return 'this_' . $slug . '_' . $for;
}

function getSectionFrom($dir) {
	return pathinfo($dir, PATHINFO_FILENAME);
}

function autoSetNode($level = 0, $where = null, $overrides = []) {
	$section = variable('section');
	$node = variable('node');

	$bc = array_merge([
		assetKey(NODEASSETS) => fileUrl($section . '/' . $node . '/assets/'),
		'nodeSiteName' => humanize($node),
		'nodeSafeName' => $node,
		'submenu-at-node' => true,
		'nodes-have-files' => true,
	], $overrides);

	if ($level == 1) {
		$slugs = explode(DIRECTORY_SEPARATOR, $where);
		$bc['nodeSlug'] = $relPath = end($slugs);
		$bc[assetKey(PARENTNODEASSETS)] = fileUrl($section . '/' . $relPath . '/assets/');
		DEFINE('PARENTNODEPATH', $where);
		variable('sectionBC1', $bc);
	}

	if ($node == 'index' OR $section == $node) return;
	variables($bc);
	DEFINE('NODEPATH', $where);
}
