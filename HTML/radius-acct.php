<?php

if ($argc < 3) exit(1);

require 'INC/include.php';
include 'INC/rad_av.php';

$user = input($argv[1]);
$type = input($argv[2]);

$acct=""; $app="";
for ($i=3; $i<$argc; $i++) {
	// $acct .= $app . input ($argv[$i]);
	$acct .= $app . $argv[$i];
	$app = " ";
}

$acct = acctRadiusAVs($user, $acct);

log_acc ($user, $type, $acct);

echo "logged";

?>
