<?php
variable('sections-have-files', true);

if (nodeIs(SITEHOME)) {
	variable('sub-theme', 'go');
	variable('footer-variation', 'plain');
}

if (nodeIs('tests')) {
	addScript('tests', SITEASSETS);
}
