<?php
global $disk_calls_total_duration;
global $disk_calls;
global $disk_calls_by_type;
global $diskCache;

$disk_calls_total_duration = 0;
$disk_calls = [];
$disk_calls_by_type = [];
$diskCache = [];

function _diskCached($where, $what) {
	//return null; //saves 11
	$key = $where . '__' . $what;
	global $diskCache;
	if (isset($diskCache[$key]))
		return $diskCache[$key];
	return null; //NOTE: needed to distinguish with false
}

function _diskCache($where, $what, $value) {
	$key = $where . '__' . $what;
	global $diskCache;
	$diskCache[$key] = $value;
}

//NB: timer start and end from MicroVC
function _disk_start() {
	global $diskBegan;
	$time = explode( ' ', microtime() );
	$diskBegan = $time[1] + $time[0];
}

function _disk_end()
{
	global $diskBegan;
	$time = explode( ' ', microtime() );
	$done = $time[1] + $time[0];

	$taken = $done -$diskBegan;

	global $disk_calls_total_duration;
	$disk_calls_total_duration += $taken;

	//NOTE: timeER - Exact and Readable
	return [$taken, time_r($taken, 'micro')];
}

function time_r($time, $what, $exact = false) {
	if ($what == '') {
		$precision = 3; $multiplier = 1;
	} else if ($what == 'milli') {
		$precision = 6; $multiplier = 1000;
	} else if ($what == 'micro') {
		$precision = 9; $multiplier = 1000 * 1000;
	}

	$time = number_format($time, $precision);

	$timeExact = $time * $multiplier;
	if ($exact) return $timeExact;

	return sprintf($timeExact . ' ' . $what . ' seconds');
}

function disk_scandir($folder) {
	debug('files.php - disk_scandir', ['$folder' => $folder]);
	if (($result = _diskCached('scandir', $folder)) == null) {
		_disk_start();
		$folder = _makeSlashesConsistent($folder);
		$result = scandir($folder);
		$time = _disk_end();
		_diskCache('scandir', $folder, $result);
		disk_call('scandir', $folder, $time);
	}
	return $result;
}

function disk_file_get_contents($file) {
	debug('files.php - disk_file_get_contents', ['$file' => $file]);
	if (($result = _diskCached('file_get_contents', $file)) == null) {
		_disk_start();
		$file = _makeSlashesConsistent($file);
		$result = file_get_contents($file);
		$time = _disk_end();
		_diskCache('file_get_contents', $file, $result);
		disk_call('file_get_contents', $file, $time);
	}
	return $result;
}

function disk_is_dir($folder) {
	if (($result = _diskCached('is_dir', $folder)) == null) {
		_disk_start();
		$folder = _makeSlashesConsistent($folder);
		$result = is_dir($folder);
		$time = _disk_end();
		_diskCache('is_dir', $folder, $result);
		disk_call('is_dir', $folder, $time);
	}
	return $result;
}

function stripExtension($file) {
	return pathinfo($file, PATHINFO_FILENAME);
}

function getExtension($file) {
	return pathinfo($file, PATHINFO_EXTENSION);
}

function doToBuffering($what) {
	if ($what == 'initialize' || $what == 1) {
		ob_flush();
		ob_start();
	} else if ($what == 'get' || $what == 2) {
		return ob_get_contents();
	} else if ($what == 'finalize' || $what == 3) {
		ob_end_clean();
	} else {
		parameterError('Unknown buffering verb', $what, DOTRACE, DODIE);
	}
}

function disk_one_of_files_exist($fwe, $extensions = 'php, html') {
	$extensions = explode(', ', $extensions);

	foreach ($extensions as $item)
		if (disk_file_exists(_makeSlashesConsistent($fwe . $item))) return $item;

	return false;
}

function disk_file_exists($file) {
	if (contains($file, _makeSlashesConsistent('/.')) || contains($file, variable('safeNL'))) return false;

	if (($result = _diskCached('file_exists', $file)) == null) {
		_disk_start();
		$file = _makeSlashesConsistent($file);
		$result = file_exists($file);
		$time = _disk_end();
		_diskCache('file_exists', $file, $result);
		disk_call('file_exists', $file, $time);
	}
	return $result;
}

function disk_include_once($file, $variables = []) {
	//breaks as not exists because entry uses new runFrameworkFile
	if (function_exists('debug'))
		debug('files.php - disk_include_once', ['$file' => $file]);

	if (!$file) parameterError('$file', $file);
	_disk_start();
	$file = _makeSlashesConsistent($file);
	extract($variables);
	$result = include_once($file);
	$time = _disk_end();
	disk_call('include_once', $file, $time);
	return $result;
}

function disk_include($file, $variables = false) {
	debug('files.php - disk_include', ['$file' => $file]);
	_disk_start();
	$file = _makeSlashesConsistent($file);
	if ($variables) extract($variables);
	$result = include($file);
	$time = _disk_end();
	disk_call('include_once', $file, $time);
	return $result;
}

//Added 15 Jan 2025 as seems path separators usage behaviour seems to no longer allow mixing
function _makeSlashesConsistent($path) {
	$fromTo = DIRECTORY_SEPARATOR == '/' ? ['\\', '/'] : ['/', '\\'];
	return str_replace($fromTo[0], $fromTo[1], $path);
}

//Needed only when traversing up..
function siteRealPath($relative) {
	return realpath(SITEPATH . $relative);
}
