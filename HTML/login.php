<?php

session_start();

if ( isset($_POST['username']) && isset($_POST['password']) ) {

	include 'auth.php';

	$username = input($_POST['username']);
	$password = input($_POST['password']);

	if (checkPassword($username,$password)) {
		$_SESSION['role'] = get_user_role($username);
		$_SESSION['user'] = $username;
		log_audit($username,"login","role: ".$_SESSION['role']);
		header("Location: index.php");
		exit;
	}

	$message="Wrong username or password";

} 

$title = "MOTP-AS - Login";

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <link href="CSS/layout.css" rel="stylesheet" type="text/css"/>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        <title>MOTP</title>
    </head>
    <body id="loginbody" onLoad='document.all.username.focus();'>
        <div id="loginlogo"></div>



<?php
 
if (isset($message)) { warning($message); }

include_once 'INC/misc.php';

echo div_header ("id", "loginbox");
echo table_header (FALSE);
echo form_header("post", "login.php");
echo table_row ( array ("Username:", input_text("username","")), "0");
echo table_row ( array ("Password:", input_password("password","")), "0");
echo table_row ( '<input type="submit" value="Login" id="submit">', "2");
echo form_footer();
echo table_footer();
echo div_footer(); 

?>
</body>
</html>
