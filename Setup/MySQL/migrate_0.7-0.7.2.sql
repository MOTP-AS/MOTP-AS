
ALTER TABLE `config` ADD `scope` CHAR( 1 );
UPDATE `config` SET `scope` = 'G' WHERE `parameter` = 'maxtries';
UPDATE `config` SET `scope` = 'G' WHERE `parameter` = 'maxdiff';
UPDATE `config` SET `scope` = 'G' WHERE `parameter` = 'valid_chars';
UPDATE `config` SET `scope` = 'A' WHERE `parameter` = 'show_pin';
UPDATE `config` SET `scope` = 'A' WHERE `parameter` = 'logs_rows';
UPDATE `config` SET `scope` = 'A' WHERE `parameter` = 'generate_passcode';
ALTER TABLE `config` ADD INDEX (`userid`);
ALTER TABLE `config` ADD UNIQUE `userid_par` ( `userid` , `parameter` );
ALTER TABLE `config` DROP `id`;
UPDATE `config` SET `value` = 'TRUE' WHERE `parameter` = 'show_buttons';
INSERT INTO `config` (`parameter`, `value`, `scope`, `userid`) VALUES
('warn_delete', 'TRUE', '', '0'),
('logs_purge_acc', '0', 'G', '0'),
('logs_purge_auth', '0', 'G', '0'),
('logs_purge_audit', '0', 'G', '0');

ALTER TABLE `rad_clients` CHANGE `ip` `ipv4` int unsigned NULL;
ALTER TABLE `rad_clients` ADD `ipv6` BINARY( 16 );

