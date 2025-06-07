<?php

if (variable('node') == 'index')
	variable('sub-theme', 'go');

if (variable('node') == 'tests') {
	addScript('tests', SITEASSETS);
}
