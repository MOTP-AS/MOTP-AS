<?php


function ldap_open () {
	static $ldc;
	if (isset($ldc)) return $ldc;
	$ldc=FALSE;
	if ( (!LDAP_CONNECT_HOST) || (LDAP_CONNECT_HOST=="") ) return FALSE;
	$hosts = explode(',',LDAP_CONNECT_HOST);
	foreach ($hosts as $host) {
		$host = trim($host);
		$ldc = @ldap_connect($host, LDAP_CONNECT_PORT);
		if (! $ldc) continue;
		ldap_set_option($ldc,LDAP_OPT_PROTOCOL_VERSION, LDAP_CONNECT_PROTO);
// ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
		if ($ldc) $ldb = @ldap_bind ($ldc, LDAP_BIND_USER, LDAP_BIND_PASSWORD);
		if ($ldb) return $ldc;
		$ldc=FALSE;
	}
	return $ldc;
}


$ldap_attributes = array (
	"dn",
	LDAP_USER_NAME,
	LDAP_USER_DEVICES
);
$ldap_entries = FALSE;

function ldap_add_attribute ($attribute) {
	global $ldap_attributes;
	$ldap_attributes[]=$attribute;
}
	
function ldap_search_user ( $username ) {
	global $ldap_attributes, $ldap_entries;

	$ldc=ldap_open();
	if (!$ldc) return FALSE;

	$filter = htmlspecialchars_decode(LDAP_USER_SEARCH,ENT_QUOTES);
	$filter = sprintf($filter,$username);
	$lds = ldap_search ($ldc, LDAP_USER_BASE, $filter, $ldap_attributes );

	$ldap_entries = ldap_get_entries($ldc,$lds);
	if ($ldap_entries["count"] != 1) return FALSE;	// username not found or not unique
	$ldap_entries = $ldap_entries[0];
	return $ldap_entries["dn"];
}

function ldap_get_attribute ( $attribute, $array=FALSE ) {
	global $ldap_entries;
	if (! $ldap_entries) return FALSE;
	$attribute = strtolower($attribute);
	if (!array_key_exists($attribute,$ldap_entries)) return FALSE;
	$entry = $ldap_entries[$attribute];
	if (! $entry) return FALSE;
	if ($array) {
		// return array
		if (! is_array($entry)) $entry=array($entry);	// always return array
		else unset($entry["count"]); 			// now array w/o count entry
	} else {
		// take first value
		if (is_array($entry)) $entry=$entry[0];
	}
	return $entry;
}

function ldap_check_password ( $username, $password ) {
	if (! LDAP_ACCESS_PWD) return FALSE;
	if ($username == "") return FALSE;
	if ($password == "") return FALSE;
	if ( (!LDAP_CONNECT_HOST) || (LDAP_CONNECT_HOST=="") ) return FALSE;

	$userdn = ldap_search_user($username);
	if (!$userdn) return FALSE;

	$hosts = explode(',',LDAP_CONNECT_HOST);
	foreach ($hosts as $host) {
		$host = trim($host);
		$userc = @ldap_connect($host, LDAP_CONNECT_PORT);
		if (! $userc) continue;
		ldap_set_option($userc,LDAP_OPT_PROTOCOL_VERSION, LDAP_CONNECT_PROTO);
		if ($userc) $userb = @ldap_bind ($userc, $userdn, $password );
		@ldap_close ($userc);
		if ($userb) return TRUE;
	}
	return FALSE;
}


