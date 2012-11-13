<?php

include 'INC/session.php';

include 'INC/include.php';

$title = "MOTP-AS - Preferences";
include 'INC/header.php';

if (isset($_POST['action'])) $action=input($_POST['action']); else $action="";

$bcs = array ( array("index.php","home"), array("pref.php","Preferences") );

$userid  = get_user_id($_SESSION['user']);
if ($userid == 0) 
	stop("","");


$global_prefs = get_config(0,'P','');
$local_prefs = get_config($userid,'P','');

$scope_global = get_config(0,'P','G');
$scope_admin= get_config(0,'P','A');


if ($action == "update") {
	$update="";

	if (isset($_POST['gp']))
	   foreach (array_keys($_POST['gp']) as $par) {
		if ($role != 'A') continue;
		$value = input($_POST['gp'][$par]);
		if ($value == "") continue;
		if ($value != $global_prefs[$par]) {
			set_config(0,$par,$value);
			log_audit($_SESSION['user'],"setting global","$par = $value");
			$update .= "Global Setting: $par = $value" . br();
		}
	}

	if (isset($_POST['lp']))
	   foreach (array_keys($_POST['lp']) as $par) {
		$value = input($_POST['lp'][$par]);
		if (array_key_exists($par,$scope_global)) continue;
		if ($role != 'A') {
			if (array_key_exists($par,$scope_admin)) continue;
		}
		if ($value == "") {
			if (! (array_key_exists($par,$local_prefs)) ) continue;
			delete_config($userid,$par);
			log_audit($_SESSION['user'],"setting local","removed: $par");
			$update .= "Personal Setting: $par reset" . br();
		} elseif ( (!(array_key_exists($par,$local_prefs))) || ($value != $local_prefs[$par]) ) {
			// echo "Local: $par = $value\n";
			set_config($userid,$par,$value);
			log_audit($_SESSION['user'],"setting local","$par = $value");
			$update .= "Personal Setting: $par = $value" . br();
		}
	}
	if ($update!="") {
		$class="ok"; $msg="Settings changed:" . br() . $update;
	} else {
		$class="warning"; $msg="No settings changed";
	}
	echo div_header("class","$class message") . $msg . div_footer();

	$global_prefs = get_config(0,'P','');
	$local_prefs = get_config($userid,'P','');
}


/* show preferences */
echo form_header("POST", "pref.php");
echo input_hidden("action","update");

echo table_header( array("","Global Settings","Personal Settings"), "grid");

function input_element ($name,$value,$select) {
	if ($select) {
		$ret = select_header($name);
		foreach ($select as $option)
			$ret .= select_option($option,$option,"$value"===$option);
		$ret .= select_footer();
		return $ret;
	}
	$size=strlen($value)+3;
	if ($size<10) $size=10;
	if ($size>20) $size=20;
	return input_text($name,$value,$size);
}
if ((strstr($par, "PASSWD")) and !($role == 'A'))   
        $global = "******";

foreach (array_keys($global_prefs) as $par) {
	$select=FALSE;

	$global = $global_prefs[$par];
	if ( ($global==="TRUE") || ($global==="FALSE") ) $select=array("TRUE","FALSE");
	if ($role == 'A')
		$global = input_element("gp[$par]",$global,$select);
	elseif (strlen($global)>20)
		$global = chunk_split($global,20,br());

	$change = TRUE;
	if (array_key_exists($par,$scope_admin)) $change=FALSE;
	if ($role == 'A') $change = TRUE;
	if (array_key_exists($par,$scope_global)) $change=FALSE;
	$local = "";
	if (array_key_exists($par,$local_prefs)) $local=$local_prefs[$par];	
	if ($select) $select=array("","TRUE","FALSE");
	if ($change)
		$local = input_element("lp[$par]",$local,$select);
	else
		$local = "-";

	echo table_row( array( $par, $global, $local) ) ;

}


echo table_footer();

echo input_submit("Update");
echo form_footer();


$help	= ""
	. p("GENERATE_PASSCODE = whether to show \"generate passcode\" button for accounts (TRUE/FALSE)")
	. p("LOGS_ROWS = nr. of rows when showing logs")
	. p("SHOW_HELP = whether to show help box (TRUE/FALSE)")
	. p("SHOW_HINT = whether to show interactive hints (TRUE/FALSE)")
	. p("SHOW_BUTTONS = whether to show graphical buttons (TRUE/FALSE)")
	. p("SHOW_PIN = "
		. li("ul",array(
			array("-"," - do not show PIN"),
			array("S"," - show PIN only to User (self)"),
			array("A"," - show PIN to Administrators (and Users)"),
			array("H"," - show PIN to Helpdesk, too, but only if not data of Administrator or other Helpdesk")
		))
	)
	. p("WARN_DELETE = confirm deletion of data")
	;



include 'INC/footer.php';

?>
