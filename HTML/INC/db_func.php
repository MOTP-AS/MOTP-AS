<?php


/* LOGIN */

$dbc = mysql_connect($mysql_server, $mysql_user, $mysql_pwd);
if (! $dbc) exit("cannot connect to database");
if (!mysql_select_db($mysql_db, $dbc)) exit("cannot select database");


/* HELPER */

function scramble ($string) {
	global $mysql_scramble;
	if ($mysql_scramble == FALSE) return $string;
	$string = strtr($string, "1234567890abcdefghijklmnopqrstuvwxyz", "JAUENCPRHQIXGTLY39Z1B8KM67OS24VW05FD");
	return $string;
}

function descramble ($string) {
	global $mysql_scramble;
	if ($mysql_scramble == FALSE) return $string;
	$string = strtr($string, "JAUENCPRHQIXGTLY39Z1B8KM67OS24VW05FD", "1234567890abcdefghijklmnopqrstuvwxyz");
	return $string;
}


/* MOTP */

function get_motp_data ($user, &$userdata, &$accountdatas, &$devicedatas) {

	// get user data
	$query = "SELECT * FROM users "
		. "WHERE user='$user' "
		. "  AND enabled=TRUE "
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return FALSE;
	$userdata = mysql_fetch_object($result);

	// get arrays of pin/secret/...
	$query = "SELECT * FROM accounts,devices "
		. "WHERE userid='$userdata->id' "
		. "  AND devices.id=accounts.deviceid "
		. "  AND devices.enabled=TRUE "
		. "  AND accounts.pin!='' "
		;
	$result = mysql_query($query);
	$number = mysql_num_rows($result);
	if (! $result) return FALSE;
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$account = new Account();
		$account->pin  = descramble($row['pin']); 
		$accountdatas[] = $account;
		$device = new Device();
		$device->id       = $row['deviceid'];
		$device->secret   = descramble($row['secret']); 
		$device->timezone = $row['timezone']; 
		$device->offset   = $row['offset']; 
		$device->lasttime = $row['lasttime'];
		$device->name     = $row['name']; 
		$devicedatas[] = $device;
	}

	// return number of account entries
	debug ("user=" . print_r($userdata,TRUE));
	debug ("accounts=" . print_r($accountdatas,TRUE));
	debug ("devices=" . print_r($devicedatas,TRUE));
	return $number;
}

function update_motp_data ($userdata, $devicedata) {

	// update nr. of tries
	debug ("updating user entry: user=$userdata->user, tries=$userdata->tries, llogin=$userdata->llogin");
	$query = "UPDATE users "
		. "  SET tries='$userdata->tries' , llogin='$userdata->llogin' "
		. "WHERE id='$userdata->id' "
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;

	// update device data
	if ($userdata->tries != 0) return TRUE;
	if ($devicedata->lasttime == 0) return TRUE;
	debug ("updating device entry: offset=$devicedata->offset lasttime=$devicedata->lasttime");
	$query = "UPDATE devices "
		. "  SET offset='$devicedata->offset' , lasttime='$devicedata->lasttime' "
		. "WHERE id='$devicedata->id' "
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;

	return TRUE;
}



/* LOGGING */

function log_db ($table, $user, $type, $message) {
	$query = "INSERT INTO $table (user, type, message) "
		. "VALUES  ('$user', '$type', '$message') "
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	return TRUE;
}

function log_purge ($table, $days) {
	$query = "DELETE FROM $table "
		. "WHERE time < FROM_DAYS(TO_DAYS(CURDATE())-$days)"
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	return TRUE;
}

function log_auth ($user, $type, $message) {
	log_db ("log_auth", $user, $type, $message);
	$purge = config('LOGS_PURGE_AUTH'); if ($purge && ($purge != 0)) log_purge("log_auth",$purge);
}

function log_acc ($user, $type, $message) {
	log_db ("log_acc", $user, $type, $message);
	$purge = config('LOGS_PURGE_ACC'); if ($purge && ($purge != 0)) log_purge("log_acc",$purge);
}

