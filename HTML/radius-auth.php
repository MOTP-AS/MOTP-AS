<?php

if ($argc != 4) exit(1);

require 'auth.php';
include 'INC/rad_av.php';

$user      = input($argv[1]);
$passcode  = input($argv[2]);
$radclient = input($argv[3]);

loadRadiusAVs($user);

$result = checkPassword($user,$passcode,$radclient);

if ($result) {
	echo getRadiusAVs($user);
	$ret=0;		// accept
} else {
	$ret=1;		// fail
}

exit($ret); 

?>
