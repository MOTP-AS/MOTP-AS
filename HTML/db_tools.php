<?php

include 'INC/session.php'; checkrole("A","index.php");
include 'INC/include.php';

if (isset($_POST['action'])) $action=input($_POST['action']); else $action="";


if ($action == "export") {
	$filename = "motp_" . $_SERVER['SERVER_NAME'] . "_" . date("YmdHis") . ".backup";
	header("Content-Type: text/plain");
	header("Content-Disposition: attachment; filename=\"$filename\"");
	log_audit($_SESSION['user'],"backup export",input(implode(", ",$_POST['export'])));
	foreach ($_POST['export'] as $export) {
		switch($export) {
		  case 'pref':
			db_export("config");
			break;
		  case 'data':
			db_export("users");
			db_export("devices");
			db_export("accounts");
			db_export("static");
			break;
		  case 'rads':
			db_export("rad_clients");
			db_export("rad_profiles");
			break;
		  case 'logs':
			db_export("log_auth");
			db_export("log_acc");
			db_export("log_audit");
			break;
		}
	}
	exit(0);
}


$title = "DB tools";
include 'INC/header.php';

$bcs = array ( array("index.php","home"), array("db_tools.php","DB tools") );


if ($action == "import") {

	if (!array_key_exists('import',$_FILES)) stop("warning","no file uploaded.");
	if ($_FILES['import']['name']=="") stop("warning","no file uploaded.");
	if ($_FILES['import']['error']!=0) stop("error","Internal error.");
	$file = $_FILES['import']['tmp_name']; 
	if (($fp = fopen($file, "r")) === FALSE) stop("error","Internal error.");
	echo "Importing ";
	while (($line = fgets($fp)) !== FALSE) {
		if ($line == "") continue;		// empty line
		if ($line[0] == '#') continue;		// comment
		if (!strstr($line,'=')) continue;	// no data
		list($table,$data) = explode('=',$line,2);
		$table=input($table);
		if (db_import($table,$data)) echo ".";
	}
	fclose($fp);
	// remove file???

	include 'INC/rad_save.php';
	rad_save();

	log_audit($_SESSION['user'],"backup import",input($_FILES['import']['name']));
	stop("ok","Backup imported.");	
} 

$warn = "Attention! If you upload the configuration all current data will be overwritten!\nARE YOU REALLY SURE TO PROCEED?";
echo p(
	table_header() . form_header("POST\" enctype=\"multipart/form-data", "db_tools.php", $warn)
	. input_hidden("action","import")
	. input_submit("Import")
	. " old configuration: "
	. input_hidden("MAX_FILE_SIZE", convertBytes(ini_get('upload_max_filesize')))
	. input_file("import")
	. form_footer() . table_footer()
);
echo p("<hr>");
echo p(
	form_header("POST", "db_tools.php")
	. input_hidden("action","export")
	. input_submit("Export")
	. " current configuration:"
	. div_header("","button")
	. table_header()
	. table_row( array( input_checkbox("export[]","data",TRUE) . " MOTP configuration: users/devices/accounts"))
	. table_row( array( input_checkbox("export[]","rads",TRUE) . " RADIUS settings"))
	. table_row( array( input_checkbox("export[]","pref",TRUE) . " system settings / preferences"))
	. table_row( array( input_checkbox("export[]","logs")      . " log files"))
	. table_footer()
	. div_footer()
	. form_footer()
);


$help = p("This Import/Export of current settings provides simple backup/restore functionality. For advanced database administration, please use phpMyAdmin.")
	. li("ul",array(
		array("Import", ": to upload files exported through this tools. <b>All existing settings will be overwritten!</b>"),
		array("Export", ": export selected configuration into PHP serialized data.")
	  ));


include 'INC/footer.php';

?>
