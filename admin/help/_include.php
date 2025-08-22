<?php
DEFINE('NODEPATH', SITEPATH . '/' . variable('section') . '/' . variable('node') . '/' . getPageParameterAt(1));
variables([
	assetKey(NODEASSETS) => fileUrl(variable('section') . '/' . variable('node') . '/assets/'),
	'nodeSiteName' => 'Help @ ' . variable('name'),
	'nodeSafeName' => variable('safeName') . '-help',
	'submenu-at-node' => true,
]);
