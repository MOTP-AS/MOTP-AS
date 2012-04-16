<?php

include 'INC/session.php'; checkrole("AH","index.php");

include 'INC/include.php';


if (isset($_POST['action'])) $action=input($_POST['action']); else $action="";
if (isset($_GET['user']))   $userid=input($_GET['user']);   else $userid=0;

$title = "MOTP-AS - Static password";
include 'INC/header.php';

$user=get_user($userid);
if ( $user && ($role != 'A') && ($user->role != 'U') && ($_SESSION['user'] != $user->user) ) 
	stop("error", "Not enough rights to access user with role \"$user->role\"");
if (! $user) stop("Unknown user.");

$bcs = array ( array("index.php","home"), array("users.php","Users"), array("user.php?user=$userid",$user->user), array("static.php?user=$userid","Static Password") );

$static=get_static($userid);
if (! $static) {
	$static = new StaticPW();
	$static->userid = $userid;
	$static->until = 0;
	$static->howoften = -1;
}

if ($action == "delete") {
	delete_static ($userid);
	log_audit($_SESSION['user'],"static delete","User #$user->id: $user->user ($user->name)");
	stop("ok","Static password deleted");
}

if ($action == "set") {
	if (isset($_POST['password'])) $password = input($_POST['password']);
	if (isset($_POST['ldap']))     $password = "LDAP";
	if (isset($_POST['until']))    $static->until    = input($_POST['until']);
	if (isset($_POST['howoften'])) $static->howoften = input($_POST['howoften']);

	if ( ($static->until=="unlimited")    || ($static->until=="")    ) $static->until=0;
	if ( ($static->howoften=="unlimited") || ($static->howoften=="") ) $static->howoften=-1;

	if ($password != "") {
		$static->salt=substr(md5(rand()),0,2);
		$static->hash=md5($static->salt . $password);
	}
	if ($password == "LDAP") {
		$static->salt="";
		$static->hash="LDAP";
	}

	set_static ($static);
	log_audit($_SESSION['user'],"static password set","User #$user->id: $user->user ($user->name); Until: $static->until; Howoften: $static->howoften");
	$msg="Static Password set."; $status="ok";
	if ( ($static->until==0) && ($static->howoften==-1) ) {
		$msg="Please note, that user must have a limitation (time range or number of allowed logins) to use static password for RADIUS authentication!" .br(). "Without limitations, the static password can only be used for login to Web Administration.";
		$status="warning";
	}
	stop($status,$msg);
}

echo '<script type="text/javascript" src="JS/datetimepicker_css.js"></script>';
$cal="<a href=\"javascript:NewCssCal('cal','yyyyMMdd','Arrow','true','24','true','23','59')\"><img src=\"IMG/cal.gif\"></a>";

echo form_header("POST", "static.php?user=" . $userid );
echo input_hidden("action","set");
echo table_header( FALSE, "edit");
$ldap = "(use LDAP password: " . input_checkbox("ldap","ldap",($static->hash=="LDAP")) . ")"
	. '<script type="text/javascript">
		password = document.getElementsByName("password")[0];
		ldap =  document.getElementsByName("ldap")[0];
		ldap.onchange=function () {
			password.disabled=ldap.checked;
			password.value=(ldap.checked?"LDAP":"");
		};
		ldap.onchange();
	</script>'
	;
echo table_row( array( "Password", input_text("password", "") . (LDAP_ACCESS_PWD ? $ldap : "") ) ) ;
echo table_row( array( "Valid until", input_text("until", ($static->until==0) ? "unlimited" : $static->until , 14, "id=\"cal\"" ) . $cal ) ) ;
echo table_row( array( "Valid for ", input_text("howoften", ($static->howoften<0) ? "unlimited" : $static->howoften ) . "logins") ) ;
echo table_footer();
echo input_submit("Set","send");
echo form_footer();

$warn = (WARN_DELETE) ? "Are you sure to delete the static password?" : FALSE;
echo form_header("POST", "static.php?user=" . $userid, $warn );
echo input_hidden("action","delete");
echo input_submit("Delete","delete");
echo form_footer();


$help   = p("You can set a (temporary) static password. The usage of the password can be limited.") 
	. p("If you select \"use LDAP password\", the LDAP password of the user will be used as temporary password.")
        . p("valid until = the static password is only valid until the given date and time. This is useful, if your users forgiot or lost their device and they need access for one day.")
        . p("valid for = the static password is only valid for the given number of logins. This is useful, if you want to allow login with a temporary password for example exactly one time.")
        . p("Please note, that you have to set a limitation, if the password should be used for RADIUS authentication. Without any limitation, it can only be used for login to the web helpdesk.")
        ;


include 'INC/footer.php';

?>
