<?php

$safeNL = "\r"; //platform safe
$nl = "\r\n";

variable('safeNL', $safeNL); //TODO: deprecate
DEFINE('SAFENEWLINE', $safeNL);
DEFINE('NEWLINE', $nl);
variable('nl', NEWLINE);
DEFINE('NEWLINES2', $nl . $nl);
variable('2nl', NEWLINES2);
variable('3nl', $nl . $nl . $nl);

$br = '<br>';
DEFINE('BRTAG', $br);
DEFINE('BRNL', $br . $nl);
$hr = '<hr>';
DEFINE('HRTAG', $hr);

variable('markdownStart', $md = '<!--markdown-->');
variable('markdownStartTag', $md . $nl); //NOTE: to detect content which doesnt start with a heading
variable('autopStart', '<!--autop-->');

function trimCrLf($txt) {
	return trim($txt, "\r\n");
}

function urlize($txt) {
	return replaceItems(strtolower($txt), ["'" => '', ' ' => '-', '&hellip;' => '__', '&' => 'and']);
}

function strip_hyphens($txt) {
	return replaceItems($txt, ['-' => ' ']);
}

function strip_paragraph($txt) {
	return replaceItems($txt, ['</p>' => '', '<p>' => '']);
}

function humanize($txt, $how = false) {
	$words = ucwords(replaceItems($txt, ['--' => ' &mdash; ', '-' => ' ', '_' => '']));
	if ($how !== 'no-site' && function_exists('site_humanize')) $words = site_humanize($words, 'title', $how);
	if (contains($words, ' A ')) $words = str_replace(' A ', ' a ', $words);
	if (contains($words, ' And ')) $words = str_replace(' And ', ' and ', $words);
	return $words;
}

function blog_heading($name, $fol) {
	if (contains($fol, 'by') || in_array($fol, variableOr('flat-blogs', []))) return humanize($name);

	$fileName_r = explode('-', $name, 2);
	$month_r = explode('-', $fol, 2);

	$fileName_r = $fileName_r[0] . ' ' . $month_r[0] . ' &mdash; ' . $fileName_r[1];
	return humanize($fileName_r);
}

//https://github.com/yieldmore/MicroVC/blob/master/tlr/app/functions.io.php
function startsWith($haystack, $needle)
{
  $length = strlen($needle);
  return (substr($haystack, 0, $length) === $needle);
}

function endsWith($haystack, $needle)
{
  $length = strlen($needle);
  if ($length == 0) {
	return true;
  }

  return (substr($haystack, -$length) === $needle);
}

function contains($haystack, $needle)
{
	return stripos($haystack, $needle) !== false;
}

function simplify_encoding($txt) {
	$replace = [
		'½' => '&frac12',
		'“' => '"', '”' => '"',
		'‘' => "'", '’' => "'",
		'—' => '-', '–' => '-', 'â€"' => '-',
		'…' => '&hellip;'];
	foreach ($replace as $search=>$replace)
 		$txt = str_replace($search, $replace, $txt);
	return $txt;
}

function contact_r($text) {
	$text = replaceItems($text, ['tel:' => '', 'mailto:' => '', 'https://' => '', 'www.' => '']);
	if (contains($text, '?subject')) $text = explode('?subject', $text)[0];
	return $text;
}

function explodeByArray(array $delimeters, string $input, int $limit = -1): array {
	if($delimeters===[]) return [$input];

	$unidelim = $delimeters[0];
	$step = str_replace($delimeters, $unidelim, $input);
	return explode($unidelim, $step, $limit);
}
