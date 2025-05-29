<?php
function variable($name, $val = null)
{
	global $cscore;
	if (!isset($cscore)) $cscore = array();
	if ($val !== null)
		$cscore[$name] = $val;
	else
		return isset($cscore[$name]) ? $cscore[$name] : false;
}

function subVariable($parent, $key)
{
	$a = variable($parent);
	return is_array($a) && isset($a[$key]) ? $a[$key] : false;
}

function subVariableOr($name, $subName, $or)
{
	return hasSubVariable($name, $subName) ? subVariable($name, $subName) : $or;
}

function variables($a)
{
	foreach ($a as $key=>$value)
		variable($key, $value);
}

function variableOr($name, $or, $hasVar = null)
{
	if (!hasVariable($name) && $hasVar !== null) return $hasVar;
	$val = variable($name);
	return hasVariable($name) ? $val : $or;
}

function clearVariable($name) {
	if (!hasVariable($name)) return;
	global $cscore;
	if (!isset($cscore)) $cscore = array();
	unset($cscore[$name]);
}

function hasVariable($key)
{
	global $cscore;
	if (!isset($cscore)) $cscore = array();
	return isset($cscore[$key]);
}

function hasSubVariable($name, $subName)
{
	$a = variableOr($name, []);
	return isset($a[$subName]);
}

function echo_if_var($key)
{
	if(!variable($key)) return;
	echo replaceVariables(variable($key));
}

function is_debug($value = false) {
	$qs = itemOr($_GET, 'debug');
	if ($value == 'verbose') return $qs == 'verbose';
	return $qs || variable('debug');
}

function replaceVariables($text, $vars = 'url, app, app-static, app-static--3p')
{
	if (!is_array($vars)) {
		$bits = explode(', ', $vars);
		$vars = [];
		foreach ($bits as $bit) {
			$vars[$bit] = variable($bit);
		}
	}

	foreach($vars as $key => $value) $text = str_replace('%' . $key . '%', $value, $text);
	return $text;
}
