<?php

$roots = array (
	'/mnt/goobi',
	'/mnt/goobi2',
	'/mnt/goobi3',
	'/mnt/goobi4',
);

$pids = array (
	'typo3' => 4152,
	'hmtl' => 4152,
	'ubl' => 5939,
);

$cores = array (
	'typo3' => 1,
	'hmtl' => 1,
	'ubl' => 2,
);

foreach ($roots as $root) {

$clients = scandir($root);

foreach ($clients as $client) {

	if (!empty($pids[$client]) && !empty($cores[$client])) {

		$dir = $root.'/'.$client;

		$processes = scandir($dir);

		$todo = array ();

		foreach ($processes as $process) {

			if ((preg_match('/^.*_[0-9]{8}[0-9A-Z]{1}(-[0-9]+)?$/i', $process) || preg_match('/^.*_[0-9]{8}[0-9A-Z]{1}(-[0-9]+)?_.*$/i', $process))
				&& is_dir($dir.'/'.$process)
				&& file_exists($dir.'/'.$process.'/'.$process.'.xml')
				&& file_exists($dir.'/'.$process.'/ready-for-indexing')
				&& !file_exists($dir.'/'.$process.'/corrupt')) {

				$parts = explode('_', $process);

				foreach ($parts as $part) {

					if (preg_match('/^[0-9]{8}[0-9A-Z]{1}(-[0-9]+)?$/i', $part)) {

						$_ppn = $part;

						break;

					}

				}

				$todo[$process] = $_ppn;

			}

			if (count($todo) == 50) {

				break;

			}

		}

		foreach ($todo as $proc => $ppn) {

			$corrupt = FALSE;

			echo "Indexing $proc ($ppn)...\n";

			exec('mv -f '.$dir.'/'.$proc.'/'.$proc.'.xml '.$dir.'/'.$proc.'/'.$proc.'_mets.xml');

			fixURN($dir.'/'.$proc.'/'.$proc.'_mets.xml');

			if (file_exists($dir.'/'.$proc.'/'.$proc.'_anchor.xml')) {

				fixURN($dir.'/'.$proc.'/'.$proc.'_anchor.xml');

			}

			if (is_dir($dir.'/'.$proc.'/'.$proc.'_tif')) {

				echo "  Images found! Moving all files to ";

				$update = FALSE;

				foreach ($roots as $root) {

					if (is_dir($root.'/'.$client.'/'.$ppn)) {

						echo "$root/$client/$ppn (UPDATE)\n";

						exec('rm -rf '.$root.'/'.$client.'/'.$ppn);

						exec('mv -f '.$dir.'/'.$proc.' '.$root.'/'.$client.'/'.$ppn);

						exec('cd '.$root.'/'.$client.'/'.$ppn.' && ln -sf '.$proc.'_mets.xml '.$ppn.'.xml');

						if (file_exists($root.'/'.$client.'/'.$ppn.'/'.$proc.'_anchor.xml')) {

							exec('cd '.$root.'/'.$client.'/'.$ppn.' && ln -sf '.$proc.'_anchor.xml '.$ppn.'_anchor.xml');

						}

						exec('rm -f '.$root.'/'.$client.'/'.$ppn.'/ready-for-indexing');

						exec('rm -f '.$root.'/'.$client.'/'.$ppn.'/processing');

						$update = TRUE;

						break;

					}

				}

				if (!$update) {

					echo "$dir/$ppn (NEW)\n";

					exec('mv -f '.$dir.'/'.$proc.' '.$dir.'/'.$ppn);

					exec('cd '.$dir.'/'.$ppn.' && ln -sf '.$proc.'_mets.xml '.$ppn.'.xml');

					if (file_exists($dir.'/'.$ppn.'/'.$proc.'_anchor.xml')) {

						exec('cd '.$dir.'/'.$ppn.' && ln -sf '.$proc.'_anchor.xml '.$ppn.'_anchor.xml');

					}

					exec('rm -f '.$dir.'/'.$ppn.'/ready-for-indexing');

					exec('rm -f '.$dir.'/'.$ppn.'/processing');

					exec('ln -sf '.$dir.'/'.$ppn.' /var/www/webroot/fileadmin/data/'.$ppn);

				}

			} else {

				echo "  No images found! Moving metadata file(s) to ";

				$update = FALSE;

				foreach ($roots as $root) {

					if (is_dir($root.'/'.$client.'/'.$ppn)) {

						echo "$root/$client/$ppn (UPDATE)\n";

						exec('mv -f '.$dir.'/'.$proc.'/'.$proc.'_mets.xml '.$root.'/'.$client.'/'.$ppn.'/'.$proc.'_mets.xml');

						exec('cd '.$root.'/'.$client.'/'.$ppn.' && ln -sf '.$proc.'_mets.xml '.$ppn.'.xml');

						if (file_exists($dir.'/'.$proc.'/'.$proc.'_anchor.xml')) {

							exec('mv -f '.$dir.'/'.$proc.'/'.$proc.'_anchor.xml '.$root.'/'.$client.'/'.$ppn.'/'.$proc.'_anchor.xml');

							exec('cd '.$root.'/'.$client.'/'.$ppn.' && ln -sf '.$proc.'_anchor.xml '.$ppn.'_anchor.xml');

						}

						exec('rm -rf '.$dir.'/'.$proc);

						$update = TRUE;

						break;

					}

				}

				if (!$update) {

					echo "nowhere (ERROR) - NO IMAGE FILES AVAILABLE!\n";

					$corrupt = TRUE;

					exec('touch '.$dir.'/'.$proc.'/corrupt');

					break;

				}

			}

			if (!$corrupt) {

				echo "  Indexing metadata file\n";

				file_get_contents('http://digital.slub-dresden.de/indexer.php?doc='.urlencode('http://digital.slub-dresden.de/fileadmin/data/'.$ppn.'/'.$proc.'_mets.xml').'&pid='.$pids[$client].'&core='.$cores[$client]);

			} else {

				echo "  Skipping indexing of metadata file\n";

			}

			echo "Done!\n\n";

		}

	}

}

}

