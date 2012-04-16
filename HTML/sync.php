<?php

include 'INC/session.php'; checkrole("AH","index.php");

include 'checkMOTP.php';


if (isset($_POST['action'])) $action=input($_POST['action']); else $action="";
if (isset($_GET['device'])) $devid =input($_GET['device']); else $devid=0;

$title = "MOTP-AS - Device synchronization";
include 'INC/header.php';


$device=get_device($devid);

if ($device) {
	$users=get_users_of_device($device->id);
	foreach ($users as $user) {
		if ( ($role != 'A') && ($user->role != 'U') && ($_SESSION['user'] != $user->user) ) 
			stop("error", "Not enough rights to access devices assigned to a user with role \"$user->role\"");
	}
	$bcs[3]=array("device.php?device=$devid",device_name($device));
} else {
	$users=array();
}

$bcs = array ( array("index.php","home"), array("devices.php","Devices"), array("device.php?device=$devid",device_name($device)), array("sync.php?device=$devid","Sync") );



if ($action == "sync") {
	if (isset($_POST['epoch']))    $epoch    = input($_POST['epoch']);
	if (isset($_POST['passcode'])) $passcode = input($_POST['passcode']);

	if ( ($epoch == "") && ($passcode == "") ) {
		stop("warning","Please specify either epoch time or passcode.");
	}

	$now = gmdate("U");

	if ($epoch != "") {

		$now = intval($now/10);
		$diff = $epoch - $now;
		$device->lasttime = $epoch;

	} elseif ($passcode != "") {

		$maxdiff = 60 * 30; 	// 30 minutes
		$pin = "1111";
		$time = checkPasscode ($passcode, $pin, $device->secret, $device->timezone, $device->offset, $now , $maxdiff);
		if ($time<0) stop("warning","Cannot sync device, please retry with device's epoch time.");
		$now = intval($now/10);
		$diff = $time - $now;
		$device->lasttime = $time;

	}

	$device->timezone = round($diff/360);
	$device->offset = 10 * ($diff - ($device->timezone * 360));

	$status = update_device($device);
	if (! $status) stop("error","Could not update device");
	log_audit($_SESSION['user'],"device synced","Device #$device->id: $device->name; Timezone: $device->timezone, Offset: $device->offset");

	stop("ok","Device synced. Timezone: $device->timezone, Offset: $device->offset");
}



echo form_header("POST", "sync.php?device=" . $devid );

echo table_header( FALSE, "edit");
echo table_row( array( "Epoch", input_text("epoch", "") ) ) ;
echo table_row( array( "Passcode", input_text("passcode", "") ) ) ;
echo table_footer();

echo input_hidden("action","sync");
echo input_submit("Sync","send");
echo form_footer();

$help   = p("To sync the device, there are two possibilites:") 
        . p("1. read epoch value from device.")
        . p("2. generate passcode using <b>PIN 1111</b> .")
        ;


include 'INC/footer.php';

?>
