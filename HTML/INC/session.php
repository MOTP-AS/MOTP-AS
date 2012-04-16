<?php

session_start();

if (  (!isset($_SESSION['role'])) || ($_SESSION['role'] == '') 
   || (!isset($_SESSION['user'])) || ($_SESSION['user'] == '') ) {
	header("Location: login.php");
	exit;
}

include 'INC/roles.php';

?>
