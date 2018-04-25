<?php
/**
 * Helper functions to connect to and manage the database
 */
function zpai_get_db( $key ) {

	/****************************************************
	*
	* SET YOUR DATABASE DETAILS ON THE FOLLOWING 4 LINES
	*
	****************************************************/

	$database_name = '';
	$database_user = '';
	$database_password = '';
	$database_host = '';
	
	/****************************************************
	*
	* DO NOT EDIT BELOW THIS LINE
	*
	****************************************************/

	switch ($key) {
		case 'name':
			return $database_name;
			break;
		case 'user':
			return $database_user;
		case 'password':
			return $database_password;
		case 'host':
			return $database_host;
	}
}

/**
 * Checks if the database details are valid.
 *
 * @return mixed bool|string True if valid, string if db details are invalid, false is db details have not been set.
 */
function zpai_check_db_details_valid() {
	$db_host = zpai_get_db('host');
	$db_user = zpai_get_db('user');
	$db_password = zpai_get_db('password');
	$db_name = zpai_get_db('name');

	// Are the custom db details set?
	if (empty($db_host) || empty($db_user) || empty($db_password) || empty($db_name)) {
		return false;
	}

	$link = mysqli_connect($db_host, $db_user, $db_password, $db_name);

	if (mysqli_connect_errno()) {
		$out = sprintf("Invalid database details: %s", mysqli_connect_error());
	} else {
		$out = true;
		mysqli_close($link);
	}

	return $out;
}

function zpai_is_atlas_installed() {
	$installed = false;
	$link = zpai_connect_db();
	if ( zpai_db_exists($link) && zpai_table_exists($link) ) {

		if(zpai_table_key_exists($link, 'PRIMARY') && zpai_table_key_exists($link, 'ix_name_country')) {

			$installed = true;

		}
	}
	return $installed;
}

function zpai_connect_db() {
	$db_host = zpai_get_db('host');
	$db_user = zpai_get_db('user');
	$db_password = zpai_get_db('password');
	$db_name = zpai_get_db('name');

	if (empty($db_host) || empty($db_user) || empty($db_password) || empty($db_name)) {
		$x = 'You must add your database details to the file. See Step 1.';
	} else {

		$link = mysqli_connect($db_host, $db_user, $db_password, $db_name);

		if (mysqli_connect_errno()) {
			$x = sprintf("Connect failed: %s", mysqli_connect_error());

		} else {
			return $link;
		}
	}

	if (!empty($x)) {
		echo json_encode(array("status" => "error","message" => $x));
		exit;
	}
}

function zpai_mysql_query_bool($link, $sql) {
	$e = false;
	if ($res = mysqli_query($link, $sql)) {
		if (1 === $res->num_rows) {
			$e = true;
		}
	}
	mysqli_free_result($res);
	return $e;
}
function zpai_db_exists($link) {
	$db_name = zpai_get_db('name');
	return zpai_mysql_query_bool($link, "SHOW DATABASES LIKE '$db_name'");
}
function zpai_table_exists($link) {
	return zpai_mysql_query_bool($link, "SHOW TABLES LIKE 'zp_atlas'");
}

function zpai_count_table_rows($link){
	$res = mysqli_query($link,"SELECT COUNT(*) FROM zp_atlas");
	$row = $res->fetch_row();
	mysqli_free_result($res);
	return (int) $row[0];
}
/**
 * Get the MySQL secure_file_priv option
 * @link object $link
 * @return string The directory allowed for MySQL files, or an empty string if --secure-file-priv is not set.
 */
function zpai_mysql_secure_file_priv($link) {
	$d = '';
	$sql = "SHOW VARIABLES LIKE 'secure_file_priv'";
	$res = mysqli_query($link,$sql);
	if ($res) {
		$row = $res->fetch_row();
		$d = $row[1];
	}
	mysqli_free_result($res);
	return $d;
}

/**
 * Attempt to LOAD DATA INFILE into the database table.
 *
 * @return mixed Returns true if data was successfully inserted, otherwise returns an error message.
 */
