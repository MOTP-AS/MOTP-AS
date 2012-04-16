<?php


$ROLES = array (	// from lowest to highest rights!
	'U' => "User", 			// only to change PIN
	'H' => "Helpdesk", 		// can manage accounts, users and devices
	'A' => "Administrator", 	// all administration rights
);


function checkrole ( $roles, $location = "" ) {
	$role = $_SESSION['role'];
	$ok = ( strpos($roles,$role) !== FALSE ) ;
	if ( $location == "" ) return $ok;
	if ( $ok === FALSE ) header("Location: $location");
}



?>
