#!/usr/bin/env php
<?php

const RED		= "\e[31m";
const GREEN		= "\e[32m";
const YELLOW	= "\e[33m";
const ALERT		= "\e[45m"; // Background magenta
const END		= "\e[m" . PHP_EOL;

/*
for($i=30; $i<38; $i++) {
	echo "\e[" . $i . 'm' . str_pad($i, 4) . "\e[m";
}
exit;
 * */

const INFOS = 'infos.xml';
const TEMPLATE = <<< EOT
<?xml version="1.0" encoding="UTF-8"?>
<document>
    <title><![CDATA[THEME]]></title>
    <author></author>
    <version>0.0.1</version>
    <date>DATE</date>
    <site></site>
    <description>Requested</description>
</document>
EOT;

const FORMAT_BANNER = YELLOW . '%3d Archive : %s (%d fichiers)' . END;
// On recherche les fichiers home.php header.php, footer.php, infos.xml, preview.* et screenshoot.* dans les entréées de l'archive .zip
const PATTERN_ZIP_ENTRIES = '@^/?([^/]+)/((?:home|header|footer)\.php|infos\.xml|(preview|screenshoot)\.(?:jpe?g|png|gif|bmp|svg))\b@i';
const FILENAME = 'assets/themes.json';

if(!file_exists(__DIR__ . '/' . FILENAME)) {
	if(!class_exists('ZipArchive')) {
		// header('HTTP/1.0 500 '.$message);
		// header('Content-type: text/plain');
		echo 'Class ZipArchive is missing in PHP library';
		exit;
	}

	if(!function_exists('simplexml_load_string')) {
		echo 'Library SimpleXML is missing';
		exit;
	}

	$items = array();

	$themes = array_map(function($item) { return preg_replace('@^theme-?@i', '', basename($item)); }, glob(__DIR__ . '/theme-*', GLOB_ONLYDIR));
	foreach($themes as $id=>$t) {
		$filename = 'theme-' . $t . '/archive.zip';
		$zip = new ZipArchive();
		if($zip->open($filename) === true) {
			printf(FORMAT_BANNER, $id, $filename, $zip->numFiles);
			$target = __DIR__ . '/theme-' . $t . '/'; // Certaines archives utilisent des chemins absolus !!!
			$root = false;
			$infos = false;
			$preview = false;
			$timestamp = 0;

			// dimensions de la vignette dans le dossier du thème
			$src = 'theme-' . $t . '/apercu_min.jpg';
			$dims = getimagesize($src);

			$props = array(
				'name'		=> $t,
				'w'			=> $dims[0], // width
				'h'			=> $dims[1], // height
				'preview'	=> 'apercu_max.jpg', // preview par défaut disponible dans le dossier du thème
				'files'		=> $zip->numFiles, // nombre d'entrées dans l'archive .zip
			);

			// On parcourt les entrées de l'archive .zip jusqu'à trouver les fichiers infos.xml, preview.* ou screenshoot.*
			for($i=0, $iMax=$zip->numFiles; $i<$iMax; $i++) {
				if(preg_match(PATTERN_ZIP_ENTRIES, $zip->getNameIndex($i), $matches)) {
					if(empty($root)) {
						$root = $matches[1];
						echo 'Folder : ' . $root . PHP_EOL;
					} elseif($root == $matches[1]) {
						if(strtolower($matches[2]) == 'infos.xml') {
							$content = $zip->getFromIndex($i);
							if(!empty($content)) {
								// file_put_contents($target . 'infos.xml', $content);
								try {
									$doc = simplexml_load_string($content);
									$props['title'] = $doc->title->__toString();
									$props['author'] = $doc->author->__toString();
									$props['date'] = $doc->date->__toString();
									$props['descr'] = $doc->description->__toString();
								} catch (Exception $e) {
									echo ALERT . $e->getMessage() . END;
								}
								$infos = true;
								echo GREEN . $matches[2] . END;
							}
						} elseif(!empty($matches[3])) { // preview
							// On extrait l'image de l'archive
							if($zip->extractTo($target, array($matches[0]))) {
								rename($target . $matches[0], $target . $matches[2]);
								$dir1 = $target . $matches[1];
								if(is_dir($dir1)) {
									rmdir($dir1);
								}
								echo GREEN . $matches[2] . END;
								$props['preview'] = $matches[2];
							}
						} else {
							echo $matches[2] . PHP_EOL;
						}
					} else {
						echo RED . 'Plusieurs dossiers racines dans l\'archive' . END;
					}

					$stats = $zip->statIndex($i);
					if($timestamp < $stats['mtime']) {
						$timestamp = $stats['mtime'];
					}
				}

				if(!empty($root) and !empty($infos) and !empty($preview)) { break; }
			}
			if(!array_key_exists('date', $props)) {
				$props['date'] = date('d/m/Y', $timestamp);
			}
			echo $props['date'] . PHP_EOL;

			if(empty($infos)) {
				echo RED . 'Missing infos.xml' . END;
				file_put_contents($target . INFOS, strtr(TEMPLATE, array(
					'THEME'	=> $t,
					'DATE'	=> date('d/m/Y', $timestamp),
				)));
			}
			$zip->close();

			$items[] = $props;

		} else {
			echo 'Problème archive pour le thème : ' . $t . PHP_EOL;
		}

		echo str_repeat('⎻', 40) . PHP_EOL;

		// if($id > 50) { break; } // pour tester
	}

	$datas = array(
		'version'	=> '1.1.0',
		'built'		=> date('d/m/Y'),
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
	echo 'Fichier ' . FILENAME . ' : ' . filesize(__DIR__ . '/' . FILENAME) . ' octets - ' . date('d/m/Y', filemtime(FILENAME)) . PHP_EOL;
	echo 'Pour mettre à jour, effacer le fichier ' . FILENAME . PHP_EOL;
}
?>