function log_audit ($user, $type, $message) {
	log_db ("log_audit", $user, $type, $message);
	$purge = config('LOGS_PURGE_AUDIT'); if ($purge && ($purge != 0)) log_purge("log_audit",$purge);
}

function count_logs ($log, $search, $start, $end) {
	return get_logs ($log, $search, $start, $end, TRUE);
}

function get_logs ($log, $search, $start, $end, $count=FALSE) {

	$table = "";
	if ($log == "auth")  $table="log_auth";
	if ($log == "acc")   $table="log_acc";
	if ($log == "audit") $table="log_audit";
	if ($table == "") return array();

	$where = "1 ";
	if ($start != "") $where .= " AND time > '$start' ";
	if ($end   != "") $where .= " AND time < '$end'   ";
	if ($search->user != "")    $where .= "AND    user LIKE '%" . $search->user . "%'";
	if ($search->type != "")    $where .= "AND    type LIKE '%" . $search->type . "%'";
	if ($search->message != "") $where .= "AND message LIKE '%" . $search->message . "%'";

	$query = "SELECT * FROM $table "
		. " WHERE " . $where
		. " ORDER BY id "
		. " LIMIT $search->id," . config('LOGS_ROWS')
		;

	if ($count) {
		$query = "SELECT COUNT(id) as counter FROM $table WHERE " . $where; 
		$result = mysql_query($query);
		if (! $result) return 0;
		$row = mysql_fetch_object($result);
		return $row->counter;
	}

	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return array();

	while ($row = mysql_fetch_object($result)) 
		$logs[] = $row;
	return $logs;
}

function get_logins_today () {
	$query = "SELECT COUNT(id) as counter FROM log_auth "
		." WHERE time > CURDATE() AND type = 'success' AND message LIKE '%RADIUS%'";
	$result = mysql_query($query);
	if (! $result) return 0;
	$row = mysql_fetch_object($result);
	return $row->counter;
}

function get_login_last () {
	$query = "SELECT * FROM log_auth ORDER BY time DESC LIMIT 1";
	$result = mysql_query($query);
	if (! $result) return array();
	if (mysql_num_rows($result) == 0) return array();
	$row = mysql_fetch_object($result);
	return $row;
}



/* USER */

function get_user_counts () {
	$query = "SELECT"
		. " COUNT(id) as count,"
		. " SUM(enabled AND tries <= ".MAXTRIES.") as enabled,"
		. " SUM(ldap) as ldap"
		. " FROM users"
		;
	$result = mysql_query($query);
	if (! $result) return 0;
	$row = mysql_fetch_array($result, MYSQL_ASSOC);
	return $row;
}

function get_user_id ($user) {
	$query = "SELECT id FROM users "
		. "WHERE user='$user' "
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return FALSE;
	$row = mysql_fetch_array($result, MYSQL_ASSOC);
	return $row['id'];
}

function get_user_role ($user) {
	$query = "SELECT * FROM users "
		. "WHERE user='$user' "
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return '';
	$row = mysql_fetch_array($result, MYSQL_ASSOC);
	return $row['role'];
}

function get_user_list ($user_filter = "", $name_filter = "", $role_filter = "", $stat_filter = "", $pw_filter = "", $ldap_filter = "") {

	$where = ""; $op = "WHERE";
	if ($user_filter != "") $where .= "$op user LIKE '%" . $user_filter . "%'";	if ($where) $op="AND";
	if ($name_filter != "") $where .= "$op name LIKE '%" . $name_filter . "%'";	if ($where) $op="AND";
	if ($role_filter != "") $where .= "$op role = '" . "$role_filter" . "'";	if ($where) $op="AND";
	if ($stat_filter != "") $where .= "$op (NOT enabled OR tries>".MAXTRIES.") != '$stat_filter'";	if ($where) $op="AND";
	if ($pw_filter   != "") $where .= "$op NOT(ISNULL(static.userid))='$pw_filter'";if ($where) $op="AND";
	if ($ldap_filter != "") $where .= "$op ldap = '$ldap_filter'";			if ($where) $op="AND";
	
	// $query = "SELECT * FROM users " 
	$query = "SELECT users.*, NOT(ISNULL(static.userid)) AS pw FROM users LEFT JOIN static ON static.userid=users.id "
		. $where 
		. " ORDER BY user "
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return array();

	while ($row = mysql_fetch_object($result)) 
		$users[] = $row;
	return $users;
}

