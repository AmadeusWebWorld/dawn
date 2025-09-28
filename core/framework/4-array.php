<?php
function itemOr($array, $key, $default = false) {
	return isset($array[$key]) ? $array[$key] : $default;
}

function replaceItems($text, $array, $wrap = '', $arrayCheck = false) {
	foreach($array as $key => $value) {
		if ($arrayCheck && is_array($value)) continue;
		$key = $wrap . $key . $wrap;
		$text = str_replace($key, $value, $text);
	}

	return $text;
}

function replaceDictionary($text, $array) { return replaceVars($text, $array); }


function valueIfSet($array, $key, $default = false) {
	return isset($array[$key]) ? $array[$key] : $default;
}

DEFINE('TYPENOCHANGE', 'no-change');
DEFINE('TYPEBOOLEAN', 'bool');
DEFINE('TYPEARRAY', 'array');

function valueIfSetAndNotEmpty($array, $key, $default = false, $type = TYPENOCHANGE) {
	return isset($array[$key]) && $array[$key] ? parseAnyType($array[$key], $type) : $default;
}

function parseAnyType($val, $type) {
	if ($type == TYPENOCHANGE) return $val;
	if ($type == TYPEBOOLEAN) {
		$false = in_array($val, [false, 'false', 'no', '0']);
		return !$false && in_array($val, [true, 'true', 'yes', '1']);
	} else if ($type == TYPEARRAY) {
		return explode(', ', $val);
	}

	parameterError('unsupported type', $type, false);
}

function arrayIfSetAndNotEmpty($array, $key, $default = false) {
	if (!isset($array[$key]) || !$array[$key])
		return $default ? [$default] : [];

	$value = $array[$key];
	if (!is_array($value)) $value = [$value];
	if ($default) $value[] = $default;
	return $value;
}

function textToArray($line) {
	$r = [];
	$items = explode(', ', $line);
	foreach ($items as $item) {
		$bits = explode(': ', $item, 2);
		$r[$bits[0]] = $bits[1];
	}
	return $r;
}

function replaceValues($text, $array) {
	foreach($array as $key => $value) $text = str_replace('%' . $key . '%', $value, $text);
	return $text;
}

function concatSlugs($params, $sep = '/') {
	return implode($sep, $params);
}

function getShuffledItems($items, $count = 1) {
	$ic = count($items);

	if ($ic == 0) return [];
	else if ($count >= $ic) $count = $ic;

	$keys = array_rand($items, $count);
	if ($count == 1) $keys = [$keys];
	$new = [];
	foreach ($keys as $key) $new[$key] = $items[$key];
	return $new;
}

function getConfigValues($file) {
	if (!disk_file_exists($file)) return false;

	$lines = textToList(file_get_contents($file));
	$config = [];

	foreach ($lines as $kv) {
		$bits = explode(': ', $kv, 2);
		$config[$bits[0]] = $bits[1];
	}

	return $config;
}

function getRange($array, $upto, $exclude = []) {
	if ($upto >= count($array)) return $array;
	$op = [];
	foreach ($array as $item) {
		if (count($op) == $upto) break;
		$op[] = $item;
	}
	return $op;
}

function arrayFirst($gp, $key) {
	if (!isset($gp[$key])) die('unable to find ' . $key . ' in user info');
	$a = $gp[$key];
	if (count($a) > 1) die('duplicates found for ' . $key . ' in user info');
	return $a[0];
}

//Moved from SHEET Section
function arrayGroupBy($array, $index, $urlize = false)
{
	$r = array();
	foreach ($array as $i)
	{
		$key = $i[$index];
		if ($urlize) $key = urlize($key);
		if (!isset($r[$key])) $r[$key] = array();
		$r[$key][] = $i;
	}
	return $r;
}

///JSON Functions
function jsonToArray($name) {
	$raw = disk_file_get_contents(contains($name, '/') ? $name : SITEPATH . '/data/' . $name . '.json');
	return json_decode($raw, true);
}

function getJsonFromUrl($url) {
	$context = stream_context_create([ 'http' => [ 'header' => "User-Agent: Chrome/126.0.0.0\r\n" ] ]);
	return json_decode(file_get_contents($url, false, $context), true);
}

function textToList($data) {
	$r = array();
	$lines = explode(variable('safeNL'), $data);
	foreach ($lines as $lin)
	{
		$lin = trimCrLf($lin);
		if ($lin == '' || $lin[0] == '|' || $lin[0] == '#') continue;
		$r[] = $lin;
	}
	return $r;
}

DEFINE('SINGLEFILECONTENT', 'rest-of-content');