function zpai_load_data_infile($link){
	$dir = sys_get_temp_dir();
	$file = $dir . '/cities.txt';
	$sql = "LOAD DATA LOCAL INFILE '$file'
		IGNORE
		INTO TABLE zp_atlas
		FIELDS TERMINATED BY '\t'
		LINES TERMINATED BY '" . PHP_EOL . "'
		(geonameid, name, latitude, longitude, country, admin1, timezone, mod_date)";

	if (mysqli_query($link,$sql)) {

		$ret = true;

	} else {

		$n = mysqli_errno($link);
		$ret = "ERROR: Could not load data into table. ($n) " . mysqli_error($link);

		if ('1290' == $n ) {
			if ($d = zpai_mysql_secure_file_priv($link)) {
				$ret .= ". TO FIX THIS: move the file (cities.txt) from the '$dir/' directory to '$d'. After that, you can try this step again.";
			}
		}

	}

	return $ret;
}

function zpai_db_insert_data($link) {
	$c = zpai_count_table_rows($link);

	if ($c > 3000000) {

		$status = 'info';
		$msg = "Cities data had already been inserted. The zp_atlas table already had $c rows.";

	} else {

		$insert = zpai_load_data_infile($link);

		if (true === $insert) {

			$inserted_results = mysqli_info($link);

			// create primary key and index
			$index = zpai_table_create_keys($link, $inserted_results);
			if (true === $index) {
				$status = "success";
				$msg = sprintf("Success! Cities data was inserted into the database. %s", $inserted_results);
			} else {
				$status = 'error';
				$msg = $index;// failed to create index/key
			}
		} else {
			$status = "error";
			$msg = $insert;// failed to insert/load data
		}
	}

	mysqli_close($link);
	echo json_encode(array("status" => $status,"message" => $msg));
	exit;

}

/**
 * Check if a specific INDEX or KEY exists in the zp_atlas table
 * 
 * @param object $link A link identifier returned by mysqli_connect() or mysqli_init()
 * @param string $key The key name
 * @return bool
 */
function zpai_table_key_exists($link, $key) {
    $e = false;
    if ($result = mysqli_query($link,"SHOW INDEX FROM zp_atlas WHERE Key_name = '$key'")) {
        if($result->num_rows >= 1) {
            $e = true;
        }
    }
    mysqli_free_result($result);
    return $e;
}

/**
 * Create both the PRIMARY KEY and the index on the zp_atlas table
 *
 * @param object $link A link identifier returned by mysqli_connect() or mysqli_init()
 * @param string $previous_results The message response from the insert query, to pass along.
 * 
 * @return mixed Returns true if both key and index were created, otherwise returns an error message.
 *
 */
function zpai_table_create_keys($link, $previous_results) {
	$sql_1 = "ALTER TABLE zp_atlas MODIFY COLUMN geonameid bigint(20) UNSIGNED NOT NULL PRIMARY KEY";
	$sql_2 = "CREATE INDEX ix_name_country ON zp_atlas (name(50),country(50) DESC)";

	// create PRIMARY KEY

	if (mysqli_query($link, $sql_1)) {

		// create the index on name,country

		if (mysqli_query($link, $sql_2)) {

			// BOTH KEYS WERE SUCCESSFULLY CREATED

			$ret = true;

		} else {

			$ret = sprintf("Cities data was inserted into the database. %s. However, the INDEX was not created due to some error: (%s) %s. You must create the INDEX yourself with this SQL query: '%s'.", $previous_results, mysqli_errno($link), mysqli_error($link), $sql_2);
		}

	} else {

		$ret = sprintf("Cities data was inserted into the database. %s. However, the PRIMARY KEY was not created due to some error: (%s) %s. You must create both the PRIMARY KEY and the INDEX yourself with these two SQL queries: '%s' and '%s'.", $previous_results, mysqli_errno($link), mysqli_error($link), $sql_1, $sql_2);

	}

	return $ret;
}

function zpai_create_cities_table($link) {

$sql = "CREATE TABLE IF NOT EXISTS zp_atlas (
geonameid bigint(20) unsigned NOT NULL,
name varchar(200) NOT NULL,
latitude decimal(10,5) NOT NULL,
longitude decimal(10,5) NOT NULL,
country varchar(200) NOT NULL,
admin1 text NOT NULL,
timezone varchar(40) NOT NULL,
mod_date date NOT NULL
) CHARACTER SET utf8;";

	if (mysqli_query($link,$sql)) {

		zpai_db_insert_data($link);

	} else {

		// could not create table.
		mysqli_close($link);
		echo json_encode(array(
			"status"    => "error",
			"message"   => sprintf("ERROR: Could not create table. (%s) %s", mysqli_errno($link), mysqli_error($link))
		));
		exit;       

	}
}
