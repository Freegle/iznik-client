-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost:3306
-- Generation Time: Sep 11, 2015 at 02:41 PM
-- Server version: 5.6.25-73.1
-- PHP Version: 5.6.99-hhvm

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
  `nameshort` varchar(80) DEFAULT NULL COMMENT 'A short name for the group',
  `namefull` varchar(255) DEFAULT NULL COMMENT 'A longer name for the group',
  `nameabbr` varchar(5) DEFAULT NULL COMMENT 'An abbreviated name for the group',
  `settings` text NOT NULL COMMENT 'JSON-encoded settings for group',
  `type` set('Reuse','Freegle','Other') DEFAULT NULL COMMENT 'High-level characteristics of the group',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `nameshort` (`nameshort`),
  UNIQUE KEY `namefull` (`namefull`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='The different groups that we host' AUTO_INCREMENT=32167 ;

-- --------------------------------------------------------

--
-- Table structure for table `locations_approved`
--

CREATE TABLE IF NOT EXISTS `locations_approved` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location` varchar(255) NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  `popularity` bigint(20) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `location` (`location`,`groupid`),
  KEY `groupid` (`groupid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=12051 ;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE IF NOT EXISTS `logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Machine assumed set to GMT',
  `byuser` bigint(20) unsigned DEFAULT NULL COMMENT 'User responsible for action, if any',
  `type` enum('Group','Message') DEFAULT NULL,
  `subtype` enum('Created','Deleted','Received','Sent','Failure','ClassifiedSpam','Joined','Left') DEFAULT NULL,
  `group` bigint(20) unsigned DEFAULT NULL COMMENT 'Any group this log is for',
  `user` bigint(20) unsigned DEFAULT NULL COMMENT 'Any user that this log is for',
  `message_approved` bigint(20) unsigned DEFAULT NULL COMMENT 'Any relevant message',
  `message_incoming` bigint(20) unsigned DEFAULT NULL COMMENT 'Any relevant message',
  `message_outgoing` bigint(20) unsigned DEFAULT NULL COMMENT 'Any relevant message',
  `message_pending` bigint(20) unsigned DEFAULT NULL COMMENT 'Any relevant message',
  `text` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `user` (`user`),
  KEY `group` (`group`),
  KEY `message_approved` (`message_approved`),
  KEY `message_incoming` (`message_incoming`),
  KEY `message_outgoing` (`message_outgoing`),
  KEY `message_pending` (`message_pending`),
  KEY `byuser` (`byuser`),
  KEY `type` (`type`,`subtype`),
  KEY `subtype` (`subtype`),
  KEY `timestamp` (`timestamp`,`type`,`subtype`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Logs.  Not guaranteed against loss' AUTO_INCREMENT=125964 ;

-- --------------------------------------------------------

--
-- Table structure for table `logs_api`
--

CREATE TABLE IF NOT EXISTS `logs_api` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session` varchar(255) NOT NULL,
  `request` longtext NOT NULL,
  `response` longtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `session` (`session`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Log of all API requests and responses' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `logs_sql`
--

CREATE TABLE IF NOT EXISTS `logs_sql` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
  `result` varchar(255) DEFAULT NULL COMMENT 'The result of the op',
  `duration` bigint(20) unsigned NOT NULL COMMENT 'How long in ms it took',
  `statement` longtext NOT NULL COMMENT 'The actual SQL statement',
  `user` bigint(20) unsigned DEFAULT NULL COMMENT 'Any user that this log is for',
  `message_approved` bigint(20) unsigned DEFAULT NULL COMMENT 'Any relevant message',
  `message_incoming` bigint(20) unsigned DEFAULT NULL COMMENT 'Any relevant message',
  `message_pending` bigint(20) unsigned DEFAULT NULL COMMENT 'Any relevant message',
  `message_outgoing` bigint(20) unsigned DEFAULT NULL COMMENT 'Any relevant message',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `user` (`user`),
  KEY `message_approved` (`message_approved`),
  KEY `message_incoming` (`message_incoming`),
  KEY `message_pending` (`message_pending`),
  KEY `message_outgoing` (`message_outgoing`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=231827 ;

-- --------------------------------------------------------

--
-- Table structure for table `memberships`
--

CREATE TABLE IF NOT EXISTS `memberships` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `userid_groupid` (`userid`,`groupid`),
  KEY `userid` (`userid`),
  KEY `groupid` (`groupid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Which groups users are members of' AUTO_INCREMENT=88 ;

-- --------------------------------------------------------

--
-- Table structure for table `Approved`
--

CREATE TABLE IF NOT EXISTS `Approved` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique iD',
  `arrival` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When this message arrived at our server',
  `groupid` bigint(20) unsigned DEFAULT NULL COMMENT 'Destination group, if identified',
  `msgid` bigint(20) unsigned DEFAULT NULL,
  `source` enum('Email','Yahoo Approved','Yahoo Pending') NOT NULL DEFAULT 'Email' COMMENT 'Source of incoming message',
  `fromip` varchar(40) DEFAULT NULL COMMENT 'IP we think this message came from',
  `message` longtext NOT NULL COMMENT 'The unparsed message',
  `envelopefrom` varchar(255) DEFAULT NULL,
  `fromname` varchar(255) DEFAULT NULL,
  `fromaddr` varchar(255) DEFAULT NULL,
  `envelopeto` varchar(255) DEFAULT NULL,
  `subject` varchar(1024) DEFAULT NULL,
  `type` enum('Offer','Taken','Wanted','Received','Admin','Other') DEFAULT NULL COMMENT 'For reuse groups, the message categorisation',
  `messageid` varchar(255) DEFAULT NULL,
  `tnpostid` varchar(80) DEFAULT NULL COMMENT 'If this message came from Trash Nothing, the unique post ID',
  `textbody` longtext,
  `htmlbody` longtext,
  `retrycount` int(11) NOT NULL DEFAULT '0' COMMENT 'We might fail to route, and later retry',
  `retrylastfailure` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `fromaddr` (`fromaddr`),
  KEY `envelopefrom` (`envelopefrom`),
  KEY `envelopeto` (`envelopeto`),
  KEY `retrylastfailure` (`retrylastfailure`),
  KEY `message-id` (`messageid`),
  KEY `groupid` (`groupid`),
  KEY `fromup` (`fromip`),
  KEY `tnpostid` (`tnpostid`),
  KEY `msgid` (`msgid`),
  KEY `type` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Messages which have been approved for members' AUTO_INCREMENT=80905 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages_attachments`
--

CREATE TABLE IF NOT EXISTS `messages_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `msgid` bigint(20) unsigned DEFAULT NULL,
  `approvedid` bigint(20) unsigned DEFAULT NULL,
  `pendingid` bigint(20) unsigned DEFAULT NULL,
  `spamid` bigint(20) unsigned DEFAULT NULL,
  `contenttype` varchar(80) NOT NULL,
  `data` blob NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `msgid` (`msgid`),
  KEY `approvedid` (`approvedid`),
  KEY `pendingid` (`pendingid`),
  KEY `spamid` (`spamid`),
  KEY `msgid_2` (`msgid`,`approvedid`,`pendingid`,`spamid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Attachments parsed out from messages' AUTO_INCREMENT=694 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages_history`
--

CREATE TABLE IF NOT EXISTS `messages_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique iD',
  `msgid` bigint(20) unsigned DEFAULT NULL,
  `arrival` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When this message arrived at our server',
  `source` enum('Email','Yahoo Approved','Yahoo Pending') NOT NULL DEFAULT 'Email' COMMENT 'Source of incoming message',
  `fromip` varchar(40) DEFAULT NULL COMMENT 'IP we think this message came from',
  `fromhost` varchar(80) DEFAULT NULL COMMENT 'Hostname for fromip if resolvable, or NULL',
  `message` longtext NOT NULL COMMENT 'The unparsed message',
  `envelopefrom` varchar(255) DEFAULT NULL,
  `fromname` varchar(255) DEFAULT NULL,
  `fromaddr` varchar(255) DEFAULT NULL,
  `envelopeto` varchar(255) DEFAULT NULL,
  `groupid` bigint(20) unsigned DEFAULT NULL COMMENT 'Destination group, if identified',
  `subject` varchar(1024) DEFAULT NULL,
  `prunedsubject` varchar(1024) DEFAULT NULL COMMENT 'For spam detection',
  `messageid` varchar(255) DEFAULT NULL,
  `textbody` longtext,
  `htmlbody` longtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `fromaddr` (`fromaddr`),
  KEY `envelopefrom` (`envelopefrom`),
  KEY `envelopeto` (`envelopeto`),
  KEY `message-id` (`messageid`),
  KEY `groupid` (`groupid`),
  KEY `fromup` (`fromip`),
  KEY `msgid` (`msgid`),
  KEY `fromhost` (`fromhost`),
  KEY `arrival` (`arrival`),
  KEY `subject` (`subject`(767)),
  KEY `prunedsubject` (`prunedsubject`(767))
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Message arrivals, used for spam checking' AUTO_INCREMENT=108512 ;

-- --------------------------------------------------------

--
-- Table structure for table `Incoming`
--

CREATE TABLE IF NOT EXISTS `Incoming` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique iD',
  `arrival` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When this message arrived at our server',
  `source` enum('Email','Yahoo Approved','Yahoo Pending') NOT NULL DEFAULT 'Email' COMMENT 'Source of incoming message',
  `fromip` varchar(40) DEFAULT NULL COMMENT 'IP we think this message came from',
  `message` longtext NOT NULL COMMENT 'The unparsed message',
  `envelopefrom` varchar(255) DEFAULT NULL,
  `fromname` varchar(255) DEFAULT NULL,
  `fromaddr` varchar(255) DEFAULT NULL,
  `envelopeto` varchar(255) DEFAULT NULL,
  `groupid` bigint(20) unsigned DEFAULT NULL COMMENT 'Destination group, if identified',
  `subject` varchar(1024) DEFAULT NULL,
  `type` enum('Offer','Taken','Wanted','Received','Admin','Other') DEFAULT NULL COMMENT 'For reuse groups, the message categorisation',
  `messageid` varchar(255) DEFAULT NULL,
  `tnpostid` varchar(80) DEFAULT NULL COMMENT 'If this message came from Trash Nothing, the unique post ID',
  `textbody` longtext,
  `htmlbody` longtext,
  `retrycount` int(11) NOT NULL DEFAULT '0' COMMENT 'We might fail to route, and later retry',
  `retrylastfailure` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `fromaddr` (`fromaddr`),
  KEY `envelopefrom` (`envelopefrom`),
  KEY `envelopeto` (`envelopeto`),
  KEY `retrylastfailure` (`retrylastfailure`),
  KEY `message-id` (`messageid`),
  KEY `groupid` (`groupid`),
  KEY `fromup` (`fromip`),
  KEY `tnpostid` (`tnpostid`),
  KEY `type` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Messages which have arrived, but not yet been processed' AUTO_INCREMENT=117821 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages_outgoing`
--

CREATE TABLE IF NOT EXISTS `messages_outgoing` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique iD',
  `source` enum('Email') NOT NULL COMMENT 'Source of incoming message',
  `message` longtext NOT NULL COMMENT 'The unparsed message',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Messages which have arrived, but not yet been processed' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `Pending`
--

CREATE TABLE IF NOT EXISTS `Pending` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique iD',
  `arrival` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When this message arrived at our server',
  `groupid` bigint(20) unsigned DEFAULT NULL COMMENT 'Destination group, if identified',
  `msgid` bigint(20) unsigned DEFAULT NULL,
  `source` enum('Email','Yahoo Approved','Yahoo Pending') NOT NULL DEFAULT 'Email' COMMENT 'Source of incoming message',
  `fromip` varchar(40) DEFAULT NULL COMMENT 'IP we think this message came from',
  `message` longtext NOT NULL COMMENT 'The unparsed message',
  `envelopefrom` varchar(255) DEFAULT NULL,
  `fromname` varchar(255) DEFAULT NULL,
  `fromaddr` varchar(255) DEFAULT NULL,
  `envelopeto` varchar(255) DEFAULT NULL,
  `subject` varchar(1024) DEFAULT NULL,
  `type` enum('Offer','Taken','Wanted','Received','Admin','Other') DEFAULT NULL COMMENT 'For reuse groups, the message categorisation',
  `messageid` varchar(255) DEFAULT NULL,
  `tnpostid` varchar(80) DEFAULT NULL COMMENT 'If this message came from Trash Nothing, the unique post ID',
  `textbody` longtext,
  `htmlbody` longtext,
  `retrycount` int(11) NOT NULL DEFAULT '0' COMMENT 'We might fail to route, and later retry',
  `retrylastfailure` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `fromaddr` (`fromaddr`),
  KEY `envelopefrom` (`envelopefrom`),
  KEY `envelopeto` (`envelopeto`),
  KEY `retrylastfailure` (`retrylastfailure`),
  KEY `message-id` (`messageid`),
  KEY `groupid` (`groupid`),
  KEY `fromup` (`fromip`),
  KEY `tnpostid` (`tnpostid`),
  KEY `msgid` (`msgid`),
  KEY `type` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Messages which have been approved for members' AUTO_INCREMENT=33477 ;

-- --------------------------------------------------------

--
-- Table structure for table `Spam`
--

CREATE TABLE IF NOT EXISTS `Spam` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique iD',
  `arrival` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When this message arrived at our server',
  `groupid` bigint(20) unsigned DEFAULT NULL COMMENT 'Destination group, if identified',
  `msgid` bigint(20) unsigned DEFAULT NULL,
  `source` enum('Email','Yahoo Approved','Yahoo Pending') NOT NULL DEFAULT 'Email' COMMENT 'Source of incoming message',
  `fromip` varchar(40) DEFAULT NULL COMMENT 'IP we think this message came from',
  `message` longtext NOT NULL COMMENT 'The unparsed message',
  `envelopefrom` varchar(255) DEFAULT NULL,
  `fromname` varchar(255) DEFAULT NULL,
  `fromaddr` varchar(255) DEFAULT NULL,
  `envelopeto` varchar(255) DEFAULT NULL,
  `subject` varchar(1024) DEFAULT NULL,
  `type` enum('Offer','Taken','Wanted','Received','Admin','Other') DEFAULT NULL COMMENT 'For reuse groups, the message categorisation',
  `messageid` varchar(255) DEFAULT NULL,
  `tnpostid` varchar(80) DEFAULT NULL COMMENT 'If this message came from Trash Nothing, the unique post ID',
  `textbody` longtext,
  `htmlbody` longtext,
  `reason` varchar(255) NOT NULL COMMENT 'Reason we flagged this as spam',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `fromaddr` (`fromaddr`),
  KEY `envelopefrom` (`envelopefrom`),
  KEY `envelopeto` (`envelopeto`),
  KEY `message-id` (`messageid`),
  KEY `groupid` (`groupid`),
  KEY `fromup` (`fromip`),
  KEY `msgid` (`msgid`),
  KEY `tnpostid` (`tnpostid`),
  KEY `type` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Messages suspected as spam, for review' AUTO_INCREMENT=1804 ;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` bigint(20) unsigned NOT NULL,
  `series` bigint(20) unsigned NOT NULL,
  `token` varchar(255) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `id` (`id`),
  KEY `date` (`date`),
  KEY `id_3` (`id`,`series`,`token`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `spam_countries`
--

CREATE TABLE IF NOT EXISTS `spam_countries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `country` varchar(80) NOT NULL COMMENT 'A country we want to block',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `country` (`country`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `spam_whitelist_ips`
--

CREATE TABLE IF NOT EXISTS `spam_whitelist_ips` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ip` varchar(80) NOT NULL,
  `comment` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `ip` (`ip`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Whitelisted IP addresses' AUTO_INCREMENT=434 ;

-- --------------------------------------------------------

--
-- Table structure for table `spam_whitelist_subjects`
--

CREATE TABLE IF NOT EXISTS `spam_whitelist_subjects` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) NOT NULL,
  `comment` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `ip` (`subject`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Whitelisted subjects' AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `firstname` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `systemrole` set('User','Moderator','Support','Admin') NOT NULL DEFAULT 'User' COMMENT 'System-wide roles',
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastaccess` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `settings` text COMMENT 'JSON-encoded settings',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `systemrole` (`systemrole`),
  KEY `added` (`added`,`lastaccess`),
  KEY `fullname` (`fullname`),
  KEY `firstname` (`firstname`),
  KEY `lastname` (`lastname`),
  KEY `firstname_2` (`firstname`,`lastname`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1072 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_emails`
--

CREATE TABLE IF NOT EXISTS `users_emails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned NOT NULL COMMENT 'Unique ID in users table',
  `email` varchar(255) NOT NULL COMMENT 'The email',
  `primary` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Preferred email for this user',
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `validated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `userid` (`userid`),
  KEY `validated` (`validated`),
  KEY `userid_2` (`userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1301 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_logins`
--

CREATE TABLE IF NOT EXISTS `users_logins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned NOT NULL COMMENT 'Unique ID in users table',
  `type` enum('Yahoo','Facebook','Google','Native') DEFAULT NULL,
  `uid` varchar(255) DEFAULT NULL COMMENT 'Unique identifier for login',
  `credentials` varchar(255) DEFAULT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastaccess` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `email` (`uid`,`type`),
  UNIQUE KEY `userid_3` (`userid`,`type`,`uid`),
  KEY `userid` (`userid`),
  KEY `validated` (`lastaccess`),
  KEY `userid_2` (`userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1245 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `locations_approved`
--
ALTER TABLE `locations_approved`
ADD CONSTRAINT `locations_approved_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`);

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`message_approved`) REFERENCES `Approved` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `logs_ibfk_2` FOREIGN KEY (`message_incoming`) REFERENCES `Incoming` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `logs_ibfk_3` FOREIGN KEY (`message_pending`) REFERENCES `logs_sql` (`message_pending`) ON DELETE SET NULL;

--
-- Constraints for table `logs_sql`
--
ALTER TABLE `logs_sql`
ADD CONSTRAINT `logs_sql_ibfk_1` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `memberships`
--
ALTER TABLE `memberships`
ADD CONSTRAINT `memberships_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `memberships_ibfk_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `Approved`
--
ALTER TABLE `Approved`
ADD CONSTRAINT `Approved_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `messages_attachments`
--
ALTER TABLE `messages_attachments`
ADD CONSTRAINT `messages_attachments_ibfk_1` FOREIGN KEY (`msgid`) REFERENCES `Incoming` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `messages_attachments_ibfk_2` FOREIGN KEY (`approvedid`) REFERENCES `Approved` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `messages_attachments_ibfk_3` FOREIGN KEY (`pendingid`) REFERENCES `Pending` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `messages_attachments_ibfk_4` FOREIGN KEY (`spamid`) REFERENCES `Spam` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `Incoming`
--
ALTER TABLE `Incoming`
ADD CONSTRAINT `Incoming_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `Spam`
--
ALTER TABLE `Spam`
ADD CONSTRAINT `Spam_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users_emails`
--
ALTER TABLE `users_emails`
ADD CONSTRAINT `users_emails_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users_logins`
--
ALTER TABLE `users_logins`
ADD CONSTRAINT `users_logins_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `pruneSessions` ON SCHEDULE EVERY 1 DAY STARTS '2015-09-04 05:00:00' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM sessions WHERE DATEDIFF(NOW(), `date`) > 7$$

CREATE DEFINER=`root`@`localhost` EVENT `pruneSpam` ON SCHEDULE EVERY 1 DAY STARTS '2015-08-28 11:13:05' ON COMPLETION NOT PRESERVE ENABLE COMMENT 'Delete old messages from spam' DO DELETE FROM Spam WHERE DATEDIFF(NOW(), arrival) > 7$$

CREATE DEFINER=`root`@`localhost` EVENT `pruneHistory` ON SCHEDULE EVERY 1 DAY STARTS '2015-08-27 07:46:30' ON COMPLETION NOT PRESERVE ENABLE COMMENT 'Delete old records from the history table which we use for spam' DO DELETE FROM messages_history WHERE DATEDIFF(NOW(), arrival) > 7$$

CREATE DEFINER=`root`@`localhost` EVENT `pruneAttachments` ON SCHEDULE EVERY 1 DAY STARTS '2015-09-11 03:00:00' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM messages_attachments WHERE msgid IS NULL AND
                                                                                                                                                                                            approvedid IS NULL AND
                                                                                                                                                                                            pendingid IS NULL AND
                                                                                                                                                                                            spamid IS NULL$$

DELIMITER ;
