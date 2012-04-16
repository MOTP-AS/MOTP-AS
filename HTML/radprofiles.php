<?php

include 'INC/session.php'; checkrole("A","index.php");

include 'INC/include.php';

$title = "MOTP-AS - RADIUS profiles";
include 'INC/header.php';

$bcs = array ( array("index.php","home"), array("radprofiles.php","RADIUS profiles") );


if (isset($_POST['match_filter'])) { $match_filter = input($_POST['match_filter']); } else { $match_filter=""; }
if (isset($_POST['type_filter']))  { $type_filter  = input($_POST['type_filter']);  } else { $type_filter=""; }
if (isset($_POST['attr_filter']))  { $attr_filter  = input($_POST['attr_filter']);  } else { $attr_filter=""; }


echo form_header("POST", "radprofiles.php");

echo table_header( array ("Match", "Type", "Attribute", "Operator", "Value" ), "list sortable" );

$select = select_header("type_filter","width:35px;");
$select .= select_option('','');
foreach (array_keys($RAD_PROF_TYPES) as $key) 
        $select .= select_option($key, "$key ($RAD_PROF_TYPES[$key])", "$type_filter"=="$key");
$select .= select_footer();

echo table_row ( array (
	input_text ("match_filter", $match_filter ),
	$select,
	input_text ("attr_filter", $attr_filter ),
	' ',
	' ',
	input_submit ("Search","search")
) );


$profiles = get_radprofile_list ($match_filter, $type_filter, $attr_filter);

foreach ( $profiles as $profile ) {
	echo table_row ( array (
		$profile->match ,
		$RAD_PROF_TYPES[$profile->type] ,
		a("radprofile.php?profile=$profile->id", $profile->attr) ,
		$RAD_PROF_OPS[$profile->op] ,
		a("radprofile.php?profile=$profile->id", $profile->value) ,
	) );
}

echo table_footer();
echo form_footer();


echo form_header("POST", "radprofile.php");
echo input_hidden("action", "add");
echo input_submit("Add new RADIUS profile","add");
echo form_footer();


echo '<script type="text/javascript" src="JS/sorttable.js"></script>';

include 'INC/footer.php';

?>
