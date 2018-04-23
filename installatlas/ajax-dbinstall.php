<?php
/**
 * Handles the ajax request to insert all cities data into the database.
 */

// check security token
$salt = 'xtdHnckcnd8f$Ds87Axichdn3';
$hash = crypt('nucHd73ksd73kdfIyd7Ykd0235d', $salt);
if(! hash_equals($hash, $_GET['t'])) {
	exit;
}

$clear = isset($_GET['v']) ? $_GET['v'] : '';
if ('dbinstall' !== $clear) {
	echo json_encode(array("status" => "error","message" => "error"));
	exit;
}

require_once 'helper-db.php';

set_time_limit(360);
ini_set('memory_limit', '-1');

$link = zpai_connect_db();

// connected

// Check if database exists
if ( ! zpai_db_exists($link) ) {
	mysqli_close($link);
	$msg = sprintf('ERROR: Database "%s" does not exist.', zpai_get_db('name'));
	echo json_encode(array("status" => "error","message" => $msg));
	exit;
}

if (zpai_table_exists($link)) {
	zpai_db_insert_data($link);
} else {
	zpai_create_cities_table($link);// create table
}
