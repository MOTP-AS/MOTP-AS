<?php

include 'INC/session.php'; checkrole("AH","index.php");

include 'INC/include.php';


if (isset($_POST['action'])) $action=input($_POST['action']); else $action="";


$title = "MOTP-AS - Wizard - Import";
include 'INC/header.php';

$bcs = array ( array("index.php","home"), array("wiz_import.php","Import") );


function process_csv ($csv) {
	global $ROLES;

	if (!is_array($csv)) return;
	if (count($csv) != 10) return;
	$warning = FALSE;
// print_r($csv);
	if ( ($csv[2]==="role") && ($csv[4]==="Secret") && ($csv[5]==="Timezone")) return;

	$user = new User();
	$user->user = $csv[0];
	$user->name = $csv[1];
	$user->role = $csv[2];
	if (!array_key_exists($user->role,$ROLES)) $user->role='U'; 
// print_r($user);
	if ($user->user !== "") {
		$user=insert_user ($user);
		if ($user) {
			log_audit($_SESSION['user'],"user add","User: $user->user ($user->name)");
		} else {
			if ($warning) $warning .= br();
			$warning .= "Error importing User: " . error_msg();
			$user = new User();
		}
	} else {
		if ($warning) $warning .= br();
		$warning .= "No User data";
	}

	$device = new Device();
	$device->name = $csv[3];
	$device->secret = $csv[4];
	$device->secret = strtolower($device->secret);
	$device->timezone = $csv[5];
// print_r($device);
	if ($device->secret !== "") {
		$device=insert_device ($device);
		if ($device) {
			log_audit($_SESSION['user'],"device add","Device: $device->id ($device->name)");
		} else {
			if ($warning) $warning .= br();
			$warning .= "Error importing Device: " . error_msg();
			$device = new Device();
		}
	} else {
		if ($warning) $warning .= br();
		$warning .= "No Device data";
	}

	$account = new Account();
	$account->pin = $csv[6];
	$account->userid = $user->id;
	$account->deviceid = $device->id;
// print_r($account);
	if ( ($account->pin !== "") && ($account->userid != "") && ($account->deviceid != "") ) {
		$account=insert_account ($account);
		if ($account) {
			log_audit($_SESSION['user'],"account add","Account: $account->id ($account->userid, $account->deviceid)");
		} else {
			if ($warning) $warning .= br();
			$warning .= "Error importing Account: " . error_msg();
			$account = new Account();
		}
	} else {
		if ($warning) $warning .= br();
		if ($account->pin === "")	$warning .= "No Account data";
		elseif ($account->userid == "")	$warning .= "No Account added: Which user?";
		else				$warning .= "No Account added: Which Device?";
	}

	$static = new StaticPW();
	$static->hash = $csv[7];
	$static->howoften = $csv[8];
	if ($static->howoften == "") $static->howoften=-1;
	$static->until = $csv[9];
	$static->userid=$user->id;
	$static->salt=substr(md5(rand()),0,2);
	$static->hash=md5($static->salt . $static->hash);
// print_r($static);
	if ( ($csv[7] !== "") && ($static->userid != "") ) {
		if (set_static ($static) !== FALSE) {
			log_audit($_SESSION['user'],"static password set","User: $user->user ($user->name); Until: $static->until; Howoften: $static->howoften");
		} else {
			if ($warning) $warning .= br();
			$warning .= "Error setting Static Password";
		}
	} elseif ($csv[7] !== "") {
		if ($warning) $warning .= br();
		$warning .= "No Static Password added: Which user?";
	}

	if ($warning) {
		$status = "<font color=\"orange\">" . $warning . "</font>";
	} else {
		$status = "<font color=\"green\">ok</font>";
	}
		
	return table_row ( array(
		($user->id       == "") ? "$csv[0]" : a("user.php?user=$user->id",user_name($user)),
			$user->role, "", 
		($device->id     == "") ? "$csv[3]" : a("device.php?device=$device->id",device_name($device)),
			$device->secret, $device->timezone, "", 
		($account->id    == "") ? "$csv[6]" : a("account.php?account=$account->id",$account->pin), 
			"", 
		($static->userid == "") ? "$csv[7]" : a("static.php?user=$static->userid",$csv[7]),
			$static->howoften, $static->until, "",
		$status
	) );
}


if ( ($action == "upload") || ($action == "import") ) {

	$header = table_header(FALSE,"show");
	$header .= table_header ( array(
		"User", "Role", "", 
		"Device", "Secret", "Timezone", "", 
		"PIN", "", 
		"Static Password", "how often", "until", "",
		"Status"
	) );

	if ($action == "upload") {
		if (!array_key_exists('csv',$_FILES)) stop("warning","no file uploaded.");
		if ($_FILES['csv']['name']=="") stop("warning","no file uploaded.");
		if ($_FILES['csv']['error']!=0) stop("error","Internal error.");
		$file = $_FILES['csv']['tmp_name']; 
		if (($fp = fopen($file, "r")) === FALSE) stop("error","Internal error.");
		echo $header;
		while (($dataset = fgetcsv($fp)) !== FALSE) {
			echo process_csv($dataset);
		}
		fclose($fp);
		// remove file???
	} else {
		if (isset($_POST['csv'])) $csv=input($_POST['csv']); else $csv="";
		if ($csv == "") stop("warning","empty file");
		$csv = strtr($csv, array('\r\n' => '\n', '\r' => '\n'));
		$csv = explode ('\n', $csv);
		echo $header;
		foreach ($csv as $line) {
			$dataset = str_getcsv($line);
			echo process_csv($dataset);
		}
	}

	echo table_footer();
		
} else {
	$example_line = "john,Test User,,Nokia Phone,1234567812345678,,1234,initpassword,1,";

	echo p(
		form_header("POST\" enctype=\"multipart/form-data", "wiz_import.php")
		. input_hidden("action","upload")
		. "Upload as file ..."
		. input_hidden("MAX_FILE_SIZE", convertBytes(ini_get('upload_max_filesize')))
		. input_file("csv")
		. input_submit("Upload")
		. br()
		. "Use " . a("import.csv","import.csv") . " as template"
		. form_footer()
	);

	if (function_exists('str_getcsv'))	// PHP version >= 5.3.0
	    echo p(
		"<hr />"
		. form_header("POST", "wiz_import.php")
		. input_hidden("action","import")
		. "... or paste into text area and "
		. input_submit("Import")
		. br()
		. input_area("csv", $example_line, "70", "20")
		. form_footer()
	);

	$help = p("For both upload and text area, please use comma seperated lines with following values; you may leave empty for default values.") 
		. li( "ul", array(
			array("user (Loginname)"," - leave empty, if you only want to import devices"),
			array("Name of User",""),
			array("User's Role"," - 'U' or '' for users, 'H' for helpdesk users and 'A' for admins"), 
			array("Device name",""),
			array("Secret"," - leave empty, if you only want to import users"),
			array("Timezone"," - leave empty for no timezone adjustment"),
			array("PIN"," - leave empty, if you do not want to create an account"),
			array("Static Password"," - leave empty for no static password"),
			array("Count"," how often static password can be used (can be empty)"),
			array("Expiration date"," for static password (can be empty)")
		) );
}


include 'INC/footer.php';

?>
