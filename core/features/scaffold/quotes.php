<?php
printNodeHeading();

$all = explode('---', disk_file_get_contents(SITEPATH . '/data/quotes.md'));

if ($show = getQueryParameter('show')) {
	_renderItem($all[$show - 1] ,$show, $show);
	return;
}

$items = getShuffledItems($all, variableOr('quotes-display-count',  2));

foreach ($items as $item) {
	$ix = array_search($item, $all) + 1;
	_renderItem($item, $ix, $show);
}

function _renderItem($item, $ix, $show) {
	$btn = 'btn btn-outline-info h2 mb-0';
	if ($show)
		$link = getLink('All Quotes', pageUrl('quotes'), $btn)
			. BRTAG . ' &mdash;&gt; ' . 'Quote # ' . $ix;
	else
		$link = getLink('Quote # ' . $ix, pageUrl('quotes/?show=' . $ix), $btn);

	contentBox('quote-' . $ix, 'container');
	h2($link);
	echo returnLines($item);
	contentBox('end');
}
