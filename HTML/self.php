<?php

include 'INC/session.php';

include 'INC/include.php';

$title = "MOTP-AS - Settings";
include 'INC/header.php';

include 'INC/ldap_func.php';
$cmd="";
if (isset($_GET['cmd']))  $cmd=input($_GET['cmd']); 	// for direct links
if (isset($_POST['cmd'])) $cmd=input($_POST['cmd']); 

$action="";
if (isset($_GET['action']))  $action=input($_GET['action']); 	// for direct links
if (isset($_POST['action'])) $action=input($_POST['action']); 

$bcs = array ( array("index.php","home"), array("self.php","User Info") );

$userid  = get_user_id($_SESSION['user']);
if ($userid == 0) 
	stop("","");

$user = get_user($userid);

$devices  = get_devices_of_user($userid);
$accounts = get_accounts_of_user($userid);


if ($action == "set pin") {
	if (isset($_POST['oldpin'])) $oldpin = input($_POST['oldpin']); else $oldpin = "";
	if (isset($_POST['newpin'])) $newpin = input($_POST['newpin']); else $newpin = "";
	if (isset($_POST['account'])) $accountid = input($_POST['account']);

	$account = get_account ($accountid);
	if (! $account) {
		stop("error","unknown account.");
	}
	if ($account->userid != $userid) {
		stop("error","permission denied.");
	}
	if ( ($account->pin !== $oldpin) && (! ( $account->pin==="" ) ) ) {
		$bcs = array ( array("index.php","home"), array("self.php?action=change+pin","Change PIN") );
		stop("warning","wrong pin");
	} 
	if ( strlen($newpin) < PIN_MIN_LENGTH ) {
		$bcs = array ( array("index.php","home"), array("self.php?action=change+pin","Change PIN") );
		stop("warning","minimal pin length is " . PIN_MIN_LENGTH . ".");
	} 
	$account->pin = $newpin;
	update_account($account);
	log_audit($_SESSION['user'],"change pin","Account: $account->id");
}


if ($action == "change pin") {

	if ($accounts == array() ) {
		stop("warning","You have no account, sorry.");
	}

	echo form_header("POST", "self.php");
	echo input_hidden("action","set pin");

	echo table_header(FALSE, "edit");

	$select = select_header("account");
	foreach ($accounts as $account)
		$select .= select_option($account->id, device_name(get_device($account->deviceid)) );
	$select .= select_footer();
	echo table_row( array( "Change PIN for Device: ", $select) ); 
	echo table_row( array( "Old PIN: ", input_text("oldpin", "", 4) ) );
	echo table_row( array( "New PIN: ", input_text("newpin", "", 4, 'id="account-pin"') ) );
	echo table_footer();

        echo '	<script type="text/javascript">
			oldpin = document.getElementsByName("oldpin")[0];
			newpin = document.getElementsByName("newpin")[0];
			account =  document.getElementsByName("account")[0];
			function emptypin() {
				oldpin.disabled=true; oldpin.value="****";
				newpin.focus();
			';
	foreach ($accounts as $account) {
		if ( $account->pin === "" )
			echo '		if (account.value == ' . $account->id . ') return; 
		';
	}
	echo '
				oldpin.disabled=false; oldpin.value="";
				oldpin.focus();
				};
			account.onchange=emptypin;
			account.onchange();
		</script>
		';

	echo input_submit("Ok");
	echo form_footer();

	$hint = TRUE;
	$bcs = array ( array("index.php","home"), array("self.php?action=change+pin","Change PIN") );

} else if ($action == "ldap") {

        echo table_header(FALSE, "show");
        echo table_row( array( "Ldap server:", @LDAP_SERVER) );
        echo table_row( array( "Ldap dn:", @LDAP_DN) );
        echo table_row( array( "Ldap login:", @LDAP_LOGIN) );
        echo table_row( array( "Ldap filter:", @LDAP_FILTER) );

        if ($cmd==1) 
        echo table_row( array( "Sync. obj:",SyncLdapUsers() ));

        echo table_footer();

        echo form_header("POST", "self.php");
        echo input_hidden("action","ldap");
        echo input_hidden("cmd","1");
        echo input_submit("Sync. Users");
        echo form_footer(); 
} else if ($action == "get client") {

        if ($accounts == array() ) {
                stop("warning","You have no account, sorry.");
        }

        echo form_header("POST\" target=\"_blank", "client.php");

        echo table_header(FALSE, "edit");

        $select = select_header("account");
        foreach ($accounts as $account)
                $select .= select_option($account->id, device_name(get_device($account->deviceid)) );
        $select .= select_footer();
        echo table_row( array( "Device: ", $select) );

        echo table_footer();

        echo input_submit("Open HTML client");
        echo form_footer();

	$bcs = array ( array("index.php","home"), array("self.php?action=get+client","HTML client") );

	$help   = p("Please be aware, that secret is stored in HTML client in clear text!");

} else {
	/* show user data */

	echo table_header(FALSE, "show");

	echo table_row( array( "User:", $user->user) );
	echo table_row( array( "Name:", $user->name) );
	echo table_row( array( "Role:", $ROLES[$user->role]) );

	echo table_row( array(
		"Status:",
		( (! $user->enabled) ? "locked" : ( ($user->tries > MAXTRIES) ? "locked" : "active" ) ) . br()
		. "$user->tries/" . MAXTRIES . " failed logins" . br()
		. "last OTP login: " . ( ($user->llogin) ? date("d.m.Y H:i:s",$user->llogin) : "never logged in" ) . br() 
	) );

	$devlist = "";
	foreach ($devices as $device) 
		$devlist .= device_name($device) . br(); 
	echo table_row( array( "Devices:", $devlist) );

	echo table_footer();

	echo form_header("POST", "self.php");
	echo input_hidden("action","change pin");
	echo input_submit("Change pin");
	echo form_footer();
}


include 'INC/footer.php';

?>