function get_user ($userid) {
	$query = "SELECT * FROM users WHERE id='$userid'";
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return FALSE;
	$row = mysql_fetch_object($result);
	return $row;
}

function update_user ($user) {
	$query = "UPDATE users "
		. "  SET   user='$user->user', "
			. "name='$user->name', "
			. "role='$user->role', "
			. "enabled='$user->enabled', "
			. "tries='$user->tries', "
			. "ldap='$user->ldap', "
			. "llogin='$user->llogin' "
		. " WHERE id='$user->id'"
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	return TRUE;
}

function delete_user ($user) {
	if (!$user) return FALSE;
	if ($user->id == 0) return FALSE;
	$query = "DELETE FROM users WHERE id='$user->id'";
	$result = mysql_query($query);
	if (! $result) return FALSE;
	$query = "DELETE FROM accounts WHERE userid='$user->id'";
	$result = mysql_query($query);
	if (! $result) return FALSE;
	$query = "DELETE FROM static WHERE userid='$user->id'";
	$result = mysql_query($query);
	if (! $result) return FALSE;
	$query = "DELETE FROM config WHERE userid='$user->id'";
	$result = mysql_query($query);
	if (! $result) return FALSE;
	return TRUE;
}

function insert_user ($user) {
	if (! $user->user) { error_msg("Username must not be empty."); return FALSE; }
	$query = "SELECT * FROM users WHERE user='$user->user' " ;
	$result = mysql_query($query);
	if ($result) $result = mysql_num_rows($result);
	if ($result) { error_msg("Duplicate username."); return FALSE; }

	$query = "INSERT INTO users (user, name, role, ldap) "
		. "VALUES  ('$user->user', '$user->name', '$user->role', '$user->ldap') "
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;

	$userid = mysql_insert_id();
	return get_user ($userid);
}

function get_devices_of_user ($userid) {
	$query = "SELECT devices.* FROM accounts,devices "
		. " WHERE userid='$userid' "
		. "   AND accounts.deviceid=devices.id "
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return array();
	while ($row = mysql_fetch_object($result))
		$devices[] = $row;
	return $devices;
}

function get_accounts_of_user ($userid) {
	$query = "SELECT * FROM accounts "
		. " WHERE userid='$userid' "
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return array();
	while ($row = mysql_fetch_object($result))
		$accounts[] = $row;
	return $accounts;
}



/* DEVICES */

function get_device_counts () {
	$query = "SELECT"
		. " COUNT(id) as count,"
		. " SUM(enabled) as enabled,"
		. " SUM(ldap) as ldap"
		. " FROM devices"
		;
	$result = mysql_query($query);
	if (! $result) return 0;
	$row = mysql_fetch_array($result, MYSQL_ASSOC);
	return $row;
}

function get_device_list ($name_filter = "", $secr_filter = "", $stat_filter = "", $ldap_filter = "") {

	$where = ""; $op = "WHERE";
	if ($name_filter != "") $where .= "$op name   LIKE '%" . $name_filter . "%'";	if ($where) $op="AND";
	$secr_filter = scramble( strtolower($secr_filter) );
	if ($secr_filter != "") $where .= "$op secret LIKE '%" . $secr_filter . "%'";	if ($where) $op="AND";
	if ($stat_filter != "") $where .= "$op enabled = $stat_filter";			if ($where) $op="AND";
	if ($ldap_filter != "") $where .= "$op ldap = '$ldap_filter'";			if ($where) $op="AND";

	$query = "SELECT * FROM devices " 
		. $where 
		. " ORDER BY name,id "
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return array();
	while ($row = mysql_fetch_object($result)) {
	        $row->secret = descramble($row->secret);
		$devices[] = $row;
	}
	return $devices;
}

