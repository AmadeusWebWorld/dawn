<?php

main::initialize();

//NOTE: allows referring to values by name and avoids duplication
class main {

	static function initialize() {
		variables([
			'assistantEmail' => 'assistant@amadeusweb.world',
			'systemEmail' => 'team@amadeusweb.com',
		]);
	}

	static function defaultSocial() {
		return [
			[ 'type' => 'linkedin', 'url' => 'https://www.linkedin.com/company/amadeusweb/', 'name' => 'Amadeus Web' ],
			[ 'type' => 'linkedin', 'url' => 'https://www.linkedin.com/in/imran-ali-namazi/', 'name' => 'Founder Imran' ],
			[ 'type' => 'youtube', 'url' => 'https://www.youtube.com/@amadeuswebbuilder', 'name' => 'AMW Core' ],
			[ 'type' => 'github', 'url' => 'https://github.com/AmadeusWebInAction/', 'name' => 'AMW Network Code' ],
		];
	}

	static function defaultSearches() {
		return [
			'amadeusweb' => ['code' => 'c0a96edc60a44407a', 'name' => 'AmadeusWeb Network&nbsp;&nbsp;', 'description' => 'All AmadeusWeb sites from 2025'],
			'imranali' =>   ['code' => '63a208ccffd5b4492', 'name' => 'Imran\'s Writing / Poems&nbsp;&nbsp;', 'description' => 'All of Imran\'s writing since 2017'],
			'yieldmore' =>  ['code' => '29e47bd630f4c73c0', 'name' => 'YieldMore Network&nbsp;', 'description' => 'All YieldMore sites from 2013 to 2024'],
			'sriaurobindo'=>['code' => '84d24b3918cbd5f1a', 'name' => 'Mother Sri Aurobindo Sites', 'description' => 'From the Aurobindonian World'],
		];
	}

	static function runAndReturn() {
		doToBuffering(1);
		main::analytics();
		main::chat();
		$result = doToBuffering(2);
		doToBuffering(3);
		return $result;
	}

	static function chat() {
		$val = variable('ChatraID');
		$val = $val && $val != 'none' ? ($val != '--use-amadeusweb' ? $val : 'wqzHJQrofB47q5oFj') : false;
		if (!$val) return;
		variable('ChatraID', $val);
		runModule('chatra');
	}

	static function analytics() {
		$val = variable('google-analytics');
		$val = $val && $val != 'none' ? ($val != '--use-amadeusweb' ? $val : 'UA-166048963-1') : false;
		if (!$val) return;
		variable('google-analytics', $val);
		runModule('google-analytics');
	}
}
