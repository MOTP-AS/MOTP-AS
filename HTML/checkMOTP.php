<?php


include 'INC/include.php';


/* CHECK PASSCODE */

function checkPasscode ($passcode, $pin, $secret, $timezone, $offset, $now, $maxdiff = 0 ) {
	if ($maxdiff == 0) $maxdiff=MAXDIFF;

	$now += $timezone*3600;
	$now += $offset;
	$now /= 10;
	$now = intval($now);

	$max = (int) $maxdiff/10;
	for ( $i=0 ; $i <= $max ; $i++ ) {

		$time = $now - $i;
		$otp = $time . $secret . $pin ;
		$otp = substr( md5($otp) ,0,6);
		debug("trying passcode $otp (time $time)");
		if ( $otp === $passcode ) return $time;
		if ($i == 0) continue;

		$time = $now + $i;
		$otp = $time . $secret . $pin ;
		$otp = substr( md5($otp) ,0,6);
		debug("trying passcode $otp (time $time)");
		if ( $otp === $passcode ) return $time;

	}
	return -1;
}



/* CHECK MOTP */

function checkMOTP ($user, $passcode, $client=FALSE) {
	$now = gmdate("U");
	$client="Client: " . ( $client ? "$client (RADIUS)" : $_SERVER['REMOTE_ADDR']." (Web)" );

	$number = get_motp_data ($user, $userdata, $accountdatas, $devicedatas);
	if (!$number) { /* no user account found */
		log_auth ($user, "failure", "no valid account");
		return FALSE;
	}

	for ($i=0; $i<$number; $i++) {
		$account = $accountdatas[$i];
		$device  = $devicedatas[$i];
		debug("trying user account nr. $i -- $account->pin, $device->secret");
		$time = checkPasscode ($passcode, $account->pin, $device->secret, $device->timezone, $device->offset, $now);
		if ($time > 0) break;
	}
	debug("user: $user, time: $time");

	$passok = (bool) ($time > 0);					debug("passok=$passok");
	$locked = (bool) ($userdata->tries > MAXTRIES);			debug("locked=$locked");
	$replay = (bool) ($passok) && (! ($time > $device->lasttime) );	debug("replay=$replay");

	if ($passok && !$replay && $locked && (LOCK_GRACE_MINS > 0)) {
		$grace_secs = LOCK_GRACE_MINS * 60;
		if ($time > $userdata->llogin + $grace_secs) 
			$locked = FALSE;
	}

	if ($passok && !$replay && !$locked ) {	// ok
		$status = TRUE;
		$userdata->tries=0;
		$userdata->llogin = $now;
	} else
		$status = FALSE;
	if (!$passok)				// wrong passcode
		$userdata->tries++;
	if ($passok && !$replay)		// no replay
		$device->lasttime = $time;

	if ($passok && !$replay)		// adjust offset
		$device->offset = 10* ( $time - intval( ($now + $device->timezone*3600)/10) );

	update_motp_data ($userdata, $device);

	if ($status)
		log_auth ($user, "success", "One Time Password, Device: " . device_full($device) . ", $client");
	else
		log_auth ($user, "failure", "One Time Password, $client; passok: ". ($passok?"y":"n") .", locked: ". ($locked?"y":"n") .", replay: ". ($replay?"y":"n"));

	return $status;
}


?>
