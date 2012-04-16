<?php

$VERSION = "0.8";


/* access to DATABASE */
$mysql_server 	= 'localhost';	// db server
$mysql_db 	= 'motp';	// database
$mysql_user 	= 'motp';	// user for accessing database
$mysql_pwd 	= 'motp';	// password, PLEASE CHANGE!
$mysql_scramble	= TRUE;		// whether to obscure data in database
				// (this is only a replacement of characters,
				// no encryption!)

/* RADIUS config */
// $RADIUS_CONF_CLIENTS = "/etc/freeradius/clients.conf";
			// config file for radius clients
			// only uncomment for Freeradius without dynamic client support!
// $RADIUS_SERV_RELOAD  = "sudo /etc/init.d/freeradius reload";	
			// command for reloading freeradius


/******************************************************************/
/* all following settings are overwritten by settings in database */


/* MOTP settings */
$MAXTRIES = 5;		// max nr. of allowed failed logins before locking
$MAXDIFF  = 180;	// max. allowed drift of mobile device, defined in sec.


/* Logs */
$LOGS_ROWS = 20;	// nr. of rows when showing logs


/* Help */
$SHOW_HELP = TRUE;	// whether to show help box
$SHOW_HINT = TRUE;	// whether to show interactive hints


/* what to show to whom */
$SHOW_PIN = 'S';		// PIN in Account settings
	//	'' = do not show
	//	S = show it only to User (self)
	//	A = show it to Administrators, too
	//	H = show it to Helpdesk, but only if not data of Administrator or other Helpdesk

$GENERATE_PASSCODE = TRUE;	// whether to show "generate passcode" button for accounts

$SHOW_BUTTONS = TRUE;		// show graphical buttons


/* authenticatin */
$VALID_CHARS = "";
$VALID_CHARS .= "1234567890";		// valid characters in username
$VALID_CHARS .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
$VALID_CHARS .= "abcdefghijklmnopqrstuvwxyz";
$VALID_CHARS .= "-_.";


?>
