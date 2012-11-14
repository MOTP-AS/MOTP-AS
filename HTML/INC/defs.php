<?php


$RAD_PROF_TYPES = array (
	'C' => "check", 
	'S' => "send", 
	'A' => "acct", 
);

$RAD_PROF_OPS = array (
	'=' => "equal", 
	'!' => "exist", 
	'*' => "LDAP", 
);

$CONF_REALMS = array(
	'P' => "Preferences",
	'S' => "System",
	'L' => "LDAP",
	'R' => "RADIUS",
);


/* configs */

if (isset($MAXTRIES))		$CONFIG['MAXTRIES']		= $MAXTRIES;
if (isset($MAXDIFF))		$CONFIG['MAXDIFF']		= $MAXDIFF;
if (isset($RADIUS_CONF_CLIENTS))$CONFIG['RADIUS_CONF_CLIENTS']	= $RADIUS_CONF_CLIENTS;
if (isset($RADIUS_SERV_RELOAD))	$CONFIG['RADIUS_SERV_RELOAD']	= $RADIUS_SERV_RELOAD;
if (isset($LOGS_ROWS))		$CONFIG['LOGS_ROWS']		= $LOGS_ROWS;
if (isset($SHOW_HELP))		$CONFIG['SHOW_HELP']		= $SHOW_HELP;
if (isset($SHOW_HINT))		$CONFIG['SHOW_HINT']		= $SHOW_HINT;
if (isset($SHOW_PIN))		$CONFIG['SHOW_PIN']		= $SHOW_PIN;
if (isset($GENERATE_PASSCODE))	$CONFIG['GENERATE_PASSCODE']	= $GENERATE_PASSCODE;
if (isset($VALID_CHARS))	$CONFIG['VALID_CHARS']		= $VALID_CHARS;
if (isset($SHOW_BUTTONS))	$CONFIG['SHOW_BUTTONS']		= $SHOW_BUTTONS;

$CONFIG = get_config(0,'','',$CONFIG);

if (isset($_SESSION['user']))
	$CONFIG = get_config( get_user_id($_SESSION['user']), '', '', $CONFIG);

function config( $par ) {
	global $CONFIG;
	if (! array_key_exists($par,$CONFIG)) return FALSE;
	$value = $CONFIG[$par];
	if ($value === "TRUE")  $value=TRUE;
	if ($value === "FALSE") $value=FALSE;
	return $value;
}

/* general */
define('LOCK_GRACE_MINS',	config('LOCK_GRACE_MINS'));
define('MAXDIFF',		config('MAXDIFF'));
define('MAXTRIES',		config('MAXTRIES'));
define('LOGS_PURGE_ACC',	config('LOGS_ROWS_ACC'));
define('LOGS_PURGE_AUDIT',	config('LOGS_ROWS_AUDIT'));
define('LOGS_PURGE_AUTH',	config('LOGS_ROWS_AUTH'));
define('PIN_MIN_LENGTH',	config('PIN_MIN_LENGTH'));
define('USE_LDAP',		config('USE_LDAP'));
define('LDAP_LOGIN',		config('LDAP_LOGIN'));
define('LDAP_PASSWD',		config('LDAP_PASSWD'));
define('LDAP_SERVER',		config('LDAP_SERVER'));
define('LDAP_DN',		config('LDAP_DN'));
define('LDAP_FILTER',		config('LDAP_FILTER'));
define('VALID_CHARS',		config('VALID_CHARS'));

/* preferences */
define('GENERATE_PASSCODE',	config('GENERATE_PASSCODE'));
define('LOGS_ROWS',		config('LOGS_ROWS'));
define('SHOW_BUTTONS',		config('SHOW_BUTTONS'));
define('SHOW_HELP',		config('SHOW_HELP'));
define('SHOW_HINT',		config('SHOW_HINT'));
define('SHOW_PIN',		config('SHOW_PIN'));
define('WARN_DELETE',		config('WARN_DELETE'));

/* RADIUS */
define('RADIUS_CONF_CLIENTS',	config('RADIUS_CONF_CLIENTS'));
define('RADIUS_SERV_RELOAD',	config('RADIUS_SERV_RELOAD'));

/* LDAP */
define('LDAP_ACCESS_PWD',	USE_LDAP && config('LDAP_ACCESS_PWD'));
define('LDAP_ACCESS_RAD',	USE_LDAP && config('LDAP_ACCESS_RAD'));
define('LDAP_ACCESS_SYNC',	USE_LDAP && config('LDAP_ACCESS_SYNC'));
define('LDAP_CONNECT_HOST',	USE_LDAP ? config('LDAP_CONNECT_HOST') : "");
define('LDAP_CONNECT_PORT',	config('LDAP_CONNECT_PORT'));
define('LDAP_CONNECT_PROTO',	config('LDAP_CONNECT_PROTO'));
define('LDAP_BIND_USER',	config('LDAP_BIND_USER'));
define('LDAP_BIND_PASSWORD',	config('LDAP_BIND_PASSWORD'));
define('LDAP_USER_BASE',	config('LDAP_USER_BASE'));
define('LDAP_USER_SEARCH',	config('LDAP_USER_SEARCH'));
define('LDAP_USER_NAME',	config('LDAP_USER_NAME'));
define('LDAP_USER_DEVICES',	config('LDAP_USER_DEVICES'));
define('LDAP_REMOVE_USERS',	config('LDAP_REMOVE_USERS'));
define('LDAP_REMOVE_DEVICES',	config('LDAP_REMOVE_DEVICES'));
define('LDAP_REMOVE_ACCOUNTS',	config('LDAP_REMOVE_ACCOUNTS'));

// print_r($CONFIG);


?>
