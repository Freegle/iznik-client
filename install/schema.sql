-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 05, 2017 at 09:09 AM
-- Server version: 5.7.17-13-57
-- PHP Version: 5.5.9-1ubuntu4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `iznik`
--

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE IF NOT EXISTS `groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique ID of group',
  `legacyid` bigint(20) unsigned DEFAULT NULL COMMENT '(Freegle) Groupid on old system',
  `nameshort` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'A short name for the group',
  `namefull` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'A longer name for the group',
  `nameabbr` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'An abbreviated name for the group',
  `namealt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Alternative name, e.g. as used by GAT',
  `settings` longtext COLLATE utf8mb4_unicode_ci COMMENT 'JSON-encoded settings for group',
  `type` set('Reuse','Freegle','Other','UnitTest') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Other' COMMENT 'High-level characteristics of the group',
  `region` enum('East','East Midlands','West Midlands','North East','North West','Northern Ireland','South East','South West','London','Wales','Yorkshire and the Humber','Scotland') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Freegle only',
  `onyahoo` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether this group is also on Yahoo Groups',
  `onhere` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether this group is available on this platform',
  `showonyahoo` tinyint(1) NOT NULL DEFAULT '1' COMMENT '(Freegle) Whether to show Yahoo links',
  `lastyahoomembersync` timestamp NULL DEFAULT NULL COMMENT 'When we last synced approved members',
  `lastyahoomessagesync` timestamp NULL DEFAULT NULL COMMENT 'When we last synced approved messages',
  `lat` decimal(10,6) DEFAULT NULL,
  `lng` decimal(10,6) DEFAULT NULL,
  `poly` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Any polygon defining core area',
  `polyofficial` longtext COLLATE utf8mb4_unicode_ci COMMENT 'If present, GAT area and poly is catchment',
  `confirmkey` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Key used to verify some operations by email',
  `publish` tinyint(4) NOT NULL DEFAULT '1' COMMENT '(Freegle) Whether this group is visible to members',
  `listable` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Whether shows up in groups API call',
  `onmap` tinyint(4) NOT NULL DEFAULT '1' COMMENT '(Freegle) Whether to show on the map of groups',
  `licenserequired` tinyint(4) DEFAULT '1' COMMENT 'Whether a license is required for this group',
  `trial` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'For ModTools, when a trial was started',
  `licensed` date DEFAULT NULL COMMENT 'For ModTools, when a group was licensed',
  `licenseduntil` date DEFAULT NULL COMMENT 'For ModTools, when a group is licensed until',
  `membercount` int(11) NOT NULL DEFAULT '0' COMMENT 'Automatically refreshed',
  `modcount` int(11) NOT NULL DEFAULT '0',
  `profile` bigint(20) unsigned DEFAULT NULL,
  `cover` bigint(20) unsigned DEFAULT NULL,
  `tagline` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '(Freegle) One liner slogan for this group',
  `description` text COLLATE utf8mb4_unicode_ci,
  `founded` date DEFAULT NULL,
  `lasteventsroundup` timestamp NULL DEFAULT NULL COMMENT '(Freegle) Last event roundup sent',
  `lastvolunteeringroundup` timestamp NULL DEFAULT NULL,
  `external` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Link to some other system e.g. Norfolk',
  `contactmail` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'For external sites',
  `welcomemail` text COLLATE utf8mb4_unicode_ci COMMENT '(Freegle) Text for welcome mail',
  `activitypercent` decimal(10,2) DEFAULT NULL COMMENT 'Within a group type, the proportion of overall activity that this group accounts for.',
  `fundingtarget` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `nameshort` (`nameshort`),
  UNIQUE KEY `namefull` (`namefull`),
  KEY `lat` (`lat`,`lng`),
  KEY `lng` (`lng`),
  KEY `namealt` (`namealt`),
  KEY `profile` (`profile`),
  KEY `cover` (`cover`),
  KEY `legacyid` (`legacyid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='The different groups that we host' AUTO_INCREMENT=415625 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`profile`) REFERENCES `groups_images` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `groups_ibfk_2` FOREIGN KEY (`cover`) REFERENCES `groups` (`id`) ON DELETE SET NULL;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `Delete Stranded Messages` ON SCHEDULE EVERY 1 DAY STARTS '2015-12-23 04:30:00' ON COMPLETION PRESERVE DISABLE ON SLAVE DO DELETE FROM messages WHERE id NOT IN (SELECT DISTINCT msgid FROM messages_groups)$$

CREATE DEFINER=`root`@`localhost` EVENT `Delete Non-Freegle Old Messages` ON SCHEDULE EVERY 1 DAY STARTS '2016-01-02 04:00:00' ON COMPLETION PRESERVE DISABLE ON SLAVE COMMENT 'Non-Freegle groups don''t have old messages preserved.' DO SELECT * FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid INNER JOIN groups ON messages_groups.groupid = groups.id WHERE  DATEDIFF(NOW(), `date`) > 31 AND groups.type != 'Freegle'$$

CREATE DEFINER=`root`@`localhost` EVENT `Delete Old Sessions` ON SCHEDULE EVERY 1 DAY STARTS '2016-01-29 04:00:00' ON COMPLETION PRESERVE DISABLE ON SLAVE DO DELETE FROM sessions WHERE DATEDIFF(NOW(), `date`) > 31$$

CREATE DEFINER=`root`@`localhost` EVENT `Delete Old API logs` ON SCHEDULE EVERY 1 DAY STARTS '2016-02-06 04:00:00' ON COMPLETION PRESERVE DISABLE ON SLAVE COMMENT 'Causes cluster hang - will replace with cron' DO DELETE FROM logs_api WHERE DATEDIFF(NOW(), `date`) > 2$$

CREATE DEFINER=`root`@`localhost` EVENT `Delete Old SQL Logs` ON SCHEDULE EVERY 1 DAY STARTS '2016-02-06 04:30:00' ON COMPLETION PRESERVE DISABLE ON SLAVE COMMENT 'Causes cluster hang - will replace with cron' DO DELETE FROM logs_sql WHERE DATEDIFF(NOW(), `date`) > 2$$

CREATE DEFINER=`root`@`localhost` EVENT `Update Member Counts` ON SCHEDULE EVERY 1 HOUR STARTS '2016-03-02 20:17:39' ON COMPLETION PRESERVE DISABLE ON SLAVE DO update groups set membercount = (select count(*) from memberships where groupid = groups.id)$$

CREATE DEFINER=`root`@`localhost` EVENT `Fix FBUser names` ON SCHEDULE EVERY 1 HOUR STARTS '2016-04-03 08:02:30' ON COMPLETION PRESERVE DISABLE ON SLAVE DO UPDATE users SET fullname = yahooid WHERE yahooid IS NOT NULL AND fullname LIKE  'fbuser%'$$

CREATE DEFINER=`root`@`localhost` EVENT `Delete Unlicensed Groups` ON SCHEDULE EVERY 1 DAY STARTS '2015-12-23 04:00:00' ON COMPLETION PRESERVE DISABLE ON SLAVE DO UPDATE groups SET publish = 0 WHERE licenserequired = 1 AND (licenseduntil IS NULL OR licenseduntil < NOW()) AND (trial IS NULL OR DATEDIFF(NOW(), trial) > 30)$$

DELIMITER ;
