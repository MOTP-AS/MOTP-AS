<?php

include 'INC/session.php';

include 'INC/include.php';
log_audit($_SESSION['user'],"logout","");

$_SESSION['role']=''; unset($_SESSION['role']);
$_SESSION['user']=''; unset($_SESSION['user']);

header("Location: index.php");
?>
