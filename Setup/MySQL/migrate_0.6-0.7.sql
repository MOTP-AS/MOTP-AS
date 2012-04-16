CREATE TABLE IF NOT EXISTS `rad_profiles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `match` varchar(255) default '',
  `type` char(1) NOT NULL,
  `attr` varchar(255) NOT NULL,
  `op` char(1) NOT NULL,
  `value` varchar(255),
  PRIMARY KEY  (`id`)
) ;

CREATE TABLE IF NOT EXISTS `config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parameter` varchar(31) NOT NULL,
  `userid` int unsigned default '0',
  `value` varchar(255),
  PRIMARY KEY  (`id`)
) ;

INSERT INTO `config` (`parameter`, `value`) VALUES 
('maxtries', '5'),
('maxdiff', '180'),
('logs_rows', '20'),
('show_help', 'TRUE'),
('show_hint', 'TRUE'),
('show_pin', 'S'),
('generate_passcode', 'TRUE'),
('valid_chars', '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-_.'),
('show_buttons', 'FALSE');

-- RADIUS profiles --

INSERT INTO `rad_profiles` (`type`, `attr`, `op`, `value`) VALUES
('A', 'Client-Shortname', '!', ''),
('A', 'Acct-Unique-Session-Id', '!', '');

