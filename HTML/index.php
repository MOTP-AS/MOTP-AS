<?php

include 'INC/session.php';

include 'INC/include.php';

if ( ($_SESSION['user']=="admin") && (count_logs("audit",New Log(),"","")==1) ) {
	header("Location: wiz_firsttime.php");
	exit;
}

$title = "MOTP-AS - Login";
include 'INC/header.php';


if ($role == 'U') {
	echo '<H1>Welcome to the MOTP-AS Self Service Portal</H1>';
?>

<div class="stat">
<div>What you can do:</div>
<ul>
<li><a href="self.php?action=change+pin">Change PINs</a></li>
<li>See the <a href="self.php">current status</a> of your user account</li>
<li>Generate a passcode using the <a href="self.php?action=get+client">HTML client</a></li>
</ul>
</div>

<?php
	include 'INC/footer.php';
	exit;
}


?>

<H1>Welcome to the Mobile OTP Authentication Server</H1>

<div class="stat">
<div>Wizards:</div>
<ul>
<?php if ($role == 'A') { echo'<li><a href="wiz_firsttime.php">First time</a> configuration</li>'; } ?>
<li><a href="wiz_quickadd.php">Quick Add</a> &ndash; add new user, device and PIN in one step</li>
<li><a href="wiz_import.php">Import</a> a list of users/devices.</li>
</ul>
</div>



<div class="stat">
<div>Database contains:</div>
<ul>
<li><?php $counts=get_device_counts(); echo $counts['count'];   ?> <a href="devices.php"   >Devices</a>
	<?php
	$d = $counts['count'] - $counts['enabled'];
	$l = $counts['ldap'];
	$del = "";
	if ($d || $l) { 
		echo "(";
		if ($d) { echo "$del" ."$d " . '<a href="devices.php?status=0">locked</a>';  $del=", "; }
		if ($l) { echo "$del" ."$l " . '<a href="devices.php?ldap=1">LDAP</a>';      $del=", "; }
		echo ")";
	}
	?>
</li>
<li><?php $counts=get_user_counts(); echo $counts['count'];     ?> <a href="users.php"     >Users</a>
	<?php
	$d = $counts['count'] - $counts['enabled'];
	$s = get_static_counts(); $s = $s['count'];
	$l = $counts['ldap'];
	$del = "";
	if ($d || $s || $l) { 
		echo "(";
		if ($d) { echo "$del" ."$d " . '<a href="users.php?status=0">locked</a>';    $del=", "; }
		if ($s) { echo "$del" ."$s " . '<a href="users.php?statpw=1">static pw</a>'; $del=", "; }
		if ($l) { echo "$del" ."$l " . '<a href="users.php?ldap=1">LDAP</a>';        $del=", "; }
		echo ")";
	}
	?>
</li>
<li><?php $counts=get_account_counts(); echo $counts['count'];  ?> <a href="accounts.php"  >Accounts</a>
	<?php
	$l = $counts['ldap'];
	$del = "";
	if ($l) { 
		echo "(";
		if ($l) { echo "$del" ."$l " . '<a href="accounts.php?ldap=1">LDAP</a>';     $del=", "; }
		echo ")";
	}
	?>
</li>

<?php
if ($role == 'H') {
	echo '</ul></div>';
	include 'INC/footer.php';
	exit;
}
?>

<li><?php $counts=get_radclient_counts(); echo $counts['count']; ?> <a href="radclients.php">RADIUS clients</a>
	<?php
	$d = $counts['count'] - $counts['enabled'];
	if ($d) echo "($d disabled)";
	?>
</li>
</ul>
</div>


<div class="stat">
<div>Usage of RADIUS server:</div>
<ul>
<li>Today <?php echo get_logins_today();  ?> <a href="logs.php?log=auth&type=success&message=RADIUS">Logins</a></li>
<li>Last login at <?php $entry=get_login_last(); echo $entry->time; ?> by user <?php echo $entry->user; ?></li>
</ul>
</div>

<div class="stat">
<div>Logging:</div>
<ul>
<li>Last Logins: <a href="logs.php?log=auth&count=last&type=success">successful</a> / <a href="logs.php?log=auth&count=last&type=failure">failured</a></li>
<li>Last Accounting Requests: <a href="logs.php?log=acc&count=last&type=Start">Start</a> / <a href="logs.php?log=acc&count=last&type=Stop">Stop</a></li>
<li>Last Changes: <a href="logs.php?log=audit&count=last&type=add">new entries</a> / <a href="logs.php?log=audit&count=last&type=delete">deleted entries</a> / <a href="logs.php?log=audit&count=last&type=static password set">static passwords</a></li>
</ul>
</div>


<?php

include 'INC/footer.php';

?>
