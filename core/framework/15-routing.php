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

DEFINE('SAFENODEVAR', 'safeNode');

DEFINE('USEDNODEVAR', 'usedNodeVars');
variable(USEDNODEVAR, []);
function nodeVarsInUse($append = false) {
	$vars = variable(USEDNODEVAR);
	if (!$append) return $vars;

	$vars[] = $append;
	sort($vars);
	variable(USEDNODEVAR, $vars);
}

function autoSetNode($level, $where, $overrides = []) {
	$section = variable('section');
	$node = variable('node');
	nodeVarsInUse($level);
	if ($node == 'index' OR $section == $node) return;

	$relPath = $level == 0 ? $node : str_replace('\\', '/', 
		substr($where, strlen(SITEPATH . '/' . $section) + 1));
	if ($level > 1) { $bits = explode('/', $relPath); $node = array_pop($bits); }

	$vars = array_merge([
		'nodeSlug' => $relPath,
		assetKey(NODEASSETS) => fileUrl($section . '/' . $relPath . '/assets/'),
		'nodeSiteName' => humanize($node),
		'nodeSafeName' => $node,
		'submenu-at-node' => true,
		'nodes-have-files' => true,
		'nodepath' => $where,
	], $overrides);

	variable('NodeVarsAt' . $level, $vars);
	variables($vars);
}

function ensureNodeVar() {
	if (count($indices = nodeVarsInUse())) {
		$vars = variable('NodeVarsAt' . end($indices));
		$slug = $vars['nodeSlug'];
		DEFINE('NODEPATH', $vars['nodepath']);
	} else {
		$slug = variable('node');
	}
	variable(SAFENODEVAR, $slug);
}