function get_device ($id) {
	$query = "SELECT * FROM devices WHERE id='$id'";
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return FALSE;
	$row = mysql_fetch_object($result);
	$row->secret = descramble($row->secret);
	return $row;
}

function get_device_id ($device) {
	$devicesecret = scramble($device);
	$query = "SELECT id FROM devices "
		. "WHERE name='$device' OR secret='$devicesecret' "
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return 0;
	$row = mysql_fetch_array($result, MYSQL_ASSOC);
	return $row['id'];
}

function update_device ($device) {
	$device->secret = scramble($device->secret);
	$query = "UPDATE devices "
		. "  SET   name='$device->name', "
			. "secret='$device->secret', "
			. "enabled='$device->enabled', "
			. "timezone='$device->timezone', "
			. "offset='$device->offset', "
			. "ldap='$device->ldap', "
			. "lasttime='$device->lasttime' "
		. " WHERE id='$device->id'"
		;
	$result = mysql_query($query);
	$device->secret = descramble($device->secret);
	if (! $result) return FALSE;
	return TRUE;
}

function delete_device ($device) {
	$query = "DELETE FROM devices WHERE id='$device->id'";
	$result = mysql_query($query);
	if (! $result) return FALSE;
	$query = "DELETE FROM accounts WHERE deviceid='$device->id'";
	$result = mysql_query($query);
	if (! $result) return FALSE;
	return TRUE;
}

function insert_device ($device) {
	if ($device->name) {
		$query = "SELECT * FROM devices WHERE name='$device->name' " ;
		$result = mysql_query($query);
		if ($result) $result = mysql_num_rows($result);
		if ($result) { error_msg("Duplicate device name."); return FALSE; }
	}

	$device->secret = scramble($device->secret);
	$query = "INSERT INTO devices (name, secret, timezone, ldap) "
		. "VALUES  ('$device->name', '$device->secret', '$device->timezone', '$device->ldap') "
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;

	$id = mysql_insert_id();
	return get_device ($id);
}

function get_users_of_device ($deviceid) {
	$query = "SELECT users.* FROM accounts,users "
		. " WHERE deviceid='$deviceid' "
		. "   AND accounts.userid=users.id "
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return array();
	while ($row = mysql_fetch_object($result))
		$users[] = $row;
	return $users;
}



/* ACCOUNTS */

function get_account_counts () {
	$query = "SELECT"
		. " COUNT(id) as count,"
		. " SUM(ldap) as ldap"
		. " FROM accounts"
		;
	$result = mysql_query($query);
	if (! $result) return 0;
	$row = mysql_fetch_array($result, MYSQL_ASSOC);
	return $row;
}

function get_account_list ($user_filter = "", $device_filter = "", $ldap_filter = "") {
	$where = ""; $op = "AND";
	if ($user_filter != "")   $where .= " $op users.user  LIKE '%" . $user_filter . "%'"; 
	if ($device_filter != "") $where .= " $op devices.name LIKE '%" . $device_filter . "%'";
	if ($ldap_filter != "")   $where .= " $op accounts.ldap = '$ldap_filter'";

	$query = "SELECT accounts.*, users.user AS user, devices.name AS device "
		. " FROM accounts, users, devices "
		. " WHERE accounts.userid = users.id AND accounts.deviceid = devices.id " 
		. $where 
		. " ORDER BY user "
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return array();
	while ($row = mysql_fetch_object($result)) {
	        $row->pin = descramble($row->pin);
		$accounts[] = $row;
	}
	return $accounts;
}

function get_account ($id) {
	$query = "SELECT accounts.*, users.user AS user, devices.name AS device "
		. " FROM accounts, users, devices "
		. " WHERE accounts.userid = users.id AND accounts.deviceid = devices.id " 
		. "   AND accounts.id='$id'"
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return FALSE;
	$row = mysql_fetch_object($result);
	$row->pin = descramble($row->pin);
	return $row;
}

