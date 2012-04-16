<?php

include 'INC/session.php'; checkrole("A","index.php");

include 'INC/include.php';

$title = "MOTP-AS - LDAP Configuration";
include 'INC/header.php';

if (isset($_GET['realm']))   $realm=input($_GET['realm']);    else $realm="S";
$bcs = array ( array("index.php","home"), array("conf.php?realm=$realm",$CONF_REALMS[$realm]) );

if (isset($_POST['action'])) $action=input($_POST['action']); else $action="";

$conf = get_config(0,$realm,'');


if ($action == "update") {
	$update="";

	if (isset($_POST['conf']))
	   foreach (array_keys($_POST['conf']) as $par) {
		$value = input($_POST['conf'][$par]);
		// if ($value == "") continue;
		if ($value != $conf[$par]) {
			set_config(0,$par,$value);
			log_audit($_SESSION['user'],"setting $CONF_REALMS[$realm]","$par = $value");
			$update .= "$par = $value" . br();
		}
	}

	if ($update!="") {
		$class="ok"; $msg="Settings changed:" . br() . $update; 
	} else { 
		$class="warning"; $msg="No settings changed";
	}
	echo div_header("class","$class message") . $msg . div_footer();

	$conf = get_config(0,$realm,'');
}


/* show settings */
echo form_header("POST", "conf.php?realm=$realm");
echo input_hidden("action","update");

echo table_header( array("","$CONF_REALMS[$realm] Settings"), "grid");

function input_element ($name,$value,$select) {
	if ($select) {
		$ret = select_header($name);
		foreach ($select as $option)
			$ret .= select_option($option,$option,"$value"===$option);
		$ret .= select_footer();
		return $ret;
	}
	$size=strlen($value)+1;
	if ($size<20) $size=20;
	if ($size>20) $size=30;
	return input_text($name,$value,$size);
}

foreach (array_keys($conf) as $par) {
	$value = $conf[$par];
	$select=FALSE; if ( ($value==="TRUE") || ($value==="FALSE") ) $select=array("TRUE","FALSE");
	$value = input_element("conf[$par]",$value,$select);

	echo table_row( array( $par, $value) ) ;
}


echo table_footer();

echo input_submit("Update");
echo form_footer();


switch ($realm) {
   case 'S':	
	$help	= ""
	        . p("LOCK_GRACE_MINS = Minutes after which a user is again allowed access, if number of tries exceeded (0 = never).")
		. p("LOGS_PURGE_<i>xxx</i> = automatically purge log entries after this number of days (0 = never)")
                . li("ul",array(
                        array("acc"," - Accounting Log"),
                        array("audit"," - Audit Log"),
                        array("auth"," - Authentication Log")
                ))
	        . p("MAXDIFF = max. allowed drift of mobile device, defined in sec.; default: 180")
	        . p("MAXTRIES = max nr. of allowed failed logins before locking; default: 5")
	        . p("PIN_MIN_LENGTH = minimal PIN length when setting new PIN")
	        . p("USE_LDAP = enable/disable LDAP support")
	        . p("VALID_CHARS = valid characters in username, verified for RADIUS logins")
		;
	break;
   case'L':
	$help	= ""
	        . p("LDAP_ACCESS_..."
		        . li("dl",array(array("","enable/disable LDAP access for ..."
			        . li("ul",array(
					array("PWD"," - temporary static passwords"),
					array("RAD"," - fetching value of RADIUS attributes"),
					array("SYNC"," - synchronization of users/devices"),
				))
			)))
		)
	        . p("LDAP_BIND_USER<br />LDAP_BIND_PASSWORD"
		        . li("dl",array(array("","username and password for connecting to LDAP server.")))
		)
		. p("")
	        . p("LDAP_CONNECT_HOST<br />LDAP_CONNECT_PORT<br />LDAP_CONNECT_PROTO"
		        . li("dl",array(array("","hostname, port, and protocol version of LDAP server; multiple hostnames can be set, seperated by comma (,).")))
		)
		. p("")
	        . p("LDAP_USER_..."
		        . li("ul",array(
				array("BASE"," - search base, for example \"DC=test,DC=com\""),
				array("SEARCH"," - search filter, for example \"(samaacountname=%s)\"; %s is replaced by the username"),
				array("NAME"," - LDAP attribute for the full username, for example \"userprincipalname\" or \"mail\""),
				array("DEVICES"," - LDAP attribute for the user's device, for example \"telephoneNumber\"; can be a multi-value-field"),
			))
		)
		. p("")
	        . p("LDAP_REMOVE_USERS<br />LDAP_REMOVE_DEVICES<br />LDAP_REMOVE_ACCOUNTS"
		        . li("dl",array(array("","If TRUE, data entries for no longer existing users will be deleted,<br />if FALSE, the user/device will be locked.")))
		)
		. p("")
		;
	break;
}


include 'INC/footer.php';

?>
