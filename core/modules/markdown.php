<?php
include_once 'markdown/block/CodeTrait.php';
include_once 'markdown/block/FencedCodeTrait.php';
include_once 'markdown/block/HeadlineTrait.php';
include_once 'markdown/block/HtmlTrait.php';
include_once 'markdown/block/ListTrait.php';
include_once 'markdown/block/QuoteTrait.php';
include_once 'markdown/block/RuleTrait.php';
include_once 'markdown/block/TableTrait.php';

include_once 'markdown/inline/CodeTrait.php';
include_once 'markdown/inline/EmphStrongTrait.php';
include_once 'markdown/inline/LinkTrait.php';
include_once 'markdown/inline/StrikeoutTrait.php';
include_once 'markdown/inline/UrlLinkTrait.php';

include_once 'markdown/Parser.php';
include_once 'markdown/Markdown.php';

function markdown($content) {
	$parser = new \cebe\markdown\Markdown();
	$parser->html5 = true;
	$result = $parser->parse($content);

	$result = str_replace('[ ]', '<input type="checkbox" disabled />', $result);
	$result = str_replace('[x]', '<input type="checkbox" checked disabled />', $result);

	return $result;
}

?>