function get_account_id ($userid, $deviceid) {
	$query = "SELECT id FROM accounts "
		. "WHERE userid='$userid' AND deviceid='$deviceid' "
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return 0;
	$row = mysql_fetch_array($result, MYSQL_ASSOC);
	return $row['id'];
}

function update_account (&$account) {
	$account->pin = scramble($account->pin);
	$query = "UPDATE accounts "
		. "  SET   userid='$account->userid', "
			. "pin='$account->pin', "
			. "deviceid='$account->deviceid', "
			. "ldap='$account->ldap' "
		. " WHERE id='$account->id'"
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	$account = get_account ($account->id);
	return TRUE;
}

function delete_account ($account) {
	$query = "DELETE FROM accounts WHERE id='$account->id'";
	$result = mysql_query($query);
	if (! $result) return FALSE;
	return TRUE;
}

function insert_account ($account) {
	$account->pin = scramble($account->pin);
	$query = "INSERT INTO accounts (userid, pin, deviceid, ldap) "
		. "VALUES  ('$account->userid', '$account->pin', '$account->deviceid', '$account->ldap') "
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;

	$id = mysql_insert_id();
	return get_account ($id);
}



/* STATIC PASSWORDS */

function get_static_counts () {
	$query = "SELECT"
		. " COUNT(userid) as count"
		. " FROM static"
		;
	$result = mysql_query($query);
	if (! $result) return 0;
	$row = mysql_fetch_array($result, MYSQL_ASSOC);
	return $row;
}

function get_static ($userid) {
	$query = "SELECT * FROM static WHERE userid='$userid'";
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return FALSE;
	$row = mysql_fetch_object($result);
	return $row;
}

function set_static ($static) {
	$query = "REPLACE INTO static (userid, salt, hash, howoften, until) "
		. "VALUES ('$static->userid', '$static->salt', '$static->hash', '$static->howoften', '$static->until')" 
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
}

function delete_static ($userid) {
	$query = "DELETE FROM static WHERE userid='$userid'";
	$result = mysql_query($query);
	if (! $result) return FALSE;
	return TRUE;
}

function check_static ($user, $password, $radius=FALSE) {
	$userid = get_user_id($user);

	$query = "SELECT * FROM static "
		. "WHERE userid='$userid'"
		. "  AND (howoften > 0 OR howoften = -1) AND (until > NOW() OR until = 0)"
		;
	if ($radius) $query .= " AND (until > 0 OR howoften > 0) ";
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return FALSE;
	$row = mysql_fetch_object($result);

	if ($row->hash == "LDAP") {
		$ok=ldap_check_password($user,$password);
		if (!$ok) return FALSE;
		$type="LDAP";
	} else {
		$hash=md5($row->salt . $password);
		if ($hash !== $row->hash) return FALSE;
		$type="Static";
	}

	if ($row->howoften > 0) {
		$row->howoften --;
		$query = "UPDATE static SET howoften='$row->howoften' WHERE  userid='$userid'" ;
		$result = mysql_query($query);
		if (! $result) return FALSE;
	}

	$query = "DELETE FROM static WHERE (until > 0 AND until < NOW()) OR (howoften = 0)";
	$result = mysql_query($query);
	if (! $result) return FALSE;

	return $type;
}


/* RADIUS CLIENTS */

function get_radclient_counts () {
	$query = "SELECT"
		. " COUNT(id) as count,"
		. " SUM(enabled) as enabled"
		. " FROM rad_clients"
		;
	$result = mysql_query($query);
	if (! $result) return 0;
	$row = mysql_fetch_array($result, MYSQL_ASSOC);
	return $row;
}

function get_radclient_list ($name_filter = "") {
	if ($name_filter != "") 
		$where = "WHERE name LIKE '%" . $name_filter . "%'"; 
	else 
		$where = "";
	$query = "SELECT id,name,enabled,secret,INET_NTOA(ipv4) AS ipv4,ipv6 "
		. " FROM rad_clients " 
		. $where 
		. " ORDER BY name,id "
	;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return array();
	while ($row = mysql_fetch_object($result)) {
		if ($row->ipv6) $row->ipv6 = inet_ntop($row->ipv6);
		$radclients[] = $row;
	}
	return $radclients;
}

