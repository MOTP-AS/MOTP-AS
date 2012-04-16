<?php

include 'INC/session.php'; checkrole("AH","index.php");

include 'INC/include.php';


if (isset($_POST['action'])) $action=input($_POST['action']); else $action="";


$title = "MOTP-AS - Wizard - Quick Add";
include 'INC/header.php';

$bcs = array ( array("index.php","home"), array("wiz_quickadd.php","Quick Add") );



if ($action == "insert") {
	echo table_header(FALSE,"show");

	$user = new User();
	if (isset($_POST['user'])) $user->user = input($_POST['user']);
	$user->role='U';
	$user->name=$user->user;
	$user=insert_user ($user);
	if (! $user) stop("error", "Could not add user: " . error_msg() );
	log_audit($_SESSION['user'],"user add","User: $user->user ($user->name)");
	echo table_row( array ("User:", a("user.php?user=$user->id", $user->user) ) );

	$device = new Device();
	if (isset($_POST['secret'])) $device->secret = input($_POST['secret']);
	$device->secret = strtolower($device->secret);
	$device->name = input($user->user . "'s Device");
	$device=insert_device ($device);
	if (! $device) stop("error", "Could not add device:" . error_msg() );
	log_audit($_SESSION['user'],"device add","Device: $device->id ($device->name)");
	echo table_row( array ("Device:", a("device.php?device=$device->id", $device->name) ) );

	$account = new Account();
	if (isset($_POST['pin'])) $account->pin = input($_POST['pin']);
	$account->userid = $user->id;
	$account->deviceid = $device->id;
	$account=insert_account ($account);
	if (! $account) stop("error", "Could not add account.");
	log_audit($_SESSION['user'],"account add","Account: $account->id ($account->userid, $account->deviceid)");
	echo table_row( array ("Account:", a("account.php?account=$account->id", $user->user ."/". $device->name) ) );

	echo table_footer();
} else {

	echo form_header("POST", "wiz_quickadd.php");
	echo input_hidden("action","insert");
	echo table_header(FALSE,"edit");
	echo table_row( array( "User",    input_text("user", "") ) ) . br() ;
	echo table_row( array ("Secret:", input_text("secret", "", 20, 'id="device-secret"' ) ));
	echo table_row( array( "PIN:",    input_text("pin", "", 4, 'id="account-pin"') ) );
	echo table_footer();	
	echo input_submit("Ok","send");
	echo form_footer();

	$help   = p("user = login name (used for authentication)")
		. p("secret = secret generated at initialization of token.")
		. p("PIN = PIN used by user for this secret.")
		;
	$hint = TRUE;


}


include 'INC/footer.php';

?>
