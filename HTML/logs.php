<?php

include 'INC/session.php'; checkrole("A","index.php");

include 'INC/include.php';

$title = "MOTP-AS - Logs";
include 'INC/header.php';

if (isset($_GET['log']))     { $log   = input($_GET['log']); }    else { $log=""; }


if (isset($_GET['start']))   { $start = input($_GET['start']); }  else { $start=date("Y-m-d") . " 00:00"; }
if (isset($_GET['end']))     { $end   = input($_GET['end']); }    else { $end=""; }

$search = new Log();
if (isset($_GET['user']))    { $search->user    = input($_GET['user']); }    else { $search->user=""; }
if (isset($_GET['type']))    { $search->type    = input($_GET['type']); }    else { $search->type=""; }
if (isset($_GET['message'])) { $search->message = input($_GET['message']); } else { $search->message=""; }
if (isset($_GET['index']))   { $search->id      = input($_GET['index']); }   else { $search->id=0; }

if (isset($_GET['count']))   { $count = input($_GET['count']); } else { $count=-1; }

if ( ! ( ($log == "auth") || ($log == "acc") || ($log == "audit") ) ) 
	stop("","");

$bcs = array ( array("index.php","home"), array("logs.php?log=$log",$log . " Log") );
echo '<script type="text/javascript" src="JS/datetimepicker_css.js"></script>';
$from="<a href=\"javascript:NewCssCal('from','yyyyMMdd','Arrow','true','24','true','00','00')\"><img src=\"IMG/cal.gif\"></a>";
$to="<a href=\"javascript:NewCssCal('to','yyyyMMdd','Arrow','true','24','true','23','59')\"><img src=\"IMG/cal.gif\"></a>";


if ($count == "last") {
	$start="";
	$count = count_logs($log, $search, $start, $end);
	$search->id =  $count - LOGS_ROWS;
	if ($search->id < 0)
		$search->id=0;
	else
		$count = $count - ($count % LOGS_ROWS);
}


echo form_header( "GET",  "logs.php" ); 
echo input_hidden ( "log", $log );
echo table_header( array( "Time", "User", "Type", "Message" ), "list sortable" );

echo table_row ( array (
	nobreak( input_text ("start", $start, 14, "id=\"from\"") . $from )
		. nobreak( input_text ("end", $end, 14, "id=\"to\"") . $to ) ,
	input_text ("user", $search->user, 10), 
	input_text ("type", $search->type, 15), 
	input_text ("message", $search->message, 30), 
	input_submit ("Search","search")
) );

$logs = get_logs ($log, $search, $start, $end);
$nr=0;
foreach ($logs as $l) {
	$nr++;
	echo table_row ( array ($l->time, $l->user, $l->type, $l->message) );
}

echo table_footer();
echo form_footer();


if ($count < 0) $count=count_logs($log, $search, $start, $end);
$nav = "";
$link = "logs.php?log=$log&start=$start&end=$end&user=$search->user&type=$search->type&message=$search->message&count=$count";

function navlink ($name, $index, $cond) {
	global $link;
	$name = "<span>" . $name . "</span>";
	if ($cond) return a("$link&index=$index", $name);
	return $name;
}

$nav .= navlink ("<<", 0, $search->id > 0);
$nav .= " ";

$nav .= navlink ("&nbsp;<&nbsp;", $search->id - LOGS_ROWS, $search->id >= LOGS_ROWS);
$nav .= " ";

$nav .= " " . intval($search->id / LOGS_ROWS)+1 . " / " . ceil($count / LOGS_ROWS) . " ";

$nav .= navlink ("&nbsp;>&nbsp;", $search->id + LOGS_ROWS, ($nr == LOGS_ROWS) && ($search->id + LOGS_ROWS < $count) );
$nav .= " ";

$nav .= navlink (">>", floor( ($count-1) / LOGS_ROWS) * LOGS_ROWS, intval($search->id / LOGS_ROWS)+1 < ceil($count / LOGS_ROWS) );

echo "<div id=\"nav\">" . $nav . "</div>";


echo '<script type="text/javascript" src="JS/sorttable.js"></script>';

include 'INC/footer.php';

?>