function parseMeta($raw) {
	$bits = explode('//meta', $raw);
	if (count($bits) == 1) return false;

	$lines = explode(SAFENEWLINE, $bits[1]);
	$r = []; //SINGLEFILECONTENT => substr($bits[2], strlen('-->'))];

	foreach ($lines as $line) {
		$line = trimCrLf($line);
		if ($line == '') continue;

		$kv = explode(': ', $line, 2);
		if (count($kv) > 1) {
			$r[$kv[0]] = $kv[1];
		}
	}

	return $r;
}

DEFINE('VALUESTART', '||');

///SHEET (TSV) FUNCTIONS
function tsvToSheet($data) {
	$rows = [];
	$columns = null;
	$values = [];
	$lines = explode(variable('safeNL'), $data);

	foreach ($lines as $line)
	{
		$line = trimCrLf($line);
		if ($line == '') continue;

		if ($line[0] == '#') {
			if ($columns != null) parameterError('Set Columns Only Once', [$columns, $line], DOTRACE, DODIE);
			$columns = array_flip(explode("	", substr($line, 1)));
			continue;
		}

		if ($line[0] == '|')
		{
			if (substr($line, 0, 2) == VALUESTART) {
				$bits = explode(': ', substr($line, strlen(VALUESTART)), 2);
				$value = str_replace('||',variable('brnl'), $bits[1]);
				$values[$bits[0]] = $value; //dbc - let it throw
			}

			continue;
		}

		$rows[] = explode("	", $line);
	}

	return compact('rows', 'columns', 'values');
}

function tsvSetCols($lin, &$c)
{
	$lin = substr($lin, 1);
	$r = explode("	", $lin);
	$c = new stdClass();
	foreach ($r as $key => $value)
	{
		$value = trim($value);
		$c->$value = trim($key);
	}
}

function _sheetPath($name) {
	return endsWith($name, '.tsv') ? $name
		: SITEPATH . '/data/' . $name . '.tsv';
}

function sheetExists($name) {
	return disk_file_exists(_sheetPath($name));
}

class sheet {
	public array $columns;
	public array $rows;
	public array $values;
	public array | null $group;

	public function firstOfGroup($key, $else = false) {
		return isset($this->group[$key]) ? $this->group[$key][0] : $else;
	}

	public function hasColumn($columnName) {
		return isset($this->columns[$columnName]);
	}

	public function getValue($item, $columnName, $default = '') {
		$result = $item[$this->columns[$columnName]];
		return $result ? $result : $default;
	}

	public function asObject($item) {
		return rowToObject($item, $this);
	}
}

function getSheet($name, $groupBy = 'section', $urlize = false) : sheet {

	$varName = 'sheet_' . $name . '_' . $groupBy;
	if ($existing = variable($varName)) return $existing;

	$file = _sheetPath($name);
	extract(tsvToSheet(disk_file_get_contents($file)));

	$r = new sheet;

	$r->columns = (array)$columns;
	$r->rows = (array)$rows;
	$r->values = (array)$values;
	$r->group = null;

	if($groupBy !== false)
		$r->group = arrayGroupBy($rows, $columns[$groupBy], $urlize);

	variable($varName, $r);
	return $r;
}

function rowToObject($item, $sheet) {
	$result = [];

	foreach ($sheet->columns as $name => $ix)
		$result[$name] = $sheet->getValue($item, $name);

	return $result;
}

DEFINE('ATNODE', 'node');
DEFINE('ATNODEITEM', 'node-item');

function siteHumanize($where = '') {
	if ($where == ATNODE || $where == ATNODEITEM)
		$where = NODEPATH . '/' . ($where == ATNODEITEM ? getPageParameterAt(1, '') . '/' : '') . 'data/node-';

	$file = $where . 'humanize' . ($where == '' ? '' : '.tsv');
	if (!sheetExists($file)) return [];

	$sheet = getSheet($file, false);
	$cols = $sheet->columns;
	$result = [];

	foreach ($sheet->rows as $item)
		$result[$item[$cols['key']]] = $item[$cols['text']];

	return $result;
}

function getPageValue($sectionName, $key, $default = false) {
	$values = variable($cacheKey = 'values_of_' . $sectionName);

	if (!$values) {
		$sheet = variable('rich-page');
		$section = $sheet->group[$sectionName];

		$valueIndex = $sheet->columns['value'];
		$values = [];

		$keys = arrayGroupBy($section, $sheet->columns['key']);
		foreach ($keys as $k => $v)
			$values[$k] = $v[0][$valueIndex];

		variable($cacheKey, $values);
	}

	if ($default && !isset($values[$key])) { echo $default; return; }
	echo !contains($key, 'content') ? $values[$key] : renderSingleLineMarkdown(str_replace('|', variable('nl'), $values[$key])); //NOTE: be strict!
}
