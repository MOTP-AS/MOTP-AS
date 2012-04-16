<?php

include 'INC/session.php';

include 'INC/include.php';


$userid  = get_user_id($_SESSION['user']);
if ($userid == 0) exit;

$user = get_user($userid);


if (isset($_POST['account'])) $accountid = input($_POST['account']);
else stop("error","No account chosen.");

$account = get_account ($accountid);
if (! $account) {
	echo "unknown account.";
	exit;
}
if ($account->userid != $userid) {
	echo "permission denied.";
	exit;
}

log_audit($_SESSION['user'],"generated HTML client","Account: $account->id");

$device = get_device($account->deviceid);
$SECRET = $device->secret;


include 'INC/htmlclient.php';

?>