function get_radclient ($id) {
	$query = "SELECT id,name,enabled,secret,INET_NTOA(ipv4) AS ipv4,ipv6 FROM rad_clients WHERE id='$id'";
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return FALSE;
	$row = mysql_fetch_object($result);
	if ($row->ipv6) $row->ipv6 = inet_ntop($row->ipv6);
	return $row;
}

function update_radclient ($client) {
	if ($client->ipv6 != '') $ipv6="'".inet_pton($client->ipv6)."'"; else $ipv6="NULL";
	if ( (! $client->ipv4) && (! $client->ipv6) ) { error_msg("No ip address given"); return FALSE; }
	$query = "UPDATE rad_clients "
		. "  SET   name='$client->name', "
			. "secret='$client->secret', "
			. "ipv4=INET_ATON('$client->ipv4'), "
			. "ipv6=$ipv6, "
			. "enabled='$client->enabled' "
		. " WHERE id='$client->id'"
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	return TRUE;
}

function delete_radclient ($client) {
	$query = "DELETE FROM rad_clients WHERE id='$client->id'";
	$result = mysql_query($query);
	if (! $result) return FALSE;
	return TRUE;
}

function insert_radclient ($client) {
	if (! $client->name) { error_msg("Client name empty."); return FALSE; }
	if ( (! $client->ipv4) && (! $client->ipv6) ) { error_msg("No ip address given"); return FALSE; }
	if ($client->ipv6 != '') $ipv6="'".inet_pton($client->ipv6)."'"; else $ipv6="NULL";
	$query  = "SELECT * FROM rad_clients"
		. " WHERE name='$client->name'"
		;
	if ($client->ipv4) $query .= " OR ipv4=INET_ATON('$client->ipv4')";
	if ($client->ipv6) $query .= " OR ipv6=$ipv6";
	$result = mysql_query($query);
	if ($result) $result = mysql_num_rows($result);
	if ($result) { error_msg("Duplicate name or IP address."); return FALSE; }

	$query = "INSERT INTO rad_clients (name, secret, ipv4, ipv6) "
		. "VALUES  ('$client->name', '$client->secret', INET_ATON('$client->ipv4'), $ipv6) "
		;
	$result = mysql_query($query);
	if (! $result) { error_msg("IP address incorrect."); return FALSE; }

	$clientid = mysql_insert_id();
	return get_radclient ($clientid);
}

function get_freeradius_client ($ip) {
	$ipv6 = inet_pton($ip);
	$query  = "SELECT name,secret FROM rad_clients "
		. " WHERE enabled = TRUE "
		. "   AND ( (ipv4=INET_ATON('$ip')) OR (ipv6='$ipv6') )";
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return FALSE;
	$row = mysql_fetch_object($result);
	return $row;
}



/* RADIUS AV PAIRS */

function get_radprofile_list ($match_filter = "", $type_filter = "", $attr_filter = "" ) {
	$where = ""; $op = "WHERE";
	if ($match_filter != "") $where .= "$op `match` LIKE '%". $match_filter ."%'";	if ($where) $op="AND";
	if ($type_filter  != "") $where .= "$op type= '" . "$type_filter" . "'";	if ($where) $op="AND";
	if ($attr_filter  != "") $where .= "$op attr LIKE '%". $attr_filter . "%'";;	if ($where) $op="AND";
	
	$query = "SELECT * "
		. " FROM rad_profiles " 
		. $where 
		. " ORDER BY `match`,`type`,`id`"
	;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return array();
	while ($row = mysql_fetch_object($result))
		$radprofiles[] = $row;
	return $radprofiles;
}

function get_radprofile ($id) {
	$query = "SELECT * FROM rad_profiles WHERE id='$id'";
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return FALSE;
	$row = mysql_fetch_object($result);
	return $row;
}

