#!/usr/bin/env php
<?php
const FILENAME = 'themes.json';

if(!file_exists(__DIR__ . '/' . FILENAME)) {
	$items = array();
	$themes = array_map(function($item) { return preg_replace('@^theme-?@i', '', basename($item)); }, glob(__DIR__ . '/theme-*', GLOB_ONLYDIR));
	foreach($themes as $id=>$t) {
		$src = 'theme-' . $t . '/apercu_min.jpg';
		$dims = getimagesize($src);
		$items[] = array(
			'name'	=> $t,
			'w'		=> $dims[0], // width
			'h'		=> $dims[1], // height
		);
	}
	$datas = array(
		'version'	=> '1.0.0',
		'error'		=> '',
		'items'		=> $items,
	);
	file_put_contents(__DIR__ . '/' . FILENAME, json_encode($datas, JSON_UNESCAPED_UNICODE));
}

if(!empty($_SERVER['HTTP_HOST'])) {
	header('Location: index.html');
	exit;
}

if(!empty($items)) {
	# FILENAME vient d'être créé

	echo 'Fichier ' . FILENAME . ' créé' . PHP_EOL;
	echo PHP_EOL . count($items) . ' themes.' . PHP_EOL;
	echo 'Taille : ' . filesize(__DIR__ . '/' . FILENAME) . ' octets' . PHP_EOL;
} else {
	echo 'Fichier ' . FILENAME . ' : ' . filesize(__DIR__ . '/' . FILENAME) . ' octets' . PHP_EOL;
}

?>
