<?php

if ($argc != 3) exit(1);

require 'INC/include.php';

$value = input($argv[1]);
$clientip = input($argv[2]);

$client = get_freeradius_client ($clientip);

if (! $client) {
	echo "unknown";
	exit();
}

if ($value == "secret") {
	$ret = $client->secret;
} 

if ($value == "shortname") {
	$ret = $client->name;
} 

echo "$ret";

?>
