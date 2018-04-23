<?php
/**
 * Handles the ajax request to download the cities.txt data file
 */
set_time_limit(360);
ini_set('memory_limit', '-1');

$datafile = 'cities.txt';

$filename = isset($_GET['f']) ? $_GET['f'] : '';
if ($datafile !== $filename) {
	echo json_encode(array("status" => "error","message" => "error"));
	exit;
}
$dir = sys_get_temp_dir();
$official_size = 275665461;// Current filesize of cities.txt @todo update
$url = 'https://download.cosmicplugins.com/' . $datafile;
$file = $dir . '/' . $filename;
$retry = isset($_GET['retry']) ? $_GET['retry'] : 0;

function zpai_curl_get_file($url, $filepath) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    $raw_file_data = curl_exec($ch);
    curl_close($ch);

    file_put_contents($filepath, $raw_file_data);

    if (filesize($filepath) > 2000) {
		echo json_encode(array("status" => "success","message" => "The cities data file was successfully downloaded."));
		exit;
	} else {
		return false;
	}

}

function zpai_file_get_contents($url, $file, $filename) {
	$msg = $status = "error";

	$put = file_put_contents($file, file_get_contents($url));

	if (false === $put) {
		$msg = "The data file ($filename) could not be downloaded.";		
	} elseif (filesize($file) > 1000) {
		$status = "success";
		$msg = "The data file ($filename) was successfully downloaded.";
	}

	echo json_encode(array("status" => $status,"message" => $msg));
	exit;
}

// only check file if this is not a retry

if (empty($retry) && file_exists($file)) {

	// file exists, but check size

	if (filesize( $file ) === $official_size ) {
		$status = "info";
		$msg = "The file, $filename, already exists, so you have already downloaded it.";		
	} else {
		/****************************************************
		* The file already exists, but it is incomplete. Trying download again....
		* send back ajax response of "working" to let it kick off another ajax to retry download.
		****************************************************/
		$status = "working";
		$msg = "$filename already exists, but it is incomplete. Trying to download again now....";
		
	}


} else {

	if (extension_loaded('curl')) {
		
		$curl = zpai_curl_get_file($url, $file);

		if (false === $curl) {
			zpai_file_get_contents($url, $file, $filename);
		}
			
	} else {
	
		zpai_file_get_contents($url, $file, $filename);

	}
}

set_time_limit(30);// restore to default
ini_set('memory_limit','128M');// restore to default
echo json_encode(array("status" => $status,"message" => $msg));