function update_radprofile ($profile) {
	$query = "UPDATE rad_profiles "
		. "  SET `match` ='$profile->match', "
			. "type  ='$profile->type', "
			. "attr  ='$profile->attr', "
			. "op    ='$profile->op', "
			. "value ='$profile->value' "
		. " WHERE id='$profile->id'"
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	return TRUE;
}

function delete_radprofile ($profile) {
	$query = "DELETE FROM rad_profiles WHERE id='$profile->id'";
	$result = mysql_query($query);
	if (! $result) return FALSE;
	return TRUE;
}

function insert_radprofile ($profile) {
	$query = "INSERT INTO rad_profiles (`match`, type, attr, op, value) "
		. "VALUES  ('$profile->match', '$profile->type', '$profile->attr', '$profile->op', '$profile->value') "
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;

	$profileid = mysql_insert_id();
	return get_radprofile ($profileid);
}

function get_avpairs ($user, $type = FALSE , $op = FALSE ) {
	$where = "(`match`='' OR `match`='$user')";
	if ($type) $where .= " AND type='$type' ";
	if ($op)   $where .= " AND op='$op' ";
	$query = "SELECT *"
		. " FROM rad_profiles " 
		. "WHERE $where "
		. "ORDER BY id"
		;
	$result = mysql_query($query);
	if (! $result) return FALSE;
	if (mysql_num_rows($result) == 0) return array();
	while ($row = mysql_fetch_object($result))
		$radprofiles[] = $row;
	return $radprofiles;
}



/* CONFIG */

function get_config ($userid, $realm, $scope, $config = array()) {
	$query = "SELECT parameter,value FROM config WHERE userid='$userid'";
	if ($scope != '') $query .= " AND scope='$scope'";
	if ($realm != '') $query .= " AND realm='$realm'";
	$query .= " ORDER BY parameter";
	$result = mysql_query($query);
	if (! $result) return $config;
	if (mysql_num_rows($result) == 0) return $config;
	while ($row = mysql_fetch_object($result))
		$config[strtoupper($row->parameter)] = $row->value;
	return $config;
}

function set_config ($userid, $par, $value ) {
	$par = strtolower($par);
	$query = "SELECT scope, realm FROM config WHERE parameter='$par' AND userid='$userid'";
	$result = mysql_query($query);
	if (! $result) return FALSE;
	$scope = ''; $realm='P';
	if (mysql_num_rows($result) > 0) {
		$row = mysql_fetch_object($result);
		$scope = $row->scope; $realm = $row->realm;
		$query = "UPDATE config SET value='$value' WHERE parameter='$par' AND userid='$userid'";
	} else {
		$query = "INSERT INTO config (parameter, scope, userid, realm, value) "
			. "VALUES ('$par', '$scope', '$userid', '$realm', '$value')" 
			;
	}
	$result = mysql_query($query);
	if (! $result) return FALSE;
	return TRUE;
}
 
function delete_config ($userid, $par) {
	if ($userid == 0) return FALSE;
	$par = strtolower($par);
	$query = "DELETE FROM config WHERE userid='$userid' AND parameter='$par'";
	$result = mysql_query($query);
	if (! $result) return FALSE;
	return TRUE;
}


/* IM-/EXPORT */

function db_export ($table) {
	echo "# table: $table\n";
	echo "# date: " . date("d.m.Y H:i:s") . "\n";

	$query = "SELECT * FROM $table";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		echo "$table=" . serialize($row) . "\n";
	}

	echo "\n";
}

function db_import ($table, $backup) {
	if ($table=="") return FALSE;
	$data = unserialize($backup);
	if ($data==FALSE) return FALSE;

	$query = "REPLACE INTO $table ";
	$del = " SET ";
	foreach (array_keys($data) as $col) {
		$query.= "$del `$col` = '" . $data[$col] . "'";
		$del=',';
	}
	$result = mysql_query($query);
	if (! $result) return FALSE;

	return TRUE;
}



?>
