<?php

include 'INC/session.php'; checkrole("AH","index.php");

include 'INC/include.php';

$title = "MOTP-AS - Users";
include 'INC/header.php';

if (isset($_POST['user']))   { $user_filter = input($_POST['user']);   } else { $user_filter=""; }
if (isset($_POST['name']))   { $name_filter = input($_POST['name']);   } else { $name_filter=""; }
if (isset($_POST['role']))   { $role_filter = input($_POST['role']);   } else { $role_filter=""; }
if (isset($_GET['status']))  { $stat_filter = input($_GET['status']);  } else { $stat_filter=""; }
if (isset($_POST['status'])) { $stat_filter = input($_POST['status']); } 
if (isset($_GET['statpw']))  { $pw_filter   = input($_GET['statpw']);  } else { $pw_filter=""; }
if (isset($_POST['statpw'])) { $pw_filter   = input($_POST['statpw']); } 
if (isset($_GET['ldap']))    { $ldap_filter = input($_GET['ldap']);    } else { $ldap_filter=""; }
if (isset($_POST['ldap']))   { $ldap_filter = input($_POST['ldap']);   } 

$bcs = array ( array("index.php","home"), array("users.php","Users") );

echo form_header("POST","users.php");
echo table_header ( array("User", "Name", "Role", "Status", "StaticPW", LDAP_ACCESS_SYNC ? "LDAP" : NULL, "Last OTP Login"), "list sortable" );

$select = select_header("role","width:35px;");
$select .= select_option('','');
foreach (array_keys($ROLES) as $key) 
	$select .= select_option($key, "$key ($ROLES[$key])", "$role_filter"=="$key");
$select .= select_footer();

echo table_row ( array (
	input_text("user", $user_filter, 10) ,
	input_text("name", $name_filter, 20) ,
	$select,
	select_header("status") 
		. select_option('','') 
		. select_option(1,"active","$stat_filter"=="1") 
		. select_option(0,"locked","$stat_filter"=="0") 
		. select_footer() ,
	select_header("statpw")
		. select_option('','')
		. select_option(1,"yes","$pw_filter"=="1")
		. select_option(0,"no","$pw_filter"=="0")
		. select_footer() ,
	(LDAP_ACCESS_SYNC) ? select_header("ldap")
		. select_option('','')
		. select_option(1,"yes","$ldap_filter"=="1")
		. select_option(0,"no","$ldap_filter"=="0")
		. select_footer() : NULL ,
	' ' ,
	input_submit("Search","search")
	) );

$users = get_user_list ($user_filter, $name_filter, $role_filter, $stat_filter, $pw_filter, $ldap_filter);

foreach ( $users as $user ) {
	echo table_row ( array (
		a("user.php?user=$user->id", $user->user) ,
		a("user.php?user=$user->id", $user->name) , 
		"$user->role" ,
		( (! $user->enabled) ? "locked" : ( ($user->tries > MAXTRIES) ? "locked (OTP)" : ("$user->tries"."/".MAXTRIES) ) ),
		( ($user->pw) ? "yes" : "no" ),
		( LDAP_ACCESS_SYNC ? (($user->ldap) ? "yes" : "no" ) : NULL ),
		( ($user->llogin) ? date("d.m.Y H:i:s",$user->llogin) : "never logged in" )
	) );
}

echo table_footer();
echo form_footer();


echo form_header("POST","user.php");
echo input_hidden("action","add");
echo input_submit("Add new User","add");
echo form_footer();

echo '<script type="text/javascript" src="JS/sorttable.js"></script>';
/* echo '<script type="text/javascript">
elem = document.getElementsByTagName("table")[0].tHead.rows[0].cells[0];
evObj = document.createEvent("MouseEvents");
evObj.initEvent( "click", true, true );
window.onload=elem.dispatchEvent(evObj);
</script>';
*/

include 'INC/footer.php';

?>
