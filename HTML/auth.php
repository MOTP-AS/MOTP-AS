<?php


include 'checkMOTP.php';


function checkPassword ($username, $password, $radius=FALSE) {

	if (str_replace(str_split(VALID_CHARS), '', $username) !== "") { 
		log_auth ($username, "failure", "invalid character in username" );
		return FALSE;
	}

	if ($password == "") { 
		log_auth ($username, "failure", "no password given" );
		return FALSE;
	}

	$userid = get_user_id($username);
	if ($userid) $user=get_user($userid);

	if ( LDAP_ACCESS_SYNC && ( (!$userid) || ($user->ldap) ) ) {
		// user not in database or entry synced from LDAP
		ldap_sync_user($username);
		$userid = get_user_id($username);
		if ($userid) $user=get_user($userid);
	}

	if (! $userid) {
		log_auth ($username, "failure", "unknown user" );
		return FALSE;
	}

	if (! $user->enabled) {
		log_auth ($username, "failure", "user locked" );
		return FALSE;	// never allow locked users
	}

	if ($radius) {
		if ( LDAP_ACCESS_RAD && ! LDAP_ACCESS_SYNC )
			ldap_search_user($username);	// no LDAP fetch until now
		if (! checkRadiusAVs($username)) {
			return FALSE;
		}
	}

	$ok = check_static($username,$password,$radius);
	if ($ok) {
		log_auth ($username, "success", "$ok Password, Client: " . ( $radius ? "$radius (RADIUS)" : $_SERVER['REMOTE_ADDR']." (Web)") );
		return TRUE;
	}

	if ( LDAP_ACCESS_PWD && LDAP_ACCESS_SYNC && ($radius == FALSE) ) {
		// authenticate via LDAP
		if (ldap_check_password($username,$password)) {
			log_auth ($username, "success", "LDAP Password, Client: " . $_SERVER['REMOTE_ADDR'] . " (Web)");
			return TRUE;
		}
	}

	return checkMOTP($username,$password,$radius);
}


?>
