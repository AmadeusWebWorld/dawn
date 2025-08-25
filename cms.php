<?php

if (nodeIs(SITEHOME))
	variable('sub-theme', 'go');

if (nodeIs('tests')) {
	addScript('tests', SITEASSETS);
}