function fixURN($file) {

	$xml = simplexml_load_file($file);

	$xml->registerXPathNamespace('mets', 'http://www.loc.gov/METS/');

	$xml->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');

	$urns = $xml->xpath('//mods:identifier[@type="urn"]');

	foreach ($urns as $urn) {

		$urn[0] = getURN((string) $urn);

	}

	file_put_contents($file, $xml->asXML());

}

function getURN($base) {

	$concordance = array(
		'0' => 1,
		'1' => 2,
		'2' => 3,
		'3' => 4,
		'4' => 5,
		'5' => 6,
		'6' => 7,
		'7' => 8,
		'8' => 9,
		'9' => 41,
		'a' => 18,
		'b' => 14,
		'c' => 19,
		'd' => 15,
		'e' => 16,
		'f' => 21,
		'g' => 22,
		'h' => 23,
		'i' => 24,
		'j' => 25,
		'k' => 42,
		'l' => 26,
		'm' => 27,
		'n' => 13,
		'o' => 28,
		'p' => 29,
		'q' => 31,
		'r' => 12,
		's' => 32,
		't' => 33,
		'u' => 11,
		'v' => 34,
		'w' => 35,
		'x' => 36,
		'y' => 37,
		'z' => 38,
		'-' => 39,
		':' => 17,
	);

	$urn = strtolower($base);

	if (preg_match('/[^a-z0-9:-]/', $urn)) {

		trigger_error('Invalid chars in URN', E_USER_WARNING);

		return '';

	}

	$digits = '';

	for ($i = 0; $i < strlen($urn); $i++) {

		$digits .= $concordance[substr($urn, $i, 1)];

	}

	$checksum = 0;

	for ($i = 0; $i < strlen($digits); $i++) {

		$checksum += ($i + 1) * intval(substr($digits, $i, 1));

	}

	$checksum = substr(intval($checksum / intval(substr($digits, -1, 1))), -1, 1);

	return $base.$checksum;

}

?>