<?php
//TODO: checks salt etc

function getCurrentUserId() {
	//not implemented
	return defined('OPUSUSER') ? OPUSUSER : false;
}

function appliesToCurrentUser($csv) {
	if ($csv == '*') return true;
	return in_array(getCurrentUserId(), explode(', ', $csv));
}
