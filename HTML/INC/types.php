<?php


/* Data sets */

class User {
	public $id;
	public $user;
	public $enabled;
	public $role;
	public $tries;
	public $llogin;
	public $name;
	public $ldap;
}

class Account {
	public $id;
	public $userid;
	public $pin;
	public $deviceid;
	public $ldap;
	/* additional to database */
	public $user;
	public $device;
}

class Device {
	public $id;
	public $secret;
	public $enabled;
	public $timezone;
	public $offset;
	public $lasttime;
	public $name;
	public $ldap;
}

class StaticPW {
	public $userid;
	public $salt;
	public $hash;
	public $howoften;
	public $until;
}

class Rad_Client {
	public $id;
	public $name;
	public $enabled;
	public $secret;
	public $ipv4;
	public $ipv6;
}

class Rad_Profile {
	public $id;
	public $match;
	public $type;
	public $attr;
	public $op;
	public $value;
}

class Log {
	public $id;
	public $time;
	public $user;
	public $type;
	public $message;
}


?>
