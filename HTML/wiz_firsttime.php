<?php

include 'INC/session.php'; checkrole("A","index.php");

include 'INC/include.php';


$title = "MOTP-AS - Wizard - First Time Configuration";
include 'INC/header.php';

$bcs = array ( array("index.php","home"), array("wiz_firsttime.php","First Time Configuration") );

echo li("ol", array(
	'<a href="static.php?user=1" target="_blank">Change Password of User "admin"</a>',
	'Configure <a href="conf.php?realm=S" target="_blank">system</a> and <a href="conf.php?realm=L" target="_blank">LDAP</a> settings.',
	'<a href="radclients.php" target="_blank">Add RADIUS clients</a>',
	'<a href="wiz_quickadd.php" target="_blank">Add users</a>',
	'Test your configuration; see <a href="http://motp-as.network-cube.de/index.php/faq/installation-faq" target="_blank">FAQs</a>.'
) );


include 'INC/footer.php';

?>
