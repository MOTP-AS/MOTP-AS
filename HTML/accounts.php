<?php

include 'INC/session.php'; checkrole("AH","index.php");

include 'INC/include.php';

$title = "MOTP-AS - Accounts";
include 'INC/header.php';

if (isset($_POST['user']))   { $user_filter   = input($_POST['user']); }   else { $user_filter=""; }
if (isset($_POST['device'])) { $device_filter = input($_POST['device']); } else { $device_filter=""; }
if (isset($_GET['ldap']))    { $ldap_filter = input($_GET['ldap']);    } else { $ldap_filter=""; }
if (isset($_POST['ldap']))   { $ldap_filter = input($_POST['ldap']);   }

$bcs = array ( array("index.php","home"), array("accounts.php","Accounts") );

echo form_header ("POST", "accounts.php" );
echo table_header( array ("User", "Device", (LDAP_ACCESS_SYNC ? "LDAP" : NULL) ), "list sortable" );

echo table_row ( array (
	input_text( "user",   $user_filter,   10),
	input_text( "device", $device_filter, 10),
	(LDAP_ACCESS_SYNC) ? select_header("ldap")
		. select_option('','')
		. select_option(1,"yes","$ldap_filter"=="1")
		. select_option(0,"no","$ldap_filter"=="0")
		. select_footer() : NULL,
	input_submit("Search","search")
) );


$accounts = get_account_list ($user_filter, $device_filter, $ldap_filter);

foreach ( $accounts as $account ) {
	echo table_row ( array (
		a("account.php?account=$account->id", $account->user) ,
		a("account.php?account=$account->id", 
		   ($account->device == "") ? ("Device #".$account->deviceid) : "$account->device" ),
		( LDAP_ACCESS_SYNC ? (($account->ldap) ? "yes" : "no") : NULL )
	) );
}

echo table_footer();
echo form_footer();


echo form_header("POST", "account.php");
echo input_hidden("action", "add");
echo input_submit("Add new Account","add");
echo form_footer();

echo '<script type="text/javascript" src="JS/sorttable.js"></script>';

include 'INC/footer.php';

?>
