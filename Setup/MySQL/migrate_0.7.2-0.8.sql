ALTER TABLE `users` ADD `ldap` BOOL NOT NULL default FALSE;
ALTER TABLE `devices` ADD `ldap` BOOL NOT NULL default FALSE;
ALTER TABLE `accounts` ADD `ldap` BOOL NOT NULL default FALSE;

ALTER TABLE `config` ADD `realm` CHAR( 1 ) default '';
UPDATE `config` SET realm='P';
UPDATE `config` SET realm='S' where 'parameter' = 'maxdiff';
UPDATE `config` SET realm='S' where 'parameter' = 'maxtries';
UPDATE `config` SET realm='S' where 'parameter' = 'logs_purge_acc';
UPDATE `config` SET realm='S' where 'parameter' = 'logs_purge_auth';
UPDATE `config` SET realm='S' where 'parameter' = 'logs_purge_audit';
UPDATE `config` SET realm='S' where 'parameter' = 'valid_chars';
INSERT INTO `config` (`realm`, `userid`, `scope`, `parameter`, `value`) VALUES
('S', '0', 'G', 'lock_grace_mins', '0'),
('S', '0', 'G', 'pin_min_length', '4'),
('S', '0', 'G', 'use_ldap', 'TRUE'),
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

