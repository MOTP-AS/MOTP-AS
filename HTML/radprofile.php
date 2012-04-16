<?php

include 'INC/session.php'; checkrole("A","index.php");

include 'INC/include.php';

if (isset($_POST['action'])) $action=input($_POST['action']); else $action="";
if (isset($_GET['profile'])) $profileid=input($_GET['profile']); else $profileid=0;

$title = "MOTP-AS - RADIUS profiles";
include 'INC/header.php';

$bcs = array ( array("index.php","home"), array("radprofiles.php","RADIUS profiles") );

$profile=get_radprofile($profileid);

if (! $profile) $profile = new Rad_Profile();


if ($action == "delete") {
	delete_radprofile ($profile);
	log_audit($_SESSION['user'],"radprofile delete","$profile->type: $profile->attr $profile->op $profile->value");
	stop("ok","RADIUS profile deleted");
}

if ($action == "insert") {
	if (isset($_POST['match'])) $profile->match = $_POST['match'];
	if (isset($_POST['type']))  $profile->type  = $_POST['type'];
	if (isset($_POST['attr']))  $profile->attr  = $_POST['attr'];
	if (isset($_POST['op']))    $profile->op    = $_POST['op'];
	if (isset($_POST['value'])) $profile->value = $_POST['value'];

	if ($profileid==0) {
		$profile=insert_radprofile ($profile);
		if (! $profile) stop("error", "Could not add profile.");
		$profileid=$profile->id;
		log_audit($_SESSION['user'],"radprofile add","$profile->type: $profile->attr $profile->op $profile->value");
	} else {
		update_radprofile ($profile);
		log_audit($_SESSION['user'],"radprofile modify","$profile->type: $profile->attr $profile->op $profile->value");
	}
	$bcs[3]=array("radprofile.php?profile=$profileid","$profile->attr $profile->op $profile->value");
}


if ( ($action == "add") || ($action == "edit") ) {

	echo form_header("POST",  "radprofile.php?profile=$profileid");
	echo input_hidden("action","insert");

	echo table_header( FALSE, "edit");

	if (isset($_POST['op']))    $profile->op    = $_POST['op'];

	echo table_row( array( "Match: ",     input_text("match", $profile->match, 15)  ) ) ;
	$select = select_header("type");
	foreach (array_keys($RAD_PROF_TYPES) as $key) {
		$select .= select_option($key, $RAD_PROF_TYPES[$key], $profile->type == $key );
	}
	$select .= select_footer();
	echo table_row( array ("Type", $select) );
	echo table_row( array( "Attribute: ", input_text("attr",  $profile->attr,  15)  ) ) ;
	$select = select_header("op");
	foreach (array_keys($RAD_PROF_OPS) as $key) {
		$select .= select_option($key, $RAD_PROF_OPS[$key], $profile->op == $key );
	}
	$select .= select_footer();
	echo table_row( array ("Operand", $select) );
	echo table_row( array( "Value: ",     input_text("value", $profile->value, 15)  ) ) ;

	echo table_footer();

	echo input_submit("Ok","send");
	echo form_footer();

	if ($action=="edit")
		$bcs[3]=array("radprofile.php?profile=$profileid","$profile->attr $profile->op $profile->value");

        $help   = p("match = For matching usernames, keep empty for all users.")
                . p("type = "
			. li("ul",array(
				array("check"," - Checking of attributes before authentication"),
				array("send"," - Sending of attributes after successful authentication"),
				array("acct"," - Accounting (log)")
			))
		   )
                . p("attribute = attribute name (case sensitive!)")
                . p("op = " 
			. li("ul",array(
				array("equal (=)"," - check/send/log value"),
				array("exist (!)"," - check existence; log as send"),
				array("LDAP (*)","  - check/send/log LDAP value")
			))
		   )
                . p("value = attribute value, can be empty")
                ;

} else {
	/* show data */

	echo table_header(FALSE,"show");
	echo table_row( array( "Match:",     $profile->match) ); 
	echo table_row( array( "Type:",      $RAD_PROF_TYPES[$profile->type] . " ($profile->type)" ) );
	echo table_row( array( "Attribute:", $profile->attr) ); 
	echo table_row( array( "Operator:",  $RAD_PROF_OPS[$profile->op] . " ($profile->op)" ) );
	echo table_row( array( "Value:",     $profile->value) ); 
	echo table_footer(); 

	echo form_header("POST","radprofile.php?profile=$profileid") . input_hidden("action", "edit")   . input_submit("Edit","edit") . form_footer();
	$warn = (WARN_DELETE) ? "Are you sure to delete this RADIUS profile?" : FALSE;
	echo form_header("POST","radprofile.php?profile=$profileid", $warn) . input_hidden("action", "delete") . input_submit("Delete","delete") . form_footer();

	$bcs[3]=array("radprofile.php?profile=$profileid","$profile->attr $profile->op $profile->value");

}


include 'INC/footer.php';

?>
