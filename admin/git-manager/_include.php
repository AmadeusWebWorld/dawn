<?php
DEFINE('NODEPATH', SITEPATH . '/' . variable('section') . '/' . variable('node'));
variables([
	assetKey(NODEASSETS) => fileUrl(variable('section') . '/' . variable('node') . '/assets/'),
	'nodeSiteName' => 'Git Tools @ ' . variable('name'),
	'nodeSafeName' => variable('safeName') . '-git-admin',
	'submenu-at-node' => true,
]);
