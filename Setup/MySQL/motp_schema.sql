
-- database --

CREATE DATABASE `motp` DEFAULT CHARACTER SET latin1 ;
USE `motp`;


-- db access user -- 

CREATE USER 'motp'@'localhost' IDENTIFIED BY 'motp';	### PLEASE CHANGE PASSWORD ###
GRANT SELECT,INSERT,UPDATE,DELETE ON motp.* TO 'motp'@'localhost';


-- tables --

CREATE TABLE IF NOT EXISTS `config` (
  `parameter` varchar(31) NOT NULL,
  `realm` char(1) DEFAULT '',
  `scope` char(1) DEFAULT '',
  `userid` int unsigned default '0',
  `value` varchar(255),
  KEY  (`userid`),
  UNIQUE KEY `userid_par` (`userid`,`parameter`)
) ;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user` varchar(31) NOT NULL,
  `enabled` bool NOT NULL default TRUE,
  `role` char(1) default '-',
  `tries` int unsigned default '0',
  `llogin` int unsigned default '0',
  `name` varchar(255),
  `ldap` bool NOT NULL default FALSE,
  PRIMARY KEY  (`id`)
) ;

CREATE TABLE IF NOT EXISTS `accounts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `userid` int unsigned NOT NULL,
  `pin` char(8) NOT NULL,
  `deviceid` int unsigned NOT NULL,
  `ldap` bool NOT NULL default FALSE,
  PRIMARY KEY  (`id`)
) ;

CREATE TABLE IF NOT EXISTS `devices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `secret` char(32) NOT NULL,
  `enabled` bool NOT NULL default TRUE,
  `timezone` int default '0',
  `offset` int default '0',
  `lasttime` int unsigned default '0',
  `name` varchar(255),
  `ldap` bool NOT NULL default FALSE,
  PRIMARY KEY  (`id`)
) ;

CREATE TABLE IF NOT EXISTS `static` (
  `userid` int unsigned NOT NULL,
  `salt` char(2) NOT NULL,
  `hash` char(32) NOT NULL,
  `howoften` int signed default '-1',
  `until` timestamp default 0,
  PRIMARY KEY  (`userid`)
) ;

CREATE TABLE IF NOT EXISTS `rad_clients` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `enabled` bool NOT NULL default '1',
  `secret` varchar(63),
  `ipv4` int unsigned,
  `ipv6` binary(16),
  PRIMARY KEY  (`id`)
) ;

CREATE TABLE IF NOT EXISTS `rad_profiles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `match` varchar(255) default '',
  `type` char(1) NOT NULL,
  `attr` varchar(255) NOT NULL,
  `op` char(1) NOT NULL,
  `value` varchar(255),
  PRIMARY KEY  (`id`)
) ;

CREATE TABLE IF NOT EXISTS `log_auth` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `time` timestamp default CURRENT_TIMESTAMP,
  `user` varchar(31),
  `type` varchar(31),
  `message` varchar(255),
  PRIMARY KEY  (`id`),
  KEY `time` (`time`)
) ;

CREATE TABLE IF NOT EXISTS `log_acc` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `time` timestamp default CURRENT_TIMESTAMP,
  `user` varchar(31),
  `type` varchar(31),
  `message` varchar(255),
  PRIMARY KEY  (`id`),
  KEY `time` (`time`)
) ;

CREATE TABLE IF NOT EXISTS `log_audit` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `time` timestamp default CURRENT_TIMESTAMP,
  `user` varchar(31),
  `type` varchar(31),
  `message` varchar(255),
  PRIMARY KEY  (`id`),
  KEY `time` (`time`)
) ;


-- admin account --

INSERT INTO `users` (`user`, `name`, `role`) VALUES 
('admin', 'admin', 'A');

INSERT INTO `static` (`userid`, `salt`, `hash`) VALUES
('1', '31', '265800bc67b47e9b104afc370ed74be6'); -- password = "motp"


-- config --

INSERT INTO `config` (`realm`, `userid`, `scope`, `parameter`, `value`) VALUES
('S', '0', 'G', 'ldap_login', 'cn=ldap,cn=Users,dc=test,DC=com'),
('S', '0', 'G', 'ldap_password', 'password'),
('S', '0', 'G', 'ldap_server', '127.0.0.1'),
('S', '0', 'G', 'ldap_dn', 'dc=test,DC=com'),
('S', '0', 'G', 'ldap_filter', '(&(objectCategory=Person)(objectClass=User)(memberOf=CN=VPNOTP,OU=Security,OU=Administrative_Groups,OU=newou,DC=test,DC=com))'),
('S', '0', 'G', 'lock_grace_mins', '0'),
('S', '0', 'G', 'maxdiff', '180'),
('S', '0', 'G', 'maxtries', '5'),
('S', '0', 'G', 'logs_purge_acc', '0'),
('S', '0', 'G', 'logs_purge_auth', '0'),
('S', '0', 'G', 'logs_purge_audit', '0'),
('S', '0', 'G', 'pin_min_length', '4'),
('S', '0', 'G', 'use_ldap', 'TRUE'),
('S', '0', 'G', 'valid_chars', '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-_.'),

('P', '0', 'A', 'generate_passcode', 'TRUE'),
('P', '0', 'A', 'logs_rows', '20'),
('P', '0', 'A', 'show_pin', 'S'),
('P', '0', '' , 'show_buttons', 'TRUE'),
('P', '0', '' , 'show_help', 'TRUE'),
('P', '0', '' , 'show_hint', 'TRUE'),
('P', '0', '' , 'warn_delete', 'TRUE'),

('L', '0', 'G', 'ldap_access_rad', 'TRUE'),
('L', '0', 'G', 'ldap_access_pwd', 'TRUE'),
('L', '0', 'G', 'ldap_access_sync', 'TRUE'),
('L', '0', 'G', 'ldap_connect_host', ''),
('L', '0', 'G', 'ldap_connect_port', '389'),
('L', '0', 'G', 'ldap_connect_proto', '3'),
('L', '0', 'G', 'ldap_bind_user', ''),
('L', '0', 'G', 'ldap_bind_password', ''),
('L', '0', 'G', 'ldap_user_base', ''),
('L', '0', 'G', 'ldap_user_search', '(cn=%s)'),
('L', '0', 'G', 'ldap_user_name', 'mail'),
('L', '0', 'G', 'ldap_user_devices', 'pager'),
('L', '0', 'G', 'ldap_remove_users', 'FALSE'),
('L', '0', 'G', 'ldap_remove_devices', 'TRUE'),
('L', '0', 'G', 'ldap_remove_accounts', 'TRUE');


-- RADIUS profiles --

INSERT INTO `rad_profiles` (`type`, `attr`, `op`, `value`) VALUES
('S', 'Reply-Message', '=', 'Hello Friend!'),
('A', 'Client-Shortname', '!', ''),
('A', 'Acct-Unique-Session-Id', '!', '');


