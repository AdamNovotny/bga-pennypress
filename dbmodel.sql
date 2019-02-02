
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- PennyPress implementation : © Adam Novotny <Adam.Novotny.ck@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

ALTER TABLE `player` ADD `player_advertisment` varchar(16) NULL;
ALTER TABLE `player` ADD `player_penny` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `player_final_edition_state` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `player_strike` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';

CREATE TABLE IF NOT EXISTS `stories` (
  `stories_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stories_name` varchar(10) NOT NULL,
  `stories_type` varchar(1) NOT NULL,
  `stories_type_arg` TINYINT(1) UNSIGNED NOT NULL,
  `stories_location` varchar(30) NOT NULL,
  `stories_location_arg` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  -- 0 base 1 rotated 3 claimed
  -- position in deck
  `stories_location_backup` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0', 
  PRIMARY KEY (`stories_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `tokens` (
  `tokens_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tokens_type` varchar(20) NOT NULL,
  `tokens_type_arg` varchar(20) NOT NULL,
  `tokens_location` varchar(30) NOT NULL,
  PRIMARY KEY (`tokens_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `articles` (
  `articles_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `articles_location` varchar(16) NOT NULL,
  `articles_position` int(10) unsigned NOT NULL,
  PRIMARY KEY (`articles_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
