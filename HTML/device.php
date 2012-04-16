<?php

include 'INC/session.php'; checkrole("AH","index.php");

include 'INC/include.php';


if (isset($_POST['action'])) $action=input($_POST['action']); else $action="";
if (isset($_GET['device']))  $devid=input($_GET['device']);   else $devid=0;

if ($action == "sync") {
	header("Location: sync.php?device=$devid");
	exit;
}


$title = "MOTP-AS - Devices";
include 'INC/header.php';

$bcs = array ( array("index.php","home"), array("devices.php","Devices") );

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


if (! $device) $device = new Device();

if ($action == "lock") {
	$device->enabled = FALSE;
	update_device ($device);
	log_audit($_SESSION['user'],"device lock","Device #$device->id: $device->name");
}

if ($action == "reset") {
	$device->enabled = TRUE;
	$device->offset = 0;
	update_device ($device);
	log_audit($_SESSION['user'],"device reset","Device #$device->id: $device->name");
}

if ($action == "delete") {
	delete_device ($device);
	log_audit($_SESSION['user'],"device delete","Device #$device->id: $device->name");
	array_splice($bcs,2);
	stop("ok", "Device deleted");
}

if ($action == "insert") {
	if (isset($_POST['name']))     $device->name     = input($_POST['name']);
	if (isset($_POST['secret']))   $device->secret   = input($_POST['secret']);
	if (isset($_POST['timezone'])) $device->timezone = input($_POST['timezone']);
	if (isset($_POST['ldap']))     $device->ldap = TRUE ; else $device->ldap = FALSE;

	$device->secret = strtolower($device->secret);

	if ($devid==0) {
		$device=insert_device ($device);
		if (! $device) stop("error", "Could not add device: " . error_msg() );
		$devid=$device->id;
		$bcs[3]=array("device.php?device=$devid",device_name($device));
		log_audit($_SESSION['user'],"device add","Device #$device->id: $device->name");
	} else {
		update_device ($device);
		log_audit($_SESSION['user'],"device modify","Device #$device->id: $device->name");
	}
}


if ( ($action == "add") || ($action == "edit") ) {

	echo form_header("POST", "device.php?device=$devid");
	echo input_hidden("action","insert");
	echo table_header(FALSE,"edit");
	echo table_row( array ("Name:"  ,   input_text("name",     $device->name,   20 ) ));
	echo table_row( array ("Secret:",   input_text("secret",   $device->secret, 20, 'id="device-secret"' ) ));
	echo table_row( array ("Timezone:", input_text("timezone", $device->timezone,3, 'id="device-tz"' ) ));
	if (LDAP_ACCESS_SYNC)
	echo table_row( array( "LDAP",      input_checkbox("ldap", $device->ldap, $device->ldap) ) ) . br() ;
	echo table_footer();	
	echo input_submit("Ok","send");
	echo form_footer();

	$help   = p("Name = name of device, can be empty") 
		. p("Secret = secret generated at initialization of token. The pin is stored in account settings.")
		. p("Timezone = difference (in hours) between GMT and device's timezone. You can find mobile otp's \"epoch time\" in the left corner of this web page. If both your mobile device and this server both use GMT, what should be the default, timezone equals 0.")

		. p("LDAP = synced via LDAP")
		;
	$hint = TRUE;

} else {
	/* show device data */

	echo table_header(FALSE,"show");
	echo table_row( array ("Name:",    device_name($device) ));
	echo table_row( array ("Secret:",  $device->secret ));
	echo table_row( array ("Timezone:",$device->timezone ));
	echo table_row( array ("Offset:",  $device->offset ));
	echo table_row( array ("Lasttime:",( ($device->lasttime) ? "$device->lasttime (".date("d.m.Y",10 * $device->lasttime).")" : "never used" ) ));
	echo table_row( array ("Status:",  $device->enabled ? "active" : "locked" ));

	$userlist="";
	foreach ($users as $user)
		$userlist .= a("user.php?user=$user->id", $user->user) . br();
	echo table_row( array ("Users:", $userlist ));

	if (LDAP_ACCESS_SYNC)
	echo table_row( array ("LDAP:",  $device->ldap ? "yes" : "no" ));
	echo table_footer();

	echo form_header("POST", "device.php?device=$devid") . input_hidden("action", "lock" )   . input_submit("Lock", "lock")     . form_footer();
	echo form_header("POST", "device.php?device=$devid") . input_hidden("action", "reset" )  . input_submit("Reset", "reset")   . form_footer();
	echo form_header("POST", "device.php?device=$devid") . input_hidden("action", "edit" )   . input_submit("Edit", "edit")     . form_footer();
	$warn = (WARN_DELETE) ? "Are you sure to delete device '".device_name($device)."'?" : FALSE;
	echo form_header("POST", "device.php?device=$devid", $warn) . input_hidden("action", "delete" ) . input_submit("Delete", "delete") . form_footer();
	echo form_header("POST", "device.php?device=$devid") . input_hidden("action", "sync" )   . input_submit("Sync", "sync")     . form_footer();

	$help   = p("Lock = lock device") 
		. p("Reset = unlock device and set offset to 0")
		. p("Edit = for editing name, secret and timezone")
		. p("Delete = delete device from database and unassign it from users (accounts)")
		. p("Sync = synchronize device's clock with server")
		;
}


include 'INC/footer.php';

?>