function ldap_sync_user ($username) {
	if (! LDAP_ACCESS_SYNC) return FALSE;

	/* check/create user entry */

	$userdn = ldap_search_user($username);
	$userid = get_user_id($username);

	if ($userdn) {	// LDAP user found
		$ldapdevices = ldap_get_attribute(LDAP_USER_DEVICES, TRUE);
		if ( (!$ldapdevices) || (count($ldapdevices)<=0) ) {	// ignore users w/o Device
			$userdn = FALSE;
			$ldapdevices = array();
		}
	} else {
		$ldapdevices = array();
	}

	if ($userid) {	// user in DB
		$user=get_user($userid);
		if (! $user->ldap) return FALSE;	//skip non LDAP user entries
	}


	if       ( (!$userdn) && (!$userid) ) {		// LDAP=no,  DB=no  => do nothing

		return FALSE;

	} elseif ( (!$userdn) && ( $userid) ) {		// LDAP=no,  DB=yes => lock/remove

		if (LDAP_REMOVE_USERS) {	// delete
			delete_user($user);
			log_audit("LDAP","user delete","User #$user->id: $user->user ($user->name)");
		} elseif ($user->enabled) {	// lock
			$user->enabled = FALSE;
			update_user($user);
			log_audit("LDAP","user locked","User #$user->id: $user->user ($user->name)");
		}

	} elseif ( ( $userdn) && ( $userid) ) {		// LDAP=yes, DB=yes => update/unlock

		$name    = ldap_get_attribute(LDAP_USER_NAME);
		if ( ($name != $user->name) || (! $user->enabled) ) {
			$msg = (! $user->enabled) ? "user unlock" : "user modify";
			$user->name    = $name;
			$user->enabled = TRUE;
			update_user($user);
			log_audit("LDAP",$msg,"User #$user->id: $user->user ($user->name)");
		}

	} elseif ( ( $userdn) && (!$userid) ) {		// LDAP=yes, DB=no  => create

		$user = New User();
		$user->user = $username;
		$user->role  = 'U';
		$user->ldap  = TRUE;
		$user->name    = ldap_get_attribute(LDAP_USER_NAME);
		$user=insert_user($user);
		$userid=$user->id;
		log_audit("LDAP","user add","User #$user->id: $user->user ($user->name)");
	}

	
	/* check/create devices and accounts */

	$ldapaccountids=array();
	$ldapdeviceids=array();

	foreach ($ldapdevices as $devicename) {

		// check device
		$devid = get_device_id($devicename);	// matches for name and secret, resp.
		if (! $devid) {
			// create device entry
			$device = new Device();
			$device->secret = $devicename;
			$device->name  = "LDAP: $devicename";
			$device->ldap  = TRUE;
			$device = insert_device($device);
			$devid = $device->id;
			log_audit("LDAP","device add","Device #$device->id: $device->name");
		}
		$ldapdeviceids[$devid] = TRUE;

		// check account
		$acctid = get_account_id($userid,$devid);
		if (! $acctid) {
			// create account
			$account = new Account();
			$account->userid   = $userid;
			$account->deviceid = $devid;
			$account->pin      = "";
			$account->ldap     = TRUE;
			$account = insert_account($account);
			$acctid = $account->id;
			log_audit("LDAP","account add","Account #$account->id: - User #$account->userid ($account->user), Device #$account->deviceid ($account->device)");
		}
		$ldapaccountids[$acctid] = TRUE;
	}

	/* check devices */
	$userdevices = get_devices_of_user($userid);
	foreach ($userdevices as $device) {
		if (! $device->ldap) continue;		// manually created => skip

		$todelete = TRUE; $tounlock=FALSE;
		if (isset($ldapdeviceids[$device->id])) {	// in use by user
			$todelete = FALSE;
			$tounlock = TRUE;
		}
		$deviceusers = get_users_of_device($device->id);
		foreach ($deviceusers as $deviceuser) {
			if ($deviceuser->id != $userid)		// device also used by other user, don't remove/lock
				$todelete = FALSE;
		}

		if ($tounlock) {			// device in use => unlock
			if (! $device->enabled) {
				$device->enabled=TRUE;
				update_device($device);
				log_audit("LDAP","device unlock","Device #$device->id: $device->name");
			}
		} elseif ($todelete) {			// device not used => lock/remove
			if (LDAP_REMOVE_DEVICES) {
				delete_device($device);
				log_audit("LDAP","device delete","Device #$device->id: $device->name");
			} elseif ($device->enabled) {
				$device->enabled = FALSE;
				update_device($device);
				log_audit("LDAP","device lock","Device #$device->id: $device->name");
			}
		}
	}

	/* check accounts */
	$useraccounts = get_accounts_of_user($userid);
	foreach ($useraccounts as $account) {
		if (! $account->ldap) continue;				// manually created => skip
		if (isset($ldapaccountids[$account->id])) continue;	// in use by user => skip
		if (LDAP_REMOVE_ACCOUNTS) {
			$account=get_account($account->id);	// to get more data for logging
			delete_account($account);
			log_audit("LDAP","account delete","Account #$account->id: - User #$account->userid ($account->user), Device #$account->deviceid ($account->device)");
		}
	}

}

?>
