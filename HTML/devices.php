<?php

include 'INC/session.php'; checkrole("AH","index.php");

include 'INC/include.php';

$title = "MOTP-AS - Devices";
include 'INC/header.php';

if (isset($_POST['name']))   { $name_filter   = input($_POST['name']); }   else { $name_filter=""; }
if (isset($_POST['secret'])) { $secret_filter = input($_POST['secret']); } else { $secret_filter=""; }
if (isset($_GET['status']))  { $stat_filter   = input($_GET['status']); }  else { $stat_filter=""; }
if (isset($_POST['status'])) { $stat_filter   = input($_POST['status']); }
if (isset($_GET['ldap']))    { $ldap_filter   = input($_GET['ldap']); }  else { $ldap_filter=""; }
if (isset($_POST['ldap']))   { $ldap_filter   = input($_POST['ldap']); }

$bcs = array ( array("index.php","home"), array("devices.php","Devices") );

echo form_header("POST","devices.php");
echo table_header (array ("Name", "Secret", "Status", (LDAP_ACCESS_SYNC ? "LDAP" : NULL), "Last usage" ), "list sortable" );

echo table_row ( array (
	input_text("name",   $name_filter,   10),
	input_text("secret", $secret_filter, 32),
	select_header("status")
		. select_option('','')
		. select_option(1,"active","$stat_filter"=="1")
		. select_option(0,"locked","$stat_filter"=="0")
		. select_footer() ,
	(LDAP_ACCESS_SYNC ? select_header("ldap")
		. select_option('','')
		. select_option(1,"yes","$ldap_filter"=="1")
		. select_option(0,"no","$ldap_filter"=="0")
		. select_footer() : NULL) ,
	' ' ,
	input_submit("Search","search")
) );


$devices = get_device_list ($name_filter, $secret_filter, $stat_filter, $ldap_filter);

foreach ( $devices as $device ) {
	echo table_row ( array (
		a("device.php?device=$device->id", device_name($device) ) ,
		a("device.php?device=$device->id", ($role == 'A') ? $device->secret : "") ,
		($device->enabled) ? "active" : "locked" ,
		(LDAP_ACCESS_SYNC ? (($device->ldap) ? "yes" : "no") : NULL) ,
		( ($device->lasttime) ? date("d.m.Y",10* $device->lasttime) : "never used" ) 
	) );
}

echo table_footer();
echo form_footer();


echo form_header("POST","device.php");
echo input_hidden("action","add");
echo input_submit("Add new Device","add");
echo form_footer();

echo '<script type="text/javascript" src="JS/sorttable.js"></script>';

include 'INC/footer.php';

?>
