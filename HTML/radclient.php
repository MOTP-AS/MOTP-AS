<?php

include 'INC/session.php'; checkrole("A","index.php");

include 'INC/include.php';

include 'INC/rad_save.php';

if (isset($_POST['action'])) $action=input($_POST['action']); else $action="";
if (isset($_GET['client'])) $clientid=input($_GET['client']); else $clientid=0;

$title = "MOTP-AS - RADIUS clients";
include 'INC/header.php';

$bcs = array ( array("index.php","home"), array("radclients.php","RADIUS clients") );

$client=get_radclient($clientid);

if (! $client) $client = new Rad_Client();

if ($action == "disable") {
	$client->enabled = FALSE;
	update_radclient ($client);
	rad_save();
	log_audit($_SESSION['user'],"radclient lock","Client: $client->name ($client->ipv4$client->ipv6)");
	$bcs[3]=array("radclient.php?client=$clientid","$client->name");
}

if ($action == "enable") {
	$client->enabled = TRUE;
	update_radclient ($client);
	rad_save();
	log_audit($_SESSION['user'],"radclient reset","Client: $client->name ($client->ipv4$client->ipv6)");
	$bcs[3]=array("radclient.php?client=$clientid","$client->name");
}

if ($action == "delete") {
	delete_radclient ($client);
	log_audit($_SESSION['user'],"radclient delete","Client: $client->name ($client->ipv4$client->ipv6)");
	rad_save();
	stop("ok","RADIUS client deleted");
}

if ($action == "insert") {
	if (isset($_POST['name']))   $client->name   = $_POST['name'];
	if (isset($_POST['secret'])) $client->secret = $_POST['secret'];
	if (isset($_POST['ip']))     $ip = $_POST['ip']; else $ip=NULL;

	$pattern_ipv4 = '/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/';
	$pattern_ipv6 = '/^[0-9A-Fa-f:]+$/';

	$client->ipv4 = $client->ipv6 = NULL;
	if (preg_match($pattern_ipv4, $ip))
		$client->ipv4 = $ip;
	elseif (preg_match($pattern_ipv6, $ip))
		$client->ipv6 = $ip;
	else
		stop("error", "Invalid IP address");

	if ($clientid==0) {
		$client=insert_radclient ($client);
		if (! $client) stop("error", "Could not add client: " . error_msg() );
		$clientid=$client->id;
		log_audit($_SESSION['user'],"radclient add","Client: $client->name ($ip)");
	} else {
		$ok = update_radclient ($client);
		if (! $ok) stop("error", "Could not update client: " . error_msg() );
		log_audit($_SESSION['user'],"radclient modify","Client: $client->name ($ip)");
	}
	$bcs[3]=array("radclient.php?client=$clientid","$client->name");

	rad_save();
}


if ( ($action == "add") || ($action == "edit") ) {

	echo form_header("POST",  "radclient.php?client=$clientid");
	echo input_hidden("action","insert");

	echo table_header( FALSE, "edit");

	echo table_row( array( "Name: ",   input_text("name",   $client->name, 15)   ) ) ;
	echo table_row( array( "Secret: ", input_text("secret", $client->secret, 15) ) ) ;
	echo table_row( array( "IP: ",     input_text("ip",     "$client->ipv4$client->ipv6", 15, 'id="radius-ip"') ) ) ;

	echo table_footer();

	echo input_submit("Ok","send");
	echo form_footer();

	if ($action=="edit") $bcs[3]=array("radclient.php?client=$clientid","$client->name");

        $help   = p("Name = name, only used to distinguish RADIUS clients")
                . p("Secret = RADIUS shared secret")
                . p("IP = IP address of RADIUS client")
                ;
	$hint = TRUE;

} else {
	/* show data */

	echo table_header(FALSE,"show");
	echo table_row( array( "Name:",   $client->name) ); 
	echo table_row( array( "Status:", ($client->enabled) ? "enabled" : "disabled" ) );
	echo table_row( array( "Secret:", $client->secret) );
	echo table_row( array( "IP:",     "$client->ipv4$client->ipv6") );
	echo table_footer(); 

	echo form_header("POST", "radclient.php?client=$clientid") . input_hidden("action", "disable") . input_submit("Disable","lock")  . form_footer();
	echo form_header("POST", "radclient.php?client=$clientid") . input_hidden("action", "enable")  . input_submit("Enable","reset")  . form_footer();
	echo form_header("POST", "radclient.php?client=$clientid") . input_hidden("action", "edit")    . input_submit("Edit","edit")     . form_footer();
	$warn = (WARN_DELETE) ? "Are you sure to delete RADIUS client '$client->name'?" : FALSE;
	echo form_header("POST", "radclient.php?client=$clientid", $warn) . input_hidden("action", "delete")  . input_submit("Delete","delete") . form_footer();

	$bcs[3]=array("radclient.php?client=$clientid","$client->name");

        $help   = p("Disable = lock entry for radius client, i.e. authentication on this RADIUS client is no longer possible.")
                . p("Enable = enable RADIUS client, if it is disabled.")
                . p("Edit = edit name, secret and IP address")
                . p("Delete = delete entry from database; authentication on this RADIUS client is no longer possible.")
                ;

}


include 'INC/footer.php';

?>
