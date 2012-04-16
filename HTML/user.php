<?php

include 'INC/session.php'; checkrole("AH","index.php");

include 'INC/include.php';


if (isset($_POST['action'])) $action=input($_POST['action']); else $action="";
if (isset($_GET['user'])) $userid=input($_GET['user']); else $userid=0;

if ($action == "static password") {
	header("Location: static.php?user=$userid");
	exit;
}

$title = "MOTP-AS - Users";
include 'INC/header.php';

$bcs = array ( array("index.php","home"), array("users.php","Users") );

$user=get_user($userid);

if ($user) $bcs[3]=array("user.php?user=$userid","$user->user");

if ( $user && ($role != 'A') && ($user->role != 'U') && ($_SESSION['user'] != $user->user) ) 
	stop("error", "Not enough rights to access user with role \"$user->role\"");

if (! $user) $user = new User();


if ($action == "lock") {
	// check_role_rights($user->role);
	$user->enabled = FALSE;
	update_user ($user);
	log_audit($_SESSION['user'],"user lock","User #$user->id: $user->user ($user->name)");
}

if ($action == "reset") {
	$user->enabled = TRUE;
	$user->tries   = 0;
	update_user ($user);
	log_audit($_SESSION['user'],"user reset","User #$user->id: $user->user ($user->name)");
}

if ($action == "delete") {
	delete_user ($user);
	log_audit($_SESSION['user'],"user delete","User #$user->id: $user->user ($user->name)");
	array_splice($bcs,2);
	stop("ok","User deleted");
}

if ($action == "insert") {
	if (isset($_POST['user'])) $user->user = input($_POST['user']);
	if (isset($_POST['role'])) $user->role = input($_POST['role']);
	if (isset($_POST['name'])) $user->name = input($_POST['name']);
	if (isset($_POST['ldap'])) $user->ldap = TRUE ; else $user->ldap = FALSE;

	if ( ($role != 'A') && ($user->role != 'U') ) 
		stop("error", "Not allowed to create an user with role \"$user->role\"");

	if ($userid==0) {
		$user=insert_user ($user);
		if (! $user) stop("error", "Could not add user: " . error_msg() );
		$userid=$user->id;
		$bcs[3]=array("user.php?user=$userid","$user->user");
		log_audit($_SESSION['user'],"user add","User #$user->id: $user->user ($user->name)");
	} else {
		update_user ($user);
		log_audit($_SESSION['user'],"user modify","User #$user->id: $user->user ($user->name)");
	}
}


if ( ($action == "add") || ($action == "edit") ) {

	echo form_header("POST", "user.php?user=$userid");
	echo input_hidden("action","insert");

	echo table_header( FALSE, "edit");
	echo table_row( array( "User", input_text("user", $user->user) ) ) . br() ;
	echo table_row( array( "Name", input_text("name", $user->name) ) ) . br() ;

	$select = select_header("role");
	foreach (array_keys($ROLES) as $key) {
		$select .= select_option($key, $ROLES[$key], ($user->role==$key) );
	}
	$select .= select_footer();
	echo table_row( array ("Role", $select) );

	if (LDAP_ACCESS_SYNC)
	echo table_row( array( "LDAP", input_checkbox("ldap", $user->ldap, $user->ldap) ) ) . br() ;
	echo table_footer();

	echo input_submit("Ok","send");
	echo form_footer();

        $help   = p("User = login name (used for authentication)") 
                . p("Name = full name, can be left empty")
                . p("Role = "
		     . li("ul", array(
			array("Administrator",": full access to web interface, including RADIUS settings"),
			array("Helpdesk",": can manage accounts for users, no access to settings of administrators or other helpdesk users, no access to RADIUS settings"),
			array("User",": Normal user, only access to \"Self Portal\" to change PIN")
		       ) )
		)
                . p("LDAP = synced via LDAP")
                ;

} else {
	/* show user data */

	echo table_header(FALSE,"show");

	echo table_row( array ( "User:"	, $user->user ) );
	echo table_row( array ( "Userid:", $user->id ) );
	echo table_row( array ( "Name:"	, $user->name ) );
	echo table_row( array ( "Role:"	, $ROLES[$user->role] ) );
	echo table_row( array ( "Status:", 
		( (! $user->enabled) ? "locked" : ( ($user->tries > MAXTRIES) ? "locked for OTP" : "active" ) )
		. br() . "$user->tries/" . MAXTRIES . " failed logins"
		. br() . "last OTP login: " . ( ($user->llogin) ? date("d.m.Y H:i:s",$user->llogin) : "never logged in" )
	) );

	$devices = get_devices_of_user($userid);
	$devlist = "";
	foreach ($devices as $device) 
		$devlist .= a("device.php?device=" . $device->id , device_name($device) ) . br() ;
	echo table_row( array ( "Devices:", $devlist) );

	echo table_row( array ( "Static Pwd:"	, get_static($user->id) ? "yes" : "no" ) );
	if (LDAP_ACCESS_SYNC)
	echo table_row( array ( "LDAP:"	, ($user->ldap) ? "yes" : "no" ) );
	echo table_footer();


	echo form_header("POST", "user.php?user=$userid") . input_hidden("action", "lock")   . input_submit("Lock", "lock")     . form_footer();
	echo form_header("POST", "user.php?user=$userid") . input_hidden("action", "reset")  . input_submit("Reset", "reset")   . form_footer();
	echo form_header("POST", "user.php?user=$userid") . input_hidden("action", "edit")   . input_submit("Edit", "edit")     . form_footer();
	$warn = (WARN_DELETE) ? "Are you sure to delete user '$user->user'?" : FALSE;
	echo form_header("POST", "user.php?user=$userid", $warn) . input_hidden("action", "delete") . input_submit("Delete", "delete") . form_footer();
	echo form_header("POST", "user.php?user=$userid") . input_hidden("action", "static password") . input_submit("Static Password","static") . form_footer();

        $help   = p("Lock = lock user; authentication with this user is no longer possible") 
                . p("Reset = unlock user, if locked, and reset number of failed logins")
                . p("Edit = edit user's name and role")
                . p("Delete = delete user from database")
                . p("Static Password = Set (temporarily) static password")
                ;
}

include 'INC/footer.php';

?>
