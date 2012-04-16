<?php

function loadRadiusAVs ($user) {
	$avpairs = get_avpairs($user, FALSE, '*');
	foreach ($avpairs as $avpair) {
		$ldapattr = $avpair->value;
		ldap_add_attribute($ldapattr);
	}
}

function checkRadiusAVs ($user) {
	$avpairs = get_avpairs($user, 'C' );

	foreach ($avpairs as $avpair) {
		$attr = strtoupper($avpair->attr);
		$attr = str_replace ("-", "_", $attr);
		$value = getenv($attr);

		switch ($avpair->op) {

		   case '=':	// check value with database (equal)
			if ($value && ($value != $avpair->value) ) {
				log_auth ($user, "failure", "RADIUS check attribute - wrong value: $attr = $avpair->value" );
				return FALSE;
			}
			break;

		   case '!':	// check attribute existence
			if (! $value) {
				log_auth ($user, "failure", "RADIUS check attribute - missing: $attr" );
				return FALSE;
			}
			break;

		   case '*':	// check value with LDAP
			if (! LDAP_ACCESS_RAD) continue 2;
			$ldapvalue = ldap_get_attribute($avpair->value);
			if ($value && ($value != $ldapvalue) ) {
				log_auth ($user, "failure", "RADIUS check attribute - wrong LDAP value: $attr = $avpair->value" );
				return FALSE;
			}
			break;

		}
	}

	return TRUE;
}


function getRadiusAVs ($user) {
	$avarray=array();
	$avpairs = get_avpairs($user, 'S');

	foreach ($avpairs as $avpair) {
		switch ($avpair->op) {

		   case '=':	// set value from database
			break;

		   case '!':	// set value as send from client
			$attr = strtoupper($avpair->attr);
			$attr = str_replace ("-", "_", $attr);
			$avpair->value = getenv($attr);
			break;

		   case '*':	// set value from LDAP
			if (! LDAP_ACCESS_RAD) continue 2;
			$avpair->value = ldap_get_attribute($avpair->value);
			break;

		}
		if ($avpair->value && ($avpair->value != "") )
			$avarray[$avpair->attr] = $avpair->value;	// overwrite if already set
	}

	$ret=""; $d="";
	foreach (array_keys($avarray) as $key) {
		$val = $avarray[$key];
		if (strpos($val,' ') !== FALSE) $val="\"$val\"";
		$ret .= $d . "$key = $val";
		$d = ", ";
	}

	return $ret;
}


function acctRadiusAVs ($user, $acct="") {
	$ret=""; $d="";
	$avs=array();

	// parse command line AV pairs
	// should bei either 'acct = value' or 'acct = "a value"'
	// all AV pairs seperated by TAB
	$acct = trim($acct);
	$acct = explode("\t",$acct);
	foreach ($acct as $av) {
		$pos = strpos( $av, '=');
		if ($pos <= 0) continue;
		$par = substr($av, 0, $pos-1); $par=trim($par); 
		$val = substr($av, $pos+1);    $val=trim($val); $val=trim($val,'"');
		$avs[$par] = $val;
	}

	if (LDAP_ACCESS_RAD) {
		$avpairs = get_avpairs($user, 'A', '*' );
		if (! empty($avpairs)) {
			loadRadiusAVs($user);
			ldap_search_user($user);
		}
	}

	$avpairs = get_avpairs($user, 'A');
	foreach ($avpairs as $avpair) {
		switch ($avpair->op) {

		   case '=':	// log value from database
			$ret .= $d . "$avpair->attr = $avpair->value";
			$d = ", ";
			break;

		   case '!':	// log value as send from client
			$attr = $avpair->attr;
			if (array_key_exists($attr, $avs)) {
				$ret .= $d . "$attr = $avs[$attr]";
				$d = ", ";
			}
			break;

		   case '*':	// log value from LDAP
			if (! LDAP_ACCESS_RAD) continue 2;
			$ldapvalue = ldap_get_attribute($avpair->value);
			$ret .= $d . "$avpair->attr = $ldapvalue";
			$d = ", ";
			break;

		}
	}

	return $ret;
}


?>
