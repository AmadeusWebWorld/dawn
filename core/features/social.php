<?php
function renderPulse($featured) {
	runFeature('tables');

	contentBox('pageHeading', 'container mt-4');
	h2('Social Media - A Pulse of Activities', 'text-center');
	contentBox('end');

	sectionId('pulse', 'text-center p-3');
	add_table('pulse',
		SITEPATH .'/data/social.tsv',
		'Title, YouTube',
		'<div class="col-lg-4 col-md-6 col-sm-12 text-center p-3"><div class="content-box"><h3>%Title%</h3>%Text%<hr/>%Social_Embed%</div></div>', [
			'use-a-bootstrap-row' => true,
			'skipItem' => $featured ? function($r) { return $r['Featured'] == ''; } : false,
		]);
	section('end');
}
