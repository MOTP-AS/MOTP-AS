<?php

include 'INC/session.php'; checkrole("AH","index.php");

include 'INC/include.php';


if (isset($_POST['action'])) $action=input($_POST['action']); else $action="";
if (isset($_GET['account'])) $accountid =input($_GET['account']); else $accountid=0;

$title = "MOTP-AS - Accounts";
include 'INC/header.php';

$bcs = array ( array("index.php","home"), array("accounts.php","Accounts") );

$account=get_account($accountid);

if ($account) {
	$userrole=get_user_role($account->user);
	if ( ($role != 'A') && ($userrole != 'U') && ($_SESSION['user'] != $account->user) ) 
		stop("error", "Not enough rights to access account of an user with role \"$userrole\"");
	$bcs[3] = array("account.php?account=$accountid","$account->user/$account->device");
}


if (! $account) $account = new Account();

if ($action == "delete") {
	delete_account ($account);
	log_audit($_SESSION['user'],"account delete","Account #$account->id: - User #$account->userid ($account->user), Device #$account->deviceid ($account->device)");
	array_splice($bcs,2);
	stop("ok", "Account deleted");
}

if ($action == "insert") {
	if (isset($_POST['user']))   $account->userid   = input($_POST['user']);
	if (isset($_POST['device'])) $account->deviceid = input($_POST['device']);
	if (isset($_POST['pin']))    {
		if ( $_POST['pin'] != "****" ) 
				     $account->pin      = input($_POST['pin']);
	}
	if (isset($_POST['ldap']))   $account->ldap = TRUE ; else $account->ldap = FALSE;

	$user=get_user($account->userid);
	$userrole=get_user_role($user->user);
	if ( ($role != 'A') && ($userrole != 'U') && ($_SESSION['user'] != $user->user) ) 
		stop("error", "Not allowed to create an account for an user with role \"$userrole\"");

	if ($accountid==0) {
		$account=insert_account ($account);
		if (! $account) stop("error", "Could not add account.");
		$accountid=$account->id;
		log_audit($_SESSION['user'],"account add","Account #$account->id: - User #$account->userid ($account->user), Device #$account->deviceid ($account->device)");
		$bcs[3] = array("account.php?account=$accountid","$account->user/$account->device");
	} else {
		update_account ($account);
		log_audit($_SESSION['user'],"account modify","Account #$account->id: - User #$account->userid ($account->user), Device #$account->deviceid ($account->device)");
	}
}


if ( ($action == "add") || ($action == "edit") ) {
	$devices = get_device_list();
	$users   = get_user_list();

	echo form_header("POST", "account.php?account=$accountid");
	echo input_hidden("action","insert");

	$userselect = select_header("user");
	foreach ($users as $user) {
		$userselect .= select_option( $user->id, ($user->name=="") ? $user->user : $user->name, ($account->userid == $user->id) );
	}
	$userselect .= select_footer();

	$deviceselect = select_header("device");
	foreach ($devices as $device) {
		$deviceselect .= select_option( $device->id, device_name($device), ($account->deviceid == $device->id) );
	}
	$deviceselect .= select_footer();

	echo table_header( FALSE, "edit");
	echo table_row( array( "User:", $userselect ) );
	$fill = ($action=="add") ? "" : "****";
	$pin = show_data( SHOW_PIN, $account->pin, $fill, $account->user, $_SESSION['user'], get_user_role($account->user), $role);
	echo table_row( array( "PIN:", 
		input_text("pin", $pin, 4, 'id="account-pin"') ) );
	echo table_row( array( "Device:", $deviceselect ) );

	if (LDAP_ACCESS_SYNC)
	echo table_row( array( "LDAP", input_checkbox("ldap", $account->ldap, $account->ldap) ) ) . br() ;
	echo table_footer();

	echo input_submit("Ok","send");
	echo form_footer();

	$help   = p("User = username (login name) for this account") 
		. p("PIN = PIN used by user. Existing PINs are only displayed if managing own accounts")
		. p("Device = device (secret) for this account")
		. p("LDAP = synced via LDAP")
		;
	$hint = TRUE;

} else {
	/* show account data */

	echo table_header(FALSE,"show");
	echo table_row( array( "User:",   a("user.php?user=$account->userid", $account->user) ));
	$pin = show_data( SHOW_PIN, $account->pin, "****", $account->user, $_SESSION['user'], get_user_role($account->user), $role);
	echo table_row( array( "PIN:", $pin ));
	echo table_row( array( "Device:", a("device.php?device=$account->deviceid" , ($account->device=="") ? "Device #".$account->deviceid : $account->device  )));
	if (LDAP_ACCESS_SYNC)
	echo table_row( array( "LDAP:", $account->ldap ? "yes" : "no" ) );

	if ($action == "generate passcode") {
		log_audit($_SESSION['user'],"generate OTP","Account #$account->id: User #$account->userid ($account->user), Device #$account->deviceid ($account->device)");
		$device = get_device ($account->deviceid);
		$passcode = substr( md5( intval((gmdate("U")+$device->timezone*3600)/10) . $device->secret . $account->pin ),0,6);
		echo table_row( array( "Passcode: ", $passcode));
		}

	echo table_footer();

	echo form_header("POST", "account.php?account=$accountid") . input_hidden("action", "edit")   . input_submit("Edit", "edit")     . form_footer();
	$warn = (WARN_DELETE) ? "Are you sure to delete account '$account->user/$account->device'?" : FALSE;
	echo form_header("POST", "account.php?account=$accountid", $warn) . input_hidden("action", "delete") . input_submit("Delete", "delete") . form_footer();

        if (GENERATE_PASSCODE) {
		echo form_header("POST", "account.php?account=$accountid") . input_hidden("action", "generate passcode") . input_submit("Generate Passcode") . form_footer();
	}


        $help   = p("Edit = change/set account properties (assigned user, assigned device, PIN)")
		. p("Delete = delete account from database")
		. p("Generate passcode = generate a one time passcode for this account")
		
		;

}


include 'INC/footer.php';

?>
