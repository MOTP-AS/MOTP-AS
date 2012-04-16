<?php
 error_reporting(E_ALL);
 if (isset($_SESSION['role'])) { $role=$_SESSION['role']; } else { $role=""; }
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <link href="CSS/layout.css" rel="stylesheet" type="text/css"/>
        <link href="CSS/menu.css" rel="stylesheet" type="text/css"/>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        <title><?php echo (isset($title)) ? $title : "MOTP-AS"; ?></title>
    </head>
    <body>
    <div id="container">
    <div id="header">
        <div id="headerleft">
            <div id="headerleft-elements"><span>TIME: <?php echo date("d.m.Y H:i:s"); echo " (".intval(gmdate("U")/10).")"; ?></span></div>
            <div id="headerleft-elements"><span>LOGIN: <?php echo (isset($_SESSION['user'])) ? $_SESSION['user'] : "" ; ?></span></div>
            <div id="headerleft-elements"><span>SYSTEM LOAD: <?php $l=sys_getloadavg(); echo round($l[0], 2) ." / ". round($l[1], 2) ." / ".  round($l[2], 2);  ?></span></div>
            <div id="headerleft-elements"><span>HOSTNAME: <?php echo $_SERVER['SERVER_NAME'] ?></span></div>
        </div>
        <div id="headerright">
        </div>
    </div>

<div id="menubox">

<ul class="Menu">

 <li><a href="index.php">HOME</a></li>

<?php if ( $role == 'A' ) { ?>
 <li><a href="#"><span>SYSTEM</span></a>
  <ul>
   <li><a href="#"><span>DATABASE</span></a>
    <ul>
     <li><a href="/phpmyadmin/">PHPMYADMIN</a></li>
     <li><a href="db_tools.php">DB TOOLS</a></li>
    </ul>
   </li>
   <li><a href="#"><span>CONFIGURATION</span></a>
    <ul>
     <li><a href="conf.php?realm=S">SYSTEM</a></li>
     <?php if (USE_LDAP) echo '<li><a href="conf.php?realm=L">LDAP</a></li>'; ?>
    </ul>
   </li>
   <li><a href="#"><span>RADIUS</span></a>
    <ul>
     <li><a href="radclients.php">RADIUS CLIENTS</a></li>
     <li><a href="radprofiles.php">RADIUS PROFILES</a></li>
    </ul>
   </li>
   <li><a href="#"><span>LOGS</span></a>
    <ul>
     <li><a href="logs.php?log=auth">AUTHENTICATION LOG</a></li>
     <li><a href="logs.php?log=acc">ACCOUNTING LOG</a></li>
     <li><a href="logs.php?log=audit">AUDIT LOG</a></li>
    </ul>
   </li>
  </ul>
 </li>
<?php } ?>

<?php if ( ($role == 'A') || ($role == 'H') ) { ?>
 <li><a href="#"><span>ADMINISTRATION</span></a>
  <ul>
   <li><a href="users.php">USERS</a></li>
   <li><a href="devices.php">DEVICES</a></li>
   <li><a href="accounts.php">ACCOUNTS</a></li>
  </ul>
 </li>
<?php } ?>

<?php if ($role != '') { ?>
 <li><a href="#"><span>SETTINGS</span></a>
  <ul>
   <li><a href="self.php">USER INFO</a></li>
   <li><a href="self.php?action=change+pin">CHANGE PIN</a></li>
   <li><a href="pref.php">PREFERENCES</a></li>
   <li><a href="self.php?action=get+client">MOTP HTML CLIENT</a></li>
  </ul>
 </li>
<?php } ?>

<?php if ($role != '') { ?>
 <li><a href="logout.php">LOGOUT</a></li>
<?php } ?>

 <li id="about"><a href="about.php">ABOUT</a></li>

</ul>

</div>

  <div id="main">
    <div id="data">

