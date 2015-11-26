-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 26, 2015 at 09:22 AM
-- Server version: 5.6.26-74.0-56-log
-- PHP Version: 5.5.9-1ubuntu4.14

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
  `onyahoo` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Whether this group is also on Yahoo Groups',
  `lastyahoomembersync` timestamp NULL DEFAULT NULL COMMENT 'When we last synced approved members',
  `lastyahoomessagesync` timestamp NULL DEFAULT NULL COMMENT 'When we last synced approved messages',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `nameshort` (`nameshort`),
  UNIQUE KEY `namefull` (`namefull`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='The different groups that we host' AUTO_INCREMENT=40850 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=12049 ;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE IF NOT EXISTS `logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Machine assumed set to GMT',
  `byuser` bigint(20) unsigned DEFAULT NULL COMMENT 'User responsible for action, if any',
  `type` enum('Group','Message','User','Plugin','Config','StdMsg') DEFAULT NULL,
  `subtype` enum('Created','Deleted','Received','Sent','Failure','ClassifiedSpam','Joined','Left','Approved','Rejected','YahooDeliveryType','YahooPostingStatus','NotSpam','Login') DEFAULT NULL,
  `groupid` bigint(20) unsigned DEFAULT NULL COMMENT 'Any group this log is for',
  `user` bigint(20) unsigned DEFAULT NULL COMMENT 'Any user that this log is about',
  `msgid` bigint(20) unsigned DEFAULT NULL COMMENT 'id in the messages table',
  `configid` bigint(20) unsigned DEFAULT NULL COMMENT 'id in the mod_configs table',
  `text` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `user` (`user`),
  KEY `group` (`groupid`),
  KEY `message_approved` (`msgid`),
  KEY `byuser` (`byuser`),
  KEY `type` (`type`,`subtype`),
  KEY `subtype` (`subtype`),
  KEY `timestamp` (`timestamp`,`type`,`subtype`),
  KEY `timestamp_2` (`timestamp`,`groupid`),
  KEY `configid` (`configid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Logs.  Not guaranteed against loss' AUTO_INCREMENT=5666306 ;

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
  `msgid` bigint(20) unsigned DEFAULT NULL COMMENT 'id in the messages table',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `user` (`user`),
  KEY `message_approved` (`msgid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=478696 ;

-- --------------------------------------------------------

--
-- Table structure for table `memberships`
--

CREATE TABLE IF NOT EXISTS `memberships` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  `role` enum('Member','Moderator','Owner') NOT NULL DEFAULT 'Member',
  `configid` bigint(20) unsigned DEFAULT NULL COMMENT 'Configuration used to moderate this group if a moderator',
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `yahooPostingStatus` enum('MODERATED','DEFAULT','PROHIBITED','UNMODERATED') DEFAULT NULL COMMENT 'Yahoo mod status if applicable',
  `yahooDeliveryType` enum('DIGEST','NONE','SINGLE','ANNOUNCEMENT') DEFAULT NULL COMMENT 'Yahoo delivery settings if applicable',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `userid_groupid` (`userid`,`groupid`),
  KEY `groupid` (`groupid`),
  KEY `groupid_2` (`groupid`,`role`),
  KEY `userid` (`userid`,`role`),
  KEY `role` (`role`),
  KEY `configid` (`configid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Which groups users are members of' AUTO_INCREMENT=2783974 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE IF NOT EXISTS `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique iD',
  `arrival` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When this message arrived at our server',
  `date` timestamp NULL DEFAULT NULL COMMENT 'When this message was created, e.g. Date header',
  `deleted` timestamp NULL DEFAULT NULL COMMENT 'When this message was deleted',
  `source` enum('Yahoo Approved','Yahoo Pending') DEFAULT NULL COMMENT 'Source of incoming message',
  `sourceheader` varchar(80) DEFAULT NULL COMMENT 'Any source header, e.g. X-Freegle-Source',
  `fromip` varchar(40) DEFAULT NULL COMMENT 'IP we think this message came from',
  `message` longtext NOT NULL COMMENT 'The unparsed message',
  `fromuser` bigint(20) unsigned DEFAULT NULL,
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
  `spamreason` varchar(255) DEFAULT NULL COMMENT 'Why we think this message may be spam',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `envelopefrom` (`envelopefrom`),
  KEY `envelopeto` (`envelopeto`),
  KEY `retrylastfailure` (`retrylastfailure`),
  KEY `message-id` (`messageid`),
  KEY `fromup` (`fromip`),
  KEY `tnpostid` (`tnpostid`),
  KEY `type` (`type`),
  KEY `sourceheader` (`sourceheader`),
  KEY `arrival` (`arrival`,`sourceheader`),
  KEY `arrival_2` (`arrival`,`fromaddr`),
  KEY `arrival_3` (`arrival`),
  KEY `fromaddr` (`fromaddr`,`subject`(767)),
  KEY `date` (`date`),
  KEY `subject` (`subject`(767)),
  KEY `fromuser` (`fromuser`),
  KEY `deleted` (`deleted`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8 COMMENT='All our messages' AUTO_INCREMENT=535520 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages_attachments`
--

CREATE TABLE IF NOT EXISTS `messages_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `msgid` bigint(20) unsigned DEFAULT NULL COMMENT 'id in the messages table',
  `contenttype` varchar(80) NOT NULL,
  `data` longblob NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `incomingid` (`msgid`),
  KEY `incomingid_2` (`msgid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Attachments parsed out from messages' AUTO_INCREMENT=308356 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages_groups`
--

CREATE TABLE IF NOT EXISTS `messages_groups` (
  `msgid` bigint(20) unsigned NOT NULL COMMENT 'id in the messages table',
  `groupid` bigint(20) unsigned NOT NULL,
  `collection` enum('Incoming','Pending','Approved','Spam') DEFAULT NULL,
  `arrival` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `yahoopendingid` varchar(20) DEFAULT NULL COMMENT 'For Yahoo messages, pending id if relevant',
  `yahooapprovedid` varchar(20) DEFAULT NULL COMMENT 'For Yahoo messages, approved id if relevant',
  `yahooapprove` varchar(255) DEFAULT NULL COMMENT 'For Yahoo messages, email to trigger approve if relevant',
  `yahooreject` varchar(255) DEFAULT NULL COMMENT 'For Yahoo messages, email to trigger reject if relevant',
  UNIQUE KEY `msgid` (`msgid`,`groupid`),
  KEY `messageid` (`msgid`,`groupid`,`collection`,`arrival`),
  KEY `groupid` (`groupid`,`collection`,`deleted`),
  KEY `collection` (`collection`),
  KEY `groupid_2` (`groupid`,`yahoopendingid`),
  KEY `groupid_3` (`groupid`,`yahooapprovedid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='The state of the message on each group';

-- --------------------------------------------------------

--
-- Table structure for table `messages_history`
--

CREATE TABLE IF NOT EXISTS `messages_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique iD',
  `msgid` bigint(20) unsigned DEFAULT NULL COMMENT 'id in the messages table',
  `arrival` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When this message arrived at our server',
  `source` enum('Email','Yahoo Approved','Yahoo Pending') NOT NULL DEFAULT 'Email' COMMENT 'Source of incoming message',
  `fromip` varchar(40) DEFAULT NULL COMMENT 'IP we think this message came from',
  `fromhost` varchar(80) DEFAULT NULL COMMENT 'Hostname for fromip if resolvable, or NULL',
  `message` longtext NOT NULL COMMENT 'The unparsed message',
  `fromuser` bigint(20) unsigned DEFAULT NULL,
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
  KEY `incomingid` (`msgid`),
  KEY `fromhost` (`fromhost`),
  KEY `arrival` (`arrival`),
  KEY `subject` (`subject`(767)),
  KEY `prunedsubject` (`prunedsubject`(767)),
  KEY `fromname` (`fromname`),
  KEY `fromuser` (`fromuser`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Message arrivals, used for spam checking' AUTO_INCREMENT=759546 ;

-- --------------------------------------------------------

--
-- Table structure for table `mod_configs`
--

CREATE TABLE IF NOT EXISTS `mod_configs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique ID of config',
  `name` varchar(255) NOT NULL COMMENT 'Name of config set',
  `createdby` bigint(20) DEFAULT NULL COMMENT 'Moderator ID who created it',
  `fromname` enum('My name','Groupname Moderator') NOT NULL DEFAULT 'My name',
  `ccrejectto` enum('Nobody','Me','Specific') NOT NULL DEFAULT 'Nobody',
  `ccrejectaddr` varchar(255) NOT NULL,
  `ccfollowupto` enum('Nobody','Me','Specific') NOT NULL DEFAULT 'Nobody',
  `ccfollowupaddr` varchar(255) NOT NULL,
  `ccrejmembto` enum('Nobody','Me','Specific') NOT NULL DEFAULT 'Nobody',
  `ccrejmembaddr` varchar(255) NOT NULL,
  `ccfollmembto` enum('Nobody','Me','Specific') NOT NULL DEFAULT 'Nobody',
  `ccfollmembaddr` varchar(255) NOT NULL,
  `protected` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Protect from edit?',
  `messageorder` text NOT NULL COMMENT 'CSL of ids of standard messages in order in which they should appear',
  `network` varchar(255) NOT NULL,
  `coloursubj` tinyint(1) NOT NULL DEFAULT '1',
  `subjreg` varchar(1024) NOT NULL DEFAULT '^(OFFER|WANTED|TAKEN|RECEIVED) *[\\:-].*\\(.*\\)',
  `subjlen` int(11) NOT NULL DEFAULT '68',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `uniqueid` (`id`,`createdby`),
  KEY `createdby` (`createdby`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Configurations for use by moderators' AUTO_INCREMENT=4820 ;

-- --------------------------------------------------------

--
-- Table structure for table `mod_stdmsgs`
--

CREATE TABLE IF NOT EXISTS `mod_stdmsgs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique ID of standard message',
  `configid` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL COMMENT 'Title of standard message',
  `action` enum('Approve','Reject','Leave','Approve Member','Reject Member','Leave Member','Leave Approved Message','Delete Approved Message','Leave Approved Member','Delete Approved Member','Edit') NOT NULL DEFAULT 'Reject' COMMENT 'What action to take',
  `subjpref` varchar(255) NOT NULL COMMENT 'Subject prefix',
  `subjsuff` varchar(255) NOT NULL COMMENT 'Subject suffix',
  `body` text NOT NULL,
  `rarelyused` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Rarely used messages may be hidden in the UI',
  `autosend` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Send the message immediately rather than wait for user',
  `newmodstatus` enum('UNCHANGED','MODERATED','DEFAULT','PROHIBITED','UNMODERATED') NOT NULL DEFAULT 'UNCHANGED' COMMENT 'Yahoo mod status afterwards',
  `newdelstatus` enum('UNCHANGED','DIGEST','NONE','SINGLE','ANNOUNCEMENT') NOT NULL DEFAULT 'UNCHANGED' COMMENT 'Yahoo delivery status afterwards',
  `edittext` enum('Unchanged','Correct Case') NOT NULL DEFAULT 'Unchanged',
  UNIQUE KEY `id` (`id`),
  KEY `configid` (`configid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=27472 ;

-- --------------------------------------------------------

--
-- Table structure for table `plugin`
--

CREATE TABLE IF NOT EXISTS `plugin` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `groupid` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `groupid` (`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Outstanding work required to be performed by the plugin' AUTO_INCREMENT=1 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Whitelisted IP addresses' AUTO_INCREMENT=226 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Whitelisted subjects' AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Table structure for table `supporters`
--

CREATE TABLE IF NOT EXISTS `supporters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `type` enum('Wowzer','Front Page','Supporter','Buyer') NOT NULL,
  `email` varchar(255) NOT NULL,
  `display` varchar(255) DEFAULT NULL,
  `voucher` varchar(255) NOT NULL COMMENT 'Voucher code',
  `vouchercount` int(11) NOT NULL DEFAULT '1' COMMENT 'Number of licenses in this voucher',
  `voucheryears` int(11) NOT NULL DEFAULT '1' COMMENT 'Number of years voucher licenses are valid for',
  `anonymous` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`,`type`,`email`),
  KEY `display` (`display`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='People who have supported this site' AUTO_INCREMENT=4100 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `yahooUserId` varchar(20) DEFAULT NULL COMMENT 'Unique ID of user on Yahoo if known',
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
  KEY `firstname_2` (`firstname`,`lastname`),
  KEY `yahooUserId` (`yahooUserId`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3878566 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3879574 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5273 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `locations_approved`
--
ALTER TABLE `locations_approved`
ADD CONSTRAINT `locations_approved_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`);

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
ADD CONSTRAINT `memberships_ibfk_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `memberships_ibfk_3` FOREIGN KEY (`configid`) REFERENCES `mod_configs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `messages_groups`
--
ALTER TABLE `messages_groups`
ADD CONSTRAINT `messages_groups_ibfk_1` FOREIGN KEY (`msgid`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `messages_groups_ibfk_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mod_stdmsgs`
--
ALTER TABLE `mod_stdmsgs`
ADD CONSTRAINT `mod_stdmsgs_ibfk_1` FOREIGN KEY (`configid`) REFERENCES `mod_configs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `plugin`
--
ALTER TABLE `plugin`
ADD CONSTRAINT `plugin_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

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
