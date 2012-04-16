<?php

require 'checkMOTP.php';

$result = checkMOTP($argv[1],$argv[2]);

if ($result) {
	echo "ACCEPT\n";
	$ret=0;
} else {
	echo "FAIL\n";
	$ret=1;
}

exit($ret); 

?>
