<?php

include 'INC/session.php'; checkrole("A","index.php");

include 'INC/include.php';

$title = "MOTP-AS - RADIUS clients";
include 'INC/header.php';

$bcs = array ( array("index.php","home"), array("radclients.php","RADIUS clients") );


if (isset($_POST['name_filter'])) { $name_filter = input($_POST['name_filter']); } else { $name_filter=""; }



echo form_header("POST", "radclients.php");

echo table_header( array ("Name", "Secret", "IP", "Status" ), "list sortable" );

echo table_row ( array (
	input_text ("name_filter", $name_filter ),
	' ',
	' ',
	' ',
	input_submit ("Search","search")
) );


$clients = get_radclient_list ($name_filter);

foreach ( $clients as $client ) {
	echo table_row ( array (
		a("radclient.php?client=$client->id", $client->name) ,
		$client->secret ,
		( $client->ipv4 ? $client->ipv4 : $client->ipv6 ),
		( ($client->enabled) ? "enabled" : "disabled" )
	) );
	if (! $client->enabled) $help=p("Please note, that one or more RADIUS clients are disabled.");
}

echo table_footer();
echo form_footer();


echo form_header("POST", "radclient.php");
echo input_hidden("action", "add");
echo input_submit("Add new RADIUS client","add");
echo form_footer();


echo '<script type="text/javascript" src="JS/sorttable.js"></script>';

include 'INC/footer.php';

?>
