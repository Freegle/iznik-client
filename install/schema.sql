-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 10, 2016 at 11:12 PM
-- Server version: 5.7.14-8-57
-- PHP Version: 5.5.9-1ubuntu4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `blog`
--

-- --------------------------------------------------------

--
-- Table structure for table `wp_comments`
--

CREATE TABLE IF NOT EXISTS `wp_comments` (
  `comment_ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_post_ID` bigint(20) unsigned NOT NULL DEFAULT '0',
  `comment_author` tinytext NOT NULL,
  `comment_author_email` varchar(100) NOT NULL DEFAULT '',
  `comment_author_url` varchar(200) NOT NULL DEFAULT '',
  `comment_author_IP` varchar(100) NOT NULL DEFAULT '',
  `comment_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_content` text NOT NULL,
  `comment_karma` int(11) NOT NULL DEFAULT '0',
  `comment_approved` varchar(20) NOT NULL DEFAULT '1',
  `comment_agent` varchar(255) NOT NULL DEFAULT '',
  `comment_type` varchar(20) NOT NULL DEFAULT '',
  `comment_parent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`comment_ID`),
  KEY `comment_approved` (`comment_approved`),
  KEY `comment_post_ID` (`comment_post_ID`),
  KEY `comment_approved_date_gmt` (`comment_approved`,`comment_date_gmt`),
  KEY `comment_date_gmt` (`comment_date_gmt`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=753 ;

-- --------------------------------------------------------

--
-- Table structure for table `wp_links`
--

CREATE TABLE IF NOT EXISTS `wp_links` (
  `link_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `link_url` varchar(255) NOT NULL DEFAULT '',
  `link_name` varchar(255) NOT NULL DEFAULT '',
  `link_image` varchar(255) NOT NULL DEFAULT '',
  `link_target` varchar(25) NOT NULL DEFAULT '',
  `link_description` varchar(255) NOT NULL DEFAULT '',
  `link_visible` varchar(20) NOT NULL DEFAULT 'Y',
  `link_owner` bigint(20) unsigned NOT NULL DEFAULT '1',
  `link_rating` int(11) NOT NULL DEFAULT '0',
  `link_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `link_rel` varchar(255) NOT NULL DEFAULT '',
  `link_notes` mediumtext NOT NULL,
  `link_rss` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`link_id`),
  KEY `link_visible` (`link_visible`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Table structure for table `wp_options`
--

CREATE TABLE IF NOT EXISTS `wp_options` (
  `option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `blog_id` int(11) NOT NULL DEFAULT '0',
  `option_name` varchar(64) NOT NULL DEFAULT '',
  `option_value` longtext NOT NULL,
  `autoload` varchar(20) NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`option_id`,`blog_id`,`option_name`),
  KEY `option_name` (`option_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=21213 ;

-- --------------------------------------------------------

--
-- Table structure for table `wp_postmeta`
--

CREATE TABLE IF NOT EXISTS `wp_postmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext,
  PRIMARY KEY (`meta_id`),
  KEY `post_id` (`post_id`),
  KEY `meta_key` (`meta_key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3807 ;

-- --------------------------------------------------------

--
-- Table structure for table `wp_posts`
--

CREATE TABLE IF NOT EXISTS `wp_posts` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_author` bigint(20) unsigned NOT NULL DEFAULT '0',
  `post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content` longtext NOT NULL,
  `post_title` text NOT NULL,
  `post_excerpt` text NOT NULL,
  `post_status` varchar(20) NOT NULL DEFAULT 'publish',
  `comment_status` varchar(20) NOT NULL DEFAULT 'open',
  `ping_status` varchar(20) NOT NULL DEFAULT 'open',
  `post_password` varchar(20) NOT NULL DEFAULT '',
  `post_name` varchar(200) NOT NULL DEFAULT '',
  `to_ping` text NOT NULL,
  `pinged` text NOT NULL,
  `post_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content_filtered` text NOT NULL,
  `post_parent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `guid` varchar(255) NOT NULL DEFAULT '',
  `menu_order` int(11) NOT NULL DEFAULT '0',
  `post_type` varchar(20) NOT NULL DEFAULT 'post',
  `post_mime_type` varchar(100) NOT NULL DEFAULT '',
  `comment_count` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `post_name` (`post_name`),
  KEY `type_status_date` (`post_type`,`post_status`,`post_date`,`ID`),
  KEY `post_parent` (`post_parent`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7782 ;

-- --------------------------------------------------------

--
-- Table structure for table `wp_term_relationships`
--

CREATE TABLE IF NOT EXISTS `wp_term_relationships` (
  `object_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `term_taxonomy_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `term_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`object_id`,`term_taxonomy_id`),
  KEY `term_taxonomy_id` (`term_taxonomy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `wp_term_taxonomy`
--

CREATE TABLE IF NOT EXISTS `wp_term_taxonomy` (
  `term_taxonomy_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `taxonomy` varchar(32) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `parent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `count` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`term_taxonomy_id`),
  UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
  KEY `taxonomy` (`taxonomy`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1491 ;

-- --------------------------------------------------------

--
-- Table structure for table `wp_terms`
--

CREATE TABLE IF NOT EXISTS `wp_terms` (
  `term_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `slug` varchar(200) NOT NULL DEFAULT '',
  `term_group` bigint(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`term_id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1491 ;

-- --------------------------------------------------------

--
-- Table structure for table `wp_usermeta`
--

CREATE TABLE IF NOT EXISTS `wp_usermeta` (
  `umeta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext,
  PRIMARY KEY (`umeta_id`),
  KEY `user_id` (`user_id`),
  KEY `meta_key` (`meta_key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=296 ;

-- --------------------------------------------------------

--
-- Table structure for table `wp_users`
--

CREATE TABLE IF NOT EXISTS `wp_users` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_login` varchar(60) NOT NULL DEFAULT '',
  `user_pass` varchar(64) NOT NULL DEFAULT '',
  `user_nicename` varchar(50) NOT NULL DEFAULT '',
  `user_email` varchar(100) NOT NULL DEFAULT '',
  `user_url` varchar(100) NOT NULL DEFAULT '',
  `user_registered` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_activation_key` varchar(60) NOT NULL DEFAULT '',
  `user_status` int(11) NOT NULL DEFAULT '0',
  `display_name` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `user_login_key` (`user_login`),
  KEY `user_nicename` (`user_nicename`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15 ;
--
-- Database: `helplive`
--

-- --------------------------------------------------------

--
-- Table structure for table `chatban`
--

CREATE TABLE IF NOT EXISTS `chatban` (
  `banid` int(11) NOT NULL AUTO_INCREMENT,
  `dtmcreated` datetime DEFAULT '0000-00-00 00:00:00',
  `dtmtill` datetime DEFAULT '0000-00-00 00:00:00',
  `address` varchar(255) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `blockedCount` int(11) DEFAULT '0',
  PRIMARY KEY (`banid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `chatconfig`
--

CREATE TABLE IF NOT EXISTS `chatconfig` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vckey` varchar(255) DEFAULT NULL,
  `vcvalue` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=33 ;

-- --------------------------------------------------------

--
-- Table structure for table `chatgroup`
--

CREATE TABLE IF NOT EXISTS `chatgroup` (
  `groupid` int(11) NOT NULL AUTO_INCREMENT,
  `vcemail` varchar(64) DEFAULT NULL,
  `vclocalname` varchar(64) NOT NULL,
  `vccommonname` varchar(64) NOT NULL,
  `vclocaldescription` varchar(1024) NOT NULL,
  `vccommondescription` varchar(1024) NOT NULL,
  PRIMARY KEY (`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `chatgroupoperator`
--

CREATE TABLE IF NOT EXISTS `chatgroupoperator` (
  `groupid` int(11) NOT NULL,
  `operatorid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `chatmessage`
--

CREATE TABLE IF NOT EXISTS `chatmessage` (
  `messageid` int(11) NOT NULL AUTO_INCREMENT,
  `threadid` int(11) NOT NULL,
  `ikind` int(11) NOT NULL,
  `agentId` int(11) NOT NULL DEFAULT '0',
  `tmessage` text NOT NULL,
  `dtmcreated` datetime DEFAULT '0000-00-00 00:00:00',
  `tname` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`messageid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=42 ;

-- --------------------------------------------------------

--
-- Table structure for table `chatoperator`
--

CREATE TABLE IF NOT EXISTS `chatoperator` (
  `operatorid` int(11) NOT NULL AUTO_INCREMENT,
  `vclogin` varchar(64) NOT NULL,
  `vcpassword` varchar(64) NOT NULL,
  `vclocalename` varchar(64) NOT NULL,
  `vccommonname` varchar(64) NOT NULL,
  `vcemail` varchar(64) DEFAULT NULL,
  `dtmlastvisited` datetime DEFAULT '0000-00-00 00:00:00',
  `istatus` int(11) DEFAULT '0',
  `vcavatar` varchar(255) DEFAULT NULL,
  `vcjabbername` varchar(255) DEFAULT NULL,
  `iperm` int(11) DEFAULT '65535',
  `dtmrestore` datetime DEFAULT '0000-00-00 00:00:00',
  `vcrestoretoken` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`operatorid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `chatresponses`
--

CREATE TABLE IF NOT EXISTS `chatresponses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locale` varchar(8) DEFAULT NULL,
  `groupid` int(11) DEFAULT NULL,
  `vcvalue` varchar(1024) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `chatrevision`
--

CREATE TABLE IF NOT EXISTS `chatrevision` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `chatthread`
--

CREATE TABLE IF NOT EXISTS `chatthread` (
  `threadid` int(11) NOT NULL AUTO_INCREMENT,
  `userName` varchar(64) NOT NULL,
  `userid` varchar(255) DEFAULT NULL,
  `agentName` varchar(64) DEFAULT NULL,
  `agentId` int(11) NOT NULL DEFAULT '0',
  `dtmcreated` datetime DEFAULT '0000-00-00 00:00:00',
  `dtmmodified` datetime DEFAULT '0000-00-00 00:00:00',
  `lrevision` int(11) NOT NULL DEFAULT '0',
  `istate` int(11) NOT NULL DEFAULT '0',
  `ltoken` int(11) NOT NULL,
  `remote` varchar(255) DEFAULT NULL,
  `referer` text,
  `nextagent` int(11) NOT NULL DEFAULT '0',
  `locale` varchar(8) DEFAULT NULL,
  `lastpinguser` datetime DEFAULT '0000-00-00 00:00:00',
  `lastpingagent` datetime DEFAULT '0000-00-00 00:00:00',
  `userTyping` int(11) DEFAULT '0',
  `agentTyping` int(11) DEFAULT '0',
  `shownmessageid` int(11) NOT NULL DEFAULT '0',
  `userAgent` varchar(255) DEFAULT NULL,
  `messageCount` varchar(16) DEFAULT NULL,
  `groupid` int(11) DEFAULT NULL,
  PRIMARY KEY (`threadid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;
--
-- Database: `ilovefreegle`
--

-- --------------------------------------------------------

--
-- Stand-in structure for view `mailshot1_noresponse`
--
CREATE TABLE IF NOT EXISTS `mailshot1_noresponse` (
   `groupID` int(10) unsigned
  ,`regionID` int(10) unsigned
  ,`groupTitle` varchar(255)
  ,`groupURL` varchar(255)
  ,`groupPublished` int(1)
  ,`groupLocalities` varchar(255)
  ,`groupMembers` int(11)
  ,`groupLatitude` double
  ,`groupLongitude` double
  ,`groupMsgMakerOffer` varchar(31)
  ,`groupMsgMakerTaken` varchar(31)
  ,`groupMsgMakerRequest` varchar(31)
  ,`groupMsgMakerReceived` varchar(31)
  ,`groupMailingPriority` int(1) unsigned
  ,`groupGoogleAnalyticsWpid` varchar(31)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `mailshot_emails`
--
CREATE TABLE IF NOT EXISTS `mailshot_emails` (
   `mailingID` int(10) unsigned
  ,`emailID` int(10) unsigned
  ,`groupID` int(10) unsigned
  ,`groupTitle` varchar(255)
  ,`groupURL` varchar(255)
  ,`mailingSubject` varchar(255)
  ,`mailingBody` text
  ,`senderName` varchar(255)
  ,`senderReplyTo` varchar(255)
  ,`senderEmail` varchar(63)
  ,`senderEmailAlt1` varchar(63)
  ,`senderEmailAlt2` varchar(63)
  ,`senderSMTPHost` varchar(63)
  ,`senderSMTPPassword` varchar(31)
  ,`emailStatusSlug` varchar(255)
  ,`mailingStatusSlug` varchar(255)
  ,`groupMailingPriority` int(1) unsigned
  ,`emailCreated` timestamp
  ,`emailError` datetime
  ,`emailErrorMessage` text
  ,`emailCompleted` datetime
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `mailshot_emails_by_group`
--
CREATE TABLE IF NOT EXISTS `mailshot_emails_by_group` (
   `mailingID` int(10) unsigned
  ,`groupCount` bigint(21)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `mailshot_shortlink_clicks`
--
CREATE TABLE IF NOT EXISTS `mailshot_shortlink_clicks` (
   `emailDate` date
  ,`mailingID` int(10) unsigned
  ,`keyword` varchar(255)
  ,`groupsEmailed` bigint(21)
  ,`groupsClicked` bigint(21)
  ,`groupsRemaining` bigint(22)
  ,`groupsClickedPercent` decimal(27,4)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `mailshot_shortlink_clicks_by_group`
--
CREATE TABLE IF NOT EXISTS `mailshot_shortlink_clicks_by_group` (
   `emailDate` date
  ,`mailingID` int(10) unsigned
  ,`keyword` varchar(255)
  ,`groupTitle` varchar(255)
  ,`clicks` bigint(21)
  ,`first` datetime
  ,`last` datetime
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `mailshot_shortlink_clicks_by_keyword`
--
CREATE TABLE IF NOT EXISTS `mailshot_shortlink_clicks_by_keyword` (
   `emailDate` date
  ,`mailingID` int(10) unsigned
  ,`keyword` varchar(255)
  ,`clicks` bigint(21)
  ,`first` datetime
  ,`last` datetime
);
-- --------------------------------------------------------

--
-- Table structure for table `perch_contentItems`
--

CREATE TABLE IF NOT EXISTS `perch_contentItems` (
  `contentID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contentKey` varchar(255) NOT NULL DEFAULT '',
  `contentPage` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '*',
  `contentHTML` longtext NOT NULL,
  `contentNew` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `contentOrder` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `contentTemplate` varchar(255) NOT NULL DEFAULT '',
  `contentMultiple` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `contentAddToTop` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `contentJSON` longtext NOT NULL,
  `contentHistory` mediumtext NOT NULL,
  `contentOptions` text NOT NULL,
  `contentSearchable` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`contentID`),
  KEY `idx_page` (`contentPage`),
  KEY `idx_key` (`contentKey`),
  FULLTEXT KEY `idx_search` (`contentJSON`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1123 ;

-- --------------------------------------------------------

--
-- Table structure for table `perch_emails`
--

CREATE TABLE IF NOT EXISTS `perch_emails` (
  `emailID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mailingID` int(10) unsigned NOT NULL,
  `groupID` int(10) unsigned NOT NULL DEFAULT '0',
  `statusID` int(10) unsigned NOT NULL DEFAULT '1',
  `emailCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `emailError` datetime DEFAULT NULL,
  `emailErrorMessage` text,
  `emailCompleted` datetime DEFAULT NULL,
  `clicks` int(11) NOT NULL DEFAULT '0',
  `replies` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`emailID`),
  KEY `idx_mailingID` (`mailingID`),
  KEY `idx_groupID` (`groupID`),
  KEY `idx_statusID` (`statusID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=234919 ;

-- --------------------------------------------------------

--
-- Table structure for table `perch_forms`
--

CREATE TABLE IF NOT EXISTS `perch_forms` (
  `formID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `formKey` varchar(64) NOT NULL DEFAULT '',
  `formTitle` varchar(255) NOT NULL DEFAULT '',
  `formTemplate` varchar(255) NOT NULL DEFAULT '',
  `formOptions` text,
  PRIMARY KEY (`formID`),
  KEY `idx_formKey` (`formKey`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12 ;

-- --------------------------------------------------------

--
-- Table structure for table `perch_forms_responses`
--

CREATE TABLE IF NOT EXISTS `perch_forms_responses` (
  `responseID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `formID` int(10) unsigned NOT NULL,
  `responseCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `responseJSON` mediumtext,
  `responseIP` varchar(16) NOT NULL DEFAULT '',
  `responseSpam` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `responseSpamData` text,
  PRIMARY KEY (`responseID`),
  KEY `idx_formID` (`formID`),
  KEY `idx_spam` (`responseSpam`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=31105 ;

-- --------------------------------------------------------

--
-- Table structure for table `perch_group_type_xref`
--

CREATE TABLE IF NOT EXISTS `perch_group_type_xref` (
  `typeID` int(10) unsigned NOT NULL,
  `groupID` int(10) unsigned NOT NULL,
  `isMember` int(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Indicates whether this group is a member of this type',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`typeID`,`groupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `perch_group_types`
--

CREATE TABLE IF NOT EXISTS `perch_group_types` (
  `typeID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(31) NOT NULL,
  `typeSlug` varchar(31) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`typeID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `perch_groups`
--

CREATE TABLE IF NOT EXISTS `perch_groups` (
  `groupID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `regionID` int(10) unsigned NOT NULL DEFAULT '0',
  `groupTitle` varchar(255) NOT NULL DEFAULT '',
  `groupURL` varchar(255) NOT NULL DEFAULT '',
  `groupPublished` int(1) NOT NULL DEFAULT '1',
  `groupLocalities` varchar(255) NOT NULL DEFAULT '',
  `groupMembers` int(11) NOT NULL,
  `groupLatitude` double NOT NULL DEFAULT '0',
  `groupLongitude` double NOT NULL DEFAULT '0',
  `groupMsgMakerOffer` varchar(31) NOT NULL DEFAULT '',
  `groupMsgMakerTaken` varchar(31) NOT NULL DEFAULT '',
  `groupMsgMakerRequest` varchar(31) NOT NULL DEFAULT '',
  `groupMsgMakerReceived` varchar(31) NOT NULL DEFAULT '',
  `groupMailingPriority` int(1) unsigned NOT NULL DEFAULT '9',
  `groupGoogleAnalyticsWpid` varchar(31) NOT NULL,
  `groupAcceptsAttachments` tinyint(1) NOT NULL DEFAULT '0',
  `groupStartDate` date NOT NULL COMMENT 'The date the group was started',
  `groupPreferredStartDate` date DEFAULT NULL,
  `groupTags` varchar(255) NOT NULL,
  `groupSiteURL` varchar(255) NOT NULL,
  `groupCafeURL` varchar(255) NOT NULL,
  `groupAttachmentType` enum('none','dist','arch','perm') NOT NULL,
  `groupSpecialism` varchar(255) DEFAULT NULL,
  `groupNorfolkCommunityId` int(10) NOT NULL,
  PRIMARY KEY (`groupID`),
  KEY `idx_regionID` (`regionID`),
  KEY `idx_groupURL` (`groupURL`),
  KEY `groupID` (`groupID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1225 ;

-- --------------------------------------------------------

--
-- Table structure for table `perch_mailings`
--

CREATE TABLE IF NOT EXISTS `perch_mailings` (
  `mailingID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `senderID` int(10) unsigned NOT NULL DEFAULT '0',
  `statusID` int(10) unsigned NOT NULL DEFAULT '1',
  `mailingSubject` varchar(255) NOT NULL DEFAULT '',
  `mailingBody` text NOT NULL,
  `mailingBodyHTML` text,
  `mailingDatetime` datetime DEFAULT NULL,
  `mailingCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mailingStarted` datetime DEFAULT NULL,
  `mailingError` datetime DEFAULT NULL,
  `mailingCompleted` datetime DEFAULT NULL,
  `mailingSQL` varchar(512) DEFAULT NULL,
  `regionID` int(10) unsigned NOT NULL DEFAULT '0',
  `groupTypeID` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`mailingID`),
  KEY `idx_senderID` (`senderID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=538 ;

-- --------------------------------------------------------

--
-- Table structure for table `perch_ornaments`
--

CREATE TABLE IF NOT EXISTS `perch_ornaments` (
  `ornamentID` int(11) NOT NULL AUTO_INCREMENT,
  `ornamentUse` int(1) NOT NULL COMMENT 'Bit mask of places where ornament can be used',
  `ornamentPriority` int(2) NOT NULL DEFAULT '1',
  `ornamentDoodleURL` varchar(255) NOT NULL,
  `ornamentTipText` varchar(255) NOT NULL,
  `ornamentDate` datetime DEFAULT NULL,
  `ornamentDay` int(2) NOT NULL COMMENT '1 to 31',
  `ornamentMonth` int(2) NOT NULL,
  `ornamentYear` int(4) NOT NULL,
  `ornamentWeekday` int(1) NOT NULL COMMENT '0 (for Sunday) through 6 (for Saturday)',
  `ornamentFromDate` datetime DEFAULT NULL,
  `ornamentToDate` datetime DEFAULT NULL,
  PRIMARY KEY (`ornamentID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Doodle and tip ornaments on website etc' AUTO_INCREMENT=370 ;

-- --------------------------------------------------------

--
-- Table structure for table `perch_regions`
--

CREATE TABLE IF NOT EXISTS `perch_regions` (
  `regionID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `regionTitle` varchar(255) NOT NULL DEFAULT '',
  `regionSlug` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`regionID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=13 ;

-- --------------------------------------------------------

--
-- Table structure for table `perch_senders`
--

CREATE TABLE IF NOT EXISTS `perch_senders` (
  `senderID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `senderName` varchar(255) NOT NULL DEFAULT '',
  `senderReplyTo` varchar(255) NOT NULL DEFAULT '',
  `senderEmail` varchar(63) NOT NULL DEFAULT '',
  `senderEmailAlt1` varchar(63) DEFAULT NULL,
  `senderEmailAlt2` varchar(63) DEFAULT NULL,
  `senderSMTPHost` varchar(63) DEFAULT NULL,
  `senderSMTPPassword` varchar(31) DEFAULT NULL,
  `senderSlug` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`senderID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=25 ;

-- --------------------------------------------------------

--
-- Table structure for table `perch_settings`
--

CREATE TABLE IF NOT EXISTS `perch_settings` (
  `settingID` varchar(60) NOT NULL DEFAULT '',
  `settingValue` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`settingID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `perch_shortlink_types`
--

CREATE TABLE IF NOT EXISTS `perch_shortlink_types` (
  `typeID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(31) NOT NULL DEFAULT '',
  `typeSlug` varchar(31) NOT NULL DEFAULT '',
  `displayOrder` tinyint(4) NOT NULL DEFAULT '98',
  `defaultServer` varchar(31) NOT NULL,
  `typeVisible` int(1) NOT NULL DEFAULT '1',
  `googleAnalyticsTracking` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`typeID`),
  UNIQUE KEY `type` (`type`),
  UNIQUE KEY `typeSlug` (`typeSlug`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=26 ;

-- --------------------------------------------------------

--
-- Table structure for table `perch_shortlinks`
--

CREATE TABLE IF NOT EXISTS `perch_shortlinks` (
  `shortlinkID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `typeID` int(10) unsigned NOT NULL DEFAULT '1',
  `keyword` varchar(255) NOT NULL DEFAULT '',
  `shortlinkURL` varchar(255) NOT NULL,
  `groupID` int(10) unsigned NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `clicks` int(11) NOT NULL DEFAULT '0',
  `googleAnalyticsWpid` varchar(31) NOT NULL DEFAULT '',
  PRIMARY KEY (`shortlinkID`),
  UNIQUE KEY `idx_keyword` (`keyword`),
  KEY `idx_regionID` (`groupID`),
  KEY `idx_shortlinkURL` (`shortlinkURL`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3573 ;

-- --------------------------------------------------------

--
-- Table structure for table `perch_shortlinks_log`
--

CREATE TABLE IF NOT EXISTS `perch_shortlinks_log` (
  `click_id` int(11) NOT NULL AUTO_INCREMENT,
  `click_time` datetime NOT NULL,
  `shortlinkID` int(10) NOT NULL,
  `emailID` int(10) DEFAULT NULL,
  `referrer` varchar(200) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `ip_address` varchar(41) NOT NULL,
  `country_code` char(2) NOT NULL,
  PRIMARY KEY (`click_id`),
  KEY `shortlinkID` (`shortlinkID`),
  KEY `emailID` (`emailID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `perch_statuses`
--

CREATE TABLE IF NOT EXISTS `perch_statuses` (
  `statusID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `statusType` enum('mailing','email') NOT NULL,
  `statusDisplayOrder` tinyint(4) NOT NULL DEFAULT '99',
  `userRoleCanChange` enum('Editor','Admin','System') NOT NULL DEFAULT 'Editor',
  `userRoleCanSet` enum('Editor','Admin','System') NOT NULL DEFAULT 'System',
  `statusName` varchar(31) NOT NULL DEFAULT '',
  `statusDescription` varchar(255) NOT NULL DEFAULT '',
  `statusSlug` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`statusID`),
  UNIQUE KEY `idx_statusTypeSlug` (`statusType`,`statusSlug`),
  UNIQUE KEY `idx_statusTypeName` (`statusType`,`statusName`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=17 ;

-- --------------------------------------------------------

--
-- Table structure for table `perch_supporters`
--

CREATE TABLE IF NOT EXISTS `perch_supporters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `added` datetime NOT NULL,
  `ip` varchar(255) NOT NULL COMMENT 'IP address added from',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=64 ;

-- --------------------------------------------------------

--
-- Table structure for table `perch_users`
--

CREATE TABLE IF NOT EXISTS `perch_users` (
  `userID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userUsername` varchar(255) NOT NULL DEFAULT '',
  `userPassword` varchar(255) NOT NULL DEFAULT '',
  `userCreated` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `userUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `userLastLogin` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `userGivenName` varchar(255) NOT NULL DEFAULT '',
  `userFamilyName` varchar(255) NOT NULL DEFAULT '',
  `userEmail` varchar(255) NOT NULL DEFAULT '',
  `userEnabled` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `userHash` char(32) NOT NULL DEFAULT '',
  `userRole` enum('Editor','Admin') NOT NULL DEFAULT 'Editor',
  PRIMARY KEY (`userID`),
  KEY `idx_enabled` (`userEnabled`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=57 ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_shortlinks`
--
CREATE TABLE IF NOT EXISTS `view_shortlinks` (
   `shortlinkID` int(10) unsigned
  ,`typeID` int(10) unsigned
  ,`keyword` varchar(255)
  ,`shortlinkURL` varchar(255)
  ,`groupID` int(10) unsigned
  ,`created` timestamp
  ,`clicks` int(11)
  ,`googleAnalyticsWpid` varchar(31)
  ,`type` varchar(31)
  ,`typeSlug` varchar(31)
  ,`defaultServer` varchar(31)
  ,`typeVisible` int(1)
  ,`groupTitle` varchar(255)
  ,`groupURL` varchar(255)
  ,`groupGoogleAnalyticsWpid` varchar(31)
  ,`googleAnalyticsTracking` int(1)
);
-- --------------------------------------------------------

--
-- Structure for view `mailshot1_noresponse`
--
DROP TABLE IF EXISTS `mailshot1_noresponse`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `mailshot1_noresponse` AS select `perch_groups`.`groupID` AS `groupID`,`perch_groups`.`regionID` AS `regionID`,`perch_groups`.`groupTitle` AS `groupTitle`,`perch_groups`.`groupURL` AS `groupURL`,`perch_groups`.`groupPublished` AS `groupPublished`,`perch_groups`.`groupLocalities` AS `groupLocalities`,`perch_groups`.`groupMembers` AS `groupMembers`,`perch_groups`.`groupLatitude` AS `groupLatitude`,`perch_groups`.`groupLongitude` AS `groupLongitude`,`perch_groups`.`groupMsgMakerOffer` AS `groupMsgMakerOffer`,`perch_groups`.`groupMsgMakerTaken` AS `groupMsgMakerTaken`,`perch_groups`.`groupMsgMakerRequest` AS `groupMsgMakerRequest`,`perch_groups`.`groupMsgMakerReceived` AS `groupMsgMakerReceived`,`perch_groups`.`groupMailingPriority` AS `groupMailingPriority`,`perch_groups`.`groupGoogleAnalyticsWpid` AS `groupGoogleAnalyticsWpid` from `perch_groups` where (not(`perch_groups`.`groupTitle` in (select `mailshot_shortlink_clicks_by_group`.`groupTitle` AS `groupTitle` from `mailshot_shortlink_clicks_by_group` where (`mailshot_shortlink_clicks_by_group`.`keyword` = 'mailingtest1')))) limit 0,3000;

-- --------------------------------------------------------

--
-- Structure for view `mailshot_emails`
--
DROP TABLE IF EXISTS `mailshot_emails`;

CREATE ALGORITHM=UNDEFINED DEFINER=`ilovefreegle`@`localhost` SQL SECURITY DEFINER VIEW `mailshot_emails` AS select `m`.`mailingID` AS `mailingID`,`e`.`emailID` AS `emailID`,`g`.`groupID` AS `groupID`,`g`.`groupTitle` AS `groupTitle`,`g`.`groupURL` AS `groupURL`,`m`.`mailingSubject` AS `mailingSubject`,`m`.`mailingBody` AS `mailingBody`,`sdr`.`senderName` AS `senderName`,`sdr`.`senderReplyTo` AS `senderReplyTo`,`sdr`.`senderEmail` AS `senderEmail`,`sdr`.`senderEmailAlt1` AS `senderEmailAlt1`,`sdr`.`senderEmailAlt2` AS `senderEmailAlt2`,`sdr`.`senderSMTPHost` AS `senderSMTPHost`,`sdr`.`senderSMTPPassword` AS `senderSMTPPassword`,`ests`.`statusSlug` AS `emailStatusSlug`,`msts`.`statusSlug` AS `mailingStatusSlug`,`g`.`groupMailingPriority` AS `groupMailingPriority`,`e`.`emailCreated` AS `emailCreated`,`e`.`emailError` AS `emailError`,`e`.`emailErrorMessage` AS `emailErrorMessage`,`e`.`emailCompleted` AS `emailCompleted` from (((((`perch_emails` `e` join `perch_groups` `g`) join `perch_mailings` `m`) join `perch_senders` `sdr`) join `perch_statuses` `ests`) join `perch_statuses` `msts`) where ((`e`.`groupID` = `g`.`groupID`) and (`e`.`mailingID` = `m`.`mailingID`) and (`e`.`statusID` = `ests`.`statusID`) and (`m`.`statusID` = `msts`.`statusID`) and (`m`.`senderID` = `sdr`.`senderID`) and (`msts`.`statusSlug` = 'sending-in-progress')) order by `m`.`mailingDatetime`,`g`.`groupMailingPriority`,`g`.`groupTitle`;

-- --------------------------------------------------------

--
-- Structure for view `mailshot_emails_by_group`
--
DROP TABLE IF EXISTS `mailshot_emails_by_group`;

CREATE ALGORITHM=UNDEFINED DEFINER=`ilovefreegle`@`localhost` SQL SECURITY DEFINER VIEW `mailshot_emails_by_group` AS select distinct `perch_emails`.`mailingID` AS `mailingID`,count(0) AS `groupCount` from `perch_emails` group by `perch_emails`.`mailingID`,`perch_emails`.`emailCreated` order by `perch_emails`.`mailingID` desc;

-- --------------------------------------------------------

--
-- Structure for view `mailshot_shortlink_clicks`
--
DROP TABLE IF EXISTS `mailshot_shortlink_clicks`;

CREATE ALGORITHM=UNDEFINED DEFINER=`ilovefreegle`@`localhost` SQL SECURITY DEFINER VIEW `mailshot_shortlink_clicks` AS select `cbg`.`emailDate` AS `emailDate`,`cbg`.`mailingID` AS `mailingID`,`cbg`.`keyword` AS `keyword`,`ebg`.`groupCount` AS `groupsEmailed`,count(0) AS `groupsClicked`,(`ebg`.`groupCount` - count(0)) AS `groupsRemaining`,((count(0) / `ebg`.`groupCount`) * 100) AS `groupsClickedPercent` from (`mailshot_shortlink_clicks_by_group` `cbg` join `mailshot_emails_by_group` `ebg`) where (`cbg`.`mailingID` = `ebg`.`mailingID`) group by `cbg`.`emailDate`,`cbg`.`mailingID`,`cbg`.`keyword` order by `cbg`.`emailDate` desc;

-- --------------------------------------------------------

--
-- Structure for view `mailshot_shortlink_clicks_by_group`
--
DROP TABLE IF EXISTS `mailshot_shortlink_clicks_by_group`;

CREATE ALGORITHM=UNDEFINED DEFINER=`ilovefreegle`@`localhost` SQL SECURITY DEFINER VIEW `mailshot_shortlink_clicks_by_group` AS select cast(`email`.`emailCreated` as date) AS `emailDate`,`email`.`mailingID` AS `mailingID`,`link`.`keyword` AS `keyword`,`grp`.`groupTitle` AS `groupTitle`,count(`log`.`click_time`) AS `clicks`,min(`log`.`click_time`) AS `first`,max(`log`.`click_time`) AS `last` from (((`perch_shortlinks_log` `log` join `perch_shortlinks` `link`) join `perch_emails` `email`) join `perch_groups` `grp`) where ((`log`.`emailID` is not null) and (`log`.`emailID` <> 0) and (`log`.`emailID` = `email`.`emailID`) and (`log`.`shortlinkID` = `link`.`shortlinkID`) and (`email`.`groupID` = `grp`.`groupID`)) group by `email`.`mailingID`,cast(`email`.`emailCreated` as date),`link`.`keyword`,`grp`.`groupTitle` order by cast(`email`.`emailCreated` as date) desc,`email`.`mailingID` desc,`link`.`keyword`,`grp`.`groupTitle`;

-- --------------------------------------------------------

--
-- Structure for view `mailshot_shortlink_clicks_by_keyword`
--
DROP TABLE IF EXISTS `mailshot_shortlink_clicks_by_keyword`;

CREATE ALGORITHM=UNDEFINED DEFINER=`ilovefreegle`@`localhost` SQL SECURITY DEFINER VIEW `mailshot_shortlink_clicks_by_keyword` AS select cast(`email`.`emailCreated` as date) AS `emailDate`,`email`.`mailingID` AS `mailingID`,`link`.`keyword` AS `keyword`,count(`log`.`click_time`) AS `clicks`,min(`log`.`click_time`) AS `first`,max(`log`.`click_time`) AS `last` from ((`perch_shortlinks_log` `log` join `perch_shortlinks` `link`) join `perch_emails` `email`) where ((`log`.`emailID` is not null) and (`log`.`emailID` <> 0) and (`log`.`emailID` = `email`.`emailID`) and (`log`.`shortlinkID` = `link`.`shortlinkID`)) group by `email`.`mailingID`,cast(`email`.`emailCreated` as date),`link`.`keyword` order by cast(`email`.`emailCreated` as date) desc,`email`.`mailingID` desc,`link`.`keyword`;

-- --------------------------------------------------------

--
-- Structure for view `view_shortlinks`
--
DROP TABLE IF EXISTS `view_shortlinks`;

CREATE ALGORITHM=UNDEFINED DEFINER=`ilovefreegle`@`localhost` SQL SECURITY DEFINER VIEW `view_shortlinks` AS select `s`.`shortlinkID` AS `shortlinkID`,`s`.`typeID` AS `typeID`,`s`.`keyword` AS `keyword`,`s`.`shortlinkURL` AS `shortlinkURL`,`s`.`groupID` AS `groupID`,`s`.`created` AS `created`,`s`.`clicks` AS `clicks`,`s`.`googleAnalyticsWpid` AS `googleAnalyticsWpid`,`t`.`type` AS `type`,`t`.`typeSlug` AS `typeSlug`,`t`.`defaultServer` AS `defaultServer`,`t`.`typeVisible` AS `typeVisible`,`g`.`groupTitle` AS `groupTitle`,`g`.`groupURL` AS `groupURL`,`g`.`groupGoogleAnalyticsWpid` AS `groupGoogleAnalyticsWpid`,`t`.`googleAnalyticsTracking` AS `googleAnalyticsTracking` from ((`perch_shortlinks` `s` join `perch_shortlink_types` `t` on((`s`.`typeID` = `t`.`typeID`))) left join `perch_groups` `g` on((`s`.`groupID` = `g`.`groupID`)));
--
-- Database: `iznik`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`app2` PROCEDURE `ANALYZE_INVALID_FOREIGN_KEYS`(
  checked_database_name VARCHAR(64),
  checked_table_name VARCHAR(64),
  temporary_result_table ENUM('Y', 'N'))
READS SQL DATA
  BEGIN
    DECLARE TABLE_SCHEMA_VAR VARCHAR(64);
    DECLARE TABLE_NAME_VAR VARCHAR(64);
    DECLARE COLUMN_NAME_VAR VARCHAR(64);
    DECLARE CONSTRAINT_NAME_VAR VARCHAR(64);
    DECLARE REFERENCED_TABLE_SCHEMA_VAR VARCHAR(64);
    DECLARE REFERENCED_TABLE_NAME_VAR VARCHAR(64);
    DECLARE REFERENCED_COLUMN_NAME_VAR VARCHAR(64);
    DECLARE KEYS_SQL_VAR VARCHAR(1024);

    DECLARE done INT DEFAULT 0;

    DECLARE foreign_key_cursor CURSOR FOR
      SELECT
        `TABLE_SCHEMA`,
        `TABLE_NAME`,
        `COLUMN_NAME`,
        `CONSTRAINT_NAME`,
        `REFERENCED_TABLE_SCHEMA`,
        `REFERENCED_TABLE_NAME`,
        `REFERENCED_COLUMN_NAME`
      FROM
        information_schema.KEY_COLUMN_USAGE
      WHERE
        `CONSTRAINT_SCHEMA` LIKE checked_database_name AND
        `TABLE_NAME` LIKE checked_table_name AND
        `REFERENCED_TABLE_SCHEMA` IS NOT NULL;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    IF temporary_result_table = 'N' THEN
      DROP TEMPORARY TABLE IF EXISTS INVALID_FOREIGN_KEYS;
      DROP TABLE IF EXISTS INVALID_FOREIGN_KEYS;

      CREATE TABLE INVALID_FOREIGN_KEYS(
        `TABLE_SCHEMA` VARCHAR(64),
        `TABLE_NAME` VARCHAR(64),
        `COLUMN_NAME` VARCHAR(64),
        `CONSTRAINT_NAME` VARCHAR(64),
        `REFERENCED_TABLE_SCHEMA` VARCHAR(64),
        `REFERENCED_TABLE_NAME` VARCHAR(64),
        `REFERENCED_COLUMN_NAME` VARCHAR(64),
        `INVALID_KEY_COUNT` INT,
        `INVALID_KEY_SQL` VARCHAR(1024)
      );
    ELSEIF temporary_result_table = 'Y' THEN
      DROP TEMPORARY TABLE IF EXISTS INVALID_FOREIGN_KEYS;
      DROP TABLE IF EXISTS INVALID_FOREIGN_KEYS;

      CREATE TEMPORARY TABLE INVALID_FOREIGN_KEYS(
        `TABLE_SCHEMA` VARCHAR(64),
        `TABLE_NAME` VARCHAR(64),
        `COLUMN_NAME` VARCHAR(64),
        `CONSTRAINT_NAME` VARCHAR(64),
        `REFERENCED_TABLE_SCHEMA` VARCHAR(64),
        `REFERENCED_TABLE_NAME` VARCHAR(64),
        `REFERENCED_COLUMN_NAME` VARCHAR(64),
        `INVALID_KEY_COUNT` INT,
        `INVALID_KEY_SQL` VARCHAR(1024)
      );
    END IF;


    OPEN foreign_key_cursor;
    foreign_key_cursor_loop: LOOP
      FETCH foreign_key_cursor INTO
        TABLE_SCHEMA_VAR,
        TABLE_NAME_VAR,
        COLUMN_NAME_VAR,
        CONSTRAINT_NAME_VAR,
        REFERENCED_TABLE_SCHEMA_VAR,
        REFERENCED_TABLE_NAME_VAR,
        REFERENCED_COLUMN_NAME_VAR;
      IF done THEN
        LEAVE foreign_key_cursor_loop;
      END IF;


      SET @from_part = CONCAT('FROM ', '`', TABLE_SCHEMA_VAR, '`.`', TABLE_NAME_VAR, '`', ' AS REFERRING ',
                              'LEFT JOIN `', REFERENCED_TABLE_SCHEMA_VAR, '`.`', REFERENCED_TABLE_NAME_VAR, '`', ' AS REFERRED ',
                              'ON (REFERRING', '.`', COLUMN_NAME_VAR, '`', ' = ', 'REFERRED', '.`', REFERENCED_COLUMN_NAME_VAR, '`', ') ',
                              'WHERE REFERRING', '.`', COLUMN_NAME_VAR, '`', ' IS NOT NULL ',
                              'AND REFERRED', '.`', REFERENCED_COLUMN_NAME_VAR, '`', ' IS NULL');
      SET @full_query = CONCAT('SELECT COUNT(*) ', @from_part, ' INTO @invalid_key_count;');
      PREPARE stmt FROM @full_query;

      EXECUTE stmt;
      IF @invalid_key_count > 0 THEN
        INSERT INTO
          INVALID_FOREIGN_KEYS
        SET
          `TABLE_SCHEMA` = TABLE_SCHEMA_VAR,
          `TABLE_NAME` = TABLE_NAME_VAR,
          `COLUMN_NAME` = COLUMN_NAME_VAR,
          `CONSTRAINT_NAME` = CONSTRAINT_NAME_VAR,
          `REFERENCED_TABLE_SCHEMA` = REFERENCED_TABLE_SCHEMA_VAR,
          `REFERENCED_TABLE_NAME` = REFERENCED_TABLE_NAME_VAR,
          `REFERENCED_COLUMN_NAME` = REFERENCED_COLUMN_NAME_VAR,
          `INVALID_KEY_COUNT` = @invalid_key_count,
          `INVALID_KEY_SQL` = CONCAT('SELECT ',
                                     'REFERRING.', '`', COLUMN_NAME_VAR, '` ', 'AS "Invalid: ', COLUMN_NAME_VAR, '", ',
                                     'REFERRING.* ',
                                     @from_part, ';');
      END IF;
      DEALLOCATE PREPARE stmt;

    END LOOP foreign_key_cursor_loop;
  END$$

CREATE DEFINER=`root`@`app2` PROCEDURE `ANALYZE_INVALID_UNIQUE_KEYS`(
  checked_database_name VARCHAR(64),
  checked_table_name VARCHAR(64))
READS SQL DATA
  BEGIN
    DECLARE TABLE_SCHEMA_VAR VARCHAR(64);
    DECLARE TABLE_NAME_VAR VARCHAR(64);
    DECLARE COLUMN_NAMES_VAR VARCHAR(1000);
    DECLARE CONSTRAINT_NAME_VAR VARCHAR(64);

    DECLARE done INT DEFAULT 0;

    DECLARE unique_key_cursor CURSOR FOR
      select kcu.table_schema sch,
             kcu.table_name tbl,
             group_concat(kcu.column_name) colName,
             kcu.constraint_name constName
      from
        information_schema.table_constraints tc
        join
        information_schema.key_column_usage kcu
          on
            kcu.constraint_name=tc.constraint_name
            and kcu.constraint_schema=tc.constraint_schema
            and kcu.table_name=tc.table_name
      where
        kcu.table_schema like checked_database_name
        and kcu.table_name like checked_table_name
        and tc.constraint_type="UNIQUE" group by sch, tbl, constName;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    DROP TEMPORARY TABLE IF EXISTS INVALID_UNIQUE_KEYS;
    CREATE TEMPORARY TABLE INVALID_UNIQUE_KEYS(
      `TABLE_SCHEMA` VARCHAR(64),
      `TABLE_NAME` VARCHAR(64),
      `COLUMN_NAMES` VARCHAR(1000),
      `CONSTRAINT_NAME` VARCHAR(64),
      `INVALID_KEY_COUNT` INT
    );



    OPEN unique_key_cursor;
    unique_key_cursor_loop: LOOP
      FETCH unique_key_cursor INTO
        TABLE_SCHEMA_VAR,
        TABLE_NAME_VAR,
        COLUMN_NAMES_VAR,
        CONSTRAINT_NAME_VAR;
      IF done THEN
        LEAVE unique_key_cursor_loop;
      END IF;

      SET @from_part = CONCAT('FROM (SELECT COUNT(*) counter FROM', '`', TABLE_SCHEMA_VAR, '`.`', TABLE_NAME_VAR, '`',
                              ' GROUP BY ', COLUMN_NAMES_VAR , ') as s where s.counter > 1');
      SET @full_query = CONCAT('SELECT COUNT(*) ', @from_part, ' INTO @invalid_key_count;');
      PREPARE stmt FROM @full_query;
      EXECUTE stmt;
      IF @invalid_key_count > 0 THEN
        INSERT INTO
          INVALID_UNIQUE_KEYS
        SET
          `TABLE_SCHEMA` = TABLE_SCHEMA_VAR,
          `TABLE_NAME` = TABLE_NAME_VAR,
          `COLUMN_NAMES` = COLUMN_NAMES_VAR,
          `CONSTRAINT_NAME` = CONSTRAINT_NAME_VAR,
          `INVALID_KEY_COUNT` = @invalid_key_count;
      END IF;
      DEALLOCATE PREPARE stmt;

    END LOOP unique_key_cursor_loop;
  END$$

--
-- Functions
--
CREATE DEFINER=`root`@`app2` FUNCTION `GetCenterPoint`(`g` GEOMETRY) RETURNS point
NO SQL
DETERMINISTIC
  BEGIN
    DECLARE envelope POLYGON;
    DECLARE sw, ne POINT;
    DECLARE lat, lng DOUBLE;

    SET envelope = ExteriorRing(Envelope(g));
    SET sw = PointN(envelope, 1);
    SET ne = PointN(envelope, 3);
    SET lat = X(sw) + (X(ne)-X(sw))/2;
    SET lng = Y(sw) + (Y(ne)-Y(sw))/2;
    RETURN POINT(lat, lng);
  END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `GetMaxDimension`(`g` GEOMETRY) RETURNS double
NO SQL
DETERMINISTIC
  BEGIN
    DECLARE envelope POLYGON;
    DECLARE sw, ne POINT;
    DECLARE xsize, ysize DOUBLE;

    DECLARE EXIT HANDLER FOR 1416
    RETURN(10000);

    SET envelope = ExteriorRing(Envelope(g));
    SET sw = PointN(envelope, 1);
    SET ne = PointN(envelope, 3);
    SET xsize = X(ne) - X(sw);
    SET ysize = Y(ne) - Y(sw);
    RETURN(GREATEST(xsize, ysize));
  END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `GetMaxDimensionT`(`g` GEOMETRY) RETURNS double
NO SQL
  BEGIN
    DECLARE envelope POLYGON;
    DECLARE sw, ne POINT;
    DECLARE xsize, ysize DOUBLE;

    SET envelope = ExteriorRing(ST_Envelope(g));
    SET sw = PointN(envelope, 1);
    SET ne = PointN(envelope, 3);
    SET xsize = X(ne) - X(sw);
    SET ysize = Y(ne) - Y(sw);
    RETURN(GREATEST(xsize, ysize));
  END$$

CREATE DEFINER=`root`@`app2` FUNCTION `haversine`(
  lat1 FLOAT, lon1 FLOAT,
  lat2 FLOAT, lon2 FLOAT
) RETURNS float
NO SQL
DETERMINISTIC
  COMMENT 'Returns the distance in degrees on the Earth\n             between two known points of latitude and longitude'
  BEGIN
    RETURN 69 * DEGREES(ACOS(
                            COS(RADIANS(lat1)) *
                            COS(RADIANS(lat2)) *
                            COS(RADIANS(lon2) - RADIANS(lon1)) +
                            SIN(RADIANS(lat1)) * SIN(RADIANS(lat2))
                        ));
  END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `VW_recentqueries`
--
CREATE TABLE IF NOT EXISTS `VW_recentqueries` (
   `id` bigint(20) unsigned
  ,`chatid` bigint(20) unsigned
  ,`userid` bigint(20) unsigned
  ,`type` enum('Default','System','ModMail','Interested','Promised','Reneged','ReportedUser')
  ,`reportreason` enum('Spam','Other')
  ,`refmsgid` bigint(20) unsigned
  ,`refchatid` bigint(20) unsigned
  ,`date` timestamp
  ,`message` text
  ,`platform` tinyint(4)
  ,`seenbyall` tinyint(1)
  ,`reviewrequired` tinyint(1)
  ,`reviewedby` bigint(20) unsigned
  ,`reviewrejected` tinyint(1)
  ,`spamscore` int(11)
);
-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE IF NOT EXISTS `admins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `createdby` bigint(20) unsigned DEFAULT NULL,
  `groupid` bigint(20) unsigned DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `complete` timestamp NULL DEFAULT NULL,
  `subject` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `groupid` (`groupid`),
  KEY `createdby` (`createdby`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Try all means to reach people with these' AUTO_INCREMENT=676 ;

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE IF NOT EXISTS `alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `createdby` bigint(20) unsigned DEFAULT NULL,
  `groupid` bigint(20) unsigned DEFAULT NULL,
  `from` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to` enum('Users','Mods') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Mods',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `groupprogress` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'For alerts to multiple groups',
  `complete` timestamp NULL DEFAULT NULL,
  `subject` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `html` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `askclick` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether to ask them to click to confirm receipt',
  `tryhard` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Whether to mail all mods addresses too',
  PRIMARY KEY (`id`),
  KEY `groupid` (`groupid`),
  KEY `createdby` (`createdby`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Try all means to reach people with these' AUTO_INCREMENT=2798 ;

-- --------------------------------------------------------

--
-- Table structure for table `alerts_tracking`
--

CREATE TABLE IF NOT EXISTS `alerts_tracking` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `alertid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned DEFAULT NULL,
  `userid` bigint(20) unsigned DEFAULT NULL,
  `emailid` bigint(20) unsigned DEFAULT NULL,
  `type` enum('ModEmail','OwnerEmail','PushNotif','ModToolsNotif') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sent` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `responded` timestamp NULL DEFAULT NULL,
  `response` enum('Read','Clicked','Bounce','Unsubscribe') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `alertid` (`alertid`),
  KEY `emailid` (`emailid`),
  KEY `groupid` (`groupid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=70437 ;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `chatid` bigint(20) unsigned NOT NULL,
  `userid` bigint(20) unsigned NOT NULL COMMENT 'From',
  `type` enum('Default','System','ModMail','Interested','Promised','Reneged','ReportedUser') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Default',
  `reportreason` enum('Spam','Other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `refmsgid` bigint(20) unsigned DEFAULT NULL,
  `refchatid` bigint(20) unsigned DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `message` text COLLATE utf8mb4_unicode_ci,
  `platform` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Whether this was created on the platform vs email',
  `seenbyall` tinyint(1) NOT NULL DEFAULT '0',
  `reviewrequired` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether a volunteer should review before it''s passed on',
  `reviewedby` bigint(20) unsigned DEFAULT NULL COMMENT 'User id of volunteer who reviewed it',
  `reviewrejected` tinyint(1) NOT NULL DEFAULT '0',
  `spamscore` int(11) DEFAULT NULL COMMENT 'SpamAssassin score for mail replies',
  PRIMARY KEY (`id`),
  KEY `chatid` (`chatid`),
  KEY `userid` (`userid`),
  KEY `chatid_2` (`chatid`,`date`),
  KEY `msgid` (`refmsgid`),
  KEY `date` (`date`,`seenbyall`),
  KEY `reviewedby` (`reviewedby`),
  KEY `reviewrequired` (`reviewrequired`),
  KEY `refchatid` (`refchatid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=953271 ;

-- --------------------------------------------------------

--
-- Table structure for table `chat_rooms`
--

CREATE TABLE IF NOT EXISTS `chat_rooms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chattype` enum('Mod2Mod','User2Mod','User2User','') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'User2User',
  `groupid` bigint(20) unsigned DEFAULT NULL COMMENT 'Restricted to a group',
  `user1` bigint(20) unsigned DEFAULT NULL COMMENT 'For DMs',
  `user2` bigint(20) unsigned DEFAULT NULL COMMENT 'For DMs',
  `description` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user1_2` (`user1`,`user2`,`chattype`),
  KEY `groupid` (`groupid`),
  KEY `user1` (`user1`),
  KEY `user2` (`user2`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=456549 ;

-- --------------------------------------------------------

--
-- Table structure for table `chat_roster`
--

CREATE TABLE IF NOT EXISTS `chat_roster` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `chatid` bigint(20) unsigned NOT NULL,
  `userid` bigint(20) unsigned NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Online','Away','Offline','Closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Online',
  `lastmsgseen` bigint(20) unsigned DEFAULT NULL,
  `lastemailed` timestamp NULL DEFAULT NULL,
  `lastmsgemailed` bigint(20) unsigned DEFAULT NULL,
  `lastip` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chatid_2` (`chatid`,`userid`),
  KEY `chatid` (`chatid`),
  KEY `userid` (`userid`),
  KEY `date` (`date`),
  KEY `lastmsg` (`lastmsgseen`),
  KEY `lastip` (`lastip`),
  KEY `status` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=56838405 ;

-- --------------------------------------------------------

--
-- Table structure for table `communityevents`
--

CREATE TABLE IF NOT EXISTS `communityevents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned DEFAULT NULL,
  `pending` tinyint(1) NOT NULL DEFAULT '0',
  `title` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `contactname` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contactphone` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contactemail` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contacturl` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  `legacyid` bigint(20) unsigned DEFAULT NULL COMMENT 'For migration from FDv1',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `title` (`title`),
  KEY `legacyid` (`legacyid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=118128 ;

-- --------------------------------------------------------

--
-- Table structure for table `communityevents_dates`
--

CREATE TABLE IF NOT EXISTS `communityevents_dates` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `eventid` bigint(20) unsigned NOT NULL,
  `start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `end` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `start` (`start`),
  KEY `eventid` (`eventid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=104388 ;

-- --------------------------------------------------------

--
-- Table structure for table `communityevents_groups`
--

CREATE TABLE IF NOT EXISTS `communityevents_groups` (
  `eventid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  `arrival` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `eventid_2` (`eventid`,`groupid`),
  KEY `eventid` (`eventid`),
  KEY `groupid` (`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `onyahoo` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether this group is also on Yahoo Groups',
  `onhere` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether this group is available on this platform',
  `showonyahoo` tinyint(1) NOT NULL DEFAULT '1' COMMENT '(Freegle) Whether to show Yahoo links',
  `lastyahoomembersync` timestamp NULL DEFAULT NULL COMMENT 'When we last synced approved members',
  `lastyahoomessagesync` timestamp NULL DEFAULT NULL COMMENT 'When we last synced approved messages',
  `lat` decimal(10,6) DEFAULT NULL,
  `lng` decimal(10,6) DEFAULT NULL,
  `poly` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Any polygon defining core area',
  `polyofficial` longtext COLLATE utf8mb4_unicode_ci COMMENT 'If present, GAT area and poly is catchment',
  `polyapproved` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether this group area has been signed off, e.g. by GAT',
  `confirmkey` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Key used to verify some operations by email',
  `publish` tinyint(4) NOT NULL DEFAULT '1' COMMENT '(Freegle) Whether this group is visible to members',
  `listable` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Whether shows up in groups API call',
  `onmap` tinyint(4) NOT NULL DEFAULT '1' COMMENT '(Freegle) Whether to show on the map of groups',
  `licenserequired` tinyint(4) DEFAULT '1' COMMENT 'Whether a license is required for this group',
  `trial` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'For ModTools, when a trial was started',
  `licensed` date DEFAULT NULL COMMENT 'For ModTools, when a group was licensed',
  `licenseduntil` date DEFAULT NULL COMMENT 'For ModTools, when a group is licensed until',
  `membercount` int(11) NOT NULL DEFAULT '0' COMMENT 'Automatically refreshed',
  `profile` bigint(20) unsigned DEFAULT NULL,
  `cover` bigint(20) unsigned DEFAULT NULL,
  `tagline` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '(Freegle) One liner slogan for this group',
  `founded` date DEFAULT NULL,
  `lasteventsroundup` timestamp NULL DEFAULT NULL COMMENT '(Freegle) Last event roundup sent',
  `external` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Link to some other system e.g. Norfolk',
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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='The different groups that we host' AUTO_INCREMENT=340348 ;

-- --------------------------------------------------------

--
-- Table structure for table `groups_digests`
--

CREATE TABLE IF NOT EXISTS `groups_digests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `groupid` bigint(20) unsigned NOT NULL,
  `frequency` int(11) NOT NULL,
  `msgid` bigint(20) unsigned DEFAULT NULL COMMENT 'Which message we got upto when sending',
  `msgdate` timestamp(6) NULL DEFAULT NULL COMMENT 'Arrival of message we have sent upto',
  `started` timestamp NULL DEFAULT NULL,
  `ended` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupid_2` (`groupid`,`frequency`),
  KEY `groupid` (`groupid`),
  KEY `msggrpid` (`msgid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=25820941 ;

-- --------------------------------------------------------

--
-- Table structure for table `groups_facebook`
--

CREATE TABLE IF NOT EXISTS `groups_facebook` (
  `groupid` bigint(20) unsigned NOT NULL,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `authdate` timestamp NULL DEFAULT NULL,
  `msgid` bigint(20) unsigned DEFAULT NULL COMMENT 'Last message tweeted',
  `eventid` bigint(20) unsigned DEFAULT NULL COMMENT 'Last event tweeted',
  `valid` tinyint(4) NOT NULL DEFAULT '1',
  `lasterror` text COLLATE utf8mb4_unicode_ci,
  `lasterrortime` timestamp NULL DEFAULT NULL,
  `sharefrom` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT '134117207097' COMMENT 'Facebook page to republish from',
  UNIQUE KEY `groupid` (`groupid`),
  KEY `msgid` (`msgid`),
  KEY `eventid` (`eventid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `groups_facebook_shares`
--

CREATE TABLE IF NOT EXISTS `groups_facebook_shares` (
  `groupid` bigint(20) unsigned NOT NULL,
  `postid` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `groupid` (`groupid`,`postid`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `groups_facebook_toshare`
--

CREATE TABLE IF NOT EXISTS `groups_facebook_toshare` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sharefrom` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Page to share from',
  `postid` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Facebook postid',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `postid` (`postid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores central posts for sharing out to group pages' AUTO_INCREMENT=1724812 ;

-- --------------------------------------------------------

--
-- Table structure for table `groups_images`
--

CREATE TABLE IF NOT EXISTS `groups_images` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `groupid` bigint(20) unsigned DEFAULT NULL COMMENT 'id in the groups table',
  `contenttype` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `archived` tinyint(4) DEFAULT '0',
  `data` longblob,
  `identification` mediumtext COLLATE utf8mb4_unicode_ci,
  `hash` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incomingid` (`groupid`),
  KEY `hash` (`hash`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=16 COMMENT='Attachments parsed out from messages and resized' AUTO_INCREMENT=1691 ;

-- --------------------------------------------------------

--
-- Table structure for table `groups_twitter`
--

CREATE TABLE IF NOT EXISTS `groups_twitter` (
  `groupid` bigint(20) unsigned NOT NULL,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `authdate` timestamp NULL DEFAULT NULL,
  `msgid` bigint(20) unsigned DEFAULT NULL COMMENT 'Last message tweeted',
  `eventid` bigint(20) unsigned DEFAULT NULL COMMENT 'Last event tweeted',
  `valid` tinyint(4) NOT NULL DEFAULT '1',
  `lasterror` text COLLATE utf8mb4_unicode_ci,
  `lasterrortime` timestamp NULL DEFAULT NULL,
  UNIQUE KEY `groupid` (`groupid`),
  KEY `msgid` (`msgid`),
  KEY `eventid` (`eventid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE IF NOT EXISTS `items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `popularity` int(11) NOT NULL DEFAULT '0',
  `weight` decimal(10,2) DEFAULT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `suggestfromphoto` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'We can exclude from image recognition',
  `suggestfromtypeahead` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'We can exclude from typeahead',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=609615 ;

-- --------------------------------------------------------

--
-- Table structure for table `items_index`
--

CREATE TABLE IF NOT EXISTS `items_index` (
  `itemid` bigint(20) unsigned NOT NULL,
  `wordid` bigint(20) unsigned NOT NULL,
  `popularity` int(11) NOT NULL DEFAULT '0',
  `categoryid` bigint(20) unsigned DEFAULT NULL,
  UNIQUE KEY `itemid` (`itemid`,`wordid`),
  KEY `itemid_2` (`itemid`),
  KEY `wordid` (`wordid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `items_non`
--

CREATE TABLE IF NOT EXISTS `items_non` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `popularity` int(11) NOT NULL DEFAULT '1',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lastexample` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='Not considered items by us, but by image recognition' AUTO_INCREMENT=190829 ;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE IF NOT EXISTS `locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `osm_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('Road','Polygon','Line','Point','Postcode') COLLATE utf8mb4_unicode_ci NOT NULL,
  `osm_place` tinyint(1) DEFAULT '0',
  `geometry` geometry DEFAULT NULL,
  `ourgeometry` geometry DEFAULT NULL COMMENT 'geometry comes from OSM; this comes from us',
  `gridid` bigint(20) unsigned DEFAULT NULL,
  `postcodeid` bigint(20) unsigned DEFAULT NULL,
  `areaid` bigint(20) unsigned DEFAULT NULL,
  `canon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `popularity` bigint(20) unsigned DEFAULT '0',
  `osm_amenity` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'For OSM locations, whether this is an amenity',
  `osm_shop` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'For OSM locations, whether this is a shop',
  `maxdimension` decimal(10,6) DEFAULT NULL COMMENT 'GetMaxDimension on geomtry',
  `lat` decimal(10,6) DEFAULT NULL,
  `lng` decimal(10,6) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `osm_id` (`osm_id`),
  KEY `canon` (`canon`),
  KEY `areaid` (`areaid`),
  KEY `postcodeid` (`postcodeid`),
  KEY `lat` (`lat`),
  KEY `lng` (`lng`),
  KEY `gridid` (`gridid`,`osm_place`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='Location data, the bulk derived from OSM' AUTO_INCREMENT=9354290 ;

-- --------------------------------------------------------

--
-- Table structure for table `locations_excluded`
--

CREATE TABLE IF NOT EXISTS `locations_excluded` (
  `locationid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  `userid` bigint(20) unsigned DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `locationid_2` (`locationid`,`groupid`),
  KEY `locationid` (`locationid`),
  KEY `groupid` (`groupid`),
  KEY `by` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='Stops locations being suggested on a group';

-- --------------------------------------------------------

--
-- Table structure for table `locations_grids`
--

CREATE TABLE IF NOT EXISTS `locations_grids` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `swlat` decimal(10,6) NOT NULL,
  `swlng` decimal(10,6) NOT NULL,
  `nelat` decimal(10,6) NOT NULL,
  `nelng` decimal(10,6) NOT NULL,
  `box` geometry NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `swlat` (`swlat`,`swlng`,`nelat`,`nelng`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='Used to map lat/lng to gridid for location searches' AUTO_INCREMENT=480067 ;

-- --------------------------------------------------------

--
-- Table structure for table `locations_grids_touches`
--

CREATE TABLE IF NOT EXISTS `locations_grids_touches` (
  `gridid` bigint(20) unsigned NOT NULL,
  `touches` bigint(20) unsigned NOT NULL,
  UNIQUE KEY `gridid` (`gridid`,`touches`),
  KEY `touches` (`touches`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='A record of which grid squares touch others';

-- --------------------------------------------------------

--
-- Table structure for table `locations_spatial`
--

CREATE TABLE IF NOT EXISTS `locations_spatial` (
  `locationid` bigint(20) unsigned NOT NULL,
  `geometry` geometry NOT NULL,
  UNIQUE KEY `locationid` (`locationid`),
  SPATIAL KEY `geometry` (`geometry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locations_test`
--

CREATE TABLE IF NOT EXISTS `locations_test` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `osm_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('Road','Polygon','Line','Point','Postcode') COLLATE utf8mb4_unicode_ci NOT NULL,
  `osm_place` tinyint(1) DEFAULT '0',
  `geometry` geometry DEFAULT NULL,
  `ourgeometry` geometry DEFAULT NULL COMMENT 'geometry comes from OSM; this comes from us',
  `gridid` bigint(20) unsigned DEFAULT NULL,
  `postcodeid` bigint(20) unsigned DEFAULT NULL,
  `areaid` bigint(20) unsigned DEFAULT NULL,
  `canon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `popularity` bigint(20) unsigned DEFAULT '0',
  `osm_amenity` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'For OSM locations, whether this is an amenity',
  `osm_shop` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'For OSM locations, whether this is a shop',
  `maxdimension` decimal(10,6) DEFAULT NULL COMMENT 'GetMaxDimension on geomtry',
  `lat` decimal(10,6) DEFAULT NULL,
  `lng` decimal(10,6) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `namelen` int(11) NOT NULL,
  `canonlen` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `osm_id` (`osm_id`),
  KEY `canon` (`canon`),
  KEY `areaid` (`areaid`),
  KEY `postcodeid` (`postcodeid`),
  KEY `lat` (`lat`),
  KEY `lng` (`lng`),
  KEY `gridid` (`gridid`,`osm_place`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='Location data, the bulk derived from OSM' AUTO_INCREMENT=9034257 ;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE IF NOT EXISTS `logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Machine assumed set to GMT',
  `byuser` bigint(20) unsigned DEFAULT NULL COMMENT 'User responsible for action, if any',
  `type` enum('Group','Message','User','Plugin','Config','StdMsg','Location','BulkOp') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtype` enum('Created','Deleted','Received','Sent','Failure','ClassifiedSpam','Joined','Left','Approved','Rejected','YahooDeliveryType','YahooPostingStatus','NotSpam','Login','Hold','Release','Edit','RoleChange','Merged','Split','Replied','Mailed','Applied','Suspect','Licensed','LicensePurchase','YahooApplied','YahooConfirmed','YahooJoined','MailOff','EventsOff','NewslettersOff','RelevantOff','Logout') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `groupid` bigint(20) unsigned DEFAULT NULL COMMENT 'Any group this log is for',
  `user` bigint(20) unsigned DEFAULT NULL COMMENT 'Any user that this log is about',
  `msgid` bigint(20) unsigned DEFAULT NULL COMMENT 'id in the messages table',
  `configid` bigint(20) unsigned DEFAULT NULL COMMENT 'id in the mod_configs table',
  `stdmsgid` bigint(20) unsigned DEFAULT NULL COMMENT 'Any stdmsg for this log',
  `bulkopid` bigint(20) unsigned DEFAULT NULL,
  `text` mediumtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `group` (`groupid`),
  KEY `type` (`type`,`subtype`),
  KEY `timestamp` (`timestamp`),
  KEY `byuser` (`byuser`),
  KEY `user` (`user`),
  KEY `msgid` (`msgid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='Logs.  Not guaranteed against loss' AUTO_INCREMENT=67751147 ;

-- --------------------------------------------------------

--
-- Table structure for table `logs_api`
--

CREATE TABLE IF NOT EXISTS `logs_api` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userid` bigint(20) DEFAULT NULL,
  `ip` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `response` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `session` (`session`),
  KEY `date` (`date`),
  KEY `userid` (`userid`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC KEY_BLOCK_SIZE=8 COMMENT='Log of all API requests and responses' AUTO_INCREMENT=11868920 ;

-- --------------------------------------------------------

--
-- Table structure for table `logs_email`
--

CREATE TABLE IF NOT EXISTS `logs_email` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `from` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uniqueid` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `userid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `logs_errors`
--

CREATE TABLE IF NOT EXISTS `logs_errors` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` enum('Exception') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `userid` bigint(20) DEFAULT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Errors from client' AUTO_INCREMENT=76205 ;

-- --------------------------------------------------------

--
-- Table structure for table `logs_events`
--

CREATE TABLE IF NOT EXISTS `logs_events` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned DEFAULT NULL,
  `sessionid` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  `clienttimestamp` timestamp(3) NOT NULL DEFAULT '0000-00-00 00:00:00.000',
  `posx` int(11) DEFAULT NULL,
  `posy` int(11) DEFAULT NULL,
  `viewx` int(11) DEFAULT NULL,
  `viewy` int(11) DEFAULT NULL,
  `data` mediumtext COLLATE utf8mb4_unicode_ci,
  `datasameas` bigint(20) unsigned DEFAULT NULL COMMENT 'Allows use to reuse data stored in table once for other rows',
  `datahash` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `route` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`,`timestamp`),
  KEY `sessionid` (`sessionid`),
  KEY `datasameas` (`datasameas`),
  KEY `datahash` (`datahash`,`datasameas`),
  KEY `ip` (`ip`),
  KEY `timestamp` (`timestamp`),
  KEY `sessionid_2` (`sessionid`,`userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED AUTO_INCREMENT=12939754 ;

-- --------------------------------------------------------

--
-- Table structure for table `logs_profile`
--

CREATE TABLE IF NOT EXISTS `logs_profile` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `caller` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `callee` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ct` bigint(20) unsigned NOT NULL DEFAULT '0',
  `wt` bigint(20) unsigned NOT NULL DEFAULT '0',
  `cpu` bigint(20) unsigned NOT NULL,
  `mu` bigint(20) unsigned NOT NULL,
  `pmu` bigint(20) unsigned NOT NULL,
  `alloc` bigint(20) unsigned NOT NULL,
  `free` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `caller` (`caller`,`callee`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `logs_sql`
--

CREATE TABLE IF NOT EXISTS `logs_sql` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `duration` decimal(15,10) unsigned DEFAULT '0.0000000000' COMMENT 'seconds',
  `userid` bigint(20) unsigned DEFAULT NULL,
  `session` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `response` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'rc:lastInsertId',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `session` (`session`),
  KEY `date` (`date`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8 COMMENT='Log of modification SQL operations' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `memberships`
--

CREATE TABLE IF NOT EXISTS `memberships` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  `role` enum('Member','Moderator','Owner') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Member',
  `collection` enum('Approved','Pending','Banned') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Approved',
  `configid` bigint(20) unsigned DEFAULT NULL COMMENT 'Configuration used to moderate this group if a moderator',
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `settings` mediumtext COLLATE utf8mb4_unicode_ci COMMENT 'Other group settings, e.g. for moderators',
  `syncdelete` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Used during member sync',
  `heldby` bigint(20) unsigned DEFAULT NULL,
  `emailfrequency` int(11) NOT NULL DEFAULT '24' COMMENT 'In hours; -1 immediately, 0 never',
  `eventsallowed` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `userid_groupid` (`userid`,`groupid`),
  KEY `groupid_2` (`groupid`,`role`),
  KEY `userid` (`userid`,`role`),
  KEY `role` (`role`),
  KEY `configid` (`configid`),
  KEY `groupid` (`groupid`,`collection`),
  KEY `heldby` (`heldby`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='Which groups users are members of' AUTO_INCREMENT=34375332 ;

-- --------------------------------------------------------

--
-- Table structure for table `memberships_history`
--

CREATE TABLE IF NOT EXISTS `memberships_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  `collection` enum('Approved','Pending','Banned') COLLATE utf8mb4_unicode_ci NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `groupid` (`groupid`),
  KEY `date` (`added`),
  KEY `userid` (`userid`,`groupid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='Used to spot multijoiners' AUTO_INCREMENT=29942022 ;

-- --------------------------------------------------------

--
-- Table structure for table `memberships_yahoo`
--

CREATE TABLE IF NOT EXISTS `memberships_yahoo` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `membershipid` bigint(20) unsigned NOT NULL,
  `role` enum('Member','Moderator','Owner') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Member',
  `collection` enum('Approved','Pending','Banned') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Approved',
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `emailid` bigint(20) unsigned NOT NULL COMMENT 'Which of their emails they use on this group',
  `yahooAlias` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `yahooPostingStatus` enum('MODERATED','DEFAULT','PROHIBITED','UNMODERATED') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Yahoo mod status if applicable',
  `yahooDeliveryType` enum('DIGEST','NONE','SINGLE','ANNOUNCEMENT') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Yahoo delivery settings if applicable',
  `syncdelete` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Used during member sync',
  `yahooapprove` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'For Yahoo groups, email to approve member if known and relevant',
  `yahooreject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'For Yahoo groups, email to reject member if known and relevant',
  `joincomment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Any joining comment for this member',
  PRIMARY KEY (`id`),
  UNIQUE KEY `membershipid_2` (`membershipid`,`emailid`),
  KEY `role` (`role`),
  KEY `emailid` (`emailid`),
  KEY `groupid` (`collection`),
  KEY `yahooPostingStatus` (`yahooPostingStatus`),
  KEY `yahooDeliveryType` (`yahooDeliveryType`),
  KEY `yahooAlias` (`yahooAlias`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='Which groups users are members of' AUTO_INCREMENT=17362374 ;

-- --------------------------------------------------------

--
-- Table structure for table `memberships_yahoo_dump`
--

CREATE TABLE IF NOT EXISTS `memberships_yahoo_dump` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `groupid` bigint(20) unsigned NOT NULL,
  `members` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastupdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lastprocessed` timestamp NULL DEFAULT NULL COMMENT 'When this was last processed into the main tables',
  `synctime` timestamp NULL DEFAULT NULL COMMENT 'Time on client when sync started',
  `backgroundok` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupid` (`groupid`),
  KEY `lastprocessed` (`lastprocessed`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=16 COMMENT='Copy of last member sync from Yahoo' AUTO_INCREMENT=168665 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE IF NOT EXISTS `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique iD',
  `arrival` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When this message arrived at our server',
  `date` timestamp NULL DEFAULT NULL COMMENT 'When this message was created, e.g. Date header',
  `deleted` timestamp NULL DEFAULT NULL COMMENT 'When this message was deleted',
  `heldby` bigint(20) unsigned DEFAULT NULL COMMENT 'If this message is held by a moderator',
  `source` enum('Yahoo Approved','Yahoo Pending','Yahoo System','Platform','Email') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Source of incoming message',
  `sourceheader` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Any source header, e.g. X-Freegle-Source',
  `fromip` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP we think this message came from',
  `fromcountry` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'fromip geocoded to country',
  `message` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The unparsed message',
  `fromuser` bigint(20) unsigned DEFAULT NULL,
  `envelopefrom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fromname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fromaddr` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `envelopeto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `replyto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `suggestedsubject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('Offer','Taken','Wanted','Received','Admin','Other') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'For reuse groups, the message categorisation',
  `messageid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tnpostid` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'If this message came from Trash Nothing, the unique post ID',
  `textbody` longtext COLLATE utf8mb4_unicode_ci,
  `htmlbody` longtext COLLATE utf8mb4_unicode_ci,
  `retrycount` int(11) NOT NULL DEFAULT '0' COMMENT 'We might fail to route, and later retry',
  `retrylastfailure` timestamp NULL DEFAULT NULL,
  `spamtype` enum('CountryBlocked','IPUsedForDifferentUsers','IPUsedForDifferentGroups','SubjectUsedForDifferentGroups','SpamAssassin','NotSpam') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spamreason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Why we think this message may be spam',
  `lat` decimal(10,6) DEFAULT NULL,
  `lng` decimal(10,6) DEFAULT NULL,
  `locationid` bigint(20) unsigned DEFAULT NULL,
  `editedby` bigint(20) unsigned DEFAULT NULL,
  `editedat` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `message-id` (`messageid`) KEY_BLOCK_SIZE=16,
  KEY `envelopefrom` (`envelopefrom`),
  KEY `envelopeto` (`envelopeto`),
  KEY `retrylastfailure` (`retrylastfailure`),
  KEY `fromup` (`fromip`),
  KEY `tnpostid` (`tnpostid`),
  KEY `type` (`type`),
  KEY `sourceheader` (`sourceheader`),
  KEY `arrival` (`arrival`,`sourceheader`),
  KEY `arrival_2` (`arrival`,`fromaddr`),
  KEY `arrival_3` (`arrival`),
  KEY `fromaddr` (`fromaddr`,`subject`),
  KEY `date` (`date`),
  KEY `subject` (`subject`),
  KEY `fromuser` (`fromuser`),
  KEY `deleted` (`deleted`),
  KEY `heldby` (`heldby`),
  KEY `lat` (`lat`) KEY_BLOCK_SIZE=16,
  KEY `lng` (`lng`) KEY_BLOCK_SIZE=16,
  KEY `locationid` (`locationid`) KEY_BLOCK_SIZE=16
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8 COMMENT='All our messages' AUTO_INCREMENT=8920776 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages_attachments`
--

CREATE TABLE IF NOT EXISTS `messages_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `msgid` bigint(20) unsigned DEFAULT NULL COMMENT 'id in the messages table',
  `contenttype` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `archived` tinyint(4) DEFAULT '0',
  `data` longblob,
  `identification` mediumtext COLLATE utf8mb4_unicode_ci,
  `hash` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incomingid` (`msgid`),
  KEY `hash` (`hash`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=16 COMMENT='Attachments parsed out from messages and resized' AUTO_INCREMENT=2339553 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages_attachments_items`
--

CREATE TABLE IF NOT EXISTS `messages_attachments_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attid` bigint(20) unsigned NOT NULL,
  `itemid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `msgid` (`attid`),
  KEY `itemid` (`itemid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=87485 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages_drafts`
--

CREATE TABLE IF NOT EXISTS `messages_drafts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `msgid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `session` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `userid` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `msgid` (`msgid`),
  KEY `userid` (`userid`),
  KEY `session` (`session`),
  KEY `groupid` (`groupid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=69942 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages_groups`
--

CREATE TABLE IF NOT EXISTS `messages_groups` (
  `msgid` bigint(20) unsigned NOT NULL COMMENT 'id in the messages table',
  `groupid` bigint(20) unsigned NOT NULL,
  `collection` enum('Incoming','Pending','Approved','Spam','QueuedYahooUser','Rejected') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arrival` timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `autoreposts` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'How many times this message has been auto-reposted',
  `lastautopostwarning` timestamp NULL DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `senttoyahoo` tinyint(1) NOT NULL DEFAULT '0',
  `yahoopendingid` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'For Yahoo messages, pending id if relevant',
  `yahooapprovedid` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'For Yahoo messages, approved id if relevant',
  `yahooapprove` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'For Yahoo messages, email to trigger approve if relevant',
  `yahooreject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'For Yahoo messages, email to trigger reject if relevant',
  `approvedby` bigint(20) unsigned DEFAULT NULL COMMENT 'Mod who approved this post (if any)',
  `approvedat` timestamp NULL DEFAULT NULL,
  `rejectedat` timestamp NULL DEFAULT NULL,
  `msgtype` enum('Offer','Taken','Wanted','Received','Admin','Other') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'In here for performance optimisation',
  UNIQUE KEY `msgid` (`msgid`,`groupid`),
  UNIQUE KEY `groupid_3` (`groupid`,`yahooapprovedid`),
  UNIQUE KEY `groupid_2` (`groupid`,`yahoopendingid`),
  KEY `messageid` (`msgid`,`groupid`,`collection`,`arrival`),
  KEY `collection` (`collection`),
  KEY `approvedby` (`approvedby`),
  KEY `groupid` (`groupid`,`collection`,`deleted`,`arrival`),
  KEY `arrival` (`arrival`,`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='The state of the message on each group';

-- --------------------------------------------------------

--
-- Table structure for table `messages_history`
--

CREATE TABLE IF NOT EXISTS `messages_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique iD',
  `msgid` bigint(20) unsigned DEFAULT NULL COMMENT 'id in the messages table',
  `arrival` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When this message arrived at our server',
  `source` enum('Yahoo Approved','Yahoo Pending','Yahoo System','Platform') CHARACTER SET latin1 DEFAULT NULL COMMENT 'Source of incoming message',
  `fromip` varchar(40) CHARACTER SET latin1 DEFAULT NULL COMMENT 'IP we think this message came from',
  `fromhost` varchar(80) CHARACTER SET latin1 DEFAULT NULL COMMENT 'Hostname for fromip if resolvable, or NULL',
  `fromuser` bigint(20) unsigned DEFAULT NULL,
  `envelopefrom` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `fromname` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `fromaddr` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `envelopeto` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `groupid` bigint(20) unsigned DEFAULT NULL COMMENT 'Destination group, if identified',
  `subject` varchar(1024) CHARACTER SET latin1 DEFAULT NULL,
  `prunedsubject` varchar(1024) CHARACTER SET latin1 DEFAULT NULL COMMENT 'For spam detection',
  `messageid` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='Message arrivals, used for spam checking' AUTO_INCREMENT=7729482 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages_index`
--

CREATE TABLE IF NOT EXISTS `messages_index` (
  `msgid` bigint(20) unsigned NOT NULL,
  `wordid` bigint(20) unsigned NOT NULL,
  `arrival` bigint(20) NOT NULL COMMENT 'We prioritise recent messages',
  `groupid` bigint(20) unsigned DEFAULT NULL,
  UNIQUE KEY `msgid` (`msgid`,`wordid`),
  KEY `arrival` (`arrival`),
  KEY `groupid` (`groupid`),
  KEY `wordid` (`wordid`,`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='For indexing messages for search keywords';

-- --------------------------------------------------------

--
-- Table structure for table `messages_items`
--

CREATE TABLE IF NOT EXISTS `messages_items` (
  `msgid` bigint(20) unsigned NOT NULL,
  `itemid` bigint(20) unsigned NOT NULL,
  UNIQUE KEY `msgid` (`msgid`,`itemid`),
  KEY `itemid` (`itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Where known, items for our message';

-- --------------------------------------------------------

--
-- Table structure for table `messages_outcomes`
--

CREATE TABLE IF NOT EXISTS `messages_outcomes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `msgid` bigint(20) unsigned NOT NULL,
  `outcome` enum('Taken','Received','Withdrawn') COLLATE utf8mb4_unicode_ci NOT NULL,
  `happiness` enum('Happy','Fine','Unhappy') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `userid` bigint(20) unsigned DEFAULT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `msgid` (`msgid`),
  KEY `timestamp` (`timestamp`),
  KEY `timestamp_2` (`timestamp`,`outcome`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=41004 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages_postings`
--

CREATE TABLE IF NOT EXISTS `messages_postings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `msgid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `msgid` (`msgid`),
  KEY `groupid` (`groupid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=31119 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages_promises`
--

CREATE TABLE IF NOT EXISTS `messages_promises` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `msgid` bigint(20) unsigned NOT NULL,
  `userid` bigint(20) unsigned NOT NULL,
  `promisedat` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `msgid_2` (`msgid`,`userid`),
  KEY `msgid` (`msgid`),
  KEY `userid` (`userid`),
  KEY `promisedat` (`promisedat`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=7775 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages_related`
--

CREATE TABLE IF NOT EXISTS `messages_related` (
  `id1` bigint(20) unsigned NOT NULL,
  `id2` bigint(20) unsigned NOT NULL,
  UNIQUE KEY `id1_2` (`id1`,`id2`),
  KEY `id1` (`id1`),
  KEY `id2` (`id2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='Messages which are related to each other';

-- --------------------------------------------------------

--
-- Table structure for table `messages_spamham`
--

CREATE TABLE IF NOT EXISTS `messages_spamham` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `msgid` bigint(20) unsigned NOT NULL,
  `spamham` enum('Spam','Ham') COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `msgid` (`msgid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='User feedback on messages ' AUTO_INCREMENT=25311 ;

-- --------------------------------------------------------

--
-- Table structure for table `mod_bulkops`
--

CREATE TABLE IF NOT EXISTS `mod_bulkops` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `configid` bigint(20) unsigned DEFAULT NULL,
  `set` enum('Members') COLLATE utf8mb4_unicode_ci NOT NULL,
  `criterion` enum('Bouncing','BouncingFor','WebOnly','All') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `runevery` int(11) NOT NULL DEFAULT '168' COMMENT 'In hours',
  `action` enum('Unbounce','Remove','ToGroup','ToSpecialNotices') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bouncingfor` int(11) NOT NULL DEFAULT '90',
  UNIQUE KEY `uniqueid` (`id`),
  KEY `configid` (`configid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=25723 ;

-- --------------------------------------------------------

--
-- Table structure for table `mod_bulkops_run`
--

CREATE TABLE IF NOT EXISTS `mod_bulkops_run` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bulkopid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  `runstarted` timestamp NULL DEFAULT NULL,
  `runfinished` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bulkopid_2` (`bulkopid`,`groupid`),
  KEY `bulkopid` (`bulkopid`),
  KEY `groupid` (`groupid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=5017296 ;

-- --------------------------------------------------------

--
-- Table structure for table `mod_configs`
--

CREATE TABLE IF NOT EXISTS `mod_configs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique ID of config',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Name of config set',
  `createdby` bigint(20) unsigned DEFAULT NULL COMMENT 'Moderator ID who created it',
  `fromname` enum('My name','Groupname Moderator') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'My name',
  `ccrejectto` enum('Nobody','Me','Specific') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Nobody',
  `ccrejectaddr` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ccfollowupto` enum('Nobody','Me','Specific') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Nobody',
  `ccfollowupaddr` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ccrejmembto` enum('Nobody','Me','Specific') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Nobody',
  `ccrejmembaddr` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ccfollmembto` enum('Nobody','Me','Specific') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Nobody',
  `ccfollmembaddr` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `protected` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Protect from edit?',
  `messageorder` mediumtext COLLATE utf8mb4_unicode_ci COMMENT 'CSL of ids of standard messages in order in which they should appear',
  `network` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `coloursubj` tinyint(1) NOT NULL DEFAULT '1',
  `subjreg` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '^(OFFER|WANTED|TAKEN|RECEIVED) *[\\:-].*\\(.*\\)',
  `subjlen` int(11) NOT NULL DEFAULT '68',
  `default` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Default configs are always visible',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `uniqueid` (`id`,`createdby`),
  KEY `createdby` (`createdby`),
  KEY `default` (`default`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='Configurations for use by moderators' AUTO_INCREMENT=50845 ;

-- --------------------------------------------------------

--
-- Table structure for table `mod_stdmsgs`
--

CREATE TABLE IF NOT EXISTS `mod_stdmsgs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique ID of standard message',
  `configid` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Title of standard message',
  `action` enum('Approve','Reject','Leave','Approve Member','Reject Member','Leave Member','Leave Approved Message','Delete Approved Message','Leave Approved Member','Delete Approved Member','Edit') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Reject' COMMENT 'What action to take',
  `subjpref` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Subject prefix',
  `subjsuff` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Subject suffix',
  `body` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `rarelyused` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Rarely used messages may be hidden in the UI',
  `autosend` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Send the message immediately rather than wait for user',
  `newmodstatus` enum('UNCHANGED','MODERATED','DEFAULT','PROHIBITED','UNMODERATED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UNCHANGED' COMMENT 'Yahoo mod status afterwards',
  `newdelstatus` enum('UNCHANGED','DIGEST','NONE','SINGLE','ANNOUNCEMENT') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UNCHANGED' COMMENT 'Yahoo delivery status afterwards',
  `edittext` enum('Unchanged','Correct Case') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Unchanged',
  `insert` enum('Top','Bottom') COLLATE utf8mb4_unicode_ci DEFAULT 'Top',
  UNIQUE KEY `id` (`id`),
  KEY `configid` (`configid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=164768 ;

-- --------------------------------------------------------

--
-- Table structure for table `newsletters`
--

CREATE TABLE IF NOT EXISTS `newsletters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `groupid` bigint(20) unsigned DEFAULT NULL,
  `subject` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `textbody` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'For people who don''t read HTML',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed` timestamp NULL DEFAULT NULL,
  `uptouser` bigint(20) unsigned DEFAULT NULL COMMENT 'User id we are upto, roughly',
  PRIMARY KEY (`id`),
  KEY `groupid` (`groupid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Table structure for table `newsletters_articles`
--

CREATE TABLE IF NOT EXISTS `newsletters_articles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `newsletterid` bigint(20) unsigned NOT NULL,
  `type` enum('Header','Article') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Article',
  `position` int(11) NOT NULL,
  `html` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `photoid` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mailid` (`newsletterid`),
  KEY `photo` (`photoid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=375 ;

-- --------------------------------------------------------

--
-- Table structure for table `newsletters_images`
--

CREATE TABLE IF NOT EXISTS `newsletters_images` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `articleid` bigint(20) unsigned DEFAULT NULL COMMENT 'id in the groups table',
  `contenttype` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `archived` tinyint(4) DEFAULT '0',
  `data` longblob,
  `identification` mediumtext COLLATE utf8mb4_unicode_ci,
  `hash` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incomingid` (`articleid`),
  KEY `hash` (`hash`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=16 COMMENT='Attachments parsed out from messages and resized' AUTO_INCREMENT=145 ;

-- --------------------------------------------------------

--
-- Table structure for table `partners_keys`
--

CREATE TABLE IF NOT EXISTS `partners_keys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `partner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='For site-to-site integration' AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `plugin`
--

CREATE TABLE IF NOT EXISTS `plugin` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `groupid` bigint(20) unsigned NOT NULL,
  `data` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `groupid` (`groupid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='Outstanding work required to be performed by the plugin' AUTO_INCREMENT=1408983 ;

-- --------------------------------------------------------

--
-- Table structure for table `prerender`
--

CREATE TABLE IF NOT EXISTS `prerender` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `html` text COLLATE utf8mb4_unicode_ci,
  `retrieved` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `timeout` int(11) NOT NULL DEFAULT '60' COMMENT 'In minutes',
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Saved copies of HTML for logged out view of pages' AUTO_INCREMENT=394033 ;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned DEFAULT NULL,
  `series` bigint(20) unsigned NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lastactive` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `id_3` (`id`,`series`,`token`),
  KEY `date` (`date`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=2449290 ;

-- --------------------------------------------------------

--
-- Table structure for table `spam_countries`
--

CREATE TABLE IF NOT EXISTS `spam_countries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `country` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A country we want to block',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `country` (`country`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `spam_keywords`
--

CREATE TABLE IF NOT EXISTS `spam_keywords` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `word` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `exclude` text COLLATE utf8mb4_unicode_ci,
  `action` enum('Review','Spam') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Review',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Keywords often used by spammers' AUTO_INCREMENT=30 ;

-- --------------------------------------------------------

--
-- Table structure for table `spam_users`
--

CREATE TABLE IF NOT EXISTS `spam_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned NOT NULL,
  `byuserid` bigint(20) unsigned DEFAULT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `collection` enum('Spammer','Whitelisted','PendingAdd','PendingRemove') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Spammer',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userid` (`userid`),
  KEY `byuserid` (`byuserid`),
  KEY `added` (`added`),
  KEY `collection` (`collection`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='Users who are spammers or trusted' AUTO_INCREMENT=19521 ;

-- --------------------------------------------------------

--
-- Table structure for table `spam_whitelist_ips`
--

CREATE TABLE IF NOT EXISTS `spam_whitelist_ips` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ip` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `ip` (`ip`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='Whitelisted IP addresses' AUTO_INCREMENT=3043 ;

-- --------------------------------------------------------

--
-- Table structure for table `spam_whitelist_subjects`
--

CREATE TABLE IF NOT EXISTS `spam_whitelist_subjects` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `ip` (`subject`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='Whitelisted subjects' AUTO_INCREMENT=7771 ;

-- --------------------------------------------------------

--
-- Table structure for table `stats`
--

CREATE TABLE IF NOT EXISTS `stats` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  `type` enum('ApprovedMessageCount','SpamMessageCount','MessageBreakdown','SpamMemberCount','PostMethodBreakdown','YahooDeliveryBreakdown','YahooPostingBreakdown','ApprovedMemberCount','SupportQueries','Happy','Fine','Unhappy','') COLLATE utf8mb4_unicode_ci NOT NULL,
  `count` bigint(20) unsigned DEFAULT NULL,
  `breakdown` mediumtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`date`,`groupid`,`type`),
  KEY `groupid` (`groupid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='Stats information used for dashboard' AUTO_INCREMENT=4176718 ;

-- --------------------------------------------------------

--
-- Table structure for table `supporters`
--

CREATE TABLE IF NOT EXISTS `supporters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('Wowzer','Front Page','Supporter','Buyer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `voucher` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Voucher code',
  `vouchercount` int(11) NOT NULL DEFAULT '1' COMMENT 'Number of licenses in this voucher',
  `voucheryears` int(11) NOT NULL DEFAULT '1' COMMENT 'Number of years voucher licenses are valid for',
  `anonymous` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`,`type`,`email`),
  KEY `display` (`display`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='People who have supported this site' AUTO_INCREMENT=133569 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `yahooUserId` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Unique ID of user on Yahoo if known',
  `firstname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fullname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `systemrole` set('User','Moderator','Support','Admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'User' COMMENT 'System-wide roles',
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastaccess` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `settings` mediumtext COLLATE utf8mb4_unicode_ci COMMENT 'JSON-encoded settings',
  `gotrealemail` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Until migrated, whether polled FD/TN to get real email',
  `suspectcount` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Number of reports of this user as suspicious',
  `suspectreason` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Last reason for suspecting this user',
  `yahooid` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Any known YahooID for this user',
  `licenses` int(11) NOT NULL DEFAULT '0' COMMENT 'Any licenses not added to groups',
  `newslettersallowed` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Central mails',
  `relevantallowed` tinyint(4) NOT NULL DEFAULT '1',
  `onholidaytill` date DEFAULT NULL,
  `ripaconsent` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether we have consent for humans to vet their messages',
  `publishconsent` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Can we republish posts to non-members?',
  `lastlocation` bigint(20) unsigned DEFAULT NULL,
  `lastrelevantcheck` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `yahooUserId` (`yahooUserId`),
  UNIQUE KEY `yahooid` (`yahooid`),
  KEY `systemrole` (`systemrole`),
  KEY `added` (`added`,`lastaccess`),
  KEY `fullname` (`fullname`),
  KEY `firstname` (`firstname`),
  KEY `lastname` (`lastname`),
  KEY `firstname_2` (`firstname`,`lastname`),
  KEY `gotrealemail` (`gotrealemail`),
  KEY `suspectcount` (`suspectcount`),
  KEY `suspectcount_2` (`suspectcount`),
  KEY `lastlocation` (`lastlocation`),
  KEY `lastrelevantcheck` (`lastrelevantcheck`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=32077035 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_banned`
--

CREATE TABLE IF NOT EXISTS `users_banned` (
  `userid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `byuser` bigint(20) unsigned DEFAULT NULL,
  UNIQUE KEY `userid_2` (`userid`,`groupid`),
  KEY `groupid` (`groupid`),
  KEY `userid` (`userid`),
  KEY `date` (`date`),
  KEY `byuser` (`byuser`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `users_comments`
--

CREATE TABLE IF NOT EXISTS `users_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  `byuserid` bigint(20) unsigned DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user1` mediumtext COLLATE utf8mb4_unicode_ci,
  `user2` mediumtext COLLATE utf8mb4_unicode_ci,
  `user3` mediumtext COLLATE utf8mb4_unicode_ci,
  `user4` mediumtext COLLATE utf8mb4_unicode_ci,
  `user5` mediumtext COLLATE utf8mb4_unicode_ci,
  `user6` mediumtext COLLATE utf8mb4_unicode_ci,
  `user7` mediumtext COLLATE utf8mb4_unicode_ci,
  `user8` mediumtext COLLATE utf8mb4_unicode_ci,
  `user9` mediumtext COLLATE utf8mb4_unicode_ci,
  `user10` mediumtext COLLATE utf8mb4_unicode_ci,
  `user11` mediumtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `groupid` (`groupid`),
  KEY `modid` (`byuserid`),
  KEY `userid` (`userid`,`groupid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='Comments from mods on members' AUTO_INCREMENT=106839 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_emails`
--

CREATE TABLE IF NOT EXISTS `users_emails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned DEFAULT NULL COMMENT 'Unique ID in users table',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The email',
  `preferred` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Preferred email for this user',
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `validatekey` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `validated` timestamp NULL DEFAULT NULL,
  `canon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'For spotting duplicates',
  `backwards` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Allows domain search',
  `bounced` timestamp NULL DEFAULT NULL,
  `viewed` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `validatekey` (`validatekey`),
  KEY `userid` (`userid`),
  KEY `validated` (`validated`),
  KEY `canon` (`canon`),
  KEY `backwards` (`backwards`),
  KEY `bounced` (`bounced`),
  KEY `viewed` (`viewed`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=113992683 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_logins`
--

CREATE TABLE IF NOT EXISTS `users_logins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned NOT NULL COMMENT 'Unique ID in users table',
  `type` enum('Yahoo','Facebook','Google','Native','Link') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Unique identifier for login',
  `credentials` text COLLATE utf8mb4_unicode_ci,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastaccess` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `email` (`uid`,`type`),
  UNIQUE KEY `userid_3` (`userid`,`type`,`uid`),
  KEY `userid` (`userid`),
  KEY `validated` (`lastaccess`),
  KEY `userid_2` (`userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=478638 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_push_notifications`
--

CREATE TABLE IF NOT EXISTS `users_push_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` enum('Google','Firefox','Test') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Google',
  `lastsent` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `subscription` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription` (`subscription`),
  KEY `userid` (`userid`,`type`),
  KEY `type` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='For sending push notifications to users' AUTO_INCREMENT=206766 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_searches`
--

CREATE TABLE IF NOT EXISTS `users_searches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `term` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `maxmsg` bigint(20) unsigned DEFAULT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  `locationid` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userid_3` (`userid`,`term`),
  KEY `userid` (`userid`),
  KEY `userid_2` (`userid`,`date`),
  KEY `maxmsg` (`maxmsg`),
  KEY `locationid` (`locationid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=238140 ;

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE IF NOT EXISTS `vouchers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `voucher` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `used` timestamp NULL DEFAULT NULL,
  `groupid` bigint(20) unsigned DEFAULT NULL COMMENT 'Group that a voucher was used on',
  `userid` bigint(20) unsigned DEFAULT NULL COMMENT 'User who redeemed a voucher',
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucher` (`voucher`),
  KEY `groupid` (`groupid`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='For licensing groups' AUTO_INCREMENT=2392 ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_freeglegroups_unreached`
--
CREATE TABLE IF NOT EXISTS `vw_freeglegroups_unreached` (
   `id` bigint(20) unsigned
  ,`nameshort` varchar(80)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_manyemails`
--
CREATE TABLE IF NOT EXISTS `vw_manyemails` (
   `id` bigint(20) unsigned
  ,`fullname` varchar(255)
  ,`email` varchar(255)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_membersyncpending`
--
CREATE TABLE IF NOT EXISTS `vw_membersyncpending` (
   `id` bigint(20) unsigned
  ,`groupid` bigint(20) unsigned
  ,`members` longtext
  ,`lastupdated` timestamp
  ,`lastprocessed` timestamp
  ,`synctime` timestamp
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_multiemails`
--
CREATE TABLE IF NOT EXISTS `vw_multiemails` (
   `id` bigint(20) unsigned
  ,`fullname` varchar(255)
  ,`count` bigint(21)
  ,`GROUP_CONCAT(email SEPARATOR ', ')` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_recentgroupaccess`
--
CREATE TABLE IF NOT EXISTS `vw_recentgroupaccess` (
   `lastaccess` timestamp
  ,`nameshort` varchar(80)
  ,`id` bigint(20) unsigned
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_recentlogins`
--
CREATE TABLE IF NOT EXISTS `vw_recentlogins` (
   `timestamp` timestamp
  ,`id` bigint(20) unsigned
  ,`fullname` varchar(255)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_recentposts`
--
CREATE TABLE IF NOT EXISTS `vw_recentposts` (
   `id` bigint(20) unsigned
  ,`date` timestamp
  ,`fromaddr` varchar(255)
  ,`subject` varchar(255)
);
-- --------------------------------------------------------

--
-- Table structure for table `words`
--

CREATE TABLE IF NOT EXISTS `words` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `word` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firstthree` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `soundex` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `popularity` bigint(20) NOT NULL DEFAULT '0' COMMENT 'Negative as DESC index not supported',
  PRIMARY KEY (`id`),
  UNIQUE KEY `word_2` (`word`),
  KEY `popularity` (`popularity`),
  KEY `word` (`word`,`popularity`),
  KEY `soundex` (`soundex`,`popularity`),
  KEY `firstthree` (`firstthree`,`popularity`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='Unique words for searches' AUTO_INCREMENT=3125349 ;

-- --------------------------------------------------------

--
-- Structure for view `VW_recentqueries`
--
DROP TABLE IF EXISTS `VW_recentqueries`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `VW_recentqueries` AS select `chat_messages`.`id` AS `id`,`chat_messages`.`chatid` AS `chatid`,`chat_messages`.`userid` AS `userid`,`chat_messages`.`type` AS `type`,`chat_messages`.`reportreason` AS `reportreason`,`chat_messages`.`refmsgid` AS `refmsgid`,`chat_messages`.`refchatid` AS `refchatid`,`chat_messages`.`date` AS `date`,`chat_messages`.`message` AS `message`,`chat_messages`.`platform` AS `platform`,`chat_messages`.`seenbyall` AS `seenbyall`,`chat_messages`.`reviewrequired` AS `reviewrequired`,`chat_messages`.`reviewedby` AS `reviewedby`,`chat_messages`.`reviewrejected` AS `reviewrejected`,`chat_messages`.`spamscore` AS `spamscore` from (`chat_messages` join `chat_rooms` on((`chat_messages`.`chatid` = `chat_rooms`.`id`))) where (`chat_rooms`.`chattype` = 'User2Mod') order by `chat_messages`.`date` desc;

-- --------------------------------------------------------

--
-- Structure for view `vw_freeglegroups_unreached`
--
DROP TABLE IF EXISTS `vw_freeglegroups_unreached`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_freeglegroups_unreached` AS select `groups`.`id` AS `id`,`groups`.`nameshort` AS `nameshort` from `groups` where ((`groups`.`type` = 'Freegle') and (not((`groups`.`nameshort` like '%playground%'))) and (not((`groups`.`nameshort` like '%test%'))) and (not(`groups`.`id` in (select `alerts_tracking`.`groupid` from `alerts_tracking` where (`alerts_tracking`.`response` is not null))))) order by `groups`.`nameshort`;

-- --------------------------------------------------------

--
-- Structure for view `vw_manyemails`
--
DROP TABLE IF EXISTS `vw_manyemails`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_manyemails` AS select `users`.`id` AS `id`,`users`.`fullname` AS `fullname`,`users_emails`.`email` AS `email` from (`users` join `users_emails` on((`users`.`id` = `users_emails`.`userid`))) where `users`.`id` in (select `users_emails`.`userid` from `users_emails` group by `users_emails`.`userid` having (count(0) > 4) order by count(0) desc);

-- --------------------------------------------------------

--
-- Structure for view `vw_membersyncpending`
--
DROP TABLE IF EXISTS `vw_membersyncpending`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_membersyncpending` AS select `memberships_yahoo_dump`.`id` AS `id`,`memberships_yahoo_dump`.`groupid` AS `groupid`,`memberships_yahoo_dump`.`members` AS `members`,`memberships_yahoo_dump`.`lastupdated` AS `lastupdated`,`memberships_yahoo_dump`.`lastprocessed` AS `lastprocessed`,`memberships_yahoo_dump`.`synctime` AS `synctime` from `memberships_yahoo_dump` where (isnull(`memberships_yahoo_dump`.`lastprocessed`) or (`memberships_yahoo_dump`.`lastupdated` > `memberships_yahoo_dump`.`lastprocessed`));

-- --------------------------------------------------------

--
-- Structure for view `vw_multiemails`
--
DROP TABLE IF EXISTS `vw_multiemails`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_multiemails` AS select `vw_manyemails`.`id` AS `id`,`vw_manyemails`.`fullname` AS `fullname`,count(0) AS `count`,group_concat(`vw_manyemails`.`email` separator ', ') AS `GROUP_CONCAT(email SEPARATOR ', ')` from `vw_manyemails` group by `vw_manyemails`.`id` order by `count` desc;

-- --------------------------------------------------------

--
-- Structure for view `vw_recentgroupaccess`
--
DROP TABLE IF EXISTS `vw_recentgroupaccess`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_recentgroupaccess` AS select `users_logins`.`lastaccess` AS `lastaccess`,`groups`.`nameshort` AS `nameshort`,`groups`.`id` AS `id` from ((`users_logins` join `memberships` on(((`users_logins`.`userid` = `memberships`.`userid`) and (`memberships`.`role` in ('Owner','Moderator'))))) join `groups` on((`memberships`.`groupid` = `groups`.`id`))) order by `users_logins`.`lastaccess` desc;

-- --------------------------------------------------------

--
-- Structure for view `vw_recentlogins`
--
DROP TABLE IF EXISTS `vw_recentlogins`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_recentlogins` AS select `logs`.`timestamp` AS `timestamp`,`users`.`id` AS `id`,`users`.`fullname` AS `fullname` from (`users` join `logs` on((`users`.`id` = `logs`.`byuser`))) where ((`logs`.`type` = 'User') and (`logs`.`subtype` = 'Login')) order by `logs`.`timestamp` desc;

-- --------------------------------------------------------

--
-- Structure for view `vw_recentposts`
--
DROP TABLE IF EXISTS `vw_recentposts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_recentposts` AS select `messages`.`id` AS `id`,`messages`.`date` AS `date`,`messages`.`fromaddr` AS `fromaddr`,`messages`.`subject` AS `subject` from (`messages` left join `messages_drafts` on((`messages_drafts`.`msgid` = `messages`.`id`))) where ((`messages`.`source` = 'Platform') and isnull(`messages_drafts`.`msgid`)) order by `messages`.`date` desc limit 20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alerts_ibfk_2` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `alerts_tracking`
--
ALTER TABLE `alerts_tracking`
  ADD CONSTRAINT `_alerts_tracking_ibfk_3` FOREIGN KEY (`emailid`) REFERENCES `users_emails` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alerts_tracking_ibfk_1` FOREIGN KEY (`alertid`) REFERENCES `alerts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alerts_tracking_ibfk_2` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alerts_tracking_ibfk_4` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`chatid`) REFERENCES `chat_rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_3` FOREIGN KEY (`refmsgid`) REFERENCES `messages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `chat_messages_ibfk_4` FOREIGN KEY (`reviewedby`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `chat_messages_ibfk_5` FOREIGN KEY (`refchatid`) REFERENCES `chat_rooms` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `chat_rooms`
--
ALTER TABLE `chat_rooms`
  ADD CONSTRAINT `chat_rooms_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_rooms_ibfk_2` FOREIGN KEY (`user1`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_rooms_ibfk_3` FOREIGN KEY (`user2`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_roster`
--
ALTER TABLE `chat_roster`
  ADD CONSTRAINT `chat_roster_ibfk_1` FOREIGN KEY (`chatid`) REFERENCES `chat_rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_roster_ibfk_2` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `communityevents`
--
ALTER TABLE `communityevents`
  ADD CONSTRAINT `communityevents_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `communityevents_dates`
--
ALTER TABLE `communityevents_dates`
  ADD CONSTRAINT `communityevents_dates_ibfk_1` FOREIGN KEY (`eventid`) REFERENCES `communityevents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `communityevents_groups`
--
ALTER TABLE `communityevents_groups`
  ADD CONSTRAINT `communityevents_groups_ibfk_1` FOREIGN KEY (`eventid`) REFERENCES `communityevents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `communityevents_groups_ibfk_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`profile`) REFERENCES `groups_images` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `groups_ibfk_2` FOREIGN KEY (`cover`) REFERENCES `groups` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `groups_digests`
--
ALTER TABLE `groups_digests`
  ADD CONSTRAINT `groups_digests_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `groups_digests_ibfk_3` FOREIGN KEY (`msgid`) REFERENCES `messages` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `groups_facebook_shares`
--
ALTER TABLE `groups_facebook_shares`
  ADD CONSTRAINT `groups_facebook_shares_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `groups_images`
--
ALTER TABLE `groups_images`
  ADD CONSTRAINT `groups_images_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `groups_twitter`
--
ALTER TABLE `groups_twitter`
  ADD CONSTRAINT `groups_twitter_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `groups_twitter_ibfk_2` FOREIGN KEY (`msgid`) REFERENCES `messages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `groups_twitter_ibfk_3` FOREIGN KEY (`eventid`) REFERENCES `communityevents` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `items_index`
--
ALTER TABLE `items_index`
  ADD CONSTRAINT `items_index_ibfk_1` FOREIGN KEY (`itemid`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `items_index_ibfk_2` FOREIGN KEY (`wordid`) REFERENCES `words` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `locations`
--
ALTER TABLE `locations`
  ADD CONSTRAINT `locations_ibfk_1` FOREIGN KEY (`gridid`) REFERENCES `locations_grids` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `locations_excluded`
--
ALTER TABLE `locations_excluded`
  ADD CONSTRAINT `_locations_excluded_ibfk_1` FOREIGN KEY (`locationid`) REFERENCES `locations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `locations_excluded_ibfk_3` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `locations_excluded_ibfk_4` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `locations_grids_touches`
--
ALTER TABLE `locations_grids_touches`
  ADD CONSTRAINT `locations_grids_touches_ibfk_1` FOREIGN KEY (`gridid`) REFERENCES `locations_grids` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `locations_grids_touches_ibfk_2` FOREIGN KEY (`touches`) REFERENCES `locations_grids` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `locations_spatial`
--
ALTER TABLE `locations_spatial`
  ADD CONSTRAINT `locations_spatial_ibfk_1` FOREIGN KEY (`locationid`) REFERENCES `locations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `memberships`
--
ALTER TABLE `memberships`
  ADD CONSTRAINT `memberships_ibfk_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `memberships_ibfk_3` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `memberships_ibfk_4` FOREIGN KEY (`heldby`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `memberships_ibfk_5` FOREIGN KEY (`configid`) REFERENCES `mod_configs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `memberships_history`
--
ALTER TABLE `memberships_history`
  ADD CONSTRAINT `memberships_history_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `memberships_history_ibfk_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `memberships_yahoo`
--
ALTER TABLE `memberships_yahoo`
  ADD CONSTRAINT `_memberships_yahoo_ibfk_1` FOREIGN KEY (`membershipid`) REFERENCES `memberships` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `memberships_yahoo_ibfk_1` FOREIGN KEY (`emailid`) REFERENCES `users_emails` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `memberships_yahoo_dump`
--
ALTER TABLE `memberships_yahoo_dump`
  ADD CONSTRAINT `memberships_yahoo_dump_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `_messages_ibfk_1` FOREIGN KEY (`heldby`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `_messages_ibfk_2` FOREIGN KEY (`fromuser`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `_messages_ibfk_3` FOREIGN KEY (`locationid`) REFERENCES `locations` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `messages_attachments`
--
ALTER TABLE `messages_attachments`
  ADD CONSTRAINT `_messages_attachments_ibfk_1` FOREIGN KEY (`msgid`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages_attachments_items`
--
ALTER TABLE `messages_attachments_items`
  ADD CONSTRAINT `messages_attachments_items_ibfk_1` FOREIGN KEY (`attid`) REFERENCES `messages_attachments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_attachments_items_ibfk_2` FOREIGN KEY (`itemid`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages_drafts`
--
ALTER TABLE `messages_drafts`
  ADD CONSTRAINT `messages_drafts_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_drafts_ibfk_2` FOREIGN KEY (`msgid`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_drafts_ibfk_3` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `messages_groups`
--
ALTER TABLE `messages_groups`
  ADD CONSTRAINT `_messages_groups_ibfk_1` FOREIGN KEY (`msgid`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `_messages_groups_ibfk_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `_messages_groups_ibfk_3` FOREIGN KEY (`approvedby`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `messages_history`
--
ALTER TABLE `messages_history`
  ADD CONSTRAINT `messages_history_ibfk_1` FOREIGN KEY (`fromuser`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `messages_history_ibfk_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages_index`
--
ALTER TABLE `messages_index`
  ADD CONSTRAINT `_messages_index_ibfk_1` FOREIGN KEY (`msgid`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `_messages_index_ibfk_3` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `messages_index_ibfk_1` FOREIGN KEY (`wordid`) REFERENCES `words` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages_items`
--
ALTER TABLE `messages_items`
  ADD CONSTRAINT `messages_items_ibfk_1` FOREIGN KEY (`msgid`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_items_ibfk_2` FOREIGN KEY (`itemid`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages_outcomes`
--
ALTER TABLE `messages_outcomes`
  ADD CONSTRAINT `messages_outcomes_ibfk_1` FOREIGN KEY (`msgid`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_outcomes_ibfk_2` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `messages_postings`
--
ALTER TABLE `messages_postings`
  ADD CONSTRAINT `messages_postings_ibfk_1` FOREIGN KEY (`msgid`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_postings_ibfk_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages_promises`
--
ALTER TABLE `messages_promises`
  ADD CONSTRAINT `messages_promises_ibfk_1` FOREIGN KEY (`msgid`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_promises_ibfk_2` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages_related`
--
ALTER TABLE `messages_related`
  ADD CONSTRAINT `messages_related_ibfk_1` FOREIGN KEY (`id1`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_related_ibfk_2` FOREIGN KEY (`id2`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages_spamham`
--
ALTER TABLE `messages_spamham`
  ADD CONSTRAINT `messages_spamham_ibfk_1` FOREIGN KEY (`msgid`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mod_bulkops`
--
ALTER TABLE `mod_bulkops`
  ADD CONSTRAINT `mod_bulkops_ibfk_1` FOREIGN KEY (`configid`) REFERENCES `mod_configs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mod_bulkops_run`
--
ALTER TABLE `mod_bulkops_run`
  ADD CONSTRAINT `mod_bulkops_run_ibfk_1` FOREIGN KEY (`bulkopid`) REFERENCES `mod_bulkops` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mod_bulkops_run_ibfk_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mod_configs`
--
ALTER TABLE `mod_configs`
  ADD CONSTRAINT `mod_configs_ibfk_1` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `mod_stdmsgs`
--
ALTER TABLE `mod_stdmsgs`
  ADD CONSTRAINT `mod_stdmsgs_ibfk_1` FOREIGN KEY (`configid`) REFERENCES `mod_configs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `newsletters`
--
ALTER TABLE `newsletters`
  ADD CONSTRAINT `newsletters_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `newsletters_articles`
--
ALTER TABLE `newsletters_articles`
  ADD CONSTRAINT `newsletters_articles_ibfk_1` FOREIGN KEY (`newsletterid`) REFERENCES `newsletters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `newsletters_articles_ibfk_2` FOREIGN KEY (`photoid`) REFERENCES `newsletters_images` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `newsletters_images`
--
ALTER TABLE `newsletters_images`
  ADD CONSTRAINT `newsletters_images_ibfk_1` FOREIGN KEY (`articleid`) REFERENCES `newsletters_articles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `plugin`
--
ALTER TABLE `plugin`
  ADD CONSTRAINT `plugin_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `spam_users`
--
ALTER TABLE `spam_users`
  ADD CONSTRAINT `spam_users_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `spam_users_ibfk_2` FOREIGN KEY (`byuserid`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stats`
--
ALTER TABLE `stats`
  ADD CONSTRAINT `stats_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`lastlocation`) REFERENCES `locations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users_banned`
--
ALTER TABLE `users_banned`
  ADD CONSTRAINT `users_banned_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_banned_ibfk_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_banned_ibfk_3` FOREIGN KEY (`byuser`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users_comments`
--
ALTER TABLE `users_comments`
  ADD CONSTRAINT `users_comments_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_comments_ibfk_2` FOREIGN KEY (`byuserid`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_comments_ibfk_3` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

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

--
-- Constraints for table `users_push_notifications`
--
ALTER TABLE `users_push_notifications`
  ADD CONSTRAINT `users_push_notifications_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users_searches`
--
ALTER TABLE `users_searches`
  ADD CONSTRAINT `users_searches_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_searches_ibfk_2` FOREIGN KEY (`maxmsg`) REFERENCES `messages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_searches_ibfk_3` FOREIGN KEY (`locationid`) REFERENCES `locations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD CONSTRAINT `vouchers_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vouchers_ibfk_2` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
--
-- Database: `iznik_dev`
--

DELIMITER $$
--
-- Functions
--
CREATE DEFINER=`root`@`app2` FUNCTION `GetCenterPoint`(g GEOMETRY) RETURNS point
NO SQL
DETERMINISTIC
  BEGIN
    DECLARE envelope POLYGON;
    DECLARE sw, ne POINT;
    DECLARE lat, lng DOUBLE;

    SET envelope = ExteriorRing(Envelope(g));
    SET sw = PointN(envelope, 1);
    SET ne = PointN(envelope, 3);
    SET lat = X(sw) + (X(ne)-X(sw))/2;
    SET lng = Y(sw) + (Y(ne)-Y(sw))/2;
    RETURN POINT(lat, lng);
  END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `GetMaxDimension`(g GEOMETRY) RETURNS double
NO SQL
DETERMINISTIC
  BEGIN
    DECLARE envelope POLYGON;
    DECLARE sw, ne POINT;
    DECLARE xsize, ysize DOUBLE;

    SET envelope = ExteriorRing(Envelope(g));
    SET sw = PointN(envelope, 1);
    SET ne = PointN(envelope, 3);
    SET xsize = X(ne) - X(sw);
    SET ysize = Y(ne) - Y(sw);
    RETURN(GREATEST(xsize, ysize));
  END$$

CREATE DEFINER=`root`@`app2` FUNCTION `haversine`(
  lat1 FLOAT, lon1 FLOAT,
  lat2 FLOAT, lon2 FLOAT
) RETURNS float
NO SQL
DETERMINISTIC
  COMMENT 'Returns the distance in degrees on the Earth\n             between two known points of latitude and longitude'
  BEGIN
    RETURN 69 * DEGREES(ACOS(
                            COS(RADIANS(lat1)) *
                            COS(RADIANS(lat2)) *
                            COS(RADIANS(lon2) - RADIANS(lon1)) +
                            SIN(RADIANS(lat1)) * SIN(RADIANS(lat2))
                        ));
  END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `_users_old`
--

CREATE TABLE IF NOT EXISTS `_users_old` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `yahooUserId` varchar(20) DEFAULT NULL COMMENT 'Unique ID of user on Yahoo if known',
  `firstname` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `systemrole` set('User','Moderator','Support','Admin') NOT NULL DEFAULT 'User' COMMENT 'System-wide roles',
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastaccess` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `settings` text COMMENT 'JSON-encoded settings',
  `gotrealemail` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Until migrated, whether polled FD/TN to get real email',
  `suspectcount` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Number of reports of this user as suspicious',
  `suspectreason` varchar(80) DEFAULT NULL COMMENT 'Last reason for suspecting this user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `systemrole` (`systemrole`),
  KEY `added` (`added`,`lastaccess`),
  KEY `fullname` (`fullname`),
  KEY `firstname` (`firstname`),
  KEY `lastname` (`lastname`),
  KEY `firstname_2` (`firstname`,`lastname`),
  KEY `yahooUserId` (`yahooUserId`),
  KEY `gotrealemail` (`gotrealemail`),
  KEY `suspectcount` (`suspectcount`),
  KEY `suspectcount_2` (`suspectcount`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE IF NOT EXISTS `groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique ID of group',
  `nameshort` varchar(80) DEFAULT NULL COMMENT 'A short name for the group',
  `namefull` varchar(255) DEFAULT NULL COMMENT 'A longer name for the group',
  `nameabbr` varchar(5) DEFAULT NULL COMMENT 'An abbreviated name for the group',
  `settings` mediumtext NOT NULL COMMENT 'JSON-encoded settings for group',
  `type` set('Reuse','Freegle','Other') DEFAULT NULL COMMENT 'High-level characteristics of the group',
  `onyahoo` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Whether this group is also on Yahoo Groups',
  `lastyahoomembersync` timestamp NULL DEFAULT NULL COMMENT 'When we last synced approved members',
  `lastyahoomessagesync` timestamp NULL DEFAULT NULL COMMENT 'When we last synced approved messages',
  `lat` decimal(10,6) DEFAULT NULL,
  `lng` decimal(10,6) DEFAULT NULL,
  `confirmkey` varchar(32) DEFAULT NULL COMMENT 'Key used to verify some operations by email',
  `publish` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Whether this group is visible to members',
  `licenserequired` tinyint(4) DEFAULT '1' COMMENT 'Whether a license is required for this group',
  `trial` date DEFAULT NULL COMMENT 'For ModTools, when a trial was started',
  `licensed` date DEFAULT NULL COMMENT 'For ModTools, when a group was licensed',
  `licenseduntil` date DEFAULT NULL COMMENT 'For ModTools, when a group is licensed until',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `nameshort` (`nameshort`),
  UNIQUE KEY `namefull` (`namefull`),
  KEY `lat` (`lat`,`lng`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='The different groups that we host' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE IF NOT EXISTS `locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `osm_id` varchar(50) DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `type` enum('Road','Polygon','Line','Point','Postcode') NOT NULL,
  `geometry` geometry DEFAULT NULL,
  `gridid` bigint(20) unsigned DEFAULT NULL,
  `postcodeid` bigint(20) unsigned DEFAULT NULL,
  `areaid` bigint(20) unsigned DEFAULT NULL,
  `canon` varchar(255) DEFAULT NULL,
  `popularity` bigint(20) unsigned DEFAULT '0',
  `osm_amenity` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'For OSM locations, whether this is an amenity',
  `osm_shop` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'For OSM locations, whether this is a shop',
  `maxdimension` decimal(10,6) DEFAULT NULL COMMENT 'GetMaxDimension on geomtry',
  `lat` decimal(10,6) DEFAULT NULL,
  `lng` decimal(10,6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `osm_id` (`osm_id`),
  KEY `gridid` (`gridid`),
  KEY `canon` (`canon`),
  KEY `areaid` (`areaid`),
  KEY `postcodeid` (`postcodeid`),
  KEY `lat` (`lat`),
  KEY `lng` (`lng`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Location data, the bulk derived from OSM' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `locations_excluded`
--

CREATE TABLE IF NOT EXISTS `locations_excluded` (
  `locationid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  `userid` bigint(20) unsigned DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `locationid_2` (`locationid`,`groupid`),
  KEY `locationid` (`locationid`),
  KEY `groupid` (`groupid`),
  KEY `by` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Stops locations being suggested on a group';

-- --------------------------------------------------------

--
-- Table structure for table `locations_grids`
--

CREATE TABLE IF NOT EXISTS `locations_grids` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `swlat` decimal(10,6) NOT NULL,
  `swlng` decimal(10,6) NOT NULL,
  `nelat` decimal(10,6) NOT NULL,
  `nelng` decimal(10,6) NOT NULL,
  `box` geometry NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `swlat` (`swlat`,`swlng`,`nelat`,`nelng`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Used to map lat/lng to gridid for location searches' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE IF NOT EXISTS `logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Machine assumed set to GMT',
  `byuser` bigint(20) unsigned DEFAULT NULL COMMENT 'User responsible for action, if any',
  `type` enum('Group','Message','User','Plugin','Config','StdMsg','Location') DEFAULT NULL,
  `subtype` enum('Created','Deleted','Received','Sent','Failure','ClassifiedSpam','Joined','Left','Approved','Rejected','YahooDeliveryType','YahooPostingStatus','NotSpam','Login','Hold','Release','Edit','RoleChange','Merged','Replied','Mailed','Applied','Suspect') DEFAULT NULL,
  `groupid` bigint(20) unsigned DEFAULT NULL COMMENT 'Any group this log is for',
  `user` bigint(20) unsigned DEFAULT NULL COMMENT 'Any user that this log is about',
  `msgid` bigint(20) unsigned DEFAULT NULL COMMENT 'id in the messages table',
  `configid` bigint(20) unsigned DEFAULT NULL COMMENT 'id in the mod_configs table',
  `stdmsgid` bigint(20) unsigned DEFAULT NULL COMMENT 'Any stdmsg for this log',
  `bulkopid` bigint(20) unsigned DEFAULT NULL,
  `text` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `group` (`groupid`),
  KEY `message_approved` (`msgid`),
  KEY `byuser` (`byuser`),
  KEY `type` (`type`,`subtype`),
  KEY `subtype` (`subtype`),
  KEY `timestamp` (`timestamp`,`type`,`subtype`),
  KEY `timestamp_2` (`timestamp`,`groupid`),
  KEY `configid` (`configid`),
  KEY `user` (`user`,`timestamp`,`type`,`subtype`),
  KEY `stdmsgid` (`stdmsgid`),
  KEY `bulkopid` (`bulkopid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Logs.  Not guaranteed against loss' AUTO_INCREMENT=1 ;

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
-- Table structure for table `memberships`
--

CREATE TABLE IF NOT EXISTS `memberships` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  `role` enum('Member','Moderator','Owner') NOT NULL DEFAULT 'Member',
  `collection` enum('Approved','Pending','Banned') NOT NULL DEFAULT 'Approved',
  `configid` bigint(20) unsigned DEFAULT NULL COMMENT 'Configuration used to moderate this group if a moderator',
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `emailid` bigint(20) unsigned DEFAULT NULL COMMENT 'Which of their emails they use on this group',
  `yahooPostingStatus` enum('MODERATED','DEFAULT','PROHIBITED','UNMODERATED') DEFAULT NULL COMMENT 'Yahoo mod status if applicable',
  `yahooDeliveryType` enum('DIGEST','NONE','SINGLE','ANNOUNCEMENT') DEFAULT NULL COMMENT 'Yahoo delivery settings if applicable',
  `settings` text COMMENT 'Other group settings, e.g. for moderators',
  `syncdelete` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Used during member sync',
  `heldby` bigint(20) unsigned DEFAULT NULL,
  `yahooapprove` varchar(255) DEFAULT NULL COMMENT 'For Yahoo groups, email to approve member if known and relevant',
  `yahooreject` varchar(255) DEFAULT NULL COMMENT 'For Yahoo groups, email to reject member if known and relevant',
  `joincomment` varchar(255) DEFAULT NULL COMMENT 'Any joining comment for this member',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `userid_groupid` (`userid`,`groupid`),
  KEY `groupid_2` (`groupid`,`role`),
  KEY `userid` (`userid`,`role`),
  KEY `role` (`role`),
  KEY `configid` (`configid`),
  KEY `emailid` (`emailid`),
  KEY `groupid` (`groupid`,`collection`),
  KEY `heldby` (`heldby`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Which groups users are members of' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `memberships_history`
--

CREATE TABLE IF NOT EXISTS `memberships_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  `collection` enum('Approved','Pending','Banned') NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `groupid` (`groupid`),
  KEY `date` (`added`),
  KEY `userid` (`userid`,`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Used to spot multijoiners' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE IF NOT EXISTS `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique iD',
  `arrival` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When this message arrived at our server',
  `date` timestamp NULL DEFAULT NULL COMMENT 'When this message was created, e.g. Date header',
  `deleted` timestamp NULL DEFAULT NULL COMMENT 'When this message was deleted',
  `heldby` bigint(20) unsigned DEFAULT NULL COMMENT 'If this message is held by a moderator',
  `source` enum('Yahoo Approved','Yahoo Pending') DEFAULT NULL COMMENT 'Source of incoming message',
  `sourceheader` varchar(80) DEFAULT NULL COMMENT 'Any source header, e.g. X-Freegle-Source',
  `fromip` varchar(40) DEFAULT NULL COMMENT 'IP we think this message came from',
  `fromcountry` varchar(2) DEFAULT NULL COMMENT 'fromip geocoded to country',
  `message` longtext NOT NULL COMMENT 'The unparsed message',
  `fromuser` bigint(20) unsigned DEFAULT NULL,
  `envelopefrom` varchar(255) DEFAULT NULL,
  `fromname` varchar(255) DEFAULT NULL,
  `fromaddr` varchar(255) DEFAULT NULL,
  `envelopeto` varchar(255) DEFAULT NULL,
  `replyto` varchar(255) DEFAULT NULL,
  `subject` varchar(1024) DEFAULT NULL,
  `type` enum('Offer','Taken','Wanted','Received','Admin','Other') DEFAULT NULL COMMENT 'For reuse groups, the message categorisation',
  `messageid` varchar(255) DEFAULT NULL,
  `tnpostid` varchar(80) DEFAULT NULL COMMENT 'If this message came from Trash Nothing, the unique post ID',
  `textbody` longtext,
  `htmlbody` longtext,
  `retrycount` int(11) NOT NULL DEFAULT '0' COMMENT 'We might fail to route, and later retry',
  `retrylastfailure` timestamp NULL DEFAULT NULL,
  `spamtype` enum('CountryBlocked','IPUsedForDifferentUsers','IPUsedForDifferentGroups','SubjectUsedForDifferentGroups','SpamAssassin') DEFAULT NULL,
  `spamreason` varchar(255) DEFAULT NULL COMMENT 'Why we think this message may be spam',
  `lat` decimal(10,6) DEFAULT NULL,
  `lng` decimal(10,6) DEFAULT NULL,
  `locationid` bigint(20) unsigned DEFAULT NULL,
  `editedby` bigint(20) unsigned DEFAULT NULL,
  `editedat` timestamp NULL DEFAULT NULL,
  `suggestedsubject` varchar(1024) DEFAULT NULL COMMENT 'Any suggested subject improvement',
  PRIMARY KEY (`id`) KEY_BLOCK_SIZE=8,
  UNIQUE KEY `id` (`id`) KEY_BLOCK_SIZE=8,
  KEY `envelopefrom` (`envelopefrom`) KEY_BLOCK_SIZE=8,
  KEY `envelopeto` (`envelopeto`) KEY_BLOCK_SIZE=8,
  KEY `retrylastfailure` (`retrylastfailure`) KEY_BLOCK_SIZE=8,
  KEY `message-id` (`messageid`) KEY_BLOCK_SIZE=8,
  KEY `fromup` (`fromip`) KEY_BLOCK_SIZE=8,
  KEY `tnpostid` (`tnpostid`) KEY_BLOCK_SIZE=8,
  KEY `type` (`type`) KEY_BLOCK_SIZE=8,
  KEY `sourceheader` (`sourceheader`) KEY_BLOCK_SIZE=8,
  KEY `arrival` (`arrival`,`sourceheader`) KEY_BLOCK_SIZE=8,
  KEY `arrival_2` (`arrival`,`fromaddr`) KEY_BLOCK_SIZE=8,
  KEY `arrival_3` (`arrival`) KEY_BLOCK_SIZE=8,
  KEY `fromaddr` (`fromaddr`,`subject`(767)) KEY_BLOCK_SIZE=8,
  KEY `date` (`date`) KEY_BLOCK_SIZE=8,
  KEY `subject` (`subject`(767)) KEY_BLOCK_SIZE=8,
  KEY `fromuser` (`fromuser`) KEY_BLOCK_SIZE=8,
  KEY `deleted` (`deleted`) KEY_BLOCK_SIZE=8,
  KEY `heldby` (`heldby`) KEY_BLOCK_SIZE=8,
  KEY `lat` (`lat`),
  KEY `lng` (`lng`),
  KEY `locationid` (`locationid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=16 COMMENT='All our messages' AUTO_INCREMENT=1 ;

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
  KEY `incomingid` (`msgid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Attachments parsed out from messages and resized' AUTO_INCREMENT=1 ;

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
  `fromuser` bigint(20) unsigned DEFAULT NULL,
  `envelopefrom` varchar(255) DEFAULT NULL,
  `fromname` varchar(255) DEFAULT NULL,
  `fromaddr` varchar(255) DEFAULT NULL,
  `envelopeto` varchar(255) DEFAULT NULL,
  `groupid` bigint(20) unsigned DEFAULT NULL COMMENT 'Destination group, if identified',
  `subject` varchar(1024) DEFAULT NULL,
  `prunedsubject` varchar(1024) DEFAULT NULL COMMENT 'For spam detection',
  `messageid` varchar(255) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Message arrivals, used for spam checking' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages_index`
--

CREATE TABLE IF NOT EXISTS `messages_index` (
  `msgid` bigint(20) unsigned NOT NULL,
  `wordid` bigint(20) unsigned NOT NULL,
  `arrival` bigint(20) NOT NULL COMMENT 'We prioritise recent messages',
  `groupid` bigint(20) unsigned DEFAULT NULL,
  UNIQUE KEY `msgid` (`msgid`,`wordid`),
  KEY `arrival` (`arrival`),
  KEY `groupid` (`groupid`),
  KEY `wordid` (`wordid`,`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='For indexing messages for search keywords';

-- --------------------------------------------------------

--
-- Table structure for table `messages_related`
--

CREATE TABLE IF NOT EXISTS `messages_related` (
  `id1` bigint(20) unsigned NOT NULL,
  `id2` bigint(20) unsigned NOT NULL,
  KEY `id1` (`id1`),
  KEY `id2` (`id2`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Messages which are related to each other';

-- --------------------------------------------------------

--
-- Table structure for table `mod_configs`
--

CREATE TABLE IF NOT EXISTS `mod_configs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique ID of config',
  `name` varchar(255) NOT NULL COMMENT 'Name of config set',
  `createdby` bigint(20) unsigned DEFAULT NULL COMMENT 'Moderator ID who created it',
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
  `default` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Default configs are always visible',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `uniqueid` (`id`,`createdby`),
  KEY `createdby` (`createdby`),
  KEY `default` (`default`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Configurations for use by moderators' AUTO_INCREMENT=1 ;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `spam_users`
--

CREATE TABLE IF NOT EXISTS `spam_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned NOT NULL,
  `byuserid` bigint(20) unsigned DEFAULT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `collection` enum('Spammer','Whitelisted','PendingAdd','PendingRemove') NOT NULL DEFAULT 'Spammer',
  `reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userid` (`userid`),
  KEY `byuserid` (`byuserid`),
  KEY `added` (`added`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Users who are spammers or trusted' AUTO_INCREMENT=1 ;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Whitelisted IP addresses' AUTO_INCREMENT=1 ;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Whitelisted subjects' AUTO_INCREMENT=1 ;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='People who have supported this site' AUTO_INCREMENT=1 ;

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
  `gotrealemail` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Until migrated, whether polled FD/TN to get real email',
  `suspectcount` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Number of reports of this user as suspicious',
  `suspectreason` varchar(80) DEFAULT NULL COMMENT 'Last reason for suspecting this user',
  `yahooid` varchar(40) DEFAULT NULL COMMENT 'Any known YahooID for this user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `yahooUserId` (`yahooUserId`),
  KEY `systemrole` (`systemrole`),
  KEY `added` (`added`,`lastaccess`),
  KEY `fullname` (`fullname`),
  KEY `firstname` (`firstname`),
  KEY `lastname` (`lastname`),
  KEY `firstname_2` (`firstname`,`lastname`),
  KEY `gotrealemail` (`gotrealemail`),
  KEY `suspectcount` (`suspectcount`),
  KEY `suspectcount_2` (`suspectcount`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_banned`
--

CREATE TABLE IF NOT EXISTS `users_banned` (
  `userid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `byuser` bigint(20) unsigned DEFAULT NULL,
  UNIQUE KEY `userid_2` (`userid`,`groupid`),
  KEY `groupid` (`groupid`),
  KEY `userid` (`userid`),
  KEY `date` (`date`),
  KEY `byuser` (`byuser`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users_comments`
--

CREATE TABLE IF NOT EXISTS `users_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  `byuserid` bigint(20) unsigned DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user1` text,
  `user2` text,
  `user3` text,
  `user4` text,
  `user5` text,
  `user6` text,
  `user7` text,
  `user8` text,
  `user9` text,
  `user10` text,
  `user11` text,
  PRIMARY KEY (`id`),
  KEY `groupid` (`groupid`),
  KEY `modid` (`byuserid`),
  KEY `userid` (`userid`,`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Comments from mods on members' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_emails`
--

CREATE TABLE IF NOT EXISTS `users_emails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned NOT NULL COMMENT 'Unique ID in users table',
  `email` varchar(255) NOT NULL COMMENT 'The email',
  `preferred` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Preferred email for this user',
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `validated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `userid` (`userid`),
  KEY `validated` (`validated`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_recentlogins`
--
CREATE TABLE IF NOT EXISTS `vw_recentlogins` (
   `timestamp` timestamp
  ,`id` bigint(20) unsigned
  ,`fullname` varchar(255)
);
-- --------------------------------------------------------

--
-- Table structure for table `words`
--

CREATE TABLE IF NOT EXISTS `words` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `word` varchar(10) NOT NULL,
  `firstthree` varchar(3) NOT NULL,
  `soundex` varchar(10) NOT NULL,
  `popularity` bigint(20) NOT NULL DEFAULT '0' COMMENT 'Negative as DESC index not supported',
  PRIMARY KEY (`id`),
  KEY `word` (`word`,`firstthree`,`soundex`),
  KEY `popularity` (`popularity`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Unique words for searches' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure for view `vw_recentlogins`
--
DROP TABLE IF EXISTS `vw_recentlogins`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_recentlogins` AS select `logs`.`timestamp` AS `timestamp`,`users`.`id` AS `id`,`users`.`fullname` AS `fullname` from (`users` join `logs` on((`users`.`id` = `logs`.`byuser`))) where ((`logs`.`type` = 'User') and (`logs`.`subtype` = 'Login')) order by `logs`.`timestamp` desc;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `locations`
--
ALTER TABLE `locations`
  ADD CONSTRAINT `locations_ibfk_1` FOREIGN KEY (`gridid`) REFERENCES `locations_grids` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `locations_excluded`
--
ALTER TABLE `locations_excluded`
  ADD CONSTRAINT `_locations_excluded_ibfk_1` FOREIGN KEY (`locationid`) REFERENCES `locations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `_locations_excluded_ibfk_3` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `locations_excluded_ibfk_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `_logs_ibfk_1` FOREIGN KEY (`stdmsgid`) REFERENCES `mod_stdmsgs` (`id`) ON DELETE NO ACTION,
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`byuser`) REFERENCES `users` (`id`) ON DELETE NO ACTION,
  ADD CONSTRAINT `logs_ibfk_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE NO ACTION,
  ADD CONSTRAINT `logs_ibfk_3` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE NO ACTION,
  ADD CONSTRAINT `logs_ibfk_4` FOREIGN KEY (`bulkopid`) REFERENCES `mod_configs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `memberships`
--
ALTER TABLE `memberships`
  ADD CONSTRAINT `_memberships_ibfk_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `_memberships_ibfk_3` FOREIGN KEY (`configid`) REFERENCES `mod_configs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `_memberships_ibfk_4` FOREIGN KEY (`heldby`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `memberships_ibfk_1` FOREIGN KEY (`emailid`) REFERENCES `users_emails` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `memberships_ibfk_3` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `memberships_history`
--
ALTER TABLE `memberships_history`
  ADD CONSTRAINT `memberships_history_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `memberships_history_ibfk_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `_messages_ibfk_1` FOREIGN KEY (`heldby`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`locationid`) REFERENCES `locations` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`fromuser`) REFERENCES `users` (`id`) ON DELETE NO ACTION;

--
-- Constraints for table `messages_attachments`
--
ALTER TABLE `messages_attachments`
  ADD CONSTRAINT `messages_attachments_ibfk_1` FOREIGN KEY (`msgid`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages_groups`
--
ALTER TABLE `messages_groups`
  ADD CONSTRAINT `_messages_groups_ibfk_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_groups_ibfk_1` FOREIGN KEY (`msgid`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages_index`
--
ALTER TABLE `messages_index`
  ADD CONSTRAINT `_messages_index_ibfk_2` FOREIGN KEY (`wordid`) REFERENCES `words` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `_messages_index_ibfk_3` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `messages_index_ibfk_1` FOREIGN KEY (`msgid`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages_related`
--
ALTER TABLE `messages_related`
  ADD CONSTRAINT `_messages_related_ibfk_1` FOREIGN KEY (`id1`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `_messages_related_ibfk_2` FOREIGN KEY (`id2`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mod_configs`
--
ALTER TABLE `mod_configs`
  ADD CONSTRAINT `mod_configs_ibfk_1` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
-- Constraints for table `spam_users`
--
ALTER TABLE `spam_users`
  ADD CONSTRAINT `spam_users_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `spam_users_ibfk_2` FOREIGN KEY (`byuserid`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users_banned`
--
ALTER TABLE `users_banned`
  ADD CONSTRAINT `users_banned_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_banned_ibfk_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_banned_ibfk_3` FOREIGN KEY (`byuser`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users_comments`
--
ALTER TABLE `users_comments`
  ADD CONSTRAINT `users_comments_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_comments_ibfk_2` FOREIGN KEY (`byuserid`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_comments_ibfk_3` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users_emails`
--
ALTER TABLE `users_emails`
  ADD CONSTRAINT `_users_emails_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users_logins`
--
ALTER TABLE `users_logins`
  ADD CONSTRAINT `users_logins_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `Delete Non-Freegle Old Messages` ON SCHEDULE EVERY 1 DAY STARTS '2016-01-02 04:00:00' ON COMPLETION PRESERVE DISABLE ON SLAVE COMMENT 'Non-Freegle groups don''t have old messages preserved.' DO SELECT * FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid INNER JOIN groups ON messages_groups.groupid = groups.id WHERE  DATEDIFF(NOW(), `date`) > 31 AND groups.type != 'Freegle'$$

CREATE DEFINER=`root`@`localhost` EVENT `Delete Stranded Messages` ON SCHEDULE EVERY 1 DAY STARTS '2015-12-23 04:30:00' ON COMPLETION PRESERVE DISABLE ON SLAVE DO DELETE FROM messages WHERE id NOT IN (SELECT DISTINCT msgid FROM messages_groups)$$

CREATE DEFINER=`root`@`localhost` EVENT `Delete Unlicensed Groups` ON SCHEDULE EVERY 1 DAY STARTS '2015-12-23 04:00:00' ON COMPLETION PRESERVE DISABLE ON SLAVE DO DELETE FROM `groups` WHERE licenserequired = 1 AND (licenseduntil IS NULL OR licenseduntil < NOW()) AND (trial IS NULL OR DATEDIFF(NOW(), trial) > 30)$$

DELIMITER ;
--
-- Database: `memberstats`
--
--
-- Database: `messagemaker`
--

-- --------------------------------------------------------

--
-- Table structure for table `bounce`
--

CREATE TABLE IF NOT EXISTS `bounce` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8 NOT NULL,
  `bouncetype` int(11) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=457 ;

-- --------------------------------------------------------

--
-- Table structure for table `mobile_codes`
--

CREATE TABLE IF NOT EXISTS `mobile_codes` (
  `codeid` int(11) NOT NULL AUTO_INCREMENT,
  `useremail` varchar(255) NOT NULL,
  `usergroup` varchar(255) NOT NULL,
  `groupemail` varchar(255) DEFAULT NULL,
  `code` varchar(15) NOT NULL,
  `ipaddress` varchar(50) NOT NULL,
  `appVersion` varchar(10) NOT NULL,
  `deviceInfo` varchar(50) NOT NULL,
  `dateinserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dateconfirmed` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`codeid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=392 ;
--
-- Database: `modtools_wiki`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_credentials`
--

CREATE TABLE IF NOT EXISTS `account_credentials` (
  `acd_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `acd_user_id` int(10) unsigned NOT NULL,
  `acd_real_name` varbinary(255) NOT NULL DEFAULT '',
  `acd_email` tinyblob NOT NULL,
  `acd_email_authenticated` varbinary(14) DEFAULT NULL,
  `acd_bio` mediumblob NOT NULL,
  `acd_notes` mediumblob NOT NULL,
  `acd_urls` mediumblob NOT NULL,
  `acd_ip` varbinary(255) DEFAULT '',
  `acd_xff` varbinary(255) DEFAULT '',
  `acd_agent` varbinary(255) DEFAULT '',
  `acd_filename` varbinary(255) DEFAULT NULL,
  `acd_storage_key` varbinary(64) DEFAULT NULL,
  `acd_areas` mediumblob NOT NULL,
  `acd_registration` varbinary(14) NOT NULL,
  `acd_accepted` varbinary(14) DEFAULT NULL,
  `acd_user` int(10) unsigned NOT NULL DEFAULT '0',
  `acd_comment` varbinary(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`acd_id`),
  UNIQUE KEY `acd_user_id` (`acd_user_id`,`acd_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=9 ;

-- --------------------------------------------------------

--
-- Table structure for table `account_requests`
--

CREATE TABLE IF NOT EXISTS `account_requests` (
  `acr_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `acr_name` varbinary(255) NOT NULL DEFAULT '',
  `acr_real_name` varbinary(255) NOT NULL DEFAULT '',
  `acr_email` varbinary(255) NOT NULL,
  `acr_email_authenticated` varbinary(14) DEFAULT NULL,
  `acr_email_token` binary(32) DEFAULT NULL,
  `acr_email_token_expires` varbinary(14) DEFAULT NULL,
  `acr_bio` mediumblob NOT NULL,
  `acr_notes` mediumblob NOT NULL,
  `acr_urls` mediumblob NOT NULL,
  `acr_ip` varbinary(255) DEFAULT '',
  `acr_xff` varbinary(255) DEFAULT '',
  `acr_agent` varbinary(255) DEFAULT '',
  `acr_filename` varbinary(255) DEFAULT NULL,
  `acr_storage_key` varbinary(64) DEFAULT NULL,
  `acr_type` tinyint(255) unsigned NOT NULL DEFAULT '0',
  `acr_areas` mediumblob NOT NULL,
  `acr_registration` varbinary(14) NOT NULL,
  `acr_deleted` tinyint(1) NOT NULL,
  `acr_rejected` varbinary(14) DEFAULT NULL,
  `acr_held` varbinary(14) DEFAULT NULL,
  `acr_user` int(10) unsigned NOT NULL DEFAULT '0',
  `acr_comment` varbinary(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`acr_id`),
  UNIQUE KEY `acr_name` (`acr_name`),
  UNIQUE KEY `acr_email` (`acr_email`),
  KEY `acr_email_token` (`acr_email_token`),
  KEY `acr_type_del_reg` (`acr_type`,`acr_deleted`,`acr_registration`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Table structure for table `archive`
--

CREATE TABLE IF NOT EXISTS `archive` (
  `ar_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ar_namespace` int(11) NOT NULL DEFAULT '0',
  `ar_title` varbinary(255) NOT NULL DEFAULT '',
  `ar_text` mediumblob NOT NULL,
  `ar_comment` varbinary(767) NOT NULL,
  `ar_user` int(10) unsigned NOT NULL DEFAULT '0',
  `ar_user_text` varbinary(255) NOT NULL,
  `ar_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `ar_minor_edit` tinyint(4) NOT NULL DEFAULT '0',
  `ar_flags` tinyblob NOT NULL,
  `ar_rev_id` int(10) unsigned DEFAULT NULL,
  `ar_text_id` int(10) unsigned DEFAULT NULL,
  `ar_deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `ar_len` int(10) unsigned DEFAULT NULL,
  `ar_page_id` int(10) unsigned DEFAULT NULL,
  `ar_parent_id` int(10) unsigned DEFAULT NULL,
  `ar_sha1` varbinary(32) NOT NULL DEFAULT '',
  `ar_content_model` varbinary(32) DEFAULT NULL,
  `ar_content_format` varbinary(64) DEFAULT NULL,
  PRIMARY KEY (`ar_id`),
  KEY `name_title_timestamp` (`ar_namespace`,`ar_title`,`ar_timestamp`),
  KEY `usertext_timestamp` (`ar_user_text`,`ar_timestamp`),
  KEY `ar_revid` (`ar_rev_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=25 ;

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE IF NOT EXISTS `category` (
  `cat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cat_title` varbinary(255) NOT NULL,
  `cat_pages` int(11) NOT NULL DEFAULT '0',
  `cat_subcats` int(11) NOT NULL DEFAULT '0',
  `cat_files` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cat_id`),
  UNIQUE KEY `cat_title` (`cat_title`),
  KEY `cat_pages` (`cat_pages`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `categorylinks`
--

CREATE TABLE IF NOT EXISTS `categorylinks` (
  `cl_from` int(10) unsigned NOT NULL DEFAULT '0',
  `cl_to` varbinary(255) NOT NULL DEFAULT '',
  `cl_sortkey` varbinary(230) NOT NULL DEFAULT '',
  `cl_sortkey_prefix` varbinary(255) NOT NULL DEFAULT '',
  `cl_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `cl_collation` varbinary(32) NOT NULL DEFAULT '',
  `cl_type` enum('page','subcat','file') NOT NULL DEFAULT 'page',
  UNIQUE KEY `cl_from` (`cl_from`,`cl_to`),
  KEY `cl_sortkey` (`cl_to`,`cl_type`,`cl_sortkey`,`cl_from`),
  KEY `cl_timestamp` (`cl_to`,`cl_timestamp`),
  KEY `cl_collation` (`cl_collation`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `change_tag`
--

CREATE TABLE IF NOT EXISTS `change_tag` (
  `ct_rc_id` int(11) DEFAULT NULL,
  `ct_log_id` int(11) DEFAULT NULL,
  `ct_rev_id` int(11) DEFAULT NULL,
  `ct_tag` varbinary(255) NOT NULL,
  `ct_params` blob,
  UNIQUE KEY `change_tag_rc_tag` (`ct_rc_id`,`ct_tag`),
  UNIQUE KEY `change_tag_log_tag` (`ct_log_id`,`ct_tag`),
  UNIQUE KEY `change_tag_rev_tag` (`ct_rev_id`,`ct_tag`),
  KEY `change_tag_tag_id` (`ct_tag`,`ct_rc_id`,`ct_rev_id`,`ct_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `external_user`
--

CREATE TABLE IF NOT EXISTS `external_user` (
  `eu_local_id` int(10) unsigned NOT NULL,
  `eu_external_id` varbinary(255) NOT NULL,
  PRIMARY KEY (`eu_local_id`),
  UNIQUE KEY `eu_external_id` (`eu_external_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `externallinks`
--

CREATE TABLE IF NOT EXISTS `externallinks` (
  `el_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `el_from` int(10) unsigned NOT NULL DEFAULT '0',
  `el_to` blob NOT NULL,
  `el_index` blob NOT NULL,
  PRIMARY KEY (`el_id`),
  KEY `el_from` (`el_from`,`el_to`(40)),
  KEY `el_to` (`el_to`(60),`el_from`),
  KEY `el_index` (`el_index`(60))
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=32 ;

-- --------------------------------------------------------

--
-- Table structure for table `filearchive`
--

CREATE TABLE IF NOT EXISTS `filearchive` (
  `fa_id` int(11) NOT NULL AUTO_INCREMENT,
  `fa_name` varbinary(255) NOT NULL DEFAULT '',
  `fa_archive_name` varbinary(255) DEFAULT '',
  `fa_storage_group` varbinary(16) DEFAULT NULL,
  `fa_storage_key` varbinary(64) DEFAULT '',
  `fa_deleted_user` int(11) DEFAULT NULL,
  `fa_deleted_timestamp` binary(14) DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `fa_deleted_reason` varbinary(767) DEFAULT '',
  `fa_size` int(10) unsigned DEFAULT '0',
  `fa_width` int(11) DEFAULT '0',
  `fa_height` int(11) DEFAULT '0',
  `fa_metadata` mediumblob,
  `fa_bits` int(11) DEFAULT '0',
  `fa_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE') DEFAULT NULL,
  `fa_major_mime` enum('unknown','application','audio','image','text','video','message','model','multipart','chemical') DEFAULT NULL,
  `fa_minor_mime` varbinary(100) DEFAULT 'unknown',
  `fa_description` varbinary(767) DEFAULT NULL,
  `fa_user` int(10) unsigned DEFAULT '0',
  `fa_user_text` varbinary(255) DEFAULT NULL,
  `fa_timestamp` binary(14) DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `fa_deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `fa_sha1` varbinary(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`fa_id`),
  KEY `fa_name` (`fa_name`,`fa_timestamp`),
  KEY `fa_storage_group` (`fa_storage_group`,`fa_storage_key`),
  KEY `fa_deleted_timestamp` (`fa_deleted_timestamp`),
  KEY `fa_user_timestamp` (`fa_user_text`,`fa_timestamp`),
  KEY `fa_sha1` (`fa_sha1`(10))
) ENGINE=InnoDB DEFAULT CHARSET=binary AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `hitcounter`
--

CREATE TABLE IF NOT EXISTS `hitcounter` (
  `hc_id` int(10) unsigned NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=latin1 MAX_ROWS=25000;

-- --------------------------------------------------------

--
-- Table structure for table `image`
--

CREATE TABLE IF NOT EXISTS `image` (
  `img_name` varbinary(255) NOT NULL DEFAULT '',
  `img_size` int(10) unsigned NOT NULL DEFAULT '0',
  `img_width` int(11) NOT NULL DEFAULT '0',
  `img_height` int(11) NOT NULL DEFAULT '0',
  `img_metadata` mediumblob NOT NULL,
  `img_bits` int(11) NOT NULL DEFAULT '0',
  `img_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE') DEFAULT NULL,
  `img_major_mime` enum('unknown','application','audio','image','text','video','message','model','multipart','chemical') DEFAULT NULL,
  `img_minor_mime` varbinary(100) NOT NULL DEFAULT 'unknown',
  `img_description` varbinary(767) NOT NULL,
  `img_user` int(10) unsigned NOT NULL DEFAULT '0',
  `img_user_text` varbinary(255) NOT NULL,
  `img_timestamp` varbinary(14) NOT NULL DEFAULT '',
  `img_sha1` varbinary(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`img_name`),
  KEY `img_usertext_timestamp` (`img_user_text`,`img_timestamp`),
  KEY `img_size` (`img_size`),
  KEY `img_timestamp` (`img_timestamp`),
  KEY `img_sha1` (`img_sha1`(10)),
  KEY `img_media_mime` (`img_media_type`,`img_major_mime`,`img_minor_mime`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `imagelinks`
--

CREATE TABLE IF NOT EXISTS `imagelinks` (
  `il_from` int(10) unsigned NOT NULL DEFAULT '0',
  `il_to` varbinary(255) NOT NULL DEFAULT '',
  `il_from_namespace` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `il_from` (`il_from`,`il_to`),
  UNIQUE KEY `il_to` (`il_to`,`il_from`),
  KEY `il_backlinks_namespace` (`il_to`,`il_from_namespace`,`il_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `interwiki`
--

CREATE TABLE IF NOT EXISTS `interwiki` (
  `iw_prefix` varbinary(32) NOT NULL,
  `iw_url` blob NOT NULL,
  `iw_api` blob NOT NULL,
  `iw_wikiid` varbinary(64) NOT NULL,
  `iw_local` tinyint(1) NOT NULL,
  `iw_trans` tinyint(4) NOT NULL DEFAULT '0',
  UNIQUE KEY `iw_prefix` (`iw_prefix`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `ipblocks`
--

CREATE TABLE IF NOT EXISTS `ipblocks` (
  `ipb_id` int(11) NOT NULL AUTO_INCREMENT,
  `ipb_address` tinyblob NOT NULL,
  `ipb_user` int(10) unsigned NOT NULL DEFAULT '0',
  `ipb_by` int(10) unsigned NOT NULL DEFAULT '0',
  `ipb_by_text` varbinary(255) NOT NULL DEFAULT '',
  `ipb_reason` varbinary(767) NOT NULL,
  `ipb_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `ipb_auto` tinyint(1) NOT NULL DEFAULT '0',
  `ipb_anon_only` tinyint(1) NOT NULL DEFAULT '0',
  `ipb_create_account` tinyint(1) NOT NULL DEFAULT '1',
  `ipb_enable_autoblock` tinyint(1) NOT NULL DEFAULT '1',
  `ipb_expiry` varbinary(14) NOT NULL DEFAULT '',
  `ipb_range_start` tinyblob NOT NULL,
  `ipb_range_end` tinyblob NOT NULL,
  `ipb_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `ipb_block_email` tinyint(1) NOT NULL DEFAULT '0',
  `ipb_allow_usertalk` tinyint(1) NOT NULL DEFAULT '0',
  `ipb_parent_block_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`ipb_id`),
  UNIQUE KEY `ipb_address` (`ipb_address`(255),`ipb_user`,`ipb_auto`,`ipb_anon_only`),
  KEY `ipb_user` (`ipb_user`),
  KEY `ipb_range` (`ipb_range_start`(8),`ipb_range_end`(8)),
  KEY `ipb_timestamp` (`ipb_timestamp`),
  KEY `ipb_expiry` (`ipb_expiry`),
  KEY `ipb_parent_block_id` (`ipb_parent_block_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Table structure for table `iwlinks`
--

CREATE TABLE IF NOT EXISTS `iwlinks` (
  `iwl_from` int(10) unsigned NOT NULL DEFAULT '0',
  `iwl_prefix` varbinary(20) NOT NULL DEFAULT '',
  `iwl_title` varbinary(255) NOT NULL DEFAULT '',
  UNIQUE KEY `iwl_from` (`iwl_from`,`iwl_prefix`,`iwl_title`),
  KEY `iwl_prefix_title_from` (`iwl_prefix`,`iwl_title`,`iwl_from`),
  KEY `iwl_prefix_from_title` (`iwl_prefix`,`iwl_from`,`iwl_title`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `job`
--

CREATE TABLE IF NOT EXISTS `job` (
  `job_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_cmd` varbinary(60) NOT NULL DEFAULT '',
  `job_namespace` int(11) NOT NULL,
  `job_title` varbinary(255) NOT NULL,
  `job_timestamp` varbinary(14) DEFAULT NULL,
  `job_params` blob NOT NULL,
  `job_random` int(10) unsigned NOT NULL DEFAULT '0',
  `job_attempts` int(10) unsigned NOT NULL DEFAULT '0',
  `job_token` varbinary(32) NOT NULL DEFAULT '',
  `job_token_timestamp` varbinary(14) DEFAULT NULL,
  `job_sha1` varbinary(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`job_id`),
  KEY `job_sha1` (`job_sha1`),
  KEY `job_cmd_token` (`job_cmd`,`job_token`,`job_random`),
  KEY `job_cmd_token_id` (`job_cmd`,`job_token`,`job_id`),
  KEY `job_cmd` (`job_cmd`,`job_namespace`,`job_title`,`job_params`(128)),
  KEY `job_timestamp` (`job_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `l10n_cache`
--

CREATE TABLE IF NOT EXISTS `l10n_cache` (
  `lc_lang` varbinary(32) NOT NULL,
  `lc_key` varbinary(255) NOT NULL,
  `lc_value` mediumblob NOT NULL,
  KEY `lc_lang_key` (`lc_lang`,`lc_key`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `langlinks`
--

CREATE TABLE IF NOT EXISTS `langlinks` (
  `ll_from` int(10) unsigned NOT NULL DEFAULT '0',
  `ll_lang` varbinary(20) NOT NULL DEFAULT '',
  `ll_title` varbinary(255) NOT NULL DEFAULT '',
  UNIQUE KEY `ll_from` (`ll_from`,`ll_lang`),
  KEY `ll_lang` (`ll_lang`,`ll_title`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `log_search`
--

CREATE TABLE IF NOT EXISTS `log_search` (
  `ls_field` varbinary(32) NOT NULL,
  `ls_value` varbinary(255) NOT NULL,
  `ls_log_id` int(10) unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `ls_field_val` (`ls_field`,`ls_value`,`ls_log_id`),
  KEY `ls_log_id` (`ls_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `logging`
--

CREATE TABLE IF NOT EXISTS `logging` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `log_type` varbinary(32) NOT NULL DEFAULT '',
  `log_action` varbinary(32) NOT NULL DEFAULT '',
  `log_timestamp` binary(14) NOT NULL DEFAULT '19700101000000',
  `log_user` int(10) unsigned NOT NULL DEFAULT '0',
  `log_user_text` varbinary(255) NOT NULL DEFAULT '',
  `log_namespace` int(11) NOT NULL DEFAULT '0',
  `log_title` varbinary(255) NOT NULL DEFAULT '',
  `log_page` int(10) unsigned DEFAULT NULL,
  `log_comment` varbinary(767) NOT NULL DEFAULT '',
  `log_params` blob NOT NULL,
  `log_deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`log_id`),
  KEY `type_time` (`log_type`,`log_timestamp`),
  KEY `user_time` (`log_user`,`log_timestamp`),
  KEY `page_time` (`log_namespace`,`log_title`,`log_timestamp`),
  KEY `times` (`log_timestamp`),
  KEY `log_user_type_time` (`log_user`,`log_type`,`log_timestamp`),
  KEY `log_page_id_time` (`log_page`,`log_timestamp`),
  KEY `type_action` (`log_type`,`log_action`,`log_timestamp`),
  KEY `log_user_text_type_time` (`log_user_text`,`log_type`,`log_timestamp`),
  KEY `log_user_text_time` (`log_user_text`,`log_timestamp`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=268 ;

-- --------------------------------------------------------

--
-- Table structure for table `module_deps`
--

CREATE TABLE IF NOT EXISTS `module_deps` (
  `md_module` varbinary(255) NOT NULL,
  `md_skin` varbinary(32) NOT NULL,
  `md_deps` mediumblob NOT NULL,
  UNIQUE KEY `md_module_skin` (`md_module`,`md_skin`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `msg_resource`
--

CREATE TABLE IF NOT EXISTS `msg_resource` (
  `mr_resource` varbinary(255) NOT NULL,
  `mr_lang` varbinary(32) NOT NULL,
  `mr_blob` mediumblob NOT NULL,
  `mr_timestamp` binary(14) NOT NULL,
  UNIQUE KEY `mr_resource_lang` (`mr_resource`,`mr_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `msg_resource_links`
--

CREATE TABLE IF NOT EXISTS `msg_resource_links` (
  `mrl_resource` varbinary(255) NOT NULL,
  `mrl_message` varbinary(255) NOT NULL,
  UNIQUE KEY `mrl_message_resource` (`mrl_message`,`mrl_resource`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `objectcache`
--

CREATE TABLE IF NOT EXISTS `objectcache` (
  `keyname` varbinary(255) NOT NULL DEFAULT '',
  `value` mediumblob,
  `exptime` datetime DEFAULT NULL,
  PRIMARY KEY (`keyname`),
  KEY `exptime` (`exptime`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `oldimage`
--

CREATE TABLE IF NOT EXISTS `oldimage` (
  `oi_name` varbinary(255) NOT NULL DEFAULT '',
  `oi_archive_name` varbinary(255) NOT NULL DEFAULT '',
  `oi_size` int(10) unsigned NOT NULL DEFAULT '0',
  `oi_width` int(11) NOT NULL DEFAULT '0',
  `oi_height` int(11) NOT NULL DEFAULT '0',
  `oi_bits` int(11) NOT NULL DEFAULT '0',
  `oi_description` varbinary(767) NOT NULL,
  `oi_user` int(10) unsigned NOT NULL DEFAULT '0',
  `oi_user_text` varbinary(255) NOT NULL,
  `oi_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `oi_metadata` mediumblob NOT NULL,
  `oi_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE') DEFAULT NULL,
  `oi_major_mime` enum('unknown','application','audio','image','text','video','message','model','multipart','chemical') DEFAULT NULL,
  `oi_minor_mime` varbinary(100) NOT NULL DEFAULT 'unknown',
  `oi_deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `oi_sha1` varbinary(32) NOT NULL DEFAULT '',
  KEY `oi_usertext_timestamp` (`oi_user_text`,`oi_timestamp`),
  KEY `oi_name_timestamp` (`oi_name`,`oi_timestamp`),
  KEY `oi_name_archive_name` (`oi_name`,`oi_archive_name`(14)),
  KEY `oi_sha1` (`oi_sha1`(10))
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `page`
--

CREATE TABLE IF NOT EXISTS `page` (
  `page_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_namespace` int(11) NOT NULL,
  `page_title` varbinary(255) NOT NULL,
  `page_restrictions` tinyblob NOT NULL,
  `page_counter` bigint(20) unsigned NOT NULL DEFAULT '0',
  `page_is_redirect` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `page_is_new` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `page_random` double unsigned NOT NULL,
  `page_touched` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `page_latest` int(10) unsigned NOT NULL,
  `page_len` int(10) unsigned NOT NULL,
  `page_content_model` varbinary(32) DEFAULT NULL,
  `page_links_updated` varbinary(14) DEFAULT NULL,
  `page_lang` varbinary(35) DEFAULT NULL,
  PRIMARY KEY (`page_id`),
  UNIQUE KEY `name_title` (`page_namespace`,`page_title`),
  KEY `page_random` (`page_random`),
  KEY `page_len` (`page_len`),
  KEY `page_redirect_namespace_len` (`page_is_redirect`,`page_namespace`,`page_len`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=86 ;

-- --------------------------------------------------------

--
-- Table structure for table `page_props`
--

CREATE TABLE IF NOT EXISTS `page_props` (
  `pp_page` int(11) NOT NULL,
  `pp_propname` varbinary(60) NOT NULL,
  `pp_value` blob NOT NULL,
  `pp_sortkey` float DEFAULT NULL,
  UNIQUE KEY `pp_page_propname` (`pp_page`,`pp_propname`),
  UNIQUE KEY `pp_propname_page` (`pp_propname`,`pp_page`),
  UNIQUE KEY `pp_propname_sortkey_page` (`pp_propname`,`pp_sortkey`,`pp_page`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `page_restrictions`
--

CREATE TABLE IF NOT EXISTS `page_restrictions` (
  `pr_page` int(11) NOT NULL,
  `pr_type` varbinary(60) NOT NULL,
  `pr_level` varbinary(60) NOT NULL,
  `pr_cascade` tinyint(4) NOT NULL,
  `pr_user` int(11) DEFAULT NULL,
  `pr_expiry` varbinary(14) DEFAULT NULL,
  `pr_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`pr_id`),
  UNIQUE KEY `pr_pagetype` (`pr_page`,`pr_type`),
  KEY `pr_typelevel` (`pr_type`,`pr_level`),
  KEY `pr_level` (`pr_level`),
  KEY `pr_cascade` (`pr_cascade`)
) ENGINE=InnoDB DEFAULT CHARSET=binary AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pagelinks`
--

CREATE TABLE IF NOT EXISTS `pagelinks` (
  `pl_from` int(10) unsigned NOT NULL DEFAULT '0',
  `pl_namespace` int(11) NOT NULL DEFAULT '0',
  `pl_title` varbinary(255) NOT NULL DEFAULT '',
  `pl_from_namespace` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `pl_from` (`pl_from`,`pl_namespace`,`pl_title`),
  UNIQUE KEY `pl_namespace` (`pl_namespace`,`pl_title`,`pl_from`),
  KEY `pl_backlinks_namespace` (`pl_namespace`,`pl_title`,`pl_from_namespace`,`pl_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `protected_titles`
--

CREATE TABLE IF NOT EXISTS `protected_titles` (
  `pt_namespace` int(11) NOT NULL,
  `pt_title` varbinary(255) NOT NULL,
  `pt_user` int(10) unsigned NOT NULL,
  `pt_reason` varbinary(767) DEFAULT NULL,
  `pt_timestamp` binary(14) NOT NULL,
  `pt_expiry` varbinary(14) NOT NULL DEFAULT '',
  `pt_create_perm` varbinary(60) NOT NULL,
  UNIQUE KEY `pt_namespace_title` (`pt_namespace`,`pt_title`),
  KEY `pt_timestamp` (`pt_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `querycache`
--

CREATE TABLE IF NOT EXISTS `querycache` (
  `qc_type` varbinary(32) NOT NULL,
  `qc_value` int(10) unsigned NOT NULL DEFAULT '0',
  `qc_namespace` int(11) NOT NULL DEFAULT '0',
  `qc_title` varbinary(255) NOT NULL DEFAULT '',
  KEY `qc_type` (`qc_type`,`qc_value`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `querycache_info`
--

CREATE TABLE IF NOT EXISTS `querycache_info` (
  `qci_type` varbinary(32) NOT NULL DEFAULT '',
  `qci_timestamp` binary(14) NOT NULL DEFAULT '19700101000000',
  UNIQUE KEY `qci_type` (`qci_type`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `querycachetwo`
--

CREATE TABLE IF NOT EXISTS `querycachetwo` (
  `qcc_type` varbinary(32) NOT NULL,
  `qcc_value` int(10) unsigned NOT NULL DEFAULT '0',
  `qcc_namespace` int(11) NOT NULL DEFAULT '0',
  `qcc_title` varbinary(255) NOT NULL DEFAULT '',
  `qcc_namespacetwo` int(11) NOT NULL DEFAULT '0',
  `qcc_titletwo` varbinary(255) NOT NULL DEFAULT '',
  KEY `qcc_type` (`qcc_type`,`qcc_value`),
  KEY `qcc_title` (`qcc_type`,`qcc_namespace`,`qcc_title`),
  KEY `qcc_titletwo` (`qcc_type`,`qcc_namespacetwo`,`qcc_titletwo`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `recentchanges`
--

CREATE TABLE IF NOT EXISTS `recentchanges` (
  `rc_id` int(11) NOT NULL AUTO_INCREMENT,
  `rc_timestamp` varbinary(14) NOT NULL DEFAULT '',
  `rc_user` int(10) unsigned NOT NULL DEFAULT '0',
  `rc_user_text` varbinary(255) NOT NULL,
  `rc_namespace` int(11) NOT NULL DEFAULT '0',
  `rc_title` varbinary(255) NOT NULL DEFAULT '',
  `rc_comment` varbinary(767) NOT NULL DEFAULT '',
  `rc_minor` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rc_bot` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rc_new` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rc_cur_id` int(10) unsigned NOT NULL DEFAULT '0',
  `rc_this_oldid` int(10) unsigned NOT NULL DEFAULT '0',
  `rc_last_oldid` int(10) unsigned NOT NULL DEFAULT '0',
  `rc_type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rc_patrolled` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rc_ip` varbinary(40) NOT NULL DEFAULT '',
  `rc_old_len` int(11) DEFAULT NULL,
  `rc_new_len` int(11) DEFAULT NULL,
  `rc_deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rc_logid` int(10) unsigned NOT NULL DEFAULT '0',
  `rc_log_type` varbinary(255) DEFAULT NULL,
  `rc_log_action` varbinary(255) DEFAULT NULL,
  `rc_params` blob,
  `rc_source` varbinary(16) NOT NULL DEFAULT '',
  PRIMARY KEY (`rc_id`),
  KEY `rc_timestamp` (`rc_timestamp`),
  KEY `rc_namespace_title` (`rc_namespace`,`rc_title`),
  KEY `rc_cur_id` (`rc_cur_id`),
  KEY `new_name_timestamp` (`rc_new`,`rc_namespace`,`rc_timestamp`),
  KEY `rc_ip` (`rc_ip`),
  KEY `rc_ns_usertext` (`rc_namespace`,`rc_user_text`),
  KEY `rc_user_text` (`rc_user_text`,`rc_timestamp`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=368 ;

-- --------------------------------------------------------

--
-- Table structure for table `redirect`
--

CREATE TABLE IF NOT EXISTS `redirect` (
  `rd_from` int(10) unsigned NOT NULL DEFAULT '0',
  `rd_namespace` int(11) NOT NULL DEFAULT '0',
  `rd_title` varbinary(255) NOT NULL DEFAULT '',
  `rd_interwiki` varbinary(32) DEFAULT NULL,
  `rd_fragment` varbinary(255) DEFAULT NULL,
  PRIMARY KEY (`rd_from`),
  KEY `rd_ns_title` (`rd_namespace`,`rd_title`,`rd_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `revision`
--

CREATE TABLE IF NOT EXISTS `revision` (
  `rev_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rev_page` int(10) unsigned NOT NULL,
  `rev_text_id` int(10) unsigned NOT NULL,
  `rev_comment` varbinary(767) NOT NULL,
  `rev_user` int(10) unsigned NOT NULL DEFAULT '0',
  `rev_user_text` varbinary(255) NOT NULL DEFAULT '',
  `rev_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `rev_minor_edit` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rev_deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rev_len` int(10) unsigned DEFAULT NULL,
  `rev_parent_id` int(10) unsigned DEFAULT NULL,
  `rev_sha1` varbinary(32) NOT NULL DEFAULT '',
  `rev_content_model` varbinary(32) DEFAULT NULL,
  `rev_content_format` varbinary(64) DEFAULT NULL,
  PRIMARY KEY (`rev_id`),
  UNIQUE KEY `rev_page_id` (`rev_page`,`rev_id`),
  KEY `rev_timestamp` (`rev_timestamp`),
  KEY `page_timestamp` (`rev_page`,`rev_timestamp`),
  KEY `user_timestamp` (`rev_user`,`rev_timestamp`),
  KEY `usertext_timestamp` (`rev_user_text`,`rev_timestamp`),
  KEY `page_user_timestamp` (`rev_page`,`rev_user`,`rev_timestamp`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary MAX_ROWS=10000000 AVG_ROW_LENGTH=1024 AUTO_INCREMENT=275 ;

-- --------------------------------------------------------

--
-- Table structure for table `searchindex`
--

CREATE TABLE IF NOT EXISTS `searchindex` (
  `si_page` int(10) unsigned NOT NULL,
  `si_title` varchar(255) NOT NULL DEFAULT '',
  `si_text` mediumtext NOT NULL,
  UNIQUE KEY `si_page` (`si_page`),
  FULLTEXT KEY `si_title` (`si_title`),
  FULLTEXT KEY `si_text` (`si_text`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `site_identifiers`
--

CREATE TABLE IF NOT EXISTS `site_identifiers` (
  `si_site` int(10) unsigned NOT NULL,
  `si_type` varbinary(32) NOT NULL,
  `si_key` varbinary(32) NOT NULL,
  UNIQUE KEY `site_ids_type` (`si_type`,`si_key`),
  KEY `site_ids_site` (`si_site`),
  KEY `site_ids_key` (`si_key`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `site_stats`
--

CREATE TABLE IF NOT EXISTS `site_stats` (
  `ss_row_id` int(10) unsigned NOT NULL,
  `ss_total_views` bigint(20) unsigned DEFAULT '0',
  `ss_total_edits` bigint(20) unsigned DEFAULT '0',
  `ss_good_articles` bigint(20) unsigned DEFAULT '0',
  `ss_total_pages` bigint(20) DEFAULT '-1',
  `ss_users` bigint(20) DEFAULT '-1',
  `ss_active_users` bigint(20) DEFAULT '-1',
  `ss_images` int(11) DEFAULT '0',
  UNIQUE KEY `ss_row_id` (`ss_row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `sites`
--

CREATE TABLE IF NOT EXISTS `sites` (
  `site_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_global_key` varbinary(32) NOT NULL,
  `site_type` varbinary(32) NOT NULL,
  `site_group` varbinary(32) NOT NULL,
  `site_source` varbinary(32) NOT NULL,
  `site_language` varbinary(32) NOT NULL,
  `site_protocol` varbinary(32) NOT NULL,
  `site_domain` varbinary(255) NOT NULL,
  `site_data` blob NOT NULL,
  `site_forward` tinyint(1) NOT NULL,
  `site_config` blob NOT NULL,
  PRIMARY KEY (`site_id`),
  UNIQUE KEY `sites_global_key` (`site_global_key`),
  KEY `sites_type` (`site_type`),
  KEY `sites_group` (`site_group`),
  KEY `sites_source` (`site_source`),
  KEY `sites_language` (`site_language`),
  KEY `sites_protocol` (`site_protocol`),
  KEY `sites_domain` (`site_domain`),
  KEY `sites_forward` (`site_forward`)
) ENGINE=InnoDB DEFAULT CHARSET=binary AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `tag_summary`
--

CREATE TABLE IF NOT EXISTS `tag_summary` (
  `ts_rc_id` int(11) DEFAULT NULL,
  `ts_log_id` int(11) DEFAULT NULL,
  `ts_rev_id` int(11) DEFAULT NULL,
  `ts_tags` blob NOT NULL,
  UNIQUE KEY `tag_summary_rc_id` (`ts_rc_id`),
  UNIQUE KEY `tag_summary_log_id` (`ts_log_id`),
  UNIQUE KEY `tag_summary_rev_id` (`ts_rev_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `templatelinks`
--

CREATE TABLE IF NOT EXISTS `templatelinks` (
  `tl_from` int(10) unsigned NOT NULL DEFAULT '0',
  `tl_namespace` int(11) NOT NULL DEFAULT '0',
  `tl_title` varbinary(255) NOT NULL DEFAULT '',
  `tl_from_namespace` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `tl_from` (`tl_from`,`tl_namespace`,`tl_title`),
  UNIQUE KEY `tl_namespace` (`tl_namespace`,`tl_title`,`tl_from`),
  KEY `tl_backlinks_namespace` (`tl_namespace`,`tl_title`,`tl_from_namespace`,`tl_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `text`
--

CREATE TABLE IF NOT EXISTS `text` (
  `old_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `old_text` mediumblob NOT NULL,
  `old_flags` tinyblob NOT NULL,
  PRIMARY KEY (`old_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary MAX_ROWS=10000000 AVG_ROW_LENGTH=10240 AUTO_INCREMENT=285 ;

-- --------------------------------------------------------

--
-- Table structure for table `transcache`
--

CREATE TABLE IF NOT EXISTS `transcache` (
  `tc_url` varbinary(255) NOT NULL,
  `tc_contents` blob,
  `tc_time` binary(14) DEFAULT NULL,
  UNIQUE KEY `tc_url_idx` (`tc_url`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `updatelog`
--

CREATE TABLE IF NOT EXISTS `updatelog` (
  `ul_key` varbinary(255) NOT NULL,
  `ul_value` blob,
  PRIMARY KEY (`ul_key`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `uploadstash`
--

CREATE TABLE IF NOT EXISTS `uploadstash` (
  `us_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `us_user` int(10) unsigned NOT NULL,
  `us_key` varbinary(255) NOT NULL,
  `us_orig_path` varbinary(255) NOT NULL,
  `us_path` varbinary(255) NOT NULL,
  `us_source_type` varbinary(50) DEFAULT NULL,
  `us_timestamp` varbinary(14) NOT NULL,
  `us_status` varbinary(50) NOT NULL,
  `us_chunk_inx` int(10) unsigned DEFAULT NULL,
  `us_props` blob,
  `us_size` int(10) unsigned NOT NULL,
  `us_sha1` varbinary(31) NOT NULL,
  `us_mime` varbinary(255) DEFAULT NULL,
  `us_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE') DEFAULT NULL,
  `us_image_width` int(10) unsigned DEFAULT NULL,
  `us_image_height` int(10) unsigned DEFAULT NULL,
  `us_image_bits` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`us_id`),
  UNIQUE KEY `us_key` (`us_key`),
  KEY `us_user` (`us_user`),
  KEY `us_timestamp` (`us_timestamp`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varbinary(255) NOT NULL DEFAULT '',
  `user_real_name` varbinary(255) NOT NULL DEFAULT '',
  `user_password` tinyblob NOT NULL,
  `user_newpassword` tinyblob NOT NULL,
  `user_newpass_time` binary(14) DEFAULT NULL,
  `user_email` tinyblob NOT NULL,
  `user_touched` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `user_token` binary(32) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `user_email_authenticated` binary(14) DEFAULT NULL,
  `user_email_token` binary(32) DEFAULT NULL,
  `user_email_token_expires` binary(14) DEFAULT NULL,
  `user_registration` binary(14) DEFAULT NULL,
  `user_editcount` int(11) DEFAULT NULL,
  `user_password_expires` varbinary(14) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `user_email_token` (`user_email_token`),
  KEY `user_email` (`user_email`(50))
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=66 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_former_groups`
--

CREATE TABLE IF NOT EXISTS `user_former_groups` (
  `ufg_user` int(10) unsigned NOT NULL DEFAULT '0',
  `ufg_group` varbinary(255) NOT NULL DEFAULT '',
  UNIQUE KEY `ufg_user_group` (`ufg_user`,`ufg_group`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `user_groups`
--

CREATE TABLE IF NOT EXISTS `user_groups` (
  `ug_user` int(10) unsigned NOT NULL DEFAULT '0',
  `ug_group` varbinary(255) NOT NULL DEFAULT '',
  UNIQUE KEY `ug_user_group` (`ug_user`,`ug_group`),
  KEY `ug_group` (`ug_group`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `user_newtalk`
--

CREATE TABLE IF NOT EXISTS `user_newtalk` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_ip` varbinary(40) NOT NULL DEFAULT '',
  `user_last_timestamp` varbinary(14) DEFAULT NULL,
  KEY `user_id` (`user_id`),
  KEY `user_ip` (`user_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `user_properties`
--

CREATE TABLE IF NOT EXISTS `user_properties` (
  `up_user` int(11) NOT NULL,
  `up_property` varbinary(255) DEFAULT NULL,
  `up_value` blob,
  UNIQUE KEY `user_properties_user_property` (`up_user`,`up_property`),
  KEY `user_properties_property` (`up_property`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `valid_tag`
--

CREATE TABLE IF NOT EXISTS `valid_tag` (
  `vt_tag` varbinary(255) NOT NULL,
  PRIMARY KEY (`vt_tag`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `watchlist`
--

CREATE TABLE IF NOT EXISTS `watchlist` (
  `wl_user` int(10) unsigned NOT NULL,
  `wl_namespace` int(11) NOT NULL DEFAULT '0',
  `wl_title` varbinary(255) NOT NULL DEFAULT '',
  `wl_notificationtimestamp` varbinary(14) DEFAULT NULL,
  UNIQUE KEY `wl_user` (`wl_user`,`wl_namespace`,`wl_title`),
  KEY `namespace_title` (`wl_namespace`,`wl_title`),
  KEY `wl_user_notificationtimestamp` (`wl_user`,`wl_notificationtimestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
--
-- Database: `oauth`
--

-- --------------------------------------------------------

--
-- Table structure for table `oauth_consumer_registry`
--

CREATE TABLE IF NOT EXISTS `oauth_consumer_registry` (
  `ocr_id` int(11) NOT NULL AUTO_INCREMENT,
  `ocr_usa_id_ref` int(11) DEFAULT NULL,
  `ocr_consumer_key` varchar(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `ocr_consumer_secret` varchar(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `ocr_signature_methods` varchar(255) NOT NULL DEFAULT 'HMAC-SHA1,PLAINTEXT',
  `ocr_server_uri` varchar(255) NOT NULL,
  `ocr_server_uri_host` varchar(128) NOT NULL,
  `ocr_server_uri_path` varchar(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `ocr_request_token_uri` varchar(255) NOT NULL,
  `ocr_authorize_uri` varchar(255) NOT NULL,
  `ocr_access_token_uri` varchar(255) NOT NULL,
  `ocr_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ocr_id`),
  UNIQUE KEY `ocr_consumer_key` (`ocr_consumer_key`,`ocr_usa_id_ref`,`ocr_server_uri`),
  KEY `ocr_server_uri` (`ocr_server_uri`),
  KEY `ocr_server_uri_host` (`ocr_server_uri_host`,`ocr_server_uri_path`),
  KEY `ocr_usa_id_ref` (`ocr_usa_id_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_consumer_token`
--

CREATE TABLE IF NOT EXISTS `oauth_consumer_token` (
  `oct_id` int(11) NOT NULL AUTO_INCREMENT,
  `oct_ocr_id_ref` int(11) NOT NULL,
  `oct_usa_id_ref` int(11) NOT NULL,
  `oct_name` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `oct_token` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `oct_token_secret` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `oct_token_type` enum('request','authorized','access') DEFAULT NULL,
  `oct_token_ttl` datetime NOT NULL DEFAULT '9999-12-31 00:00:00',
  `oct_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`oct_id`),
  UNIQUE KEY `oct_ocr_id_ref` (`oct_ocr_id_ref`,`oct_token`),
  UNIQUE KEY `oct_usa_id_ref` (`oct_usa_id_ref`,`oct_ocr_id_ref`,`oct_token_type`,`oct_name`),
  KEY `oct_token_ttl` (`oct_token_ttl`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_log`
--

CREATE TABLE IF NOT EXISTS `oauth_log` (
  `olg_id` int(11) NOT NULL AUTO_INCREMENT,
  `olg_osr_consumer_key` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `olg_ost_token` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `olg_ocr_consumer_key` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `olg_oct_token` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `olg_usa_id_ref` int(11) DEFAULT NULL,
  `olg_received` text NOT NULL,
  `olg_sent` text NOT NULL,
  `olg_base_string` text NOT NULL,
  `olg_notes` text NOT NULL,
  `olg_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `olg_remote_ip` bigint(20) NOT NULL,
  PRIMARY KEY (`olg_id`),
  KEY `olg_osr_consumer_key` (`olg_osr_consumer_key`,`olg_id`),
  KEY `olg_ost_token` (`olg_ost_token`,`olg_id`),
  KEY `olg_ocr_consumer_key` (`olg_ocr_consumer_key`,`olg_id`),
  KEY `olg_oct_token` (`olg_oct_token`,`olg_id`),
  KEY `olg_usa_id_ref` (`olg_usa_id_ref`,`olg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_server_nonce`
--

CREATE TABLE IF NOT EXISTS `oauth_server_nonce` (
  `osn_id` int(11) NOT NULL AUTO_INCREMENT,
  `osn_consumer_key` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `osn_token` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `osn_timestamp` bigint(20) NOT NULL,
  `osn_nonce` varchar(80) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`osn_id`),
  UNIQUE KEY `osn_consumer_key` (`osn_consumer_key`,`osn_token`,`osn_timestamp`,`osn_nonce`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_server_registry`
--

CREATE TABLE IF NOT EXISTS `oauth_server_registry` (
  `osr_id` int(11) NOT NULL AUTO_INCREMENT,
  `osr_usa_id_ref` int(11) DEFAULT NULL,
  `osr_consumer_key` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `osr_consumer_secret` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `osr_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `osr_status` varchar(16) NOT NULL,
  `osr_requester_name` varchar(64) NOT NULL,
  `osr_requester_email` varchar(64) NOT NULL,
  `osr_callback_uri` varchar(255) NOT NULL,
  `osr_application_uri` varchar(255) NOT NULL,
  `osr_application_title` varchar(80) NOT NULL,
  `osr_application_descr` text NOT NULL,
  `osr_application_notes` text NOT NULL,
  `osr_application_type` varchar(20) NOT NULL,
  `osr_application_commercial` tinyint(1) NOT NULL DEFAULT '0',
  `osr_issue_date` datetime NOT NULL,
  `osr_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`osr_id`),
  UNIQUE KEY `osr_consumer_key` (`osr_consumer_key`),
  KEY `osr_usa_id_ref` (`osr_usa_id_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_server_token`
--

CREATE TABLE IF NOT EXISTS `oauth_server_token` (
  `ost_id` int(11) NOT NULL AUTO_INCREMENT,
  `ost_osr_id_ref` int(11) NOT NULL,
  `ost_usa_id_ref` int(11) NOT NULL,
  `ost_token` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `ost_token_secret` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `ost_token_type` enum('request','access') DEFAULT NULL,
  `ost_authorized` tinyint(1) NOT NULL DEFAULT '0',
  `ost_referrer_host` varchar(128) NOT NULL DEFAULT '',
  `ost_token_ttl` datetime NOT NULL DEFAULT '9999-12-31 00:00:00',
  `ost_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ost_verifier` char(10) DEFAULT NULL,
  `ost_callback_url` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`ost_id`),
  UNIQUE KEY `ost_token` (`ost_token`),
  KEY `ost_osr_id_ref` (`ost_osr_id_ref`),
  KEY `ost_token_ttl` (`ost_token_ttl`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `oauth_consumer_token`
--
ALTER TABLE `oauth_consumer_token`
  ADD CONSTRAINT `oauth_consumer_token_ibfk_1` FOREIGN KEY (`oct_ocr_id_ref`) REFERENCES `oauth_consumer_registry` (`ocr_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `oauth_server_token`
--
ALTER TABLE `oauth_server_token`
  ADD CONSTRAINT `oauth_server_token_ibfk_1` FOREIGN KEY (`ost_osr_id_ref`) REFERENCES `oauth_server_registry` (`osr_id`) ON DELETE CASCADE ON UPDATE CASCADE;
--
-- Database: `phpmyadmin`
--

-- --------------------------------------------------------

--
-- Table structure for table `pma_bookmark`
--

CREATE TABLE IF NOT EXISTS `pma_bookmark` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dbase` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `user` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `label` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `query` text COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Bookmarks' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pma_column_info`
--

CREATE TABLE IF NOT EXISTS `pma_column_info` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `db_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `table_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `column_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `comment` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `mimetype` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `transformation` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `transformation_options` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Column information for phpMyAdmin' AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `pma_designer_coords`
--

CREATE TABLE IF NOT EXISTS `pma_designer_coords` (
  `db_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `table_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `x` int(11) DEFAULT NULL,
  `y` int(11) DEFAULT NULL,
  `v` tinyint(4) DEFAULT NULL,
  `h` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`db_name`,`table_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table coordinates for Designer';

-- --------------------------------------------------------

--
-- Table structure for table `pma_history`
--

CREATE TABLE IF NOT EXISTS `pma_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `db` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `table` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `timevalue` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sqlquery` text COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`,`db`,`table`,`timevalue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='SQL history for phpMyAdmin' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pma_pdf_pages`
--

CREATE TABLE IF NOT EXISTS `pma_pdf_pages` (
  `db_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `page_nr` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_descr` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '',
  PRIMARY KEY (`page_nr`),
  KEY `db_name` (`db_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='PDF relation pages for phpMyAdmin' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pma_relation`
--

CREATE TABLE IF NOT EXISTS `pma_relation` (
  `master_db` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `master_table` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `master_field` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `foreign_db` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `foreign_table` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `foreign_field` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  PRIMARY KEY (`master_db`,`master_table`,`master_field`),
  KEY `foreign_field` (`foreign_db`,`foreign_table`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Relation table';

-- --------------------------------------------------------

--
-- Table structure for table `pma_table_coords`
--

CREATE TABLE IF NOT EXISTS `pma_table_coords` (
  `db_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `table_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `pdf_page_number` int(11) NOT NULL DEFAULT '0',
  `x` float unsigned NOT NULL DEFAULT '0',
  `y` float unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`db_name`,`table_name`,`pdf_page_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table coordinates for phpMyAdmin PDF output';

-- --------------------------------------------------------

--
-- Table structure for table `pma_table_info`
--

CREATE TABLE IF NOT EXISTS `pma_table_info` (
  `db_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `table_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `display_field` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  PRIMARY KEY (`db_name`,`table_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma_tracking`
--

CREATE TABLE IF NOT EXISTS `pma_tracking` (
  `db_name` varchar(64) COLLATE utf8_bin NOT NULL,
  `table_name` varchar(64) COLLATE utf8_bin NOT NULL,
  `version` int(10) unsigned NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `schema_snapshot` text COLLATE utf8_bin NOT NULL,
  `schema_sql` text COLLATE utf8_bin,
  `data_sql` longtext COLLATE utf8_bin,
  `tracking` set('UPDATE','REPLACE','INSERT','DELETE','TRUNCATE','CREATE DATABASE','ALTER DATABASE','DROP DATABASE','CREATE TABLE','ALTER TABLE','RENAME TABLE','DROP TABLE','CREATE INDEX','DROP INDEX','CREATE VIEW','ALTER VIEW','DROP VIEW') COLLATE utf8_bin DEFAULT NULL,
  `tracking_active` int(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`db_name`,`table_name`,`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin ROW_FORMAT=COMPACT;
--
-- Database: `polls`
--

-- --------------------------------------------------------

--
-- Table structure for table `voters`
--

CREATE TABLE IF NOT EXISTS `voters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `vote` enum('-','Agree','Disagree','Abstain') NOT NULL DEFAULT '-',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10 ;
--
-- Database: `quiz`
--

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE IF NOT EXISTS `answers` (
  `questionid` int(11) NOT NULL COMMENT 'Question ID',
  `a1count` int(11) NOT NULL COMMENT 'Number of people choosing this answer',
  `a2count` int(11) NOT NULL COMMENT 'Number of people choosing this answer',
  `a3count` int(11) NOT NULL COMMENT 'Number of people choosing this answer',
  `a4count` int(11) NOT NULL COMMENT 'Number of people choosing this answer',
  `right` int(11) NOT NULL COMMENT 'Number of people getting it right',
  `wrong` int(11) NOT NULL COMMENT 'Number of people getting it wrong'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE IF NOT EXISTS `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID of question',
  `quiz` int(11) NOT NULL COMMENT 'Corresponding quiz',
  `question` varchar(1024) NOT NULL COMMENT 'Text of question',
  `a1` varchar(1024) NOT NULL COMMENT 'Answer option 1',
  `a2` varchar(1024) NOT NULL COMMENT 'Answer option 2',
  `a3` varchar(1024) NOT NULL COMMENT 'Answer option 3',
  `a4` varchar(1024) NOT NULL COMMENT 'Answer option 4',
  `answer` int(11) NOT NULL COMMENT 'The correct answer',
  `answertext` varchar(1024) NOT NULL COMMENT 'Answer to display',
  `source` varchar(1024) NOT NULL COMMENT 'Source of information',
  `image` varchar(255) NOT NULL COMMENT 'Image to display with this question',
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=42 ;

-- --------------------------------------------------------

--
-- Table structure for table `quiz`
--

CREATE TABLE IF NOT EXISTS `quiz` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
  `name` varchar(255) NOT NULL COMMENT 'Title of Quiz',
  `start` date NOT NULL COMMENT 'Start date',
  `end` date NOT NULL COMMENT 'End date',
  `resultid` int(11) NOT NULL COMMENT 'Which set of results to show',
  `shown` int(11) NOT NULL COMMENT 'Number of times shown',
  `shared` int(11) NOT NULL COMMENT 'Number of times shared on Facebook',
  `getfreegling` int(11) NOT NULL COMMENT 'Number of times Get Freegling button clicked',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE IF NOT EXISTS `results` (
  `id` int(11) NOT NULL COMMENT 'ID of result set',
  `score` int(11) NOT NULL COMMENT 'Score value',
  `verdict` varchar(1024) NOT NULL COMMENT 'Verdict on performance',
  UNIQUE KEY `id` (`id`,`score`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `scores`
--

CREATE TABLE IF NOT EXISTS `scores` (
  `quizid` int(11) NOT NULL COMMENT 'Corresponding quiz',
  `count` int(11) NOT NULL COMMENT 'Number of responses',
  `scoretotal` int(11) NOT NULL COMMENT 'Total of all scores'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
--
-- Database: `republisher`
--

DELIMITER $$
--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `regex_replace`(pattern VARCHAR(1000),replacement VARCHAR(1000),original VARCHAR(1000)) RETURNS varchar(1000) CHARSET latin1
DETERMINISTIC
  BEGIN
    DECLARE temp VARCHAR(1000);
    DECLARE ch VARCHAR(1);
    DECLARE i INT;
    SET i = 1;
    SET temp = '';
    IF original REGEXP pattern THEN
      loop_label: LOOP
        IF i>CHAR_LENGTH(original) THEN
          LEAVE loop_label;
        END IF;
        SET ch = SUBSTRING(original,i,1);
        IF NOT ch REGEXP pattern THEN
          SET temp = CONCAT(temp,ch);
        ELSE
          SET temp = CONCAT(temp,replacement);
        END IF;
        SET i=i+1;
      END LOOP;
    ELSE
      SET temp = original;
    END IF;
    RETURN temp;
  END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `GAYLPopup`
--
CREATE TABLE IF NOT EXISTS `GAYLPopup` (
   `clicked` tinyint(1)
  ,`COUNT(*)` bigint(21)
);
-- --------------------------------------------------------

--
-- Table structure for table `I_S_INDEXES`
--

CREATE TABLE IF NOT EXISTS `I_S_INDEXES` (
  `TABLE_SCHEMA` varchar(64) DEFAULT NULL,
  `TABLE_NAME` varchar(64) DEFAULT NULL,
  `INDEX_NAME` varchar(64) DEFAULT NULL,
  `INDEX_TYPE` varchar(16) DEFAULT NULL,
  `IS_UNIQUE` varchar(3) DEFAULT NULL,
  `COLUMNS` varchar(341) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `I_S_REDUNDANT_INDEXES`
--

CREATE TABLE IF NOT EXISTS `I_S_REDUNDANT_INDEXES` (
  `TABLE_SCHEMA` varchar(64) DEFAULT NULL,
  `TABLE_NAME` varchar(64) DEFAULT NULL,
  `REDUNDANT_INDEX_NAME` varchar(64) DEFAULT NULL,
  `INDEX_NAME` varchar(341) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `InterestingWeights`
--

CREATE TABLE IF NOT EXISTS `InterestingWeights` (
  `uniqueweight` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'table unique',
  `weight` float NOT NULL,
  `keyword` varchar(255) NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `dateadded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `facebookid` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`uniqueweight`),
  KEY `keyword` (`keyword`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Weights to replace PHP function' AUTO_INCREMENT=702 ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `VW_groups_with_joining_issues`
--
CREATE TABLE IF NOT EXISTS `VW_groups_with_joining_issues` (
   `groupname` varchar(255)
  ,`MIN(dateapplied)` datetime
  ,`COUNT(*)` bigint(21)
);
-- --------------------------------------------------------

--
-- Table structure for table `acceptedham`
--

CREATE TABLE IF NOT EXISTS `acceptedham` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `msg` longtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `date` (`date`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=24868 ;

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE IF NOT EXISTS `alerts` (
  `alertid` int(11) NOT NULL AUTO_INCREMENT,
  `facebookid` varchar(255) NOT NULL,
  `alerttype` tinyint(4) NOT NULL,
  `search` varchar(255) NOT NULL,
  `matchcount` int(11) NOT NULL DEFAULT '0' COMMENT 'Number of hits on this alert',
  `lastmatch` datetime NOT NULL COMMENT 'Time of last hit',
  `dateadded` datetime NOT NULL COMMENT 'Time alert added',
  `lastcheck` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Last time we checked this alert against new messages',
  PRIMARY KEY (`alertid`),
  KEY `facebookid` (`facebookid`),
  KEY `alerttype` (`alerttype`),
  KEY `lastmatch` (`lastmatch`),
  KEY `search` (`search`),
  KEY `lastcheck` (`lastcheck`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=49055 ;

-- --------------------------------------------------------

--
-- Table structure for table `alerts_view`
--

CREATE TABLE IF NOT EXISTS `alerts_view` (
  `alertid` int(11) DEFAULT NULL,
  `facebookid` varchar(255) DEFAULT NULL,
  `alerttype` tinyint(4) DEFAULT NULL,
  `search` varchar(255) DEFAULT NULL,
  `matchcount` int(11) DEFAULT NULL,
  `lastmatch` datetime DEFAULT NULL,
  `dateadded` datetime DEFAULT NULL,
  `facebookname` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `bounces`
--

CREATE TABLE IF NOT EXISTS `bounces` (
  `userid` bigint(20) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `date` (`date`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `centralmails`
--

CREATE TABLE IF NOT EXISTS `centralmails` (
  `uniqueid` bigint(20) NOT NULL AUTO_INCREMENT,
  `type` enum('General','GAYL') NOT NULL DEFAULT 'General',
  `status` enum('Idle','Starting','Mailing','Paused','Complete','Cancelling') NOT NULL DEFAULT 'Idle',
  `from` varchar(255) NOT NULL DEFAULT 'noreply@ilovefreegle.org',
  `subject` varchar(255) NOT NULL,
  `trackurl` varchar(255) NOT NULL,
  `htmlbody` text NOT NULL,
  `textbody` text NOT NULL,
  `start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `end` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `testing` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`uniqueid`),
  UNIQUE KEY `uniqueid` (`uniqueid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=43 ;

-- --------------------------------------------------------

--
-- Table structure for table `centralmailtracking`
--

CREATE TABLE IF NOT EXISTS `centralmailtracking` (
  `userid` bigint(20) NOT NULL,
  `mailingid` bigint(20) NOT NULL,
  `sent` tinyint(1) NOT NULL DEFAULT '0',
  `clicked` tinyint(1) NOT NULL DEFAULT '0',
  `unsubscribed` tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `userid` (`userid`,`mailingid`),
  KEY `mailingid` (`mailingid`,`sent`,`clicked`),
  KEY `mailingid_2` (`mailingid`,`sent`,`clicked`,`unsubscribed`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `digesttracking`
--

CREATE TABLE IF NOT EXISTS `digesttracking` (
  `uniqueid` bigint(20) NOT NULL COMMENT 'Unique user ID',
  `groupid` int(11) NOT NULL,
  `lastdigest` datetime NOT NULL COMMENT 'Last digest sent',
  `nextdigest` datetime NOT NULL COMMENT 'Next digest due',
  `lastmessagedigest` varchar(1024) NOT NULL COMMENT 'Messages in last digest',
  UNIQUE KEY `uniqueid` (`uniqueid`),
  KEY `groupid` (`groupid`,`lastdigest`,`nextdigest`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `droplist`
--

CREATE TABLE IF NOT EXISTS `droplist` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `email` varchar(255) NOT NULL,
  `comment` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Table structure for table `emails`
--

CREATE TABLE IF NOT EXISTS `emails` (
  `email` varchar(255) NOT NULL,
  `groupid` int(11) NOT NULL,
  UNIQUE KEY `email` (`email`,`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eventfeeds`
--

CREATE TABLE IF NOT EXISTS `eventfeeds` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
  `groupid` int(11) NOT NULL COMMENT 'Group which this feed is on',
  `name` varchar(200) NOT NULL COMMENT 'Name',
  `url` varchar(1024) NOT NULL COMMENT 'URL of feed',
  `type` enum('RSS') NOT NULL COMMENT 'Type of feed',
  `lastchecked` datetime NOT NULL COMMENT 'When we last scanned',
  `idfield` varchar(100) NOT NULL COMMENT 'Field in feed which gives a unique ID',
  `titlefield` varchar(100) NOT NULL COMMENT 'Field in stream which gives an event title',
  `titlereg` varchar(200) NOT NULL COMMENT 'Regular expression to extract title',
  `titlerep` varchar(200) NOT NULL COMMENT 'Regular expression for replace',
  `descfield` varchar(100) NOT NULL COMMENT 'Field in stream which gives an event description',
  `descreg` varchar(200) NOT NULL COMMENT 'Regular expression to extract description',
  `descrep` varchar(200) NOT NULL COMMENT 'Regular expression for replace',
  `startfield` varchar(100) NOT NULL COMMENT 'Field in stream which gives a start date',
  `startreg` varchar(200) NOT NULL COMMENT 'Regular expression to extract start date',
  `startrep` varchar(200) NOT NULL COMMENT 'Regular expression for replace',
  `endfield` varchar(100) NOT NULL COMMENT 'Field in stream which gives an end date',
  `endreg` varchar(200) NOT NULL COMMENT 'Regular expression to extract end date',
  `endrep` varchar(200) NOT NULL COMMENT 'Regular expression for replace',
  `locationfield` varchar(100) NOT NULL COMMENT 'Field in feed containing the location',
  `locationreg` varchar(200) NOT NULL COMMENT 'Regular expression to extract location',
  `locationrep` varchar(200) NOT NULL COMMENT 'Regular expression for replace',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `eventfeedtracking`
--

CREATE TABLE IF NOT EXISTS `eventfeedtracking` (
  `feedid` int(11) NOT NULL,
  `id` varchar(1000) NOT NULL,
  `processed` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE IF NOT EXISTS `events` (
  `uniqueevent` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID for this event',
  `from` varchar(1024) NOT NULL COMMENT 'ID of creator',
  `pending` tinyint(1) NOT NULL COMMENT 'Are we waiting for this event to be moderated',
  `key` int(11) NOT NULL COMMENT 'Key for moderation',
  `groupid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `location` text NOT NULL,
  `start` datetime NOT NULL COMMENT 'UTC',
  `end` datetime NOT NULL COMMENT 'UTC',
  `url` varchar(1024) NOT NULL,
  `contactname` varchar(255) NOT NULL,
  `contactphone` varchar(255) NOT NULL,
  `contactemail` varchar(255) NOT NULL,
  `description` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `added` datetime NOT NULL COMMENT 'Date event added to table',
  `views` int(11) NOT NULL COMMENT 'Number of times someone has clicked to view',
  `repeat` int(11) NOT NULL DEFAULT '0' COMMENT 'Number of days after which event repeats, or 0',
  `occurrences` int(11) NOT NULL COMMENT 'Number of times to recur',
  `facebookid` varchar(255) NOT NULL COMMENT 'ID of any corresponding Facebook event',
  `facebookevent` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Whether to create an event',
  PRIMARY KEY (`uniqueevent`),
  KEY `pending` (`pending`,`groupid`,`start`,`end`),
  KEY `facebookid` (`facebookid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=98387 ;

-- --------------------------------------------------------

--
-- Table structure for table `eventtweettracking`
--

CREATE TABLE IF NOT EXISTS `eventtweettracking` (
  `uniqueid` int(11) NOT NULL COMMENT 'Unique ID of event',
  `date` datetime NOT NULL COMMENT 'Date last tweeted',
  UNIQUE KEY `uniqueid` (`uniqueid`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `facebook`
--

CREATE TABLE IF NOT EXISTS `facebook` (
  `fduniqueid` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
  `dateadded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `auth` varchar(100) NOT NULL COMMENT 'Unique key for login',
  `accounttype` enum('Facebook','OpenID','Email','Freegle') NOT NULL DEFAULT 'Email',
  `shownew` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Only show new messages by default on My Posts page',
  `facebookid` varchar(255) NOT NULL,
  `facebookaccesstoken` text NOT NULL COMMENT 'Access token for offline posting',
  `facebookname` varchar(255) NOT NULL,
  `facebooklocation` text NOT NULL,
  `googlerefreshtoken` int(11) DEFAULT NULL COMMENT 'Used to obtain google access tokens',
  `email` varchar(255) NOT NULL,
  `password` text NOT NULL,
  `lastemail` varchar(255) NOT NULL,
  `verified` tinyint(1) NOT NULL,
  `datechallenged` datetime NOT NULL,
  `dateverified` datetime NOT NULL,
  `showdaysold` int(11) NOT NULL DEFAULT '3',
  `hideprofilepic` tinyint(4) NOT NULL DEFAULT '0',
  `hidename` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Don''t include name in message posts',
  `lastaccess` datetime NOT NULL,
  `lastaccessip` varchar(20) NOT NULL,
  `lastgroup` int(11) NOT NULL,
  `lastlocation` varchar(255) NOT NULL,
  `pageadmincache` text NOT NULL,
  `lastpublicised` datetime NOT NULL,
  `verifybounced` tinyint(4) NOT NULL,
  `joinednative` tinyint(4) NOT NULL DEFAULT '0',
  `lastradius` int(11) NOT NULL DEFAULT '10000' COMMENT 'Any previously specified radius of posts to display',
  `lastfbalbum` varchar(255) NOT NULL,
  `logo` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Include logo in email notifications; turn off to reduce size',
  `includebody` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Include message body in digests',
  `graffiti` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'We can publish news to this user''s wall',
  `lastaccesstoken` datetime NOT NULL COMMENT 'Date when we last got an access token',
  `seennews` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether this user has seen a newsflash',
  `seenappeal` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Seen our appeal?',
  `onholidaytill` date NOT NULL DEFAULT '0000-00-00' COMMENT 'Don''t send digests until this date',
  `referredsuccess` int(11) NOT NULL DEFAULT '0' COMMENT 'Number of successful referrals',
  `referredlastip` varchar(20) NOT NULL COMMENT 'IP address of last referral, to reduce repeat counts',
  `lastdisplaygroup` int(11) NOT NULL COMMENT 'Last group displayed on the app',
  `usefop` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Default to using Fair Offer Policy',
  `hideimages` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Don''t display images',
  `debug` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether to record debug logs for this user',
  `access` set('Member','Weights','Support','Admin','MultiJoinOK') NOT NULL DEFAULT 'Member' COMMENT 'Access rights',
  `totaljoined` bigint(20) NOT NULL DEFAULT '0',
  `gaylsupporter` tinyint(4) NOT NULL DEFAULT '0',
  `gaylpluginseen` tinyint(1) NOT NULL DEFAULT '0',
  `gaylpluginaskinstall` tinyint(1) NOT NULL DEFAULT '0',
  `gaylpluginaskenable` tinyint(1) NOT NULL DEFAULT '0',
  `gaylinlinesignup` tinyint(4) NOT NULL DEFAULT '0',
  `canonmail` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`fduniqueid`),
  UNIQUE KEY `facebookid` (`facebookid`),
  UNIQUE KEY `uniqueid` (`fduniqueid`),
  UNIQUE KEY `facebookid_2` (`facebookid`,`email`),
  KEY `facebookname` (`facebookname`),
  KEY `email` (`email`),
  KEY `joinedfromnative` (`joinednative`),
  KEY `verified` (`verified`),
  KEY `datechallenged` (`datechallenged`),
  KEY `dateverified` (`dateverified`),
  KEY `lastaccess` (`lastaccess`),
  KEY `graffiti` (`graffiti`),
  KEY `auth` (`auth`,`facebookid`),
  KEY `canonmail` (`canonmail`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4618466 ;

--
-- Triggers `facebook`
--
DROP TRIGGER IF EXISTS `insert_facebook_facebookid`;
DELIMITER //
CREATE TRIGGER `insert_facebook_facebookid` BEFORE INSERT ON `facebook`
FOR EACH ROW BEGIN
  DECLARE str_len INT DEFAULT 0;

  IF LENGTH(NEW.facebookid) = 0
  THEN
    signal sqlstate '45000';
  END IF;
END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `facebook_joining`
--

CREATE TABLE IF NOT EXISTS `facebook_joining` (
  `groupid` int(11) NOT NULL,
  `groupname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `dateapplied` datetime NOT NULL,
  `dateconfirmed` datetime NOT NULL,
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE IF NOT EXISTS `feedback` (
  `feedbackid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID for this feedback',
  `voter` varchar(255) NOT NULL COMMENT 'The person giving the feedback',
  `votee` varchar(255) NOT NULL COMMENT 'The person fed back upon',
  `vote` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`feedbackid`),
  UNIQUE KEY `votee` (`votee`),
  UNIQUE KEY `votervotee` (`voter`,`votee`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=152074 ;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE IF NOT EXISTS `groups` (
  `groupid` int(11) NOT NULL,
  `logemail` varchar(255) NOT NULL,
  `groupname` varchar(128) NOT NULL,
  `groupaltname` varchar(255) DEFAULT NULL,
  `grouppublish` tinyint(1) NOT NULL DEFAULT '1',
  `dontask` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Don''t ask for permission to republish',
  `groupdaystoshow` tinyint(4) NOT NULL DEFAULT '7',
  `userpublishdefault` tinyint(1) NOT NULL DEFAULT '0',
  `userquerytext` text NOT NULL,
  `facebookpage` varchar(255) NOT NULL,
  `facebookpageid` varchar(255) NOT NULL,
  `facebookpagename` varchar(255) NOT NULL,
  `facebookcomments` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Whether to autoreply to comments',
  `facebookfooter` varchar(1024) NOT NULL,
  `twitterpage` varchar(255) NOT NULL,
  `twittertoken` text NOT NULL,
  `twittersecret` text NOT NULL,
  `twitterauthdate` date NOT NULL,
  `key` int(11) NOT NULL,
  `changerequest` mediumtext NOT NULL,
  `offerkeyword` varchar(20) NOT NULL DEFAULT 'OFFER',
  `takenkeyword` varchar(20) NOT NULL DEFAULT 'TAKEN',
  `wantedkeyword` varchar(20) NOT NULL DEFAULT 'WANTED',
  `receivedkeyword` varchar(20) NOT NULL DEFAULT 'RECEIVED',
  `repostoffer` tinyint(4) NOT NULL DEFAULT '2',
  `repostwanted` tinyint(4) NOT NULL DEFAULT '31',
  `maxreposts` tinyint(4) NOT NULL DEFAULT '10' COMMENT 'After this no chaseups will be sent',
  `crosspostoffer` tinyint(4) NOT NULL DEFAULT '3',
  `crosspostwanted` tinyint(4) NOT NULL,
  `pointlinksat` tinyint(4) NOT NULL DEFAULT '0',
  `defaultmapzoom` int(11) NOT NULL DEFAULT '9',
  `dontmapoffer` tinyint(4) NOT NULL DEFAULT '0',
  `dontmapwanted` tinyint(1) NOT NULL DEFAULT '0',
  `mapsearchhint` text NOT NULL,
  `mapdistance` smallint(6) NOT NULL DEFAULT '30',
  `locality` text NOT NULL,
  `dontmailaboutlocations` tinyint(4) NOT NULL DEFAULT '0',
  `chaseupenabled` tinyint(4) NOT NULL DEFAULT '0',
  `chaseupmail` text NOT NULL,
  `chaseupidle` tinyint(4) NOT NULL DEFAULT '0',
  `chaseupidleafter` tinyint(4) NOT NULL,
  `chaseupidleafterthereafter` int(11) NOT NULL,
  `chaseupidleyahoomail` text NOT NULL,
  `chaseupidlefbmail` text NOT NULL,
  `nativepost` tinyint(4) NOT NULL DEFAULT '0',
  `allowrepost` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Allow repost onto Wall and Tweet of central info',
  `graffititoken` text NOT NULL,
  `maxwallpostlength` int(11) NOT NULL DEFAULT '500',
  `maxwallpostdelay` int(11) NOT NULL DEFAULT '30',
  `maxwallpostgap` int(11) NOT NULL DEFAULT '5',
  `minchaseupdate` date NOT NULL,
  `interestedin` tinyint(4) NOT NULL DEFAULT '1',
  `preferredlanding` enum('Yahoo','Facebook','Native') NOT NULL DEFAULT 'Yahoo' COMMENT 'Where to direct members by default, e.g. when finding a group',
  `facebookallowed` tinyint(1) NOT NULL DEFAULT '1',
  `nativeallowed` tinyint(1) NOT NULL DEFAULT '0',
  `keepcommentsprivate` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether to restrict comments provided by members to the group owners.',
  `digesttrial` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether to generate trial digests for members on Yahoo Digest',
  `immediatetrial` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether to do a trial of the immediate notifications',
  `estimatedweight` int(11) NOT NULL DEFAULT '0' COMMENT 'Estimated weight in kg kept out of landfill in last  31 days',
  `estimatedvalue` decimal(10,2) NOT NULL DEFAULT '0.00',
  `groupdescription` text NOT NULL COMMENT 'HTML group description',
  `grouplogo` text NOT NULL COMMENT 'URL of group logo',
  `groupsidebar` text NOT NULL COMMENT 'HTML group sidebar for FD',
  `allowfeedback` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Allow feedback on this group',
  `fdjoinmessage` text NOT NULL COMMENT 'Sent by email to new Freegle Direct',
  `fbjoinmessage` text NOT NULL COMMENT 'Sent by email to new Facebook members',
  `blockedips` text NOT NULL COMMENT 'Comma-separated list of blocked IPs',
  `blockedemails` text NOT NULL COMMENT 'Emails banned from this group',
  `offerwantedratio` float NOT NULL COMMENT 'Ratio of OFFERs to WANTEDs in recent posts',
  `dontshowdonate` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'This group does not want donate mechanisms to be displayed',
  `loccheck` text NOT NULL COMMENT 'Words to check on post form.  Can use | between.',
  `locwarn` text NOT NULL,
  `bodycheck1` text NOT NULL,
  `bodywarn1` text NOT NULL,
  `bodycheck2` text NOT NULL,
  `bodywarn2` text NOT NULL,
  `bodycheck3` text NOT NULL,
  `bodywarn3` text NOT NULL,
  `dontweightpublish` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether to publish weights',
  `lastweightpublish` datetime NOT NULL COMMENT 'Last time we published our recent weight savings on Facebook or Twitter',
  `approvemembers` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether to approve members',
  `approvescreenshown` int(11) NOT NULL DEFAULT '0' COMMENT 'Number of times approve members screen shown',
  `approvescreencompleted` int(11) NOT NULL DEFAULT '0' COMMENT 'Number of times approve screen completed',
  `eventsdisabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Can members post events on this group?',
  `lasteventroundup` date NOT NULL COMMENT 'Last time we sent an event roundup',
  `alloweddomains` varchar(1000) NOT NULL COMMENT 'If present, CSL of allowable domains',
  `dontremovespammers` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Don''t automatically remove spammers',
  `dontallowanon` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Allow anonymous posts on this group?',
  `lastcheckedactive` timestamp NULL DEFAULT NULL,
  `lastconfirmedactive` datetime NOT NULL COMMENT 'If a group is receiving no messages, then we mail them to check if the group is active.  This is the last time they clicked to confirm that they were.',
  `keepspare` int(11) NOT NULL DEFAULT '10' COMMENT 'Number of joining members to keep',
  `centralmailsdisabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether to disable GAYL promotion to this group',
  `snaplyallowed` tinyint(4) NOT NULL DEFAULT '0',
  `repeateventsdisabled` tinyint(4) NOT NULL DEFAULT '0',
  `g4gchaseupdisabled` tinyint(4) NOT NULL DEFAULT '0',
  `bodyprivate` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether bodies on this message can be shown to non-members',
  UNIQUE KEY `groupid` (`groupid`),
  UNIQUE KEY `groupname_2` (`groupname`),
  KEY `facebookpageid` (`facebookpageid`),
  KEY `groupname` (`groupname`),
  KEY `chaseupenabled` (`chaseupenabled`),
  KEY `chaseupidle` (`chaseupidle`),
  KEY `interestedin` (`interestedin`),
  KEY `grouppublish` (`grouppublish`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Stand-in structure for view `groups_latlng`
--
CREATE TABLE IF NOT EXISTS `groups_latlng` (
   `groupURL` varchar(255)
  ,`groupLatitude` double
  ,`groupLongitude` double
);
-- --------------------------------------------------------

--
-- Table structure for table `itemwords`
--

CREATE TABLE IF NOT EXISTS `itemwords` (
  `word` varchar(255) NOT NULL,
  `count` int(11) NOT NULL,
  UNIQUE KEY `word` (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `join_bounced`
--

CREATE TABLE IF NOT EXISTS `join_bounced` (
  `date` datetime NOT NULL COMMENT 'Date we bounced them',
  `email` varchar(255) NOT NULL COMMENT 'Email we bounced',
  `groupid` int(11) NOT NULL COMMENT 'Group we bounced',
  UNIQUE KEY `email` (`email`,`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `joinreason`
--

CREATE TABLE IF NOT EXISTS `joinreason` (
  `uniqueid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID of this join reason',
  `facebookid` varchar(255) NOT NULL COMMENT 'ID of this user in facebook table; note that it may have been deleted if the user has left',
  `dateasked` datetime NOT NULL COMMENT 'When we asked them',
  `reason` enum('Personal Recommendation','Search Engine (Google etc)','Websites (e.g. Martin Lewis)','National media (newspapers/radio/TV etc)','Local media (newspapers/radio/TV/etc)','Social media (Facebook/Twitter)','Posters/flyers/cards','Other','Did not reply') NOT NULL DEFAULT 'Did not reply',
  `otherinfo` text NOT NULL COMMENT 'Other comments',
  `public` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Can use comments in tweets etc',
  `approved` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'This comment has been approved as suitable for public use',
  `published` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'This comment has been published',
  PRIMARY KEY (`uniqueid`),
  UNIQUE KEY `facebookid_2` (`facebookid`),
  KEY `facebookid` (`facebookid`,`dateasked`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=18824698 ;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE IF NOT EXISTS `locations` (
  `locationid` int(11) NOT NULL AUTO_INCREMENT,
  `groupid` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `lat` float NOT NULL,
  `lng` float NOT NULL,
  `trusted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`locationid`),
  UNIQUE KEY `groupid_2` (`groupid`,`location`),
  UNIQUE KEY `locationid` (`locationid`),
  KEY `location` (`location`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4167569 ;

-- --------------------------------------------------------

--
-- Table structure for table `locations_approved`
--

CREATE TABLE IF NOT EXISTS `locations_approved` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location` varchar(255) NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  `popularity` bigint(20) unsigned NOT NULL DEFAULT '1',
  `lat` decimal(10,5) NOT NULL,
  `lng` decimal(10,5) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `location` (`location`,`groupid`),
  KEY `groupid` (`groupid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1065571 ;

-- --------------------------------------------------------

--
-- Table structure for table `locations_ignore`
--

CREATE TABLE IF NOT EXISTS `locations_ignore` (
  `groupid` int(11) NOT NULL,
  `ignoreword` varchar(255) NOT NULL,
  UNIQUE KEY `groupid` (`groupid`,`ignoreword`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE IF NOT EXISTS `logs` (
  `uniqueid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID of this log',
  `type` enum('Membership','Posting','Settings','Chaseup','Social','Alerts','Digest','Events','Possible','Interested','View','Message','Spam','API') NOT NULL COMMENT 'Type of log',
  `email` varchar(255) NOT NULL COMMENT 'Any relevant email',
  `groupid` int(11) NOT NULL COMMENT 'Any relevant group',
  `viewid` int(11) NOT NULL COMMENT 'Any relevant view ID',
  `uniquemsg` int(11) NOT NULL COMMENT 'Any relevant message',
  `text` text NOT NULL COMMENT 'Log text',
  `date` datetime NOT NULL COMMENT 'When log was made',
  PRIMARY KEY (`uniqueid`),
  KEY `type` (`type`,`email`,`groupid`,`date`),
  KEY `uniquemsg` (`uniquemsg`),
  KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `mailinvites`
--

CREATE TABLE IF NOT EXISTS `mailinvites` (
  `uniqueid` int(11) NOT NULL COMMENT 'Unique ID of users',
  `lastmailed` datetime NOT NULL COMMENT 'When we last mailed this user to invite people',
  `successcount` int(11) NOT NULL COMMENT 'Number of times that someone has clicked on a link from this member',
  `dontmail` tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `uniqueid` (`uniqueid`),
  KEY `lastmailed` (`lastmailed`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `message_body`
--

CREATE TABLE IF NOT EXISTS `message_body` (
  `uniqueid` bigint(20) NOT NULL,
  `textbody` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE IF NOT EXISTS `messages` (
  `uniquemsg` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID for this message',
  `pendingid` bigint(20) NOT NULL COMMENT 'If present, pending ID as returned from an FD post',
  `arrivalepoch` int(11) NOT NULL,
  `arrivaldate` datetime NOT NULL,
  `epochtime` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `id` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  `groupname` varchar(255) NOT NULL,
  `subject` text NOT NULL,
  `correctedsubject` text NOT NULL COMMENT 'A better subject, if we can work one out',
  `from` varchar(255) NOT NULL,
  `fromname` text NOT NULL,
  `messagevisible` tinyint(1) NOT NULL DEFAULT '1',
  `messageid` varchar(1000) NOT NULL,
  `attachments` varchar(1024) NOT NULL,
  `attachhash` varchar(255) DEFAULT NULL COMMENT 'MD5 hash of attachment contents',
  `textbody` text NOT NULL,
  `messagetype` tinyint(4) NOT NULL,
  `messageavailable` tinyint(1) NOT NULL DEFAULT '1',
  `relatedmessage` int(11) NOT NULL,
  `matchconfidence` tinyint(4) NOT NULL,
  `lastchaseup` datetime NOT NULL,
  `chaseupcount` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Number of chaseups for this message',
  `chaseuptotal` int(11) NOT NULL,
  `key` int(11) NOT NULL,
  `chaseupworked` tinyint(4) NOT NULL DEFAULT '0',
  `chaseuprspepoch` int(11) NOT NULL COMMENT 'Epoch time when last chaseup response click occurred',
  `repostedcount` int(11) NOT NULL DEFAULT '0' COMMENT 'Number of times this message has been reposted by us',
  `withdrawn` tinyint(4) NOT NULL DEFAULT '0',
  `facebookpostid` varchar(255) NOT NULL,
  `postedtowall` tinyint(4) NOT NULL DEFAULT '1',
  `wallpostattempts` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Number of attempts to post to wall',
  `suggestedinterested` text NOT NULL,
  `interestedresponse` int(11) NOT NULL DEFAULT '0' COMMENT 'Number of clicks from people interested in the item',
  `weight` float NOT NULL DEFAULT '0' COMMENT 'Estimated weight in kg.  0=not estimated, -1=unknown',
  `estimatedvalue` decimal(10,2) DEFAULT NULL,
  `category` tinyint(4) NOT NULL DEFAULT '4' COMMENT 'Category in nb_categories or 0 if not categorised yet',
  `recategorised` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Manually recategorised?',
  `lat` float NOT NULL COMMENT 'lat of mapped message, may not be up to date if mappings change',
  `lng` float NOT NULL COMMENT 'lng of mapped message, may not be up to date if mappings change',
  PRIMARY KEY (`uniquemsg`),
  UNIQUE KEY `uniqueyahoo` (`id`,`groupid`),
  KEY `epochtime` (`epochtime`,`id`),
  KEY `messagevisible` (`messagevisible`),
  KEY `messageavailable` (`messageavailable`),
  KEY `messagetype` (`messagetype`),
  KEY `id` (`id`),
  KEY `date` (`date`),
  KEY `facebookpostid` (`facebookpostid`),
  KEY `groupid_2` (`groupid`,`messagevisible`,`messageavailable`),
  KEY `chaseupcount` (`chaseupcount`),
  KEY `postedtowall` (`postedtowall`),
  KEY `wallpostattempts` (`wallpostattempts`),
  KEY `weight` (`weight`),
  KEY `category` (`category`),
  KEY `groupid_4` (`groupid`,`category`),
  KEY `fromindex` (`from`),
  KEY `epochtime_2` (`epochtime`,`groupid`,`messagevisible`,`messagetype`,`messageavailable`),
  KEY `arrivalepoch` (`arrivalepoch`,`arrivaldate`,`groupid`,`messagevisible`,`messagetype`),
  KEY `uniquemsg` (`uniquemsg`,`epochtime`,`groupid`,`messagetype`,`messageavailable`),
  KEY `messageid` (`messageid`(767)),
  KEY `uniquemsg_2` (`uniquemsg`,`groupid`,`category`),
  KEY `attachhash` (`attachhash`),
  KEY `attachments` (`attachments`(767),`attachhash`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=27855266 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages_archive`
--

CREATE TABLE IF NOT EXISTS `messages_archive` (
  `uniquemsg` bigint(20) NOT NULL COMMENT 'Unique ID for this message',
  `pendingid` bigint(20) NOT NULL COMMENT 'If present, pending ID as returned from an FD post',
  `arrivalepoch` int(11) NOT NULL,
  `arrivaldate` datetime NOT NULL,
  `epochtime` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `id` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  `groupname` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `correctedsubject` text NOT NULL,
  `from` varchar(255) NOT NULL,
  `fromname` text NOT NULL,
  `messagevisible` tinyint(1) NOT NULL DEFAULT '1',
  `messageid` varchar(1000) NOT NULL,
  `attachments` varchar(1024) NOT NULL,
  `attachhash` varchar(255) DEFAULT NULL COMMENT 'MD5 hash of attachment contents',
  `textbody` text NOT NULL,
  `messagetype` tinyint(4) NOT NULL,
  `messageavailable` tinyint(1) NOT NULL DEFAULT '1',
  `relatedmessage` int(11) NOT NULL,
  `matchconfidence` tinyint(4) NOT NULL,
  `lastchaseup` datetime NOT NULL,
  `chaseupcount` tinyint(4) NOT NULL DEFAULT '0',
  `chaseuptotal` int(11) NOT NULL COMMENT 'Number of chaseups including reposts',
  `key` int(11) NOT NULL,
  `chaseupworked` tinyint(4) NOT NULL DEFAULT '0',
  `chaseuprspepoch` int(11) NOT NULL COMMENT 'Epoch time when last chaseup response click occurred',
  `repostedcount` int(11) NOT NULL COMMENT 'Number of times this message has been reposted by us',
  `withdrawn` tinyint(4) NOT NULL DEFAULT '0',
  `facebookpostid` varchar(255) NOT NULL,
  `postedtowall` tinyint(4) NOT NULL DEFAULT '1',
  `wallpostattempts` tinyint(4) NOT NULL DEFAULT '0',
  `suggestedinterested` text NOT NULL,
  `interestedresponse` int(11) NOT NULL,
  `weight` float NOT NULL DEFAULT '0' COMMENT 'Estimated weight. 0=not estimated, -1=unknown',
  `estimatedvalue` decimal(10,2) DEFAULT NULL,
  `category` tinyint(4) NOT NULL DEFAULT '4' COMMENT 'Category in nb_categories',
  `recategorised` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Manually recategorised?',
  `lat` float NOT NULL COMMENT 'lat of mapped message, may not be up to date if mappings change',
  `lng` float NOT NULL COMMENT 'lng of mapped message, may not be up to date if mappings change',
  UNIQUE KEY `uniquemsg_2` (`uniquemsg`),
  KEY `date` (`groupid`,`date`),
  KEY `attachhash` (`attachhash`),
  KEY `date_2` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=1;

-- --------------------------------------------------------

--
-- Table structure for table `moderationtimes`
--

CREATE TABLE IF NOT EXISTS `moderationtimes` (
  `groupid` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `submitted` datetime NOT NULL,
  `processed` datetime NOT NULL,
  UNIQUE KEY `groupid` (`groupid`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Statistics about moderation delay';

-- --------------------------------------------------------

--
-- Table structure for table `mostRecentMessage`
--

CREATE TABLE IF NOT EXISTS `mostRecentMessage` (
  `groupid` int(11) DEFAULT NULL,
  `from` varchar(255) DEFAULT NULL,
  `MaxDate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `namedviewrequests`
--

CREATE TABLE IF NOT EXISTS `namedviewrequests` (
  `parentgroup` int(11) NOT NULL COMMENT 'Parent group for view',
  `name` varchar(255) NOT NULL COMMENT 'Desired name',
  `shortlink` varchar(255) NOT NULL COMMENT 'Desired shortlink',
  `categories` set('1','2','3','4','5','6','7','8','9','10','11') NOT NULL COMMENT 'Categories for view',
  `key` int(11) NOT NULL COMMENT 'For authenticating approvals',
  UNIQUE KEY `name` (`name`,`shortlink`),
  KEY `parentgroup` (`parentgroup`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `namedviews`
--

CREATE TABLE IF NOT EXISTS `namedviews` (
  `uniqueid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID of this view',
  `name` varchar(128) NOT NULL COMMENT 'Name of this view',
  `parentgroup` int(11) NOT NULL COMMENT 'Group of which this is a view',
  `categories` set('1','2','3','4','5','6','7','8','9','10','11') NOT NULL COMMENT 'Categories seen in this view',
  `logo` text NOT NULL COMMENT 'Logo for this view',
  `description` text NOT NULL COMMENT 'HTML view description',
  `sidebar` text NOT NULL COMMENT 'HTML sidebar',
  `joins` int(11) NOT NULL COMMENT 'Number of members joined via this view',
  PRIMARY KEY (`uniqueid`),
  UNIQUE KEY `uniqueid` (`uniqueid`),
  UNIQUE KEY `name` (`name`),
  KEY `parentgroup` (`parentgroup`,`categories`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nb_categories`
--

CREATE TABLE IF NOT EXISTS `nb_categories` (
  `uniqueid` tinyint(4) NOT NULL AUTO_INCREMENT,
  `category_id` varchar(250) NOT NULL DEFAULT '',
  `category_name` varchar(255) NOT NULL,
  `Comment` text NOT NULL,
  `probability` double NOT NULL DEFAULT '0',
  `word_count` bigint(20) NOT NULL DEFAULT '0',
  `recentweight` int(11) NOT NULL COMMENT 'Weight in this category within the last 31 days',
  PRIMARY KEY (`uniqueid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=12 ;

-- --------------------------------------------------------

--
-- Table structure for table `nb_references`
--

CREATE TABLE IF NOT EXISTS `nb_references` (
  `id` varchar(250) NOT NULL DEFAULT '',
  `category_id` varchar(250) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nb_wordfreqs`
--

CREATE TABLE IF NOT EXISTS `nb_wordfreqs` (
  `word` varchar(250) NOT NULL DEFAULT '',
  `category_id` varchar(250) NOT NULL DEFAULT '',
  `count` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`word`,`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `notificationtracking`
--

CREATE TABLE IF NOT EXISTS `notificationtracking` (
  `facebookid` varchar(255) NOT NULL,
  `lastnotified` datetime NOT NULL COMMENT 'When did we last notify them?',
  UNIQUE KEY `facebookid` (`facebookid`),
  KEY `lastnotified` (`lastnotified`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `numbers`
--

CREATE TABLE IF NOT EXISTS `numbers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=306 ;

-- --------------------------------------------------------

--
-- Table structure for table `pending`
--

CREATE TABLE IF NOT EXISTS `pending` (
  `uniquemsg` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID of pending message',
  `from` varchar(255) NOT NULL,
  `groupid` int(11) NOT NULL,
  `groupname` varchar(255) NOT NULL,
  `submitted` datetime NOT NULL COMMENT 'Date submitted',
  `subject` text NOT NULL,
  `textbody` text NOT NULL,
  `attachments` varchar(1024) NOT NULL,
  PRIMARY KEY (`uniquemsg`),
  UNIQUE KEY `uniquemsg` (`uniquemsg`),
  KEY `from` (`from`,`groupid`,`groupname`,`submitted`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Pending messages submitted to Yahoo' AUTO_INCREMENT=965810 ;

-- --------------------------------------------------------

--
-- Table structure for table `popups`
--

CREATE TABLE IF NOT EXISTS `popups` (
  `uniqueid` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID of this popup',
  `groupid` int(11) NOT NULL COMMENT 'Group this alert corresponds to.  0 = all',
  `title` varchar(255) NOT NULL COMMENT 'Title of popup',
  `content` text NOT NULL COMMENT 'HTML content of poup',
  `buttonname` varchar(50) NOT NULL COMMENT 'Text for button in popup',
  `url` varchar(1025) NOT NULL COMMENT 'Page to open if button clicked',
  `added` datetime NOT NULL COMMENT 'Date popup setup',
  `excludedgroups` varchar(255) NOT NULL COMMENT 'CSL of excluded group ids',
  `gaylpopup` tinyint(1) NOT NULL DEFAULT '0',
  `joindelay` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`uniqueid`),
  KEY `groupid` (`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `popuptracking`
--

CREATE TABLE IF NOT EXISTS `popuptracking` (
  `groupid` int(11) NOT NULL COMMENT 'Group for popup',
  `facebookid` varchar(255) NOT NULL COMMENT 'ID of user',
  `popupid` bigint(20) NOT NULL COMMENT 'ID of popup',
  `lastseen` datetime NOT NULL COMMENT 'When user last saw popup',
  `clicked` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether button was clicked',
  UNIQUE KEY `groupid` (`groupid`,`facebookid`,`popupid`),
  KEY `groupid_2` (`groupid`,`facebookid`,`lastseen`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `publicphotos`
--

CREATE TABLE IF NOT EXISTS `publicphotos` (
  `uniquemsg` bigint(20) NOT NULL COMMENT 'Unique ID of message',
  `date` datetime NOT NULL COMMENT 'Date made public',
  `photo` varchar(1024) NOT NULL COMMENT 'URL of photo',
  `groupid` int(11) NOT NULL DEFAULT '0',
  `category` int(11) NOT NULL COMMENT 'Category of message',
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  UNIQUE KEY `uniquemsg` (`uniquemsg`),
  KEY `date` (`date`),
  KEY `category` (`category`),
  KEY `groupid` (`groupid`),
  KEY `photo` (`photo`(767)),
  KEY `date_2` (`date`,`width`,`height`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rejectedspam`
--

CREATE TABLE IF NOT EXISTS `rejectedspam` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `msg` longtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `date` (`date`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4121 ;

-- --------------------------------------------------------

--
-- Table structure for table `replies`
--

CREATE TABLE IF NOT EXISTS `replies` (
  `replyid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID for this reply',
  `groupid` int(11) NOT NULL,
  `groupname` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `id` int(11) NOT NULL COMMENT 'Message ID',
  `from` varchar(255) NOT NULL,
  `fromname` text NOT NULL,
  `to` varchar(255) NOT NULL,
  `subject` text NOT NULL,
  `body` text NOT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Deleted by user',
  `favourite` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Starred',
  `uniquemsg` bigint(20) NOT NULL COMMENT 'Unique ID of message to which this is a reply',
  `seen` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether this reply has been viewed on the web',
  PRIMARY KEY (`replyid`),
  KEY `date` (`date`),
  KEY `groupid_2` (`groupid`,`date`,`id`),
  KEY `fromtodate` (`from`,`to`,`date`),
  KEY `uniquemsg` (`uniquemsg`),
  KEY `to` (`to`),
  KEY `groupid_3` (`groupid`,`id`,`from`,`deleted`),
  KEY `fromlot` (`from`,`to`,`groupid`,`id`,`deleted`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=1 AUTO_INCREMENT=14751797 ;

-- --------------------------------------------------------

--
-- Table structure for table `reuseoutlets`
--

CREATE TABLE IF NOT EXISTS `reuseoutlets` (
  `id` bigint(11) unsigned NOT NULL AUTO_INCREMENT,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `addedby` bigint(20) DEFAULT NULL,
  `lat` double NOT NULL,
  `lng` double NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `description` text,
  `url` varchar(255) DEFAULT NULL,
  `hours` text,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `howitworks` text COMMENT 'Overrides network',
  PRIMARY KEY (`id`),
  KEY `lat` (`lat`,`lng`),
  KEY `lng` (`lng`),
  KEY `addedby` (`addedby`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4519 ;

-- --------------------------------------------------------

--
-- Table structure for table `reuseoutlets_edits`
--

CREATE TABLE IF NOT EXISTS `reuseoutlets_edits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `editedby` bigint(20) NOT NULL,
  `outletid` bigint(20) unsigned NOT NULL,
  `old` text NOT NULL,
  `new` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `outletid` (`outletid`),
  KEY `timestamp` (`timestamp`),
  KEY `editedby` (`editedby`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=178 ;

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
-- Table structure for table `shortlinks`
--

CREATE TABLE IF NOT EXISTS `shortlinks` (
  `shortlinkid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `keyword` varchar(6) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL,
  `clicks` smallint(5) unsigned NOT NULL DEFAULT '0',
  `shortlinktype` varchar(16) DEFAULT NULL,
  `groupid` smallint(5) unsigned DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`shortlinkid`),
  UNIQUE KEY `keyword` (`keyword`),
  KEY `shortlinktype` (`shortlinktype`),
  KEY `groupid` (`groupid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=40128572 ;

-- --------------------------------------------------------

--
-- Table structure for table `spammerlist`
--

CREATE TABLE IF NOT EXISTS `spammerlist` (
  `email` varchar(255) NOT NULL COMMENT 'Email of spammer',
  `added` date NOT NULL COMMENT 'When added',
  `reason` text NOT NULL COMMENT 'Why is this a spammer?',
  `who` text NOT NULL COMMENT 'Who added this?',
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `spamsubjects`
--

CREATE TABLE IF NOT EXISTS `spamsubjects` (
  `subject` varchar(512) NOT NULL,
  `count` int(11) NOT NULL,
  UNIQUE KEY `subject` (`subject`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `stories`
--

CREATE TABLE IF NOT EXISTS `stories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `submitted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `groupid` int(11) NOT NULL,
  `fduniqueid` bigint(20) NOT NULL,
  `story` text NOT NULL,
  `mailedtogroup` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `submitted` (`submitted`,`groupid`,`fduniqueid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3103 ;

-- --------------------------------------------------------

--
-- Table structure for table `subjectindex`
--

CREATE TABLE IF NOT EXISTS `subjectindex` (
  `uniqueid` bigint(20) NOT NULL,
  `groupid` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `keyword` varchar(32) NOT NULL COMMENT 'Individual word from subject of post',
  `added` datetime NOT NULL,
  KEY `added` (`added`),
  KEY `groupid` (`groupid`,`id`),
  KEY `keyword` (`keyword`),
  KEY `uniqueid` (`uniqueid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `uniqueid` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Unique user number',
  `dateinserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date user inserted into this table',
  `useremail` varchar(255) NOT NULL,
  `role` enum('Member','Moderator','Owner') NOT NULL DEFAULT 'Member',
  `userpublish` tinyint(1) NOT NULL DEFAULT '1',
  `legacypublish` int(11) NOT NULL DEFAULT '2' COMMENT 'Any previously expressed preference about publishing to Facebook',
  `publishcontent` tinyint(1) NOT NULL DEFAULT '0',
  `lastqueryepoch` int(11) NOT NULL,
  `lastquerycreds` int(11) NOT NULL,
  `dontquery` tinyint(4) NOT NULL DEFAULT '0',
  `groupid` int(11) NOT NULL,
  `usergroup` varchar(255) NOT NULL,
  `lastresponseepoch` int(11) NOT NULL,
  `groupsemail` varchar(255) NOT NULL,
  `dontchaseup` tinyint(4) NOT NULL DEFAULT '0',
  `lastidlechaseup` datetime NOT NULL,
  `idlechaseupworked` tinyint(4) NOT NULL DEFAULT '0',
  `idlechaseupkey` int(11) NOT NULL,
  `notinterested` tinyint(4) NOT NULL DEFAULT '0',
  `interestedkey` bigint(11) NOT NULL DEFAULT '0',
  `lastmessageseen` int(11) NOT NULL DEFAULT '0' COMMENT 'The most recent message id which has been displayed to this member',
  `allowreply` int(11) NOT NULL DEFAULT '0',
  `digest` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether to generate our own digests',
  `maxdigestdelay` int(11) NOT NULL DEFAULT '24' COMMENT 'Number of hours before a digest will be generated when messages are outstanding; -1=immediately; 0=never',
  `digesttrial` enum('No','Next Time','Once Only','Sent') NOT NULL DEFAULT 'No' COMMENT 'Whether to generate a trial digest for this member.',
  `immediatetrial` enum('No','Next Time','Once Only','Sent') NOT NULL DEFAULT 'No' COMMENT 'Whether to send a trial of the immediate notifications',
  `auth` varchar(100) NOT NULL COMMENT 'Auto-created password used in email links to allow login',
  `yahooid` varchar(255) NOT NULL COMMENT 'Yahoo ID on Yahoo Group',
  `yahoomailsetting` enum('Individual Emails','Daily Digest','Special Notices','No Email','Unknown') NOT NULL DEFAULT 'Unknown' COMMENT 'Yahoo''s current mail setting for this member',
  `yahoojoindate` date NOT NULL COMMENT 'Join date on Yahoo group',
  `lastlocation` varchar(255) NOT NULL COMMENT 'Last location in a message sent to the group',
  `deletedfromyahoo` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Member has been removed from Yahoo',
  `eventsdisabled` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'This user does not want to receive event mails',
  `lastreconfirmed` datetime NOT NULL COMMENT 'When we last checked with Yahoo that this was a member',
  `categories` set('1','2','3','4','5','6','7','8','9','10','11') NOT NULL COMMENT 'Categories which this user wants to see for this group, or empty for all',
  `lat` float NOT NULL COMMENT 'Latitude of last location or 0',
  `lng` float NOT NULL COMMENT 'Longitude of last location or 0',
  `centralmailsdisabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether to disable direct central mails, e.g. for GAYL',
  PRIMARY KEY (`uniqueid`),
  UNIQUE KEY `useremail_2` (`useremail`,`usergroup`),
  KEY `lastquerycreds` (`lastquerycreds`),
  KEY `usergroup` (`usergroup`),
  KEY `useremail` (`useremail`),
  KEY `groupsemail` (`groupsemail`),
  KEY `publishcontent` (`publishcontent`),
  KEY `dontchaseup` (`dontchaseup`),
  KEY `lastidlechaseup` (`lastidlechaseup`),
  KEY `interestedkey` (`interestedkey`),
  KEY `notinterested` (`notinterested`),
  KEY `digest` (`digest`),
  KEY `maxdigestdelay` (`maxdigestdelay`),
  KEY `groupid_2` (`groupid`,`groupsemail`),
  KEY `emailsandgroupid` (`useremail`,`groupid`,`groupsemail`),
  KEY `yahooid` (`yahooid`),
  KEY `deletedfromyahoo` (`deletedfromyahoo`),
  KEY `eventsdisabled` (`eventsdisabled`),
  KEY `groupid` (`groupid`,`role`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7518203 ;

--
-- Triggers `users`
--
DROP TRIGGER IF EXISTS `insert_users_var`;
DELIMITER //
CREATE TRIGGER `insert_users_var` BEFORE INSERT ON `users`
FOR EACH ROW BEGIN
  DECLARE str_len INT DEFAULT 0;
  SET str_len = LENGTH(NEW.groupsemail);

  IF str_len < 1
  THEN
    CALL col_length_outside_range_error();
  END IF;
END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `userwallreposts`
--

CREATE TABLE IF NOT EXISTS `userwallreposts` (
  `facebookid` varchar(255) NOT NULL,
  `postid` varchar(128) NOT NULL,
  `date` datetime NOT NULL,
  UNIQUE KEY `facebookid_2` (`facebookid`,`postid`),
  KEY `postid` (`postid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `view_invitation_responses`
--

CREATE TABLE IF NOT EXISTS `view_invitation_responses` (
  `uniqueid` int(11) DEFAULT NULL,
  `lastmailed` datetime DEFAULT NULL,
  `successcount` int(11) DEFAULT NULL,
  `dontmail` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `vw_approval`
--

CREATE TABLE IF NOT EXISTS `vw_approval` (
  `groupname` varchar(128) DEFAULT NULL,
  `approvescreenshown` int(11) DEFAULT NULL,
  `approvescreencompleted` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `vw_messages_ascot`
--

CREATE TABLE IF NOT EXISTS `vw_messages_ascot` (
  `epochtime` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `id` int(11) DEFAULT NULL,
  `groupname` varchar(255) DEFAULT NULL,
  `subject` text,
  `from` varchar(255) DEFAULT NULL,
  `messagevisible` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `vw_messages_edinburgh`
--

CREATE TABLE IF NOT EXISTS `vw_messages_edinburgh` (
  `epochtime` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `id` int(11) DEFAULT NULL,
  `groupname` varchar(255) DEFAULT NULL,
  `subject` text,
  `from` varchar(255) DEFAULT NULL,
  `messagevisible` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `vw_permit_content_or_replies`
--

CREATE TABLE IF NOT EXISTS `vw_permit_content_or_replies` (
  `useremail` varchar(255) DEFAULT NULL,
  `userpublish` tinyint(1) DEFAULT NULL,
  `publishcontent` tinyint(1) DEFAULT NULL,
  `allowreply` int(11) DEFAULT NULL,
  `lastqueryepoch` int(11) DEFAULT NULL,
  `lastquerycreds` int(11) DEFAULT NULL,
  `groupid` int(11) DEFAULT NULL,
  `usergroup` varchar(255) DEFAULT NULL,
  `lastresponseepoch` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `vw_users_ascot`
--

CREATE TABLE IF NOT EXISTS `vw_users_ascot` (
  `useremail` varchar(255) DEFAULT NULL,
  `userpublish` tinyint(1) DEFAULT NULL,
  `lastqueryepoch` int(11) DEFAULT NULL,
  `lastquerycreds` int(11) DEFAULT NULL,
  `usergroup` varchar(255) DEFAULT NULL,
  `lastresponseepoch` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `vw_users_edinburgh`
--

CREATE TABLE IF NOT EXISTS `vw_users_edinburgh` (
  `useremail` varchar(255) DEFAULT NULL,
  `userpublish` tinyint(1) DEFAULT NULL,
  `lastqueryepoch` int(11) DEFAULT NULL,
  `lastquerycreds` int(11) DEFAULT NULL,
  `usergroup` varchar(255) DEFAULT NULL,
  `lastresponseepoch` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wallreposts`
--

CREATE TABLE IF NOT EXISTS `wallreposts` (
  `groupid` int(11) NOT NULL,
  `postid` varchar(128) NOT NULL COMMENT 'Wall post which we have made',
  `date` datetime NOT NULL,
  UNIQUE KEY `groupid_2` (`groupid`,`postid`),
  KEY `postid` (`postid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `weights`
--

CREATE TABLE IF NOT EXISTS `weights` (
  `uniqueweight` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'table unique',
  `weight` float NOT NULL,
  `keyword` varchar(255) NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `dateadded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estimatedvalue` decimal(10,2) DEFAULT NULL COMMENT 'Estimate of financial value in GBP',
  `estimatedon` date DEFAULT NULL,
  `estimatedisabled` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`uniqueweight`),
  KEY `keyword` (`keyword`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Weights to replace PHP function' AUTO_INCREMENT=14202 ;

-- --------------------------------------------------------

--
-- Table structure for table `wordnet_categories`
--

CREATE TABLE IF NOT EXISTS `wordnet_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `word` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

-- --------------------------------------------------------

--
-- Table structure for table `wordnet_catmap`
--

CREATE TABLE IF NOT EXISTS `wordnet_catmap` (
  `wordnetid` int(11) NOT NULL,
  `wordnetword` varchar(255) NOT NULL,
  `catid` int(11) NOT NULL,
  PRIMARY KEY (`wordnetid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure for view `GAYLPopup`
--
DROP TABLE IF EXISTS `GAYLPopup`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `GAYLPopup` AS select `popuptracking`.`clicked` AS `clicked`,count(0) AS `COUNT(*)` from `popuptracking` where (`popuptracking`.`popupid` = 141) group by `popuptracking`.`clicked`;

-- --------------------------------------------------------

--
-- Structure for view `VW_groups_with_joining_issues`
--
DROP TABLE IF EXISTS `VW_groups_with_joining_issues`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `VW_groups_with_joining_issues` AS select `facebook_joining`.`groupname` AS `groupname`,min(`facebook_joining`.`dateapplied`) AS `MIN(dateapplied)`,count(0) AS `COUNT(*)` from `facebook_joining` where ((`facebook_joining`.`dateconfirmed` = '0000-00-00 00:00:00') and (not(`facebook_joining`.`groupid` in (select `facebook_joining`.`groupid` from `facebook_joining` where (`facebook_joining`.`dateconfirmed` <> '0000-00-00 00:00:00'))))) group by `facebook_joining`.`groupid` order by count(0) desc;

-- --------------------------------------------------------

--
-- Structure for view `groups_latlng`
--
DROP TABLE IF EXISTS `groups_latlng`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `groups_latlng` AS select `ilovefreegle`.`perch_groups`.`groupURL` AS `groupURL`,`ilovefreegle`.`perch_groups`.`groupLatitude` AS `groupLatitude`,`ilovefreegle`.`perch_groups`.`groupLongitude` AS `groupLongitude` from `ilovefreegle`.`perch_groups`;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bounces`
--
ALTER TABLE `bounces`
  ADD CONSTRAINT `bounces_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`uniqueid`) ON DELETE CASCADE;

--
-- Constraints for table `reuseoutlets`
--
ALTER TABLE `reuseoutlets`
  ADD CONSTRAINT `reuseoutlets_ibfk_1` FOREIGN KEY (`addedby`) REFERENCES `facebook` (`fduniqueid`) ON DELETE CASCADE;

--
-- Constraints for table `reuseoutlets_edits`
--
ALTER TABLE `reuseoutlets_edits`
  ADD CONSTRAINT `reuseoutlets_edits_ibfk_1` FOREIGN KEY (`outletid`) REFERENCES `reuseoutlets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reuseoutlets_edits_ibfk_2` FOREIGN KEY (`editedby`) REFERENCES `facebook` (`fduniqueid`) ON DELETE CASCADE ON UPDATE NO ACTION;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `Delete FBUser` ON SCHEDULE EVERY 1 DAY STARTS '2015-09-25 06:12:52' ON COMPLETION NOT PRESERVE DISABLE ON SLAVE DO DELETE FROM users WHERE useremail LIKE 'FBUser%'$$

CREATE DEFINER=`root`@`localhost` EVENT `Missing digests` ON SCHEDULE EVERY 1 DAY STARTS '2016-04-08 06:00:00' ON COMPLETION PRESERVE DISABLE ON SLAVE DO INSERT IGNORE INTO digesttracking (SELECT fduniqueid, groupid, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '' FROM facebook INNER JOIN users ON facebook.email = users.useremail AND deletedfromyahoo = 0 AND digest = 1 WHERE fduniqueid NOT IN (SELECT uniqueid FROM digesttracking))$$

CREATE DEFINER=`root`@`localhost` EVENT `Fix groups facebookpage htpss` ON SCHEDULE EVERY 1 DAY STARTS '2016-04-16 04:00:00' ON COMPLETION PRESERVE DISABLE ON SLAVE DO UPDATE `groups`
SET `facebookpage`=CONCAT('https',SUBSTRING(`facebookpage`,7))
WHERE `facebookpage` LIKE 'httpss%'$$

DELIMITER ;
--
-- Database: `statistics`
--

-- --------------------------------------------------------

--
-- Table structure for table `freecycleuk`
--

CREATE TABLE IF NOT EXISTS `freecycleuk` (
  `Groupurl` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE IF NOT EXISTS `groups` (
  `groupid` int(11) NOT NULL AUTO_INCREMENT,
  `groupalias` varchar(255) NOT NULL,
  `groupURL` varchar(255) NOT NULL,
  PRIMARY KEY (`groupid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1265 ;

-- --------------------------------------------------------

--
-- Table structure for table `groups_membercounts`
--

CREATE TABLE IF NOT EXISTS `groups_membercounts` (
  `groups_membercountid` int(11) NOT NULL AUTO_INCREMENT,
  `groupid` int(11) NOT NULL,
  `when` datetime NOT NULL,
  `membercount` int(11) NOT NULL,
  PRIMARY KEY (`groups_membercountid`),
  KEY `groupid` (`groupid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=157029 ;

-- --------------------------------------------------------

--
-- Table structure for table `groups_messagecounts`
--

CREATE TABLE IF NOT EXISTS `groups_messagecounts` (
  `groups_messagecountid` int(11) NOT NULL AUTO_INCREMENT,
  `groupid` int(11) NOT NULL,
  `year_month` date NOT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY (`groups_messagecountid`),
  KEY `groupid` (`groupid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10708889 ;

-- --------------------------------------------------------

--
-- Table structure for table `groups_old`
--

CREATE TABLE IF NOT EXISTS `groups_old` (
  `groupname` varchar(255) NOT NULL,
  `Republisher` text NOT NULL,
  `Chaseup` text NOT NULL,
  `MemberApproval` text NOT NULL,
  `1-1-02` int(11) NOT NULL,
  `Feb-2002` int(11) NOT NULL,
  `Mar-2002` int(11) NOT NULL,
  `Apr-2002` int(11) NOT NULL,
  `May-2002` int(11) NOT NULL,
  `Jun-2002` int(11) NOT NULL,
  `Jul-2002` int(11) NOT NULL,
  `Aug-2002` int(11) NOT NULL,
  `Sep-2002` int(11) NOT NULL,
  `Oct-2002` int(11) NOT NULL,
  `Nov-2002` int(11) NOT NULL,
  `Dec-2002` int(11) NOT NULL,
  `Jan-2003` int(11) NOT NULL,
  `Feb-2003` int(11) NOT NULL,
  `Mar-2003` int(11) NOT NULL,
  `Apr-2003` int(11) NOT NULL,
  `May-2003` int(11) NOT NULL,
  `Jun-2003` int(11) NOT NULL,
  `Jul-2003` int(11) NOT NULL,
  `Aug-2003` int(11) NOT NULL,
  `Sep-2003` int(11) NOT NULL,
  `Oct-2003` int(11) NOT NULL,
  `Nov-2003` int(11) NOT NULL,
  `Dec-2003` int(11) NOT NULL,
  `Jan-2004` int(11) NOT NULL,
  `Feb-2004` int(11) NOT NULL,
  `Mar-2004` int(11) NOT NULL,
  `Apr-2004` int(11) NOT NULL,
  `May-2004` int(11) NOT NULL,
  `Jun-2004` int(11) NOT NULL,
  `Jul-2004` int(11) NOT NULL,
  `Aug-2004` int(11) NOT NULL,
  `Sep-2004` int(11) NOT NULL,
  `Oct-2004` int(11) NOT NULL,
  `Nov-2004` int(11) NOT NULL,
  `Dec-2004` int(11) NOT NULL,
  `Jan-2005` int(11) NOT NULL,
  `Feb-2005` int(11) NOT NULL,
  `Mar-2005` int(11) NOT NULL,
  `Apr-2005` int(11) NOT NULL,
  `May-2005` int(11) NOT NULL,
  `Jun-2005` int(11) NOT NULL,
  `Jul-2005` int(11) NOT NULL,
  `Aug-2005` int(11) NOT NULL,
  `Sep-2005` int(11) NOT NULL,
  `Oct-2005` int(11) NOT NULL,
  `Nov-2005` int(11) NOT NULL,
  `Dec-2005` int(11) NOT NULL,
  `Jan-2006` int(11) NOT NULL,
  `Feb-2006` int(11) NOT NULL,
  `Mar-2006` int(11) NOT NULL,
  `Apr-2006` int(11) NOT NULL,
  `May-2006` int(11) NOT NULL,
  `Jun-2006` int(11) NOT NULL,
  `Jul-2006` int(11) NOT NULL,
  `Aug-2006` int(11) NOT NULL,
  `Sep-2006` int(11) NOT NULL,
  `Oct-2006` int(11) NOT NULL,
  `Nov-2006` int(11) NOT NULL,
  `Dec-2006` int(11) NOT NULL,
  `Jan-2007` int(11) NOT NULL,
  `Feb-2007` int(11) NOT NULL,
  `Mar-2007` int(11) NOT NULL,
  `Apr-2007` int(11) NOT NULL,
  `May-2007` int(11) NOT NULL,
  `Jun-2007` int(11) NOT NULL,
  `Jul-2007` int(11) NOT NULL,
  `Aug-2007` int(11) NOT NULL,
  `Sep-2007` int(11) NOT NULL,
  `Oct-2007` int(11) NOT NULL,
  `Nov-2007` int(11) NOT NULL,
  `Dec-2007` int(11) NOT NULL,
  `Jan-2008` int(11) NOT NULL,
  `Feb-2008` int(11) NOT NULL,
  `Mar-2008` int(11) NOT NULL,
  `Apr--2008` int(11) NOT NULL,
  `May-2008` int(11) NOT NULL,
  `Jun-2008` int(11) NOT NULL,
  `Jul-2008` int(11) NOT NULL,
  `Aug-2008` int(11) NOT NULL,
  `Sep-2008` int(11) NOT NULL,
  `Oct-2008` int(11) NOT NULL,
  `Nov-2008` int(11) NOT NULL,
  `Dec-2008` int(11) NOT NULL,
  `Jan-2009` int(11) NOT NULL,
  `Feb-2009` int(11) NOT NULL,
  `Mar-2009` int(11) NOT NULL,
  `Apr-2009` int(11) NOT NULL,
  `May-2009` int(11) NOT NULL,
  `Jun-2009` int(11) NOT NULL,
  `Jul-2009` int(11) NOT NULL,
  `Aug-2009` int(11) NOT NULL,
  `Sep-2009` int(11) NOT NULL,
  `Oct-2009` int(11) NOT NULL,
  `Nov-2009` int(11) NOT NULL,
  `Dec-2009` int(11) NOT NULL,
  `Jan-2010` int(11) NOT NULL,
  `Feb-2010` int(11) NOT NULL,
  `Mar-2010` int(11) NOT NULL,
  `Apr-2010` int(11) NOT NULL,
  `May-2010` int(11) NOT NULL,
  `Jun-2010` int(11) NOT NULL,
  `Jul-2010` int(11) NOT NULL,
  `Aug-2010` int(11) NOT NULL,
  `Sep-2010` int(11) NOT NULL,
  `Oct-2010` int(11) NOT NULL,
  `Nov-2010` int(11) NOT NULL,
  `Dec-2010` int(11) NOT NULL,
  `Jan-2011` int(11) NOT NULL,
  `Feb-2011` int(11) NOT NULL,
  `Mar-2011` int(11) NOT NULL,
  `Apr-2011` int(11) NOT NULL,
  `May-2011` int(11) NOT NULL,
  `Jun-2011` int(11) NOT NULL,
  `Jul-2011` int(11) NOT NULL,
  `Aug-2011` int(11) NOT NULL,
  `Sep-2011` int(11) NOT NULL,
  `Oct-2011` int(11) NOT NULL,
  `Nov-2011` int(11) NOT NULL,
  `Dec-2011` int(11) NOT NULL,
  PRIMARY KEY (`groupname`),
  KEY `groupid` (`groupname`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
--
-- Database: `surveys`
--

-- --------------------------------------------------------

--
-- Table structure for table `lime_answers`
--

CREATE TABLE IF NOT EXISTS `lime_answers` (
  `qid` int(11) NOT NULL DEFAULT '0',
  `code` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `answer` text COLLATE utf8_unicode_ci NOT NULL,
  `default_value` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N',
  `assessment_value` int(11) NOT NULL DEFAULT '0',
  `sortorder` int(11) NOT NULL,
  `language` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  PRIMARY KEY (`qid`,`code`,`language`),
  KEY `answers_idx2` (`sortorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_assessments`
--

CREATE TABLE IF NOT EXISTS `lime_assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL DEFAULT '0',
  `scope` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `gid` int(11) NOT NULL DEFAULT '0',
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `minimum` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `maximum` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `language` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  PRIMARY KEY (`id`,`language`),
  KEY `assessments_idx2` (`sid`),
  KEY `assessments_idx3` (`gid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `lime_conditions`
--

CREATE TABLE IF NOT EXISTS `lime_conditions` (
  `cid` int(11) NOT NULL AUTO_INCREMENT,
  `qid` int(11) NOT NULL DEFAULT '0',
  `scenario` int(11) NOT NULL DEFAULT '1',
  `cqid` int(11) NOT NULL DEFAULT '0',
  `cfieldname` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `method` char(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`cid`),
  KEY `conditions_idx2` (`qid`),
  KEY `conditions_idx3` (`cqid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `lime_groups`
--

CREATE TABLE IF NOT EXISTS `lime_groups` (
  `gid` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL DEFAULT '0',
  `group_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `group_order` int(11) NOT NULL DEFAULT '0',
  `description` text COLLATE utf8_unicode_ci,
  `language` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  PRIMARY KEY (`gid`,`language`),
  KEY `groups_idx2` (`sid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=13 ;

-- --------------------------------------------------------

--
-- Table structure for table `lime_labels`
--

CREATE TABLE IF NOT EXISTS `lime_labels` (
  `lid` int(11) NOT NULL DEFAULT '0',
  `code` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `title` text COLLATE utf8_unicode_ci,
  `sortorder` int(11) NOT NULL,
  `assessment_value` int(11) NOT NULL DEFAULT '0',
  `language` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  PRIMARY KEY (`lid`,`sortorder`,`language`),
  KEY `ixcode` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_labelsets`
--

CREATE TABLE IF NOT EXISTS `lime_labelsets` (
  `lid` int(11) NOT NULL AUTO_INCREMENT,
  `label_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `languages` varchar(200) COLLATE utf8_unicode_ci DEFAULT 'en',
  PRIMARY KEY (`lid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Table structure for table `lime_old_survey_16164_20110212234127`
--

CREATE TABLE IF NOT EXISTS `lime_old_survey_16164_20110212234127` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `submitdate` datetime DEFAULT NULL,
  `lastpage` int(11) DEFAULT NULL,
  `startlanguage` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(36) COLLATE utf8_unicode_ci DEFAULT NULL,
  `16164X10X29` text COLLATE utf8_unicode_ci,
  `16164X10X30` text COLLATE utf8_unicode_ci,
  `16164X10X2801` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `16164X10X2802` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `16164X10X2803` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `16164X10X2804` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `16164X10X2805` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `16164X10X2806` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `16164X10X2807` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `16164X10X2808` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=161 ;

-- --------------------------------------------------------

--
-- Table structure for table `lime_old_survey_58653_20110104081741`
--

CREATE TABLE IF NOT EXISTS `lime_old_survey_58653_20110104081741` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `submitdate` datetime DEFAULT NULL,
  `lastpage` int(11) DEFAULT NULL,
  `startlanguage` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(36) COLLATE utf8_unicode_ci DEFAULT NULL,
  `58653X5X16` text COLLATE utf8_unicode_ci,
  `58653X5X17` text COLLATE utf8_unicode_ci,
  `58653X5X18` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `58653X5X19` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `58653X5X20` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `58653X5X21` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `58653X5X22` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `58653X5X22comment` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=112 ;

-- --------------------------------------------------------

--
-- Table structure for table `lime_old_survey_82492_20100819231215`
--

CREATE TABLE IF NOT EXISTS `lime_old_survey_82492_20100819231215` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `submitdate` datetime DEFAULT NULL,
  `lastpage` int(11) DEFAULT NULL,
  `startlanguage` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `datestamp` datetime NOT NULL,
  `startdate` datetime NOT NULL,
  `ipaddr` text COLLATE utf8_unicode_ci,
  `refurl` text COLLATE utf8_unicode_ci,
  `82492X4X12` text COLLATE utf8_unicode_ci,
  `82492X4X13` text COLLATE utf8_unicode_ci,
  `82492X4X151` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `82492X4X152` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `82492X4X153` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `82492X4X154` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=105 ;

-- --------------------------------------------------------

--
-- Table structure for table `lime_old_survey_87542_20100627231254`
--

CREATE TABLE IF NOT EXISTS `lime_old_survey_87542_20100627231254` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `submitdate` datetime DEFAULT NULL,
  `lastpage` int(11) DEFAULT NULL,
  `startlanguage` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `87542X3X10` text COLLATE utf8_unicode_ci,
  `87542X3X11` text COLLATE utf8_unicode_ci,
  `87542X3X8` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `87542X3X9` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=164 ;

-- --------------------------------------------------------

--
-- Table structure for table `lime_question_attributes`
--

CREATE TABLE IF NOT EXISTS `lime_question_attributes` (
  `qaid` int(11) NOT NULL AUTO_INCREMENT,
  `qid` int(11) NOT NULL DEFAULT '0',
  `attribute` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`qaid`),
  KEY `question_attributes_idx2` (`qid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=573 ;

-- --------------------------------------------------------

--
-- Table structure for table `lime_questions`
--

CREATE TABLE IF NOT EXISTS `lime_questions` (
  `qid` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL DEFAULT '0',
  `gid` int(11) NOT NULL DEFAULT '0',
  `type` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'T',
  `title` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `question` text COLLATE utf8_unicode_ci NOT NULL,
  `preg` text COLLATE utf8_unicode_ci,
  `help` text COLLATE utf8_unicode_ci,
  `other` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N',
  `mandatory` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lid` int(11) NOT NULL DEFAULT '0',
  `lid1` int(11) NOT NULL DEFAULT '0',
  `question_order` int(11) NOT NULL,
  `language` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  PRIMARY KEY (`qid`,`language`),
  KEY `questions_idx2` (`sid`),
  KEY `questions_idx3` (`gid`),
  KEY `questions_idx4` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=35 ;

-- --------------------------------------------------------

--
-- Table structure for table `lime_quota`
--

CREATE TABLE IF NOT EXISTS `lime_quota` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `qlimit` int(8) DEFAULT NULL,
  `action` int(2) DEFAULT NULL,
  `active` int(1) NOT NULL DEFAULT '1',
  `autoload_url` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `quota_idx2` (`sid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `lime_quota_languagesettings`
--

CREATE TABLE IF NOT EXISTS `lime_quota_languagesettings` (
  `quotals_id` int(11) NOT NULL AUTO_INCREMENT,
  `quotals_quota_id` int(11) NOT NULL DEFAULT '0',
  `quotals_language` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  `quotals_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `quotals_message` text COLLATE utf8_unicode_ci NOT NULL,
  `quotals_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `quotals_urldescrip` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`quotals_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `lime_quota_members`
--

CREATE TABLE IF NOT EXISTS `lime_quota_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) DEFAULT NULL,
  `qid` int(11) DEFAULT NULL,
  `quota_id` int(11) DEFAULT NULL,
  `code` varchar(11) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sid` (`sid`,`qid`,`quota_id`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `lime_saved_control`
--

CREATE TABLE IF NOT EXISTS `lime_saved_control` (
  `scid` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL DEFAULT '0',
  `srid` int(11) NOT NULL DEFAULT '0',
  `identifier` text COLLATE utf8_unicode_ci NOT NULL,
  `access_code` text COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(320) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ip` text COLLATE utf8_unicode_ci NOT NULL,
  `saved_thisstep` text COLLATE utf8_unicode_ci NOT NULL,
  `status` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `saved_date` datetime NOT NULL,
  `refurl` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`scid`),
  KEY `saved_control_idx2` (`sid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `lime_settings_global`
--

CREATE TABLE IF NOT EXISTS `lime_settings_global` (
  `stg_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `stg_value` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`stg_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_surveys`
--

CREATE TABLE IF NOT EXISTS `lime_surveys` (
  `sid` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `admin` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `active` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N',
  `expires` datetime DEFAULT NULL,
  `startdate` datetime DEFAULT NULL,
  `adminemail` varchar(320) COLLATE utf8_unicode_ci DEFAULT NULL,
  `private` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `faxto` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `format` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `template` varchar(100) COLLATE utf8_unicode_ci DEFAULT 'default',
  `language` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `additional_languages` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `datestamp` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `usecookie` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `notification` char(1) COLLATE utf8_unicode_ci DEFAULT '0',
  `allowregister` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `allowsave` char(1) COLLATE utf8_unicode_ci DEFAULT 'Y',
  `autonumber_start` bigint(11) DEFAULT '0',
  `autoredirect` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `allowprev` char(1) COLLATE utf8_unicode_ci DEFAULT 'Y',
  `printanswers` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `ipaddr` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `refurl` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `datecreated` date DEFAULT NULL,
  `publicstatistics` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `publicgraphs` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `listpublic` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `htmlemail` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `tokenanswerspersistence` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `assessments` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `usecaptcha` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `usetokens` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `bounce_email` varchar(320) COLLATE utf8_unicode_ci DEFAULT NULL,
  `attributedescriptions` text COLLATE utf8_unicode_ci,
  `emailresponseto` text COLLATE utf8_unicode_ci,
  `tokenlength` tinyint(2) DEFAULT '15',
  PRIMARY KEY (`sid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_surveys_languagesettings`
--

CREATE TABLE IF NOT EXISTS `lime_surveys_languagesettings` (
  `surveyls_survey_id` int(10) unsigned NOT NULL DEFAULT '0',
  `surveyls_language` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  `surveyls_title` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `surveyls_description` text COLLATE utf8_unicode_ci,
  `surveyls_welcometext` text COLLATE utf8_unicode_ci,
  `surveyls_endtext` text COLLATE utf8_unicode_ci,
  `surveyls_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `surveyls_urldescription` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `surveyls_email_invite_subj` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `surveyls_email_invite` text COLLATE utf8_unicode_ci,
  `surveyls_email_remind_subj` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `surveyls_email_remind` text COLLATE utf8_unicode_ci,
  `surveyls_email_register_subj` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `surveyls_email_register` text COLLATE utf8_unicode_ci,
  `surveyls_email_confirm_subj` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `surveyls_email_confirm` text COLLATE utf8_unicode_ci,
  `surveyls_dateformat` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`surveyls_survey_id`,`surveyls_language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_surveys_rights`
--

CREATE TABLE IF NOT EXISTS `lime_surveys_rights` (
  `sid` int(10) unsigned NOT NULL DEFAULT '0',
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `edit_survey_property` tinyint(1) NOT NULL DEFAULT '0',
  `define_questions` tinyint(1) NOT NULL DEFAULT '0',
  `browse_response` tinyint(1) NOT NULL DEFAULT '0',
  `export` tinyint(1) NOT NULL DEFAULT '0',
  `delete_survey` tinyint(1) NOT NULL DEFAULT '0',
  `activate_survey` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sid`,`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_templates`
--

CREATE TABLE IF NOT EXISTS `lime_templates` (
  `folder` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `creator` int(11) NOT NULL,
  PRIMARY KEY (`folder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_templates_rights`
--

CREATE TABLE IF NOT EXISTS `lime_templates_rights` (
  `uid` int(11) NOT NULL,
  `folder` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `use` int(1) NOT NULL,
  PRIMARY KEY (`uid`,`folder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_tokens_97117`
--

CREATE TABLE IF NOT EXISTS `lime_tokens_97117` (
  `tid` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` text COLLATE utf8_unicode_ci,
  `emailstatus` text COLLATE utf8_unicode_ci,
  `token` varchar(36) COLLATE utf8_unicode_ci DEFAULT NULL,
  `language` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sent` varchar(17) COLLATE utf8_unicode_ci DEFAULT 'N',
  `remindersent` varchar(17) COLLATE utf8_unicode_ci DEFAULT 'N',
  `remindercount` int(11) DEFAULT '0',
  `completed` varchar(17) COLLATE utf8_unicode_ci DEFAULT 'N',
  `validfrom` datetime DEFAULT NULL,
  `validuntil` datetime DEFAULT NULL,
  `mpid` int(11) DEFAULT NULL,
  PRIMARY KEY (`tid`),
  KEY `lime_tokens_97117_idx` (`token`),
  KEY `idx_lime_tokens_97117_efl` (`email`(120),`firstname`,`lastname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `lime_user_groups`
--

CREATE TABLE IF NOT EXISTS `lime_user_groups` (
  `ugid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `owner_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ugid`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Table structure for table `lime_user_in_groups`
--

CREATE TABLE IF NOT EXISTS `lime_user_in_groups` (
  `ugid` int(10) unsigned NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ugid`,`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_users`
--

CREATE TABLE IF NOT EXISTS `lime_users` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `users_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `password` blob NOT NULL,
  `full_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  `lang` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(320) COLLATE utf8_unicode_ci DEFAULT NULL,
  `create_survey` tinyint(1) NOT NULL DEFAULT '0',
  `create_user` tinyint(1) NOT NULL DEFAULT '0',
  `delete_user` tinyint(1) NOT NULL DEFAULT '0',
  `superadmin` tinyint(1) NOT NULL DEFAULT '0',
  `configurator` tinyint(1) NOT NULL DEFAULT '0',
  `manage_template` tinyint(1) NOT NULL DEFAULT '0',
  `manage_label` tinyint(1) NOT NULL DEFAULT '0',
  `htmleditormode` varchar(7) COLLATE utf8_unicode_ci DEFAULT 'default',
  `one_time_pw` blob,
  `dateformat` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `users_name` (`users_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5 ;
--
-- Database: `sys`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `create_synonym_db`( IN in_db_name VARCHAR(64),  IN in_synonym VARCHAR(64) )
MODIFIES SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Takes a source database name and synonym name, and then creates the \n synonym database with views that point to all of the tables within\n the source database.\n \n Useful for creating a "ps" synonym for "performance_schema",\n or "is" instead of "information_schema", for example.\n \n Parameters\n \n in_db_name (VARCHAR(64)):\n The database name that you would like to create a synonym for.\n in_synonym (VARCHAR(64)):\n The database synonym name.\n \n Example\n \n mysql> SHOW DATABASES;\n +--------------------+\n | Database           |\n +--------------------+\n | information_schema |\n | mysql              |\n | performance_schema |\n | sys                |\n | test               |\n +--------------------+\n 5 rows in set (0.00 sec)\n \n mysql> CALL sys.create_synonym_db(''performance_schema'', ''ps'');\n +---------------------------------------+\n | summary                               |\n +---------------------------------------+\n | Created 74 views in the `ps` database |\n +---------------------------------------+\n 1 row in set (8.57 sec)\n \n Query OK, 0 rows affected (8.57 sec)\n \n mysql> SHOW DATABASES;\n +--------------------+\n | Database           |\n +--------------------+\n | information_schema |\n | mysql              |\n | performance_schema |\n | ps                 |\n | sys                |\n | test               |\n +--------------------+\n 6 rows in set (0.00 sec)\n \n mysql> SHOW FULL TABLES FROM ps;\n +------------------------------------------------------+------------+\n | Tables_in_ps                                         | Table_type |\n +------------------------------------------------------+------------+\n | accounts                                             | VIEW       |\n | cond_instances                                       | VIEW       |\n | events_stages_current                                | VIEW       |\n | events_stages_history                                | VIEW       |\n ...\n '
  BEGIN DECLARE v_done bool DEFAULT FALSE; DECLARE v_db_name_check VARCHAR(64); DECLARE v_db_err_msg TEXT; DECLARE v_table VARCHAR(64); DECLARE v_views_created INT DEFAULT 0;  DECLARE db_doesnt_exist CONDITION FOR SQLSTATE '42000'; DECLARE db_name_exists CONDITION FOR SQLSTATE 'HY000';  DECLARE c_table_names CURSOR FOR  SELECT TABLE_NAME  FROM INFORMATION_SCHEMA.TABLES  WHERE TABLE_SCHEMA = in_db_name;  DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;  SELECT SCHEMA_NAME INTO v_db_name_check FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = in_db_name;  IF v_db_name_check IS NULL THEN SET v_db_err_msg = CONCAT('Unknown database ', in_db_name); SIGNAL SQLSTATE 'HY000' SET MESSAGE_TEXT = v_db_err_msg; END IF;  SELECT SCHEMA_NAME INTO v_db_name_check FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = in_synonym;  IF v_db_name_check = in_synonym THEN SET v_db_err_msg = CONCAT('Can\'t create database ', in_synonym, '; database exists'); SIGNAL SQLSTATE 'HY000' SET MESSAGE_TEXT = v_db_err_msg; END IF;  SET @create_db_stmt := CONCAT('CREATE DATABASE ', sys.quote_identifier(in_synonym)); PREPARE create_db_stmt FROM @create_db_stmt; EXECUTE create_db_stmt; DEALLOCATE PREPARE create_db_stmt;  SET v_done = FALSE; OPEN c_table_names; c_table_names: LOOP FETCH c_table_names INTO v_table; IF v_done THEN LEAVE c_table_names; END IF;  SET @create_view_stmt = CONCAT( 'CREATE SQL SECURITY INVOKER VIEW ', sys.quote_identifier(in_synonym), '.', sys.quote_identifier(v_table), ' AS SELECT * FROM ', sys.quote_identifier(in_db_name), '.', sys.quote_identifier(v_table) ); PREPARE create_view_stmt FROM @create_view_stmt; EXECUTE create_view_stmt; DEALLOCATE PREPARE create_view_stmt;  SET v_views_created = v_views_created + 1; END LOOP; CLOSE c_table_names;  SELECT CONCAT( 'Created ', v_views_created, ' view', IF(v_views_created != 1, 's', ''), ' in the ', sys.quote_identifier(in_synonym), ' database' ) AS summary;  END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `diagnostics`( IN in_max_runtime int unsigned, IN in_interval int unsigned, IN in_auto_config enum ('current', 'medium', 'full') )
READS SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Create a report of the current status of the server for diagnostics purposes. Data collected includes (some items depends on versions and settings):\n \n * The GLOBAL VARIABLES\n * Several sys schema views including metrics or equivalent (depending on version and settings)\n * Queries in the 95th percentile\n * Several ndbinfo views for MySQL Cluster\n * Replication (both master and slave) information.\n \n Some of the sys schema views are calculated as initial (optional), overall, delta:\n \n * The initial view is the content of the view at the start of this procedure.\n This output will be the same as the the start values used for the delta view.\n The initial view is included if @sys.diagnostics.include_raw = ''ON''.\n * The overall view is the content of the view at the end of this procedure.\n This output is the same as the end values used for the delta view.\n The overall view is always included.\n * The delta view is the difference from the beginning to the end. Note that for min and max values\n they are simply the min or max value from the end view respectively, so does not necessarily reflect\n the minimum/maximum value in the monitored period.\n Note: except for the metrics views the delta is only calculation between the first and last outputs.\n \n Requires the SUPER privilege for "SET sql_log_bin = 0;".\n \n Versions supported:\n * MySQL 5.6: 5.6.10 and later\n * MySQL 5.7: 5.7.9 and later\n \n Parameters\n \n in_max_runtime (INT UNSIGNED):\n The maximum time to keep collecting data.\n Use NULL to get the default which is 60 seconds, otherwise enter a value greater than 0.\n in_interval (INT UNSIGNED):\n How long to sleep between data collections.\n Use NULL to get the default which is 30 seconds, otherwise enter a value greater than 0.\n in_auto_config (ENUM(''current'', ''medium'', ''full''))\n Automatically enable Performance Schema instruments and consumers.\n NOTE: The more that are enabled, the more impact on the performance.\n Supported values are:\n * current - use the current settings.\n * medium - enable some settings.\n * full - enables all settings. This will have a big impact on the\n performance - be careful using this option.\n If another setting the ''current'' is chosen, the current settings\n are restored at the end of the procedure.\n \n \n Configuration Options\n \n sys.diagnostics.allow_i_s_tables\n Specifies whether it is allowed to do table scan queries on information_schema.TABLES. This can be expensive if there\n are many tables. Set to ''ON'' to allow, ''OFF'' to not allow.\n Default is ''OFF''.\n \n sys.diagnostics.include_raw\n Set to ''ON'' to include the raw data (e.g. the original output of "SELECT * FROM sys.metrics").\n Use this to get the initial values of the various views.\n Default is ''OFF''.\n \n sys.statement_truncate_len\n How much of queries in the process list output to include.\n Default is 64.\n \n sys.debug\n Whether to provide debugging output.\n Default is ''OFF''. Set to ''ON'' to include.\n \n \n Example\n \n To create a report and append it to the file diag.out:\n \n mysql> TEE diag.out;\n mysql> CALL sys.diagnostics(120, 30, ''current'');\n ...\n mysql> NOTEE;\n '
  BEGIN DECLARE v_start, v_runtime, v_iter_start, v_sleep DECIMAL(20,2) DEFAULT 0.0; DECLARE v_has_innodb, v_has_ndb, v_has_ps, v_has_replication, v_has_ps_replication VARCHAR(8) CHARSET utf8 DEFAULT 'NO'; DECLARE v_this_thread_enabled, v_has_ps_vars, v_has_metrics ENUM('YES', 'NO'); DECLARE v_table_name, v_banner VARCHAR(64) CHARSET utf8; DECLARE v_sql_status_summary_select, v_sql_status_summary_delta, v_sql_status_summary_from, v_no_delta_names TEXT; DECLARE v_output_time, v_output_time_prev DECIMAL(20,3) UNSIGNED; DECLARE v_output_count, v_count, v_old_group_concat_max_len INT UNSIGNED DEFAULT 0; DECLARE v_status_summary_width TINYINT UNSIGNED DEFAULT 50; DECLARE v_done BOOLEAN DEFAULT FALSE; DECLARE c_ndbinfo CURSOR FOR SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'ndbinfo' AND TABLE_NAME NOT IN ( 'blocks', 'config_params', 'dict_obj_types', 'disk_write_speed_base', 'memory_per_fragment', 'memoryusage', 'operations_per_fragment', 'threadblocks' ); DECLARE c_sysviews_w_delta CURSOR FOR SELECT table_name FROM tmp_sys_views_delta ORDER BY table_name;  DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;  SELECT INSTRUMENTED INTO v_this_thread_enabled FROM performance_schema.threads WHERE PROCESSLIST_ID = CONNECTION_ID(); IF (v_this_thread_enabled = 'YES') THEN CALL sys.ps_setup_disable_thread(CONNECTION_ID()); END IF;  IF (in_max_runtime < in_interval) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'in_max_runtime must be greater than or equal to in_interval'; END IF; IF (in_max_runtime = 0) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'in_max_runtime must be greater than 0'; END IF; IF (in_interval = 0) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'in_interval must be greater than 0'; END IF;  IF (@sys.diagnostics.allow_i_s_tables IS NULL) THEN SET @sys.diagnostics.allow_i_s_tables = sys.sys_get_config('diagnostics.allow_i_s_tables', 'OFF'); END IF; IF (@sys.diagnostics.include_raw IS NULL) THEN SET @sys.diagnostics.include_raw      = sys.sys_get_config('diagnostics.include_raw'     , 'OFF'); END IF; IF (@sys.debug IS NULL) THEN SET @sys.debug                        = sys.sys_get_config('debug'                       , 'OFF'); END IF; IF (@sys.statement_truncate_len IS NULL) THEN SET @sys.statement_truncate_len       = sys.sys_get_config('statement_truncate_len'      , '64' ); END IF;  SET @log_bin := @@sql_log_bin; IF (@log_bin = 1) THEN SET sql_log_bin = 0; END IF;  SET v_no_delta_names = CONCAT('s%{COUNT}.Variable_name NOT IN (', '''innodb_buffer_pool_pages_total'', ', '''innodb_page_size'', ', '''last_query_cost'', ', '''last_query_partial_plans'', ', '''qcache_total_blocks'', ', '''slave_last_heartbeat'', ', '''ssl_ctx_verify_depth'', ', '''ssl_ctx_verify_mode'', ', '''ssl_session_cache_size'', ', '''ssl_verify_depth'', ', '''ssl_verify_mode'', ', '''ssl_version'', ', '''buffer_flush_lsn_avg_rate'', ', '''buffer_flush_pct_for_dirty'', ', '''buffer_flush_pct_for_lsn'', ', '''buffer_pool_pages_total'', ', '''lock_row_lock_time_avg'', ', '''lock_row_lock_time_max'', ', '''innodb_page_size''', ')');  IF (in_auto_config <> 'current') THEN IF (@sys.debug = 'ON') THEN SELECT CONCAT('Updating Performance Schema configuration to ', in_auto_config) AS 'Debug'; END IF; CALL sys.ps_setup_save(0);  IF (in_auto_config = 'medium') THEN UPDATE performance_schema.setup_consumers SET ENABLED = 'YES' WHERE NAME NOT LIKE '%\_history%';  UPDATE performance_schema.setup_instruments SET ENABLED = 'YES', TIMED   = 'YES' WHERE NAME NOT LIKE 'wait/synch/%'; ELSEIF (in_auto_config = 'full') THEN UPDATE performance_schema.setup_consumers SET ENABLED = 'YES';  UPDATE performance_schema.setup_instruments SET ENABLED = 'YES', TIMED   = 'YES'; END IF;  UPDATE performance_schema.threads SET INSTRUMENTED = 'YES' WHERE PROCESSLIST_ID <> CONNECTION_ID(); END IF;  SET v_start        = UNIX_TIMESTAMP(NOW(2)), in_interval    = IFNULL(in_interval, 30), in_max_runtime = IFNULL(in_max_runtime, 60);  SET v_banner = REPEAT( '-', LEAST( GREATEST( 36, CHAR_LENGTH(VERSION()), CHAR_LENGTH(@@global.version_comment), CHAR_LENGTH(@@global.version_compile_os), CHAR_LENGTH(@@global.version_compile_machine), CHAR_LENGTH(@@global.socket), CHAR_LENGTH(@@global.datadir) ), 64 ) ); SELECT 'Hostname' AS 'Name', @@global.hostname AS 'Value' UNION ALL SELECT 'Port' AS 'Name', @@global.port AS 'Value' UNION ALL SELECT 'Socket' AS 'Name', @@global.socket AS 'Value' UNION ALL SELECT 'Datadir' AS 'Name', @@global.datadir AS 'Value' UNION ALL SELECT 'Server UUID' AS 'Name', @@global.server_uuid AS 'Value' UNION ALL SELECT REPEAT('-', 23) AS 'Name', v_banner AS 'Value' UNION ALL SELECT 'MySQL Version' AS 'Name', VERSION() AS 'Value' UNION ALL SELECT 'Sys Schema Version' AS 'Name', (SELECT sys_version FROM sys.version) AS 'Value' UNION ALL SELECT 'Version Comment' AS 'Name', @@global.version_comment AS 'Value' UNION ALL SELECT 'Version Compile OS' AS 'Name', @@global.version_compile_os AS 'Value' UNION ALL SELECT 'Version Compile Machine' AS 'Name', @@global.version_compile_machine AS 'Value' UNION ALL SELECT REPEAT('-', 23) AS 'Name', v_banner AS 'Value' UNION ALL SELECT 'UTC Time' AS 'Name', UTC_TIMESTAMP() AS 'Value' UNION ALL SELECT 'Local Time' AS 'Name', NOW() AS 'Value' UNION ALL SELECT 'Time Zone' AS 'Name', @@global.time_zone AS 'Value' UNION ALL SELECT 'System Time Zone' AS 'Name', @@global.system_time_zone AS 'Value' UNION ALL SELECT 'Time Zone Offset' AS 'Name', TIMEDIFF(NOW(), UTC_TIMESTAMP()) AS 'Value';  SET v_has_innodb         = IFNULL((SELECT SUPPORT FROM information_schema.ENGINES WHERE ENGINE = 'InnoDB'), 'NO'), v_has_ndb            = IFNULL((SELECT SUPPORT FROM information_schema.ENGINES WHERE ENGINE = 'NDBCluster'), 'NO'), v_has_ps             = IFNULL((SELECT SUPPORT FROM information_schema.ENGINES WHERE ENGINE = 'PERFORMANCE_SCHEMA'), 'NO'), v_has_ps_replication = IF(v_has_ps = 'YES' AND EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'performance_schema' AND TABLE_NAME = 'replication_applier_status'), 'YES', 'NO' ), v_has_replication    =  IF(v_has_ps_replication = 'YES', IF((SELECT COUNT(*) FROM performance_schema.replication_connection_status) > 0, 'YES', 'NO'), IF(@@master_info_repository = 'TABLE', IF((SELECT COUNT(*) FROM mysql.slave_master_info) > 0, 'YES', 'NO'), IF(@@relay_log_info_repository = 'TABLE', IF((SELECT COUNT(*) FROM mysql.slave_relay_log_info) > 0, 'YES', 'NO'), 'MAYBE')) ) , v_has_metrics        = IF(v_has_ps = 'YES' OR (sys.version_major() = 5 AND sys.version_minor() = 6), 'YES', 'NO'), v_has_ps_vars        = 'NO';   SET v_has_ps_vars = IF(@@global.show_compatibility_56, 'NO', 'YES');  SET v_has_ps_vars = 'YES';  IF (@sys.debug = 'ON') THEN SELECT v_has_innodb AS 'Has_InnoDB', v_has_ndb AS 'Has_NDBCluster', v_has_ps AS 'Has_Performance_Schema', v_has_ps_vars AS 'Has_P_S_SHOW_Variables', v_has_metrics AS 'Has_metrics', v_has_ps_replication 'AS Has_P_S_Replication', v_has_replication AS 'Has_Replication'; END IF;  IF (v_has_innodb IN ('DEFAULT', 'YES')) THEN SET @sys.diagnostics.sql = 'SHOW ENGINE InnoDB STATUS'; PREPARE stmt_innodb_status FROM @sys.diagnostics.sql; END IF;  IF (v_has_ps = 'YES') THEN SET @sys.diagnostics.sql = 'SHOW ENGINE PERFORMANCE_SCHEMA STATUS'; PREPARE stmt_ps_status FROM @sys.diagnostics.sql; END IF;  IF (v_has_ndb IN ('DEFAULT', 'YES')) THEN SET @sys.diagnostics.sql = 'SHOW ENGINE NDBCLUSTER STATUS'; PREPARE stmt_ndbcluster_status FROM @sys.diagnostics.sql; END IF;  SET @sys.diagnostics.sql_gen_query_template = 'SELECT CONCAT( ''SELECT '', GROUP_CONCAT( CASE WHEN (SUBSTRING(TABLE_NAME, 3), COLUMN_NAME) IN ( (''io_global_by_file_by_bytes'', ''total''), (''io_global_by_wait_by_bytes'', ''total_requested'') ) THEN CONCAT(''sys.format_bytes('', COLUMN_NAME, '') AS '', COLUMN_NAME) WHEN SUBSTRING(COLUMN_NAME, -8) = ''_latency'' THEN CONCAT(''sys.format_time('', COLUMN_NAME, '') AS '', COLUMN_NAME) WHEN SUBSTRING(COLUMN_NAME, -7) = ''_memory'' OR SUBSTRING(COLUMN_NAME, -17) = ''_memory_allocated'' OR ((SUBSTRING(COLUMN_NAME, -5) = ''_read'' OR SUBSTRING(COLUMN_NAME, -8) = ''_written'' OR SUBSTRING(COLUMN_NAME, -6) = ''_write'') AND SUBSTRING(COLUMN_NAME, 1, 6) <> ''COUNT_'') THEN CONCAT(''sys.format_bytes('', COLUMN_NAME, '') AS '', COLUMN_NAME) ELSE COLUMN_NAME END ORDER BY ORDINAL_POSITION SEPARATOR '',\n       '' ), ''\n  FROM tmp_'', SUBSTRING(TABLE_NAME FROM 3), ''_%{OUTPUT}'' ) AS Query INTO @sys.diagnostics.sql_select FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ''sys'' AND TABLE_NAME = ? GROUP BY TABLE_NAME';  SET @sys.diagnostics.sql_gen_query_delta = 'SELECT CONCAT( ''SELECT '', GROUP_CONCAT( CASE WHEN FIND_IN_SET(COLUMN_NAME, diag.pk) THEN COLUMN_NAME WHEN diag.TABLE_NAME = ''io_global_by_file_by_bytes'' AND COLUMN_NAME = ''write_pct'' THEN CONCAT(''IFNULL(ROUND(100-(((e.total_read-IFNULL(s.total_read, 0))'', ''/NULLIF(((e.total_read-IFNULL(s.total_read, 0))+(e.total_written-IFNULL(s.total_written, 0))), 0))*100), 2), 0.00) AS '', COLUMN_NAME) WHEN (diag.TABLE_NAME, COLUMN_NAME) IN ( (''io_global_by_file_by_bytes'', ''total''), (''io_global_by_wait_by_bytes'', ''total_requested'') ) THEN CONCAT(''sys.format_bytes(e.'', COLUMN_NAME, ''-IFNULL(s.'', COLUMN_NAME, '', 0)) AS '', COLUMN_NAME) WHEN SUBSTRING(COLUMN_NAME, 1, 4) IN (''max_'', ''min_'') AND SUBSTRING(COLUMN_NAME, -8) = ''_latency'' THEN CONCAT(''sys.format_time(e.'', COLUMN_NAME, '') AS '', COLUMN_NAME) WHEN COLUMN_NAME = ''avg_latency'' THEN CONCAT(''sys.format_time((e.total_latency - IFNULL(s.total_latency, 0))'', ''/NULLIF(e.total - IFNULL(s.total, 0), 0)) AS '', COLUMN_NAME) WHEN SUBSTRING(COLUMN_NAME, -12) = ''_avg_latency'' THEN CONCAT(''sys.format_time((e.'', SUBSTRING(COLUMN_NAME FROM 1 FOR CHAR_LENGTH(COLUMN_NAME)-12), ''_latency - IFNULL(s.'', SUBSTRING(COLUMN_NAME FROM 1 FOR CHAR_LENGTH(COLUMN_NAME)-12), ''_latency, 0))'', ''/NULLIF(e.'', SUBSTRING(COLUMN_NAME FROM 1 FOR CHAR_LENGTH(COLUMN_NAME)-12), ''s - IFNULL(s.'', SUBSTRING(COLUMN_NAME FROM 1 FOR CHAR_LENGTH(COLUMN_NAME)-12), ''s, 0), 0)) AS '', COLUMN_NAME) WHEN SUBSTRING(COLUMN_NAME, -8) = ''_latency'' THEN CONCAT(''sys.format_time(e.'', COLUMN_NAME, '' - IFNULL(s.'', COLUMN_NAME, '', 0)) AS '', COLUMN_NAME) WHEN COLUMN_NAME IN (''avg_read'', ''avg_write'', ''avg_written'') THEN CONCAT(''sys.format_bytes(IFNULL((e.total_'', IF(COLUMN_NAME = ''avg_read'', ''read'', ''written''), ''-IFNULL(s.total_'', IF(COLUMN_NAME = ''avg_read'', ''read'', ''written''), '', 0))'', ''/NULLIF(e.count_'', IF(COLUMN_NAME = ''avg_read'', ''read'', ''write''), ''-IFNULL(s.count_'', IF(COLUMN_NAME = ''avg_read'', ''read'', ''write''), '', 0), 0), 0)) AS '', COLUMN_NAME) WHEN SUBSTRING(COLUMN_NAME, -7) = ''_memory'' OR SUBSTRING(COLUMN_NAME, -17) = ''_memory_allocated'' OR ((SUBSTRING(COLUMN_NAME, -5) = ''_read'' OR SUBSTRING(COLUMN_NAME, -8) = ''_written'' OR SUBSTRING(COLUMN_NAME, -6) = ''_write'') AND SUBSTRING(COLUMN_NAME, 1, 6) <> ''COUNT_'') THEN CONCAT(''sys.format_bytes(e.'', COLUMN_NAME, '' - IFNULL(s.'', COLUMN_NAME, '', 0)) AS '', COLUMN_NAME) ELSE CONCAT(''(e.'', COLUMN_NAME, '' - IFNULL(s.'', COLUMN_NAME, '', 0)) AS '', COLUMN_NAME) END ORDER BY ORDINAL_POSITION SEPARATOR '',\n       '' ), ''\n  FROM tmp_'', diag.TABLE_NAME, ''_end e LEFT OUTER JOIN tmp_'', diag.TABLE_NAME, ''_start s USING ('', diag.pk, '')'' ) AS Query INTO @sys.diagnostics.sql_select FROM tmp_sys_views_delta diag INNER JOIN information_schema.COLUMNS c ON c.TABLE_NAME = CONCAT(''x$'', diag.TABLE_NAME) WHERE c.TABLE_SCHEMA = ''sys'' AND diag.TABLE_NAME = ? GROUP BY diag.TABLE_NAME';  IF (v_has_ps = 'YES') THEN DROP TEMPORARY TABLE IF EXISTS tmp_sys_views_delta; CREATE TEMPORARY TABLE tmp_sys_views_delta ( TABLE_NAME varchar(64) NOT NULL, order_by text COMMENT 'ORDER BY clause for the initial and overall views', order_by_delta text COMMENT 'ORDER BY clause for the delta views', where_delta text COMMENT 'WHERE clause to use for delta views to only include rows with a "count" > 0', limit_rows int unsigned COMMENT 'The maximum number of rows to include for the view', pk varchar(128) COMMENT 'Used with the FIND_IN_SET() function so use comma separated list without whitespace', PRIMARY KEY (TABLE_NAME) );  IF (@sys.debug = 'ON') THEN SELECT 'Populating tmp_sys_views_delta' AS 'Debug'; END IF; INSERT INTO tmp_sys_views_delta VALUES ('host_summary'                       , '%{TABLE}.statement_latency DESC', '(e.statement_latency-IFNULL(s.statement_latency, 0)) DESC', '(e.statements - IFNULL(s.statements, 0)) > 0', NULL, 'host'), ('host_summary_by_file_io'            , '%{TABLE}.io_latency DESC', '(e.io_latency-IFNULL(s.io_latency, 0)) DESC', '(e.ios - IFNULL(s.ios, 0)) > 0', NULL, 'host'), ('host_summary_by_file_io_type'       , '%{TABLE}.host, %{TABLE}.total_latency DESC', 'e.host, (e.total_latency-IFNULL(s.total_latency, 0)) DESC', '(e.total - IFNULL(s.total, 0)) > 0', NULL, 'host,event_name'), ('host_summary_by_stages'             , '%{TABLE}.host, %{TABLE}.total_latency DESC', 'e.host, (e.total_latency-IFNULL(s.total_latency, 0)) DESC', '(e.total - IFNULL(s.total, 0)) > 0', NULL, 'host,event_name'), ('host_summary_by_statement_latency'  , '%{TABLE}.total_latency DESC', '(e.total_latency-IFNULL(s.total_latency, 0)) DESC', '(e.total - IFNULL(s.total, 0)) > 0', NULL, 'host'), ('host_summary_by_statement_type'     , '%{TABLE}.host, %{TABLE}.total_latency DESC', 'e.host, (e.total_latency-IFNULL(s.total_latency, 0)) DESC', '(e.total - IFNULL(s.total, 0)) > 0', NULL, 'host,statement'), ('io_by_thread_by_latency'            , '%{TABLE}.total_latency DESC', '(e.total_latency-IFNULL(s.total_latency, 0)) DESC', '(e.total - IFNULL(s.total, 0)) > 0', NULL, 'user,thread_id,processlist_id'), ('io_global_by_file_by_bytes'         , '%{TABLE}.total DESC', '(e.total-IFNULL(s.total, 0)) DESC', '(e.total - IFNULL(s.total, 0)) > 0', 100, 'file'), ('io_global_by_file_by_latency'       , '%{TABLE}.total_latency DESC', '(e.total_latency-IFNULL(s.total_latency, 0)) DESC', '(e.total - IFNULL(s.total, 0)) > 0', 100, 'file'), ('io_global_by_wait_by_bytes'         , '%{TABLE}.total_requested DESC', '(e.total_requested-IFNULL(s.total_requested, 0)) DESC', '(e.total - IFNULL(s.total, 0)) > 0', NULL, 'event_name'), ('io_global_by_wait_by_latency'       , '%{TABLE}.total_latency DESC', '(e.total_latency-IFNULL(s.total_latency, 0)) DESC', '(e.total - IFNULL(s.total, 0)) > 0', NULL, 'event_name'), ('schema_index_statistics'            , '(%{TABLE}.select_latency+%{TABLE}.insert_latency+%{TABLE}.update_latency+%{TABLE}.delete_latency) DESC', '((e.select_latency+e.insert_latency+e.update_latency+e.delete_latency)-IFNULL(s.select_latency+s.insert_latency+s.update_latency+s.delete_latency, 0)) DESC', '((e.rows_selected+e.insert_latency+e.rows_updated+e.rows_deleted)-IFNULL(s.rows_selected+s.rows_inserted+s.rows_updated+s.rows_deleted, 0)) > 0', 100, 'table_schema,table_name,index_name'), ('schema_table_statistics'            , '%{TABLE}.total_latency DESC', '(e.total_latency-IFNULL(s.total_latency, 0)) DESC', '(e.total_latency-IFNULL(s.total_latency, 0)) > 0', 100, 'table_schema,table_name'), ('schema_tables_with_full_table_scans', '%{TABLE}.rows_full_scanned DESC', '(e.rows_full_scanned-IFNULL(s.rows_full_scanned, 0)) DESC', '(e.rows_full_scanned-IFNULL(s.rows_full_scanned, 0)) > 0', 100, 'object_schema,object_name'), ('user_summary'                       , '%{TABLE}.statement_latency DESC', '(e.statement_latency-IFNULL(s.statement_latency, 0)) DESC', '(e.statements - IFNULL(s.statements, 0)) > 0', NULL, 'user'), ('user_summary_by_file_io'            , '%{TABLE}.io_latency DESC', '(e.io_latency-IFNULL(s.io_latency, 0)) DESC', '(e.ios - IFNULL(s.ios, 0)) > 0', NULL, 'user'), ('user_summary_by_file_io_type'       , '%{TABLE}.user, %{TABLE}.latency DESC', 'e.user, (e.latency-IFNULL(s.latency, 0)) DESC', '(e.total - IFNULL(s.total, 0)) > 0', NULL, 'user,event_name'), ('user_summary_by_stages'             , '%{TABLE}.user, %{TABLE}.total_latency DESC', 'e.user, (e.total_latency-IFNULL(s.total_latency, 0)) DESC', '(e.total - IFNULL(s.total, 0)) > 0', NULL, 'user,event_name'), ('user_summary_by_statement_latency'  , '%{TABLE}.total_latency DESC', '(e.total_latency-IFNULL(s.total_latency, 0)) DESC', '(e.total - IFNULL(s.total, 0)) > 0', NULL, 'user'), ('user_summary_by_statement_type'     , '%{TABLE}.user, %{TABLE}.total_latency DESC', 'e.user, (e.total_latency-IFNULL(s.total_latency, 0)) DESC', '(e.total - IFNULL(s.total, 0)) > 0', NULL, 'user,statement'), ('wait_classes_global_by_avg_latency' , 'IFNULL(%{TABLE}.total_latency / NULLIF(%{TABLE}.total, 0), 0) DESC', 'IFNULL((e.total_latency-IFNULL(s.total_latency, 0)) / NULLIF((e.total - IFNULL(s.total, 0)), 0), 0) DESC', '(e.total - IFNULL(s.total, 0)) > 0', NULL, 'event_class'), ('wait_classes_global_by_latency'     , '%{TABLE}.total_latency DESC', '(e.total_latency-IFNULL(s.total_latency, 0)) DESC', '(e.total - IFNULL(s.total, 0)) > 0', NULL, 'event_class'), ('waits_by_host_by_latency'           , '%{TABLE}.host, %{TABLE}.total_latency DESC', 'e.host, (e.total_latency-IFNULL(s.total_latency, 0)) DESC', '(e.total - IFNULL(s.total, 0)) > 0', NULL, 'host,event'), ('waits_by_user_by_latency'           , '%{TABLE}.user, %{TABLE}.total_latency DESC', 'e.user, (e.total_latency-IFNULL(s.total_latency, 0)) DESC', '(e.total - IFNULL(s.total, 0)) > 0', NULL, 'user,event'), ('waits_global_by_latency'            , '%{TABLE}.total_latency DESC', '(e.total_latency-IFNULL(s.total_latency, 0)) DESC', '(e.total - IFNULL(s.total, 0)) > 0', NULL, 'events') ; END IF;   SELECT '  =======================  Configuration  =======================  ' AS ''; SELECT 'GLOBAL VARIABLES' AS 'The following output is:'; IF (v_has_ps_vars = 'YES') THEN SELECT LOWER(VARIABLE_NAME) AS Variable_name, VARIABLE_VALUE AS Variable_value FROM performance_schema.global_variables ORDER BY VARIABLE_NAME; ELSE SELECT LOWER(VARIABLE_NAME) AS Variable_name, VARIABLE_VALUE AS Variable_value FROM information_schema.GLOBAL_VARIABLES ORDER BY VARIABLE_NAME; END IF;  IF (v_has_ps = 'YES') THEN SELECT 'Performance Schema Setup - Actors' AS 'The following output is:'; SELECT * FROM performance_schema.setup_actors;  SELECT 'Performance Schema Setup - Consumers' AS 'The following output is:'; SELECT NAME AS Consumer, ENABLED, sys.ps_is_consumer_enabled(NAME) AS COLLECTS FROM performance_schema.setup_consumers;  SELECT 'Performance Schema Setup - Instruments' AS 'The following output is:'; SELECT SUBSTRING_INDEX(NAME, '/', 2) AS 'InstrumentClass', ROUND(100*SUM(IF(ENABLED = 'YES', 1, 0))/COUNT(*), 2) AS 'EnabledPct', ROUND(100*SUM(IF(TIMED = 'YES', 1, 0))/COUNT(*), 2) AS 'TimedPct' FROM performance_schema.setup_instruments GROUP BY SUBSTRING_INDEX(NAME, '/', 2) ORDER BY SUBSTRING_INDEX(NAME, '/', 2);  SELECT 'Performance Schema Setup - Objects' AS 'The following output is:'; SELECT * FROM performance_schema.setup_objects;  SELECT 'Performance Schema Setup - Threads' AS 'The following output is:'; SELECT `TYPE` AS ThreadType, COUNT(*) AS 'Total', ROUND(100*SUM(IF(INSTRUMENTED = 'YES', 1, 0))/COUNT(*), 2) AS 'InstrumentedPct' FROM performance_schema.threads GROUP BY TYPE; END IF;   IF (v_has_replication = 'NO') THEN SELECT 'No Replication Configured' AS 'Replication Status'; ELSE SELECT CONCAT('Replication Configured: ', v_has_replication, ' - Performance Schema Replication Tables: ', v_has_ps_replication) AS 'Replication Status';  IF (v_has_ps_replication = 'YES') THEN SELECT 'Replication - Connection Configuration' AS 'The following output is:'; SELECT * FROM performance_schema.replication_connection_configuration ORDER BY CHANNEL_NAME ; END IF;  IF (v_has_ps_replication = 'YES') THEN SELECT 'Replication - Applier Configuration' AS 'The following output is:'; SELECT * FROM performance_schema.replication_applier_configuration ORDER BY CHANNEL_NAME; END IF;  IF (@@master_info_repository = 'TABLE') THEN SELECT 'Replication - Master Info Repository Configuration' AS 'The following output is:'; SELECT  Channel_name, Host, User_name, Port, Connect_retry, Enabled_ssl, Ssl_ca, Ssl_capath, Ssl_cert, Ssl_cipher, Ssl_key, Ssl_verify_server_cert, Heartbeat, Bind, Ignored_server_ids, Uuid, Retry_count, Ssl_crl, Ssl_crlpath, Tls_version, Enabled_auto_position FROM mysql.slave_master_info ORDER BY Channel_name ; END IF;  IF (@@relay_log_info_repository = 'TABLE') THEN SELECT 'Replication - Relay Log Repository Configuration' AS 'The following output is:'; SELECT  Channel_name, Sql_delay, Number_of_workers, Id FROM mysql.slave_relay_log_info ORDER BY Channel_name ; END IF; END IF;   IF (v_has_ndb IN ('DEFAULT', 'YES')) THEN SELECT 'Cluster Thread Blocks' AS 'The following output is:'; SELECT * FROM ndbinfo.threadblocks; END IF;  IF (v_has_ps = 'YES') THEN IF (@sys.diagnostics.include_raw = 'ON') THEN SELECT '  ========================  Initial Status  ========================  ' AS ''; END IF;  DROP TEMPORARY TABLE IF EXISTS tmp_digests_start; CALL sys.statement_performance_analyzer('create_tmp', 'tmp_digests_start', NULL); CALL sys.statement_performance_analyzer('snapshot', NULL, NULL); CALL sys.statement_performance_analyzer('save', 'tmp_digests_start', NULL);  IF (@sys.diagnostics.include_raw = 'ON') THEN SET @sys.diagnostics.sql = REPLACE(@sys.diagnostics.sql_gen_query_template, '%{OUTPUT}', 'start'); IF (@sys.debug = 'ON') THEN SELECT 'The following query will be used to generate the query for each sys view' AS 'Debug'; SELECT @sys.diagnostics.sql AS 'Debug'; END IF; PREPARE stmt_gen_query FROM @sys.diagnostics.sql; END IF; SET v_done = FALSE; OPEN c_sysviews_w_delta; c_sysviews_w_delta_loop: LOOP FETCH c_sysviews_w_delta INTO v_table_name; IF v_done THEN LEAVE c_sysviews_w_delta_loop; END IF;  IF (@sys.debug = 'ON') THEN SELECT CONCAT('The following queries are for storing the initial content of ', v_table_name) AS 'Debug'; END IF;  CALL sys.execute_prepared_stmt(CONCAT('DROP TEMPORARY TABLE IF EXISTS `tmp_', v_table_name, '_start`')); CALL sys.execute_prepared_stmt(CONCAT('CREATE TEMPORARY TABLE `tmp_', v_table_name, '_start` SELECT * FROM `sys`.`x$', v_table_name, '`'));  IF (@sys.diagnostics.include_raw = 'ON') THEN SET @sys.diagnostics.table_name = CONCAT('x$', v_table_name); EXECUTE stmt_gen_query USING @sys.diagnostics.table_name; SELECT CONCAT(@sys.diagnostics.sql_select, IF(order_by IS NOT NULL, CONCAT('\n ORDER BY ', REPLACE(order_by, '%{TABLE}', CONCAT('tmp_', v_table_name, '_start'))), ''), IF(limit_rows IS NOT NULL, CONCAT('\n LIMIT ', limit_rows), '') ) INTO @sys.diagnostics.sql_select FROM tmp_sys_views_delta WHERE TABLE_NAME = v_table_name; SELECT CONCAT('Initial ', v_table_name) AS 'The following output is:'; CALL sys.execute_prepared_stmt(@sys.diagnostics.sql_select); END IF; END LOOP; CLOSE c_sysviews_w_delta;  IF (@sys.diagnostics.include_raw = 'ON') THEN DEALLOCATE PREPARE stmt_gen_query; END IF; END IF;  SET v_sql_status_summary_select = 'SELECT Variable_name', v_sql_status_summary_delta  = '', v_sql_status_summary_from   = '';  REPEAT  SET v_output_count = v_output_count + 1; IF (v_output_count > 1) THEN SET v_sleep = in_interval-(UNIX_TIMESTAMP(NOW(2))-v_iter_start); SELECT NOW() AS 'Time', CONCAT('Going to sleep for ', v_sleep, ' seconds. Please do not interrupt') AS 'The following output is:'; DO SLEEP(in_interval); END IF; SET v_iter_start = UNIX_TIMESTAMP(NOW(2));  SELECT NOW(), CONCAT('Iteration Number ', IFNULL(v_output_count, 'NULL')) AS 'The following output is:';  IF (@@log_bin = 1) THEN SELECT 'SHOW MASTER STATUS' AS 'The following output is:'; SHOW MASTER STATUS; END IF;  IF (v_has_replication <> 'NO') THEN SELECT 'SHOW SLAVE STATUS' AS 'The following output is:'; SHOW SLAVE STATUS;  IF (v_has_ps_replication = 'YES') THEN SELECT 'Replication Connection Status' AS 'The following output is:'; SELECT * FROM performance_schema.replication_connection_status;  SELECT 'Replication Applier Status' AS 'The following output is:'; SELECT * FROM performance_schema.replication_applier_status ORDER BY CHANNEL_NAME;  SELECT 'Replication Applier Status - Coordinator' AS 'The following output is:'; SELECT * FROM performance_schema.replication_applier_status_by_coordinator ORDER BY CHANNEL_NAME;  SELECT 'Replication Applier Status - Worker' AS 'The following output is:'; SELECT * FROM performance_schema.replication_applier_status_by_worker ORDER BY CHANNEL_NAME, WORKER_ID; END IF;  IF (@@master_info_repository = 'TABLE') THEN SELECT 'Replication - Master Log Status' AS 'The following output is:'; SELECT Master_log_name, Master_log_pos FROM mysql.slave_master_info; END IF;  IF (@@relay_log_info_repository = 'TABLE') THEN SELECT 'Replication - Relay Log Status' AS 'The following output is:'; SELECT sys.format_path(Relay_log_name) AS Relay_log_name, Relay_log_pos, Master_log_name, Master_log_pos FROM mysql.slave_relay_log_info;  SELECT 'Replication - Worker Status' AS 'The following output is:'; SELECT Id, sys.format_path(Relay_log_name) AS Relay_log_name, Relay_log_pos, Master_log_name, Master_log_pos, sys.format_path(Checkpoint_relay_log_name) AS Checkpoint_relay_log_name, Checkpoint_relay_log_pos, Checkpoint_master_log_name, Checkpoint_master_log_pos, Checkpoint_seqno, Checkpoint_group_size, HEX(Checkpoint_group_bitmap) AS Checkpoint_group_bitmap , Channel_name FROM mysql.slave_worker_info ORDER BY  Channel_name, Id; END IF; END IF;  SET v_table_name = CONCAT('tmp_metrics_', v_output_count); CALL sys.execute_prepared_stmt(CONCAT('DROP TEMPORARY TABLE IF EXISTS ', v_table_name));  CALL sys.execute_prepared_stmt(CONCAT('CREATE TEMPORARY TABLE ', v_table_name, ' ( Variable_name VARCHAR(193) NOT NULL, Variable_value VARCHAR(1024), Type VARCHAR(225) NOT NULL, Enabled ENUM(''YES'', ''NO'', ''PARTIAL'') NOT NULL, PRIMARY KEY (Type, Variable_name) ) ENGINE = InnoDB DEFAULT CHARSET=utf8'));  IF (v_has_metrics) THEN SET @sys.diagnostics.sql = CONCAT( 'INSERT INTO ', v_table_name, ' SELECT Variable_name, REPLACE(Variable_value, ''\n'', ''\\\\n'') AS Variable_value, Type, Enabled FROM sys.metrics' ); ELSE SET @sys.diagnostics.sql = CONCAT( 'INSERT INTO ', v_table_name, '(SELECT LOWER(VARIABLE_NAME) AS Variable_name, REPLACE(VARIABLE_VALUE, ''\n'', ''\\\\n'') AS Variable_value, ''Global Status'' AS Type, ''YES'' AS Enabled FROM performance_schema.global_status ) UNION ALL ( SELECT NAME AS Variable_name, COUNT AS Variable_value, CONCAT(''InnoDB Metrics - '', SUBSYSTEM) AS Type, IF(STATUS = ''enabled'', ''YES'', ''NO'') AS Enabled FROM information_schema.INNODB_METRICS WHERE NAME NOT IN ( ''lock_row_lock_time'', ''lock_row_lock_time_avg'', ''lock_row_lock_time_max'', ''lock_row_lock_waits'', ''buffer_pool_reads'', ''buffer_pool_read_requests'', ''buffer_pool_write_requests'', ''buffer_pool_wait_free'', ''buffer_pool_read_ahead'', ''buffer_pool_read_ahead_evicted'', ''buffer_pool_pages_total'', ''buffer_pool_pages_misc'', ''buffer_pool_pages_data'', ''buffer_pool_bytes_data'', ''buffer_pool_pages_dirty'', ''buffer_pool_bytes_dirty'', ''buffer_pool_pages_free'', ''buffer_pages_created'', ''buffer_pages_written'', ''buffer_pages_read'', ''buffer_data_reads'', ''buffer_data_written'', ''file_num_open_files'', ''os_log_bytes_written'', ''os_log_fsyncs'', ''os_log_pending_fsyncs'', ''os_log_pending_writes'', ''log_waits'', ''log_write_requests'', ''log_writes'', ''innodb_dblwr_writes'', ''innodb_dblwr_pages_written'', ''innodb_page_size'') ) UNION ALL ( SELECT ''NOW()'' AS Variable_name, NOW(3) AS Variable_value, ''System Time'' AS Type, ''YES'' AS Enabled ) UNION ALL ( SELECT ''UNIX_TIMESTAMP()'' AS Variable_name, ROUND(UNIX_TIMESTAMP(NOW(3)), 3) AS Variable_value, ''System Time'' AS Type, ''YES'' AS Enabled ) ORDER BY Type, Variable_name;' ); END IF; CALL sys.execute_prepared_stmt(@sys.diagnostics.sql);  CALL sys.execute_prepared_stmt( CONCAT('(SELECT Variable_value INTO @sys.diagnostics.output_time FROM ', v_table_name, ' WHERE Type = ''System Time'' AND Variable_name = ''UNIX_TIMESTAMP()'')') ); SET v_output_time = @sys.diagnostics.output_time;  SET v_sql_status_summary_select = CONCAT(v_sql_status_summary_select, ', CONCAT( LEFT(s', v_output_count, '.Variable_value, ', v_status_summary_width, '), IF(', REPLACE(v_no_delta_names, '%{COUNT}', v_output_count), ' AND s', v_output_count, '.Variable_value REGEXP ''^[0-9]+(\\\\.[0-9]+)?$'', CONCAT('' ('', ROUND(s', v_output_count, '.Variable_value/', v_output_time, ', 2), ''/sec)''), '''') ) AS ''Output ', v_output_count, ''''), v_sql_status_summary_from   = CONCAT(v_sql_status_summary_from, ' ', IF(v_output_count = 1, '  FROM ', '       INNER JOIN '), v_table_name, ' s', v_output_count, IF (v_output_count = 1, '', ' USING (Type, Variable_name)')); IF (v_output_count > 1) THEN SET v_sql_status_summary_delta  = CONCAT(v_sql_status_summary_delta, ', IF(', REPLACE(v_no_delta_names, '%{COUNT}', v_output_count), ' AND s', (v_output_count-1), '.Variable_value REGEXP ''^[0-9]+(\\\\.[0-9]+)?$'' AND s', v_output_count, '.Variable_value REGEXP ''^[0-9]+(\\\\.[0-9]+)?$'', CONCAT(IF(s', (v_output_count-1), '.Variable_value REGEXP ''^[0-9]+\\\\.[0-9]+$'' OR s', v_output_count, '.Variable_value REGEXP ''^[0-9]+\\\\.[0-9]+$'', ROUND((s', v_output_count, '.Variable_value-s', (v_output_count-1), '.Variable_value), 2), (s', v_output_count, '.Variable_value-s', (v_output_count-1), '.Variable_value) ), '' ('', ROUND((s', v_output_count, '.Variable_value-s', (v_output_count-1), '.Variable_value)/(', v_output_time, '-', v_output_time_prev, '), 2), ''/sec)'' ), '''' ) AS ''Delta (', (v_output_count-1), ' -> ', v_output_count, ')'''); END IF;  SET v_output_time_prev = v_output_time;  IF (@sys.diagnostics.include_raw = 'ON') THEN IF (v_has_metrics) THEN SELECT 'SELECT * FROM sys.metrics' AS 'The following output is:'; ELSE SELECT 'sys.metrics equivalent' AS 'The following output is:'; END IF; CALL sys.execute_prepared_stmt(CONCAT('SELECT Type, Variable_name, Enabled, Variable_value FROM ', v_table_name, ' ORDER BY Type, Variable_name')); END IF;  IF (v_has_innodb IN ('DEFAULT', 'YES')) THEN SELECT 'SHOW ENGINE INNODB STATUS' AS 'The following output is:'; EXECUTE stmt_innodb_status; SELECT 'InnoDB - Transactions' AS 'The following output is:'; SELECT * FROM information_schema.INNODB_TRX; END IF;  IF (v_has_ndb IN ('DEFAULT', 'YES')) THEN SELECT 'SHOW ENGINE NDBCLUSTER STATUS' AS 'The following output is:'; EXECUTE stmt_ndbcluster_status;  SELECT 'ndbinfo.memoryusage' AS 'The following output is:'; SELECT node_id, memory_type, sys.format_bytes(used) AS used, used_pages, sys.format_bytes(total) AS total, total_pages, ROUND(100*(used/total), 2) AS 'Used %' FROM ndbinfo.memoryusage;  SET v_done = FALSE; OPEN c_ndbinfo; c_ndbinfo_loop: LOOP FETCH c_ndbinfo INTO v_table_name; IF v_done THEN LEAVE c_ndbinfo_loop; END IF;  SELECT CONCAT('SELECT * FROM ndbinfo.', v_table_name) AS 'The following output is:'; CALL sys.execute_prepared_stmt(CONCAT('SELECT * FROM `ndbinfo`.`', v_table_name, '`')); END LOOP; CLOSE c_ndbinfo;  SELECT * FROM information_schema.FILES; END IF;  SELECT 'SELECT * FROM sys.processlist' AS 'The following output is:'; SELECT processlist.* FROM sys.processlist;  IF (v_has_ps = 'YES') THEN IF (sys.ps_is_consumer_enabled('events_waits_history_long') = 'YES') THEN SELECT 'SELECT * FROM sys.latest_file_io' AS 'The following output is:'; SELECT * FROM sys.latest_file_io; END IF;  IF (EXISTS(SELECT 1 FROM performance_schema.setup_instruments WHERE NAME LIKE 'memory/%' AND ENABLED = 'YES')) THEN SELECT 'SELECT * FROM sys.memory_by_host_by_current_bytes' AS 'The following output is:'; SELECT * FROM sys.memory_by_host_by_current_bytes;  SELECT 'SELECT * FROM sys.memory_by_thread_by_current_bytes' AS 'The following output is:'; SELECT * FROM sys.memory_by_thread_by_current_bytes;  SELECT 'SELECT * FROM sys.memory_by_user_by_current_bytes' AS 'The following output is:'; SELECT * FROM sys.memory_by_user_by_current_bytes;  SELECT 'SELECT * FROM sys.memory_global_by_current_bytes' AS 'The following output is:'; SELECT * FROM sys.memory_global_by_current_bytes; END IF; END IF;  SET v_runtime = (UNIX_TIMESTAMP(NOW(2)) - v_start); UNTIL (v_runtime + in_interval >= in_max_runtime) END REPEAT;  IF (v_has_ps = 'YES') THEN SELECT 'SHOW ENGINE PERFORMANCE_SCHEMA STATUS' AS 'The following output is:'; EXECUTE stmt_ps_status; END IF;  IF (v_has_innodb IN ('DEFAULT', 'YES')) THEN DEALLOCATE PREPARE stmt_innodb_status; END IF; IF (v_has_ps = 'YES') THEN DEALLOCATE PREPARE stmt_ps_status; END IF; IF (v_has_ndb IN ('DEFAULT', 'YES')) THEN DEALLOCATE PREPARE stmt_ndbcluster_status; END IF;   SELECT '  ============================  Schema Information  ============================  ' AS '';  SELECT COUNT(*) AS 'Total Number of Tables' FROM information_schema.TABLES;  IF (@sys.diagnostics.allow_i_s_tables = 'ON') THEN SELECT 'Storage Engine Usage' AS 'The following output is:'; SELECT ENGINE, COUNT(*) AS NUM_TABLES, sys.format_bytes(SUM(DATA_LENGTH)) AS DATA_LENGTH, sys.format_bytes(SUM(INDEX_LENGTH)) AS INDEX_LENGTH, sys.format_bytes(SUM(DATA_LENGTH+INDEX_LENGTH)) AS TOTAL FROM information_schema.TABLES GROUP BY ENGINE;  SELECT 'Schema Object Overview' AS 'The following output is:'; SELECT * FROM sys.schema_object_overview;  SELECT 'Tables without a PRIMARY KEY' AS 'The following output is:'; SELECT TABLES.TABLE_SCHEMA, ENGINE, COUNT(*) AS NumTables FROM information_schema.TABLES LEFT OUTER JOIN information_schema.STATISTICS ON STATISTICS.TABLE_SCHEMA = TABLES.TABLE_SCHEMA AND STATISTICS.TABLE_NAME = TABLES.TABLE_NAME AND STATISTICS.INDEX_NAME = 'PRIMARY' WHERE STATISTICS.TABLE_NAME IS NULL AND TABLES.TABLE_SCHEMA NOT IN ('mysql', 'information_schema', 'performance_schema', 'sys') AND TABLES.TABLE_TYPE = 'BASE TABLE' GROUP BY TABLES.TABLE_SCHEMA, ENGINE; END IF;  IF (v_has_ps = 'YES') THEN SELECT 'Unused Indexes' AS 'The following output is:'; SELECT object_schema, COUNT(*) AS NumUnusedIndexes FROM performance_schema.table_io_waits_summary_by_index_usage  WHERE index_name IS NOT NULL AND count_star = 0 AND object_schema NOT IN ('mysql', 'sys') AND index_name != 'PRIMARY' GROUP BY object_schema; END IF;  IF (v_has_ps = 'YES') THEN SELECT '  =========================  Overall Status  =========================  ' AS '';  SELECT 'CALL sys.ps_statement_avg_latency_histogram()' AS 'The following output is:'; CALL sys.ps_statement_avg_latency_histogram();  CALL sys.statement_performance_analyzer('snapshot', NULL, NULL); CALL sys.statement_performance_analyzer('overall', NULL, 'with_runtimes_in_95th_percentile');  SET @sys.diagnostics.sql = REPLACE(@sys.diagnostics.sql_gen_query_template, '%{OUTPUT}', 'end'); IF (@sys.debug = 'ON') THEN SELECT 'The following query will be used to generate the query for each sys view' AS 'Debug'; SELECT @sys.diagnostics.sql AS 'Debug'; END IF; PREPARE stmt_gen_query FROM @sys.diagnostics.sql;  SET v_done = FALSE; OPEN c_sysviews_w_delta; c_sysviews_w_delta_loop: LOOP FETCH c_sysviews_w_delta INTO v_table_name; IF v_done THEN LEAVE c_sysviews_w_delta_loop; END IF;  IF (@sys.debug = 'ON') THEN SELECT CONCAT('The following queries are for storing the final content of ', v_table_name) AS 'Debug'; END IF;  CALL sys.execute_prepared_stmt(CONCAT('DROP TEMPORARY TABLE IF EXISTS `tmp_', v_table_name, '_end`')); CALL sys.execute_prepared_stmt(CONCAT('CREATE TEMPORARY TABLE `tmp_', v_table_name, '_end` SELECT * FROM `sys`.`x$', v_table_name, '`'));  IF (@sys.diagnostics.include_raw = 'ON') THEN SET @sys.diagnostics.table_name = CONCAT('x$', v_table_name); EXECUTE stmt_gen_query USING @sys.diagnostics.table_name; SELECT CONCAT(@sys.diagnostics.sql_select, IF(order_by IS NOT NULL, CONCAT('\n ORDER BY ', REPLACE(order_by, '%{TABLE}', CONCAT('tmp_', v_table_name, '_end'))), ''), IF(limit_rows IS NOT NULL, CONCAT('\n LIMIT ', limit_rows), '') ) INTO @sys.diagnostics.sql_select FROM tmp_sys_views_delta WHERE TABLE_NAME = v_table_name; SELECT CONCAT('Overall ', v_table_name) AS 'The following output is:'; CALL sys.execute_prepared_stmt(@sys.diagnostics.sql_select); END IF; END LOOP; CLOSE c_sysviews_w_delta;  DEALLOCATE PREPARE stmt_gen_query;   SELECT '  ======================  Delta Status  ======================  ' AS '';  CALL sys.statement_performance_analyzer('delta', 'tmp_digests_start', 'with_runtimes_in_95th_percentile'); CALL sys.statement_performance_analyzer('cleanup', NULL, NULL);  DROP TEMPORARY TABLE tmp_digests_start;  IF (@sys.debug = 'ON') THEN SELECT 'The following query will be used to generate the query for each sys view delta' AS 'Debug'; SELECT @sys.diagnostics.sql_gen_query_delta AS 'Debug'; END IF; PREPARE stmt_gen_query_delta FROM @sys.diagnostics.sql_gen_query_delta;  SET v_old_group_concat_max_len = @@session.group_concat_max_len; SET @@session.group_concat_max_len = 2048; SET v_done = FALSE; OPEN c_sysviews_w_delta; c_sysviews_w_delta_loop: LOOP FETCH c_sysviews_w_delta INTO v_table_name; IF v_done THEN LEAVE c_sysviews_w_delta_loop; END IF;  SET @sys.diagnostics.table_name = v_table_name; EXECUTE stmt_gen_query_delta USING @sys.diagnostics.table_name; SELECT CONCAT(@sys.diagnostics.sql_select, IF(where_delta IS NOT NULL, CONCAT('\n WHERE ', where_delta), ''), IF(order_by_delta IS NOT NULL, CONCAT('\n ORDER BY ', order_by_delta), ''), IF(limit_rows IS NOT NULL, CONCAT('\n LIMIT ', limit_rows), '') ) INTO @sys.diagnostics.sql_select FROM tmp_sys_views_delta WHERE TABLE_NAME = v_table_name;  SELECT CONCAT('Delta ', v_table_name) AS 'The following output is:'; CALL sys.execute_prepared_stmt(@sys.diagnostics.sql_select);  CALL sys.execute_prepared_stmt(CONCAT('DROP TEMPORARY TABLE `tmp_', v_table_name, '_end`')); CALL sys.execute_prepared_stmt(CONCAT('DROP TEMPORARY TABLE `tmp_', v_table_name, '_start`')); END LOOP; CLOSE c_sysviews_w_delta; SET @@session.group_concat_max_len = v_old_group_concat_max_len;  DEALLOCATE PREPARE stmt_gen_query_delta; DROP TEMPORARY TABLE tmp_sys_views_delta; END IF;  IF (v_has_metrics) THEN SELECT 'SELECT * FROM sys.metrics' AS 'The following output is:'; ELSE SELECT 'sys.metrics equivalent' AS 'The following output is:'; END IF; CALL sys.execute_prepared_stmt( CONCAT(v_sql_status_summary_select, v_sql_status_summary_delta, ', Type, s1.Enabled', v_sql_status_summary_from, ' ORDER BY Type, Variable_name' ) );  SET v_count = 0; WHILE (v_count < v_output_count) DO SET v_count = v_count + 1; SET v_table_name = CONCAT('tmp_metrics_', v_count); CALL sys.execute_prepared_stmt(CONCAT('DROP TEMPORARY TABLE IF EXISTS ', v_table_name)); END WHILE;  IF (in_auto_config <> 'current') THEN CALL sys.ps_setup_reload_saved(); SET sql_log_bin = @log_bin; END IF;  SET @sys.diagnostics.output_time            = NULL, @sys.diagnostics.sql                    = NULL, @sys.diagnostics.sql_gen_query_delta    = NULL, @sys.diagnostics.sql_gen_query_template = NULL, @sys.diagnostics.sql_select             = NULL, @sys.diagnostics.table_name             = NULL;  IF (v_this_thread_enabled = 'YES') THEN CALL sys.ps_setup_enable_thread(CONNECTION_ID()); END IF;  IF (@log_bin = 1) THEN SET sql_log_bin = @log_bin; END IF; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `execute_prepared_stmt`( IN in_query longtext CHARACTER SET UTF8 )
READS SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Takes the query in the argument and executes it using a prepared statement. The prepared statement is deallocated,\n so the procedure is mainly useful for executing one off dynamically created queries.\n \n The sys_execute_prepared_stmt prepared statement name is used for the query and is required not to exist.\n \n \n Parameters\n \n in_query (longtext CHARACTER SET UTF8):\n The query to execute.\n \n \n Configuration Options\n \n sys.debug\n Whether to provide debugging output.\n Default is ''OFF''. Set to ''ON'' to include.\n \n \n Example\n \n mysql> CALL sys.execute_prepared_stmt(''SELECT * FROM sys.sys_config'');\n +------------------------+-------+---------------------+--------+\n | variable               | value | set_time            | set_by |\n +------------------------+-------+---------------------+--------+\n | statement_truncate_len | 64    | 2015-06-30 13:06:00 | NULL   |\n +------------------------+-------+---------------------+--------+\n 1 row in set (0.00 sec)\n \n Query OK, 0 rows affected (0.00 sec)\n '
  BEGIN IF (@sys.debug IS NULL) THEN SET @sys.debug = sys.sys_get_config('debug', 'OFF'); END IF;  IF (in_query IS NULL OR LENGTH(in_query) < 4) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = "The @sys.execute_prepared_stmt.sql must contain a query"; END IF;  SET @sys.execute_prepared_stmt.sql = in_query;  IF (@sys.debug = 'ON') THEN SELECT @sys.execute_prepared_stmt.sql AS 'Debug'; END IF; PREPARE sys_execute_prepared_stmt FROM @sys.execute_prepared_stmt.sql; EXECUTE sys_execute_prepared_stmt; DEALLOCATE PREPARE sys_execute_prepared_stmt;  SET @sys.execute_prepared_stmt.sql = NULL; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_setup_disable_background_threads`()
MODIFIES SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Disable all background thread instrumentation within Performance Schema.\n \n Parameters\n \n None.\n \n Example\n \n mysql> CALL sys.ps_setup_disable_background_threads();\n +--------------------------------+\n | summary                        |\n +--------------------------------+\n | Disabled 18 background threads |\n +--------------------------------+\n 1 row in set (0.00 sec)\n '
  BEGIN UPDATE performance_schema.threads SET instrumented = 'NO' WHERE type = 'BACKGROUND';  SELECT CONCAT('Disabled ', @rows := ROW_COUNT(), ' background thread', IF(@rows != 1, 's', '')) AS summary; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_setup_disable_consumer`( IN consumer VARCHAR(128) )
MODIFIES SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Disables consumers within Performance Schema \n matching the input pattern.\n \n Parameters\n \n consumer (VARCHAR(128)):\n A LIKE pattern match (using "%consumer%") of consumers to disable\n \n Example\n \n To disable all consumers:\n \n mysql> CALL sys.ps_setup_disable_consumer('''');\n +--------------------------+\n | summary                  |\n +--------------------------+\n | Disabled 15 consumers    |\n +--------------------------+\n 1 row in set (0.02 sec)\n \n To disable just the event_stage consumers:\n \n mysql> CALL sys.ps_setup_disable_comsumers(''stage'');\n +------------------------+\n | summary                |\n +------------------------+\n | Disabled 3 consumers   |\n +------------------------+\n 1 row in set (0.00 sec)\n '
  BEGIN UPDATE performance_schema.setup_consumers SET enabled = 'NO' WHERE name LIKE CONCAT('%', consumer, '%');  SELECT CONCAT('Disabled ', @rows := ROW_COUNT(), ' consumer', IF(@rows != 1, 's', '')) AS summary; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_setup_disable_instrument`( IN in_pattern VARCHAR(128) )
MODIFIES SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Disables instruments within Performance Schema \n matching the input pattern.\n \n Parameters\n \n in_pattern (VARCHAR(128)):\n A LIKE pattern match (using "%in_pattern%") of events to disable\n \n Example\n \n To disable all mutex instruments:\n \n mysql> CALL sys.ps_setup_disable_instrument(''wait/synch/mutex'');\n +--------------------------+\n | summary                  |\n +--------------------------+\n | Disabled 155 instruments |\n +--------------------------+\n 1 row in set (0.02 sec)\n \n To disable just a specific TCP/IP based network IO instrument:\n \n mysql> CALL sys.ps_setup_disable_instrument(''wait/io/socket/sql/server_tcpip_socket'');\n +------------------------+\n | summary                |\n +------------------------+\n | Disabled 1 instruments |\n +------------------------+\n 1 row in set (0.00 sec)\n \n To disable all instruments:\n \n mysql> CALL sys.ps_setup_disable_instrument('''');\n +--------------------------+\n | summary                  |\n +--------------------------+\n | Disabled 547 instruments |\n +--------------------------+\n 1 row in set (0.01 sec)\n '
  BEGIN UPDATE performance_schema.setup_instruments SET enabled = 'NO', timed = 'NO' WHERE name LIKE CONCAT('%', in_pattern, '%');  SELECT CONCAT('Disabled ', @rows := ROW_COUNT(), ' instrument', IF(@rows != 1, 's', '')) AS summary; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_setup_disable_thread`( IN in_connection_id BIGINT )
MODIFIES SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Disable the given connection/thread in Performance Schema.\n \n Parameters\n \n in_connection_id (BIGINT):\n The connection ID (PROCESSLIST_ID from performance_schema.threads\n or the ID shown within SHOW PROCESSLIST)\n \n Example\n \n mysql> CALL sys.ps_setup_disable_thread(3);\n +-------------------+\n | summary           |\n +-------------------+\n | Disabled 1 thread |\n +-------------------+\n 1 row in set (0.01 sec)\n \n To disable the current connection:\n \n mysql> CALL sys.ps_setup_disable_thread(CONNECTION_ID());\n +-------------------+\n | summary           |\n +-------------------+\n | Disabled 1 thread |\n +-------------------+\n 1 row in set (0.00 sec)\n '
  BEGIN UPDATE performance_schema.threads SET instrumented = 'NO' WHERE processlist_id = in_connection_id;  SELECT CONCAT('Disabled ', @rows := ROW_COUNT(), ' thread', IF(@rows != 1, 's', '')) AS summary; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_setup_enable_background_threads`()
MODIFIES SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Enable all background thread instrumentation within Performance Schema.\n \n Parameters\n \n None.\n \n Example\n \n mysql> CALL sys.ps_setup_enable_background_threads();\n +-------------------------------+\n | summary                       |\n +-------------------------------+\n | Enabled 18 background threads |\n +-------------------------------+\n 1 row in set (0.00 sec)\n '
  BEGIN UPDATE performance_schema.threads SET instrumented = 'YES' WHERE type = 'BACKGROUND';  SELECT CONCAT('Enabled ', @rows := ROW_COUNT(), ' background thread', IF(@rows != 1, 's', '')) AS summary; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_setup_enable_consumer`( IN consumer VARCHAR(128) )
MODIFIES SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Enables consumers within Performance Schema \n matching the input pattern.\n \n Parameters\n \n consumer (VARCHAR(128)):\n A LIKE pattern match (using "%consumer%") of consumers to enable\n \n Example\n \n To enable all consumers:\n \n mysql> CALL sys.ps_setup_enable_consumer('''');\n +-------------------------+\n | summary                 |\n +-------------------------+\n | Enabled 10 consumers    |\n +-------------------------+\n 1 row in set (0.02 sec)\n \n Query OK, 0 rows affected (0.02 sec)\n \n To enable just "waits" consumers:\n \n mysql> CALL sys.ps_setup_enable_consumer(''waits'');\n +-----------------------+\n | summary               |\n +-----------------------+\n | Enabled 3 consumers   |\n +-----------------------+\n 1 row in set (0.00 sec)\n \n Query OK, 0 rows affected (0.00 sec)\n '
  BEGIN UPDATE performance_schema.setup_consumers SET enabled = 'YES' WHERE name LIKE CONCAT('%', consumer, '%');  SELECT CONCAT('Enabled ', @rows := ROW_COUNT(), ' consumer', IF(@rows != 1, 's', '')) AS summary; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_setup_enable_instrument`( IN in_pattern VARCHAR(128) )
MODIFIES SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Enables instruments within Performance Schema \n matching the input pattern.\n \n Parameters\n \n in_pattern (VARCHAR(128)):\n A LIKE pattern match (using "%in_pattern%") of events to enable\n \n Example\n \n To enable all mutex instruments:\n \n mysql> CALL sys.ps_setup_enable_instrument(''wait/synch/mutex'');\n +-------------------------+\n | summary                 |\n +-------------------------+\n | Enabled 155 instruments |\n +-------------------------+\n 1 row in set (0.02 sec)\n \n Query OK, 0 rows affected (0.02 sec)\n \n To enable just a specific TCP/IP based network IO instrument:\n \n mysql> CALL sys.ps_setup_enable_instrument(''wait/io/socket/sql/server_tcpip_socket'');\n +-----------------------+\n | summary               |\n +-----------------------+\n | Enabled 1 instruments |\n +-----------------------+\n 1 row in set (0.00 sec)\n \n Query OK, 0 rows affected (0.00 sec)\n \n To enable all instruments:\n \n mysql> CALL sys.ps_setup_enable_instrument('''');\n +-------------------------+\n | summary                 |\n +-------------------------+\n | Enabled 547 instruments |\n +-------------------------+\n 1 row in set (0.01 sec)\n \n Query OK, 0 rows affected (0.01 sec)\n '
  BEGIN UPDATE performance_schema.setup_instruments SET enabled = 'YES', timed = 'YES' WHERE name LIKE CONCAT('%', in_pattern, '%');  SELECT CONCAT('Enabled ', @rows := ROW_COUNT(), ' instrument', IF(@rows != 1, 's', '')) AS summary; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_setup_enable_thread`( IN in_connection_id BIGINT )
MODIFIES SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Enable the given connection/thread in Performance Schema.\n \n Parameters\n \n in_connection_id (BIGINT):\n The connection ID (PROCESSLIST_ID from performance_schema.threads\n or the ID shown within SHOW PROCESSLIST)\n \n Example\n \n mysql> CALL sys.ps_setup_enable_thread(3);\n +------------------+\n | summary          |\n +------------------+\n | Enabled 1 thread |\n +------------------+\n 1 row in set (0.01 sec)\n \n To enable the current connection:\n \n mysql> CALL sys.ps_setup_enable_thread(CONNECTION_ID());\n +------------------+\n | summary          |\n +------------------+\n | Enabled 1 thread |\n +------------------+\n 1 row in set (0.00 sec)\n '
  BEGIN UPDATE performance_schema.threads SET instrumented = 'YES' WHERE processlist_id = in_connection_id;  SELECT CONCAT('Enabled ', @rows := ROW_COUNT(), ' thread', IF(@rows != 1, 's', '')) AS summary; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_setup_reload_saved`()
MODIFIES SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Reloads a saved Performance Schema configuration,\n so that you can alter the setup for debugging purposes, \n but restore it to a previous state.\n \n Use the companion procedure - ps_setup_save(), to \n save a configuration.\n \n Requires the SUPER privilege for "SET sql_log_bin = 0;".\n \n Parameters\n \n None.\n \n Example\n \n mysql> CALL sys.ps_setup_save();\n Query OK, 0 rows affected (0.08 sec)\n \n mysql> UPDATE performance_schema.setup_instruments SET enabled = ''YES'', timed = ''YES'';\n Query OK, 547 rows affected (0.40 sec)\n Rows matched: 784  Changed: 547  Warnings: 0\n \n /* Run some tests that need more detailed instrumentation here */\n \n mysql> CALL sys.ps_setup_reload_saved();\n Query OK, 0 rows affected (0.32 sec)\n '
  BEGIN DECLARE v_done bool DEFAULT FALSE; DECLARE v_lock_result INT; DECLARE v_lock_used_by BIGINT; DECLARE v_signal_message TEXT; DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SIGNAL SQLSTATE VALUE '90001' SET MESSAGE_TEXT = 'An error occurred, was sys.ps_setup_save() run before this procedure?'; END;  SET @log_bin := @@sql_log_bin; SET sql_log_bin = 0;  SELECT IS_USED_LOCK('sys.ps_setup_save') INTO v_lock_used_by;  IF (v_lock_used_by != CONNECTION_ID()) THEN SET v_signal_message = CONCAT('The sys.ps_setup_save lock is currently owned by ', v_lock_used_by); SIGNAL SQLSTATE VALUE '90002' SET MESSAGE_TEXT = v_signal_message; END IF;  DELETE FROM performance_schema.setup_actors; INSERT INTO performance_schema.setup_actors SELECT * FROM tmp_setup_actors;  BEGIN DECLARE v_name varchar(64); DECLARE v_enabled enum('YES', 'NO'); DECLARE c_consumers CURSOR FOR SELECT NAME, ENABLED FROM tmp_setup_consumers; DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;  SET v_done = FALSE; OPEN c_consumers; c_consumers_loop: LOOP FETCH c_consumers INTO v_name, v_enabled; IF v_done THEN LEAVE c_consumers_loop; END IF;  UPDATE performance_schema.setup_consumers SET ENABLED = v_enabled WHERE NAME = v_name; END LOOP; CLOSE c_consumers; END;  UPDATE performance_schema.setup_instruments INNER JOIN tmp_setup_instruments USING (NAME) SET performance_schema.setup_instruments.ENABLED = tmp_setup_instruments.ENABLED, performance_schema.setup_instruments.TIMED   = tmp_setup_instruments.TIMED; BEGIN DECLARE v_thread_id bigint unsigned; DECLARE v_instrumented enum('YES', 'NO'); DECLARE c_threads CURSOR FOR SELECT THREAD_ID, INSTRUMENTED FROM tmp_threads; DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;  SET v_done = FALSE; OPEN c_threads; c_threads_loop: LOOP FETCH c_threads INTO v_thread_id, v_instrumented; IF v_done THEN LEAVE c_threads_loop; END IF;  UPDATE performance_schema.threads SET INSTRUMENTED = v_instrumented WHERE THREAD_ID = v_thread_id; END LOOP; CLOSE c_threads; END;  UPDATE performance_schema.threads SET INSTRUMENTED = IF(PROCESSLIST_USER IS NOT NULL, sys.ps_is_account_enabled(PROCESSLIST_HOST, PROCESSLIST_USER), 'YES') WHERE THREAD_ID NOT IN (SELECT THREAD_ID FROM tmp_threads);  DROP TEMPORARY TABLE tmp_setup_actors; DROP TEMPORARY TABLE tmp_setup_consumers; DROP TEMPORARY TABLE tmp_setup_instruments; DROP TEMPORARY TABLE tmp_threads;  SELECT RELEASE_LOCK('sys.ps_setup_save') INTO v_lock_result;  SET sql_log_bin = @log_bin;  END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_setup_reset_to_default`( IN in_verbose BOOLEAN )
MODIFIES SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Resets the Performance Schema setup to the default settings.\n \n Parameters\n \n in_verbose (BOOLEAN):\n Whether to print each setup stage (including the SQL) whilst running.\n \n Example\n \n mysql> CALL sys.ps_setup_reset_to_default(true)\\G\n *************************** 1. row ***************************\n status: Resetting: setup_actors\n DELETE\n FROM performance_schema.setup_actors\n WHERE NOT (HOST = ''%'' AND USER = ''%'' AND ROLE = ''%'')\n 1 row in set (0.00 sec)\n \n *************************** 1. row ***************************\n status: Resetting: setup_actors\n INSERT IGNORE INTO performance_schema.setup_actors\n VALUES (''%'', ''%'', ''%'')\n 1 row in set (0.00 sec)\n ...\n \n mysql> CALL sys.ps_setup_reset_to_default(false)\\G\n Query OK, 0 rows affected (0.00 sec)\n '
  BEGIN SET @query = 'DELETE FROM performance_schema.setup_actors WHERE NOT (HOST = ''%'' AND USER = ''%'' AND ROLE = ''%'')';  IF (in_verbose) THEN SELECT CONCAT('Resetting: setup_actors\n', REPLACE(@query, '  ', '')) AS status; END IF;  PREPARE reset_stmt FROM @query; EXECUTE reset_stmt; DEALLOCATE PREPARE reset_stmt;  SET @query = 'INSERT IGNORE INTO performance_schema.setup_actors VALUES (''%'', ''%'', ''%'', ''YES'', ''YES'')';  IF (in_verbose) THEN SELECT CONCAT('Resetting: setup_actors\n', REPLACE(@query, '  ', '')) AS status; END IF;  PREPARE reset_stmt FROM @query; EXECUTE reset_stmt; DEALLOCATE PREPARE reset_stmt;  SET @query = 'UPDATE performance_schema.setup_instruments SET ENABLED = sys.ps_is_instrument_default_enabled(NAME), TIMED   = sys.ps_is_instrument_default_timed(NAME)';  IF (in_verbose) THEN SELECT CONCAT('Resetting: setup_instruments\n', REPLACE(@query, '  ', '')) AS status; END IF;  PREPARE reset_stmt FROM @query; EXECUTE reset_stmt; DEALLOCATE PREPARE reset_stmt;  SET @query = 'UPDATE performance_schema.setup_consumers SET ENABLED = IF(NAME IN (''events_statements_current'', ''events_transactions_current'', ''global_instrumentation'', ''thread_instrumentation'', ''statements_digest''), ''YES'', ''NO'')';  IF (in_verbose) THEN SELECT CONCAT('Resetting: setup_consumers\n', REPLACE(@query, '  ', '')) AS status; END IF;  PREPARE reset_stmt FROM @query; EXECUTE reset_stmt; DEALLOCATE PREPARE reset_stmt;  SET @query = 'DELETE FROM performance_schema.setup_objects WHERE NOT (OBJECT_TYPE IN (''EVENT'', ''FUNCTION'', ''PROCEDURE'', ''TABLE'', ''TRIGGER'') AND OBJECT_NAME = ''%''  AND (OBJECT_SCHEMA = ''mysql''              AND ENABLED = ''NO''  AND TIMED = ''NO'' ) OR (OBJECT_SCHEMA = ''performance_schema'' AND ENABLED = ''NO''  AND TIMED = ''NO'' ) OR (OBJECT_SCHEMA = ''information_schema'' AND ENABLED = ''NO''  AND TIMED = ''NO'' ) OR (OBJECT_SCHEMA = ''%''                  AND ENABLED = ''YES'' AND TIMED = ''YES''))';  IF (in_verbose) THEN SELECT CONCAT('Resetting: setup_objects\n', REPLACE(@query, '  ', '')) AS status; END IF;  PREPARE reset_stmt FROM @query; EXECUTE reset_stmt; DEALLOCATE PREPARE reset_stmt;  SET @query = 'INSERT IGNORE INTO performance_schema.setup_objects VALUES (''EVENT''    , ''mysql''             , ''%'', ''NO'' , ''NO'' ), (''EVENT''    , ''performance_schema'', ''%'', ''NO'' , ''NO'' ), (''EVENT''    , ''information_schema'', ''%'', ''NO'' , ''NO'' ), (''EVENT''    , ''%''                 , ''%'', ''YES'', ''YES''), (''FUNCTION'' , ''mysql''             , ''%'', ''NO'' , ''NO'' ), (''FUNCTION'' , ''performance_schema'', ''%'', ''NO'' , ''NO'' ), (''FUNCTION'' , ''information_schema'', ''%'', ''NO'' , ''NO'' ), (''FUNCTION'' , ''%''                 , ''%'', ''YES'', ''YES''), (''PROCEDURE'', ''mysql''             , ''%'', ''NO'' , ''NO'' ), (''PROCEDURE'', ''performance_schema'', ''%'', ''NO'' , ''NO'' ), (''PROCEDURE'', ''information_schema'', ''%'', ''NO'' , ''NO'' ), (''PROCEDURE'', ''%''                 , ''%'', ''YES'', ''YES''), (''TABLE''    , ''mysql''             , ''%'', ''NO'' , ''NO'' ), (''TABLE''    , ''performance_schema'', ''%'', ''NO'' , ''NO'' ), (''TABLE''    , ''information_schema'', ''%'', ''NO'' , ''NO'' ), (''TABLE''    , ''%''                 , ''%'', ''YES'', ''YES''), (''TRIGGER''  , ''mysql''             , ''%'', ''NO'' , ''NO'' ), (''TRIGGER''  , ''performance_schema'', ''%'', ''NO'' , ''NO'' ), (''TRIGGER''  , ''information_schema'', ''%'', ''NO'' , ''NO'' ), (''TRIGGER''  , ''%''                 , ''%'', ''YES'', ''YES'')';  IF (in_verbose) THEN SELECT CONCAT('Resetting: setup_objects\n', REPLACE(@query, '  ', '')) AS status; END IF;  PREPARE reset_stmt FROM @query; EXECUTE reset_stmt; DEALLOCATE PREPARE reset_stmt;  SET @query = 'UPDATE performance_schema.threads SET INSTRUMENTED = ''YES''';  IF (in_verbose) THEN SELECT CONCAT('Resetting: threads\n', REPLACE(@query, '  ', '')) AS status; END IF;  PREPARE reset_stmt FROM @query; EXECUTE reset_stmt; DEALLOCATE PREPARE reset_stmt; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_setup_save`( IN in_timeout INT )
MODIFIES SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Saves the current configuration of Performance Schema, \n so that you can alter the setup for debugging purposes, \n but restore it to a previous state.\n \n Use the companion procedure - ps_setup_reload_saved(), to \n restore the saved config.\n \n The named lock "sys.ps_setup_save" is taken before the\n current configuration is saved. If the attempt to get the named\n lock times out, an error occurs.\n \n The lock is released after the settings have been restored by\n calling ps_setup_reload_saved().\n \n Requires the SUPER privilege for "SET sql_log_bin = 0;".\n \n Parameters\n \n in_timeout INT\n The timeout in seconds used when trying to obtain the lock.\n A negative timeout means infinite timeout.\n \n Example\n \n mysql> CALL sys.ps_setup_save(-1);\n Query OK, 0 rows affected (0.08 sec)\n \n mysql> UPDATE performance_schema.setup_instruments \n ->    SET enabled = ''YES'', timed = ''YES'';\n Query OK, 547 rows affected (0.40 sec)\n Rows matched: 784  Changed: 547  Warnings: 0\n \n /* Run some tests that need more detailed instrumentation here */\n \n mysql> CALL sys.ps_setup_reload_saved();\n Query OK, 0 rows affected (0.32 sec)\n '
  BEGIN DECLARE v_lock_result INT;  SET @log_bin := @@sql_log_bin; SET sql_log_bin = 0;  SELECT GET_LOCK('sys.ps_setup_save', in_timeout) INTO v_lock_result;  IF v_lock_result THEN DROP TEMPORARY TABLE IF EXISTS tmp_setup_actors; DROP TEMPORARY TABLE IF EXISTS tmp_setup_consumers; DROP TEMPORARY TABLE IF EXISTS tmp_setup_instruments; DROP TEMPORARY TABLE IF EXISTS tmp_threads;  CREATE TEMPORARY TABLE tmp_setup_actors LIKE performance_schema.setup_actors; CREATE TEMPORARY TABLE tmp_setup_consumers LIKE performance_schema.setup_consumers; CREATE TEMPORARY TABLE tmp_setup_instruments LIKE performance_schema.setup_instruments; CREATE TEMPORARY TABLE tmp_threads (THREAD_ID bigint unsigned NOT NULL PRIMARY KEY, INSTRUMENTED enum('YES','NO') NOT NULL);  INSERT INTO tmp_setup_actors SELECT * FROM performance_schema.setup_actors; INSERT INTO tmp_setup_consumers SELECT * FROM performance_schema.setup_consumers; INSERT INTO tmp_setup_instruments SELECT * FROM performance_schema.setup_instruments; INSERT INTO tmp_threads SELECT THREAD_ID, INSTRUMENTED FROM performance_schema.threads; ELSE SIGNAL SQLSTATE VALUE '90000' SET MESSAGE_TEXT = 'Could not lock the sys.ps_setup_save user lock, another thread has a saved configuration'; END IF;  SET sql_log_bin = @log_bin; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_setup_show_disabled`( IN in_show_instruments BOOLEAN, IN in_show_threads BOOLEAN )
READS SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Shows all currently disable Performance Schema configuration.\n \n Disabled users is only available for MySQL 5.7.6 and later.\n In earlier versions it was only possible to enable users.\n \n Parameters\n \n in_show_instruments (BOOLEAN):\n Whether to print disabled instruments (can print many items)\n \n in_show_threads (BOOLEAN):\n Whether to print disabled threads\n \n Example\n \n mysql> CALL sys.ps_setup_show_disabled(TRUE, TRUE);\n +----------------------------+\n | performance_schema_enabled |\n +----------------------------+\n |                          1 |\n +----------------------------+\n 1 row in set (0.00 sec)\n \n +--------------------+\n | disabled_users     |\n +--------------------+\n | ''mark''@''localhost'' |\n +--------------------+\n 1 row in set (0.00 sec)\n \n +-------------+----------------------+---------+-------+\n | object_type | objects              | enabled | timed |\n +-------------+----------------------+---------+-------+\n | EVENT       | mysql.%              | NO      | NO    |\n | EVENT       | performance_schema.% | NO      | NO    |\n | EVENT       | information_schema.% | NO      | NO    |\n | FUNCTION    | mysql.%              | NO      | NO    |\n | FUNCTION    | performance_schema.% | NO      | NO    |\n | FUNCTION    | information_schema.% | NO      | NO    |\n | PROCEDURE   | mysql.%              | NO      | NO    |\n | PROCEDURE   | performance_schema.% | NO      | NO    |\n | PROCEDURE   | information_schema.% | NO      | NO    |\n | TABLE       | mysql.%              | NO      | NO    |\n | TABLE       | performance_schema.% | NO      | NO    |\n | TABLE       | information_schema.% | NO      | NO    |\n | TRIGGER     | mysql.%              | NO      | NO    |\n | TRIGGER     | performance_schema.% | NO      | NO    |\n | TRIGGER     | information_schema.% | NO      | NO    |\n +-------------+----------------------+---------+-------+\n 15 rows in set (0.00 sec)\n \n +----------------------------------+\n | disabled_consumers               |\n +----------------------------------+\n | events_stages_current            |\n | events_stages_history            |\n | events_stages_history_long       |\n | events_statements_history        |\n | events_statements_history_long   |\n | events_transactions_history      |\n | events_transactions_history_long |\n | events_waits_current             |\n | events_waits_history             |\n | events_waits_history_long        |\n +----------------------------------+\n 10 rows in set (0.00 sec)\n \n Empty set (0.00 sec)\n \n +---------------------------------------------------------------------------------------+-------+\n | disabled_instruments                                                                  | timed |\n +---------------------------------------------------------------------------------------+-------+\n | wait/synch/mutex/sql/TC_LOG_MMAP::LOCK_tc                                             | NO    |\n | wait/synch/mutex/sql/LOCK_des_key_file                                                | NO    |\n | wait/synch/mutex/sql/MYSQL_BIN_LOG::LOCK_commit                                       | NO    |\n ...\n | memory/sql/servers_cache                                                              | NO    |\n | memory/sql/udf_mem                                                                    | NO    |\n | wait/lock/metadata/sql/mdl                                                            | NO    |\n +---------------------------------------------------------------------------------------+-------+\n 547 rows in set (0.00 sec)\n \n Query OK, 0 rows affected (0.01 sec)\n '
  BEGIN SELECT @@performance_schema AS performance_schema_enabled;   SELECT CONCAT('\'', user, '\'@\'', host, '\'') AS disabled_users FROM performance_schema.setup_actors WHERE enabled = 'NO' ORDER BY disabled_users;   SELECT object_type, CONCAT(object_schema, '.', object_name) AS objects, enabled, timed FROM performance_schema.setup_objects WHERE enabled = 'NO' ORDER BY object_type, objects;  SELECT name AS disabled_consumers FROM performance_schema.setup_consumers WHERE enabled = 'NO' ORDER BY disabled_consumers;  IF (in_show_threads) THEN SELECT IF(name = 'thread/sql/one_connection',  CONCAT(processlist_user, '@', processlist_host),  REPLACE(name, 'thread/', '')) AS disabled_threads, TYPE AS thread_type FROM performance_schema.threads WHERE INSTRUMENTED = 'NO' ORDER BY disabled_threads; END IF;  IF (in_show_instruments) THEN SELECT name AS disabled_instruments, timed FROM performance_schema.setup_instruments WHERE enabled = 'NO' ORDER BY disabled_instruments; END IF; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_setup_show_disabled_consumers`()
READS SQL DATA
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Shows all currently disabled consumers.\n \n Parameters\n \n None\n \n Example\n \n mysql> CALL sys.ps_setup_show_disabled_consumers();\n \n +---------------------------+\n | disabled_consumers        |\n +---------------------------+\n | events_statements_current |\n | global_instrumentation    |\n | thread_instrumentation    |\n | statements_digest         |\n +---------------------------+\n 4 rows in set (0.05 sec)\n '
  BEGIN SELECT name AS disabled_consumers FROM performance_schema.setup_consumers WHERE enabled = 'NO' ORDER BY disabled_consumers; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_setup_show_disabled_instruments`()
READS SQL DATA
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Shows all currently disabled instruments.\n \n Parameters\n \n None\n \n Example\n \n mysql> CALL sys.ps_setup_show_disabled_instruments();\n '
  BEGIN SELECT name AS disabled_instruments, timed FROM performance_schema.setup_instruments WHERE enabled = 'NO' ORDER BY disabled_instruments; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_setup_show_enabled`( IN in_show_instruments BOOLEAN, IN in_show_threads BOOLEAN )
READS SQL DATA
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Shows all currently enabled Performance Schema configuration.\n \n Parameters\n \n in_show_instruments (BOOLEAN):\n Whether to print enabled instruments (can print many items)\n \n in_show_threads (BOOLEAN):\n Whether to print enabled threads\n \n Example\n \n mysql> CALL sys.ps_setup_show_enabled(TRUE, TRUE);\n +----------------------------+\n | performance_schema_enabled |\n +----------------------------+\n |                          1 |\n +----------------------------+\n 1 row in set (0.00 sec)\n \n +---------------+\n | enabled_users |\n +---------------+\n | ''%''@''%''       |\n +---------------+\n 1 row in set (0.01 sec)\n \n +-------------+---------+---------+-------+\n | object_type | objects | enabled | timed |\n +-------------+---------+---------+-------+\n | EVENT       | %.%     | YES     | YES   |\n | FUNCTION    | %.%     | YES     | YES   |\n | PROCEDURE   | %.%     | YES     | YES   |\n | TABLE       | %.%     | YES     | YES   |\n | TRIGGER     | %.%     | YES     | YES   |\n +-------------+---------+---------+-------+\n 5 rows in set (0.01 sec)\n \n +---------------------------+\n | enabled_consumers         |\n +---------------------------+\n | events_statements_current |\n | global_instrumentation    |\n | thread_instrumentation    |\n | statements_digest         |\n +---------------------------+\n 4 rows in set (0.05 sec)\n \n +---------------------------------+-------------+\n | enabled_threads                 | thread_type |\n +---------------------------------+-------------+\n | sql/main                        | BACKGROUND  |\n | sql/thread_timer_notifier       | BACKGROUND  |\n | innodb/io_ibuf_thread           | BACKGROUND  |\n | innodb/io_log_thread            | BACKGROUND  |\n | innodb/io_read_thread           | BACKGROUND  |\n | innodb/io_read_thread           | BACKGROUND  |\n | innodb/io_write_thread          | BACKGROUND  |\n | innodb/io_write_thread          | BACKGROUND  |\n | innodb/page_cleaner_thread      | BACKGROUND  |\n | innodb/srv_lock_timeout_thread  | BACKGROUND  |\n | innodb/srv_error_monitor_thread | BACKGROUND  |\n | innodb/srv_monitor_thread       | BACKGROUND  |\n | innodb/srv_master_thread        | BACKGROUND  |\n | innodb/srv_purge_thread         | BACKGROUND  |\n | innodb/srv_worker_thread        | BACKGROUND  |\n | innodb/srv_worker_thread        | BACKGROUND  |\n | innodb/srv_worker_thread        | BACKGROUND  |\n | innodb/buf_dump_thread          | BACKGROUND  |\n | innodb/dict_stats_thread        | BACKGROUND  |\n | sql/signal_handler              | BACKGROUND  |\n | sql/compress_gtid_table         | FOREGROUND  |\n | root@localhost                  | FOREGROUND  |\n +---------------------------------+-------------+\n 22 rows in set (0.01 sec)\n \n +-------------------------------------+-------+\n | enabled_instruments                 | timed |\n +-------------------------------------+-------+\n | wait/io/file/sql/map                | YES   |\n | wait/io/file/sql/binlog             | YES   |\n ...\n | statement/com/Error                 | YES   |\n | statement/com/                      | YES   |\n | idle                                | YES   |\n +-------------------------------------+-------+\n 210 rows in set (0.08 sec)\n \n Query OK, 0 rows affected (0.89 sec)\n '
  BEGIN SELECT @@performance_schema AS performance_schema_enabled;  SELECT CONCAT('\'', user, '\'@\'', host, '\'') AS enabled_users FROM performance_schema.setup_actors  WHERE enabled = 'YES'  ORDER BY enabled_users;  SELECT object_type, CONCAT(object_schema, '.', object_name) AS objects, enabled, timed FROM performance_schema.setup_objects WHERE enabled = 'YES' ORDER BY object_type, objects;  SELECT name AS enabled_consumers FROM performance_schema.setup_consumers WHERE enabled = 'YES' ORDER BY enabled_consumers;  IF (in_show_threads) THEN SELECT IF(name = 'thread/sql/one_connection',  CONCAT(processlist_user, '@', processlist_host),  REPLACE(name, 'thread/', '')) AS enabled_threads, TYPE AS thread_type FROM performance_schema.threads WHERE INSTRUMENTED = 'YES' ORDER BY enabled_threads; END IF;  IF (in_show_instruments) THEN SELECT name AS enabled_instruments, timed FROM performance_schema.setup_instruments WHERE enabled = 'YES' ORDER BY enabled_instruments; END IF; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_setup_show_enabled_consumers`()
READS SQL DATA
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Shows all currently enabled consumers.\n \n Parameters\n \n None\n \n Example\n \n mysql> CALL sys.ps_setup_show_enabled_consumers();\n \n +---------------------------+\n | enabled_consumers         |\n +---------------------------+\n | events_statements_current |\n | global_instrumentation    |\n | thread_instrumentation    |\n | statements_digest         |\n +---------------------------+\n 4 rows in set (0.05 sec)\n '
  BEGIN SELECT name AS enabled_consumers FROM performance_schema.setup_consumers WHERE enabled = 'YES' ORDER BY enabled_consumers; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_setup_show_enabled_instruments`()
READS SQL DATA
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Shows all currently enabled instruments.\n \n Parameters\n \n None\n \n Example\n \n mysql> CALL sys.ps_setup_show_enabled_instruments();\n '
  BEGIN SELECT name AS enabled_instruments, timed FROM performance_schema.setup_instruments WHERE enabled = 'YES' ORDER BY enabled_instruments; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_statement_avg_latency_histogram`()
READS SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Outputs a textual histogram graph of the average latency values\n across all normalized queries tracked within the Performance Schema\n events_statements_summary_by_digest table.\n \n Can be used to show a very high level picture of what kind of \n latency distribution statements running within this instance have.\n \n Parameters\n \n None.\n \n Example\n \n mysql> CALL sys.ps_statement_avg_latency_histogram()\\G\n *************************** 1. row ***************************\n Performance Schema Statement Digest Average Latency Histogram:\n \n . = 1 unit\n * = 2 units\n # = 3 units\n \n (0 - 38ms)     240 | ################################################################################\n (38 - 77ms)    38  | ......................................\n (77 - 115ms)   3   | ...\n (115 - 154ms)  62  | *******************************\n (154 - 192ms)  3   | ...\n (192 - 231ms)  0   |\n (231 - 269ms)  0   |\n (269 - 307ms)  0   |\n (307 - 346ms)  0   |\n (346 - 384ms)  1   | .\n (384 - 423ms)  1   | .\n (423 - 461ms)  0   |\n (461 - 499ms)  0   |\n (499 - 538ms)  0   |\n (538 - 576ms)  0   |\n (576 - 615ms)  1   | .\n \n Total Statements: 350; Buckets: 16; Bucket Size: 38 ms;\n '
  BEGIN SELECT CONCAT('\n', '\n  . = 1 unit', '\n  * = 2 units', '\n  # = 3 units\n', @label := CONCAT(@label_inner := CONCAT('\n(0 - ', ROUND((@bucket_size := (SELECT ROUND((MAX(avg_us) - MIN(avg_us)) / (@buckets := 16)) AS size FROM sys.x$ps_digest_avg_latency_distribution)) / (@unit_div := 1000)), (@unit := 'ms'), ')'), REPEAT(' ', (@max_label_size := ((1 + LENGTH(ROUND((@bucket_size * 15) / @unit_div)) + 3 + LENGTH(ROUND(@bucket_size * 16) / @unit_div)) + 1)) - LENGTH(@label_inner)), @count_in_bucket := IFNULL((SELECT SUM(cnt) FROM sys.x$ps_digest_avg_latency_distribution AS b1  WHERE b1.avg_us <= @bucket_size), 0)), REPEAT(' ', (@max_label_len := (@max_label_size + LENGTH((@total_queries := (SELECT SUM(cnt) FROM sys.x$ps_digest_avg_latency_distribution)))) + 1) - LENGTH(@label)), '| ', IFNULL(REPEAT(IF(@count_in_bucket < (@one_unit := 40), '.', IF(@count_in_bucket < (@two_unit := 80), '*', '#')),  	             IF(@count_in_bucket < @one_unit, @count_in_bucket, 	             	IF(@count_in_bucket < @two_unit, @count_in_bucket / 2, @count_in_bucket / 3))), ''),  @label := CONCAT(@label_inner := CONCAT('\n(', ROUND(@bucket_size / @unit_div), ' - ', ROUND((@bucket_size * 2) / @unit_div), @unit, ')'), REPEAT(' ', @max_label_size - LENGTH(@label_inner)), @count_in_bucket := IFNULL((SELECT SUM(cnt) FROM sys.x$ps_digest_avg_latency_distribution AS b1  WHERE b1.avg_us > @bucket_size AND b1.avg_us <= @bucket_size * 2), 0)), REPEAT(' ', @max_label_len - LENGTH(@label)), '| ', IFNULL(REPEAT(IF(@count_in_bucket < @one_unit, '.', IF(@count_in_bucket < @two_unit, '*', '#')),  	             IF(@count_in_bucket < @one_unit, @count_in_bucket, 	             	IF(@count_in_bucket < @two_unit, @count_in_bucket / 2, @count_in_bucket / 3))), ''), @label := CONCAT(@label_inner := CONCAT('\n(', ROUND((@bucket_size * 2) / @unit_div), ' - ', ROUND((@bucket_size * 3) / @unit_div), @unit, ')'), REPEAT(' ', @max_label_size - LENGTH(@label_inner)), @count_in_bucket := IFNULL((SELECT SUM(cnt) FROM sys.x$ps_digest_avg_latency_distribution AS b1  WHERE b1.avg_us > @bucket_size * 2 AND b1.avg_us <= @bucket_size * 3), 0)), REPEAT(' ', @max_label_len - LENGTH(@label)), '| ', IFNULL(REPEAT(IF(@count_in_bucket < @one_unit, '.', IF(@count_in_bucket < @two_unit, '*', '#')),  	             IF(@count_in_bucket < @one_unit, @count_in_bucket, 	             	IF(@count_in_bucket < @two_unit, @count_in_bucket / 2, @count_in_bucket / 3))), ''), @label := CONCAT(@label_inner := CONCAT('\n(', ROUND((@bucket_size * 3) / @unit_div), ' - ', ROUND((@bucket_size * 4) / @unit_div), @unit, ')'), REPEAT(' ', @max_label_size - LENGTH(@label_inner)), @count_in_bucket := IFNULL((SELECT SUM(cnt) FROM sys.x$ps_digest_avg_latency_distribution AS b1  WHERE b1.avg_us > @bucket_size * 3 AND b1.avg_us <= @bucket_size * 4), 0)), REPEAT(' ', @max_label_len - LENGTH(@label)), '| ', IFNULL(REPEAT(IF(@count_in_bucket < @one_unit, '.', IF(@count_in_bucket < @two_unit, '*', '#')),  	             IF(@count_in_bucket < @one_unit, @count_in_bucket, 	             	IF(@count_in_bucket < @two_unit, @count_in_bucket / 2, @count_in_bucket / 3))), ''), @label := CONCAT(@label_inner := CONCAT('\n(', ROUND((@bucket_size * 4) / @unit_div), ' - ', ROUND((@bucket_size * 5) / @unit_div), @unit, ')'), REPEAT(' ', @max_label_size - LENGTH(@label_inner)), @count_in_bucket := IFNULL((SELECT SUM(cnt) FROM sys.x$ps_digest_avg_latency_distribution AS b1  WHERE b1.avg_us > @bucket_size * 4 AND b1.avg_us <= @bucket_size * 5), 0)), REPEAT(' ', @max_label_len - LENGTH(@label)), '| ', IFNULL(REPEAT(IF(@count_in_bucket < @one_unit, '.', IF(@count_in_bucket < @two_unit, '*', '#')),  	             IF(@count_in_bucket < @one_unit, @count_in_bucket, 	             	IF(@count_in_bucket < @two_unit, @count_in_bucket / 2, @count_in_bucket / 3))), ''), @label := CONCAT(@label_inner := CONCAT('\n(', ROUND((@bucket_size * 5) / @unit_div), ' - ', ROUND((@bucket_size * 6) / @unit_div), @unit, ')'), REPEAT(' ', @max_label_size - LENGTH(@label_inner)), @count_in_bucket := IFNULL((SELECT SUM(cnt) FROM sys.x$ps_digest_avg_latency_distribution AS b1  WHERE b1.avg_us > @bucket_size * 5 AND b1.avg_us <= @bucket_size * 6), 0)), REPEAT(' ', @max_label_len - LENGTH(@label)), '| ', IFNULL(REPEAT(IF(@count_in_bucket < @one_unit, '.', IF(@count_in_bucket < @two_unit, '*', '#')),  	             IF(@count_in_bucket < @one_unit, @count_in_bucket, 	             	IF(@count_in_bucket < @two_unit, @count_in_bucket / 2, @count_in_bucket / 3))), ''), @label := CONCAT(@label_inner := CONCAT('\n(', ROUND((@bucket_size * 6) / @unit_div), ' - ', ROUND((@bucket_size * 7) / @unit_div), @unit, ')'), REPEAT(' ', @max_label_size - LENGTH(@label_inner)), @count_in_bucket := IFNULL((SELECT SUM(cnt) FROM sys.x$ps_digest_avg_latency_distribution AS b1  WHERE b1.avg_us > @bucket_size * 6 AND b1.avg_us <= @bucket_size * 7), 0)), REPEAT(' ', @max_label_len - LENGTH(@label)), '| ', IFNULL(REPEAT(IF(@count_in_bucket < @one_unit, '.', IF(@count_in_bucket < @two_unit, '*', '#')),  	             IF(@count_in_bucket < @one_unit, @count_in_bucket, 	             	IF(@count_in_bucket < @two_unit, @count_in_bucket / 2, @count_in_bucket / 3))), ''), @label := CONCAT(@label_inner := CONCAT('\n(', ROUND((@bucket_size * 7) / @unit_div), ' - ', ROUND((@bucket_size * 8) / @unit_div), @unit, ')'), REPEAT(' ', @max_label_size - LENGTH(@label_inner)), @count_in_bucket := IFNULL((SELECT SUM(cnt) FROM sys.x$ps_digest_avg_latency_distribution AS b1  WHERE b1.avg_us > @bucket_size * 7 AND b1.avg_us <= @bucket_size * 8), 0)), REPEAT(' ', @max_label_len - LENGTH(@label)), '| ', IFNULL(REPEAT(IF(@count_in_bucket < @one_unit, '.', IF(@count_in_bucket < @two_unit, '*', '#')),  	             IF(@count_in_bucket < @one_unit, @count_in_bucket, 	             	IF(@count_in_bucket < @two_unit, @count_in_bucket / 2, @count_in_bucket / 3))), ''), @label := CONCAT(@label_inner := CONCAT('\n(', ROUND((@bucket_size * 8) / @unit_div), ' - ', ROUND((@bucket_size * 9) / @unit_div), @unit, ')'), REPEAT(' ', @max_label_size - LENGTH(@label_inner)), @count_in_bucket := IFNULL((SELECT SUM(cnt) FROM sys.x$ps_digest_avg_latency_distribution AS b1  WHERE b1.avg_us > @bucket_size * 8 AND b1.avg_us <= @bucket_size * 9), 0)), REPEAT(' ', @max_label_len - LENGTH(@label)), '| ', IFNULL(REPEAT(IF(@count_in_bucket < @one_unit, '.', IF(@count_in_bucket < @two_unit, '*', '#')),  	             IF(@count_in_bucket < @one_unit, @count_in_bucket, 	             	IF(@count_in_bucket < @two_unit, @count_in_bucket / 2, @count_in_bucket / 3))), ''), @label := CONCAT(@label_inner := CONCAT('\n(', ROUND((@bucket_size * 9) / @unit_div), ' - ', ROUND((@bucket_size * 10) / @unit_div), @unit, ')'), REPEAT(' ', @max_label_size - LENGTH(@label_inner)), @count_in_bucket := IFNULL((SELECT SUM(cnt) FROM sys.x$ps_digest_avg_latency_distribution AS b1  WHERE b1.avg_us > @bucket_size * 9 AND b1.avg_us <= @bucket_size * 10), 0)), REPEAT(' ', @max_label_len - LENGTH(@label)), '| ', IFNULL(REPEAT(IF(@count_in_bucket < @one_unit, '.', IF(@count_in_bucket < @two_unit, '*', '#')),  	             IF(@count_in_bucket < @one_unit, @count_in_bucket, 	             	IF(@count_in_bucket < @two_unit, @count_in_bucket / 2, @count_in_bucket / 3))), ''), @label := CONCAT(@label_inner := CONCAT('\n(', ROUND((@bucket_size * 10) / @unit_div), ' - ', ROUND((@bucket_size * 11) / @unit_div), @unit, ')'), REPEAT(' ', @max_label_size - LENGTH(@label_inner)), @count_in_bucket := IFNULL((SELECT SUM(cnt) FROM sys.x$ps_digest_avg_latency_distribution AS b1  WHERE b1.avg_us > @bucket_size * 10 AND b1.avg_us <= @bucket_size * 11), 0)), REPEAT(' ', @max_label_len - LENGTH(@label)), '| ', IFNULL(REPEAT(IF(@count_in_bucket < @one_unit, '.', IF(@count_in_bucket < @two_unit, '*', '#')),  	             IF(@count_in_bucket < @one_unit, @count_in_bucket, 	             	IF(@count_in_bucket < @two_unit, @count_in_bucket / 2, @count_in_bucket / 3))), ''), @label := CONCAT(@label_inner := CONCAT('\n(', ROUND((@bucket_size * 11) / @unit_div), ' - ', ROUND((@bucket_size * 12) / @unit_div), @unit, ')'), REPEAT(' ', @max_label_size - LENGTH(@label_inner)), @count_in_bucket := IFNULL((SELECT SUM(cnt) FROM sys.x$ps_digest_avg_latency_distribution AS b1  WHERE b1.avg_us > @bucket_size * 11 AND b1.avg_us <= @bucket_size * 12), 0)), REPEAT(' ', @max_label_len - LENGTH(@label)), '| ', IFNULL(REPEAT(IF(@count_in_bucket < @one_unit, '.', IF(@count_in_bucket < @two_unit, '*', '#')),  	             IF(@count_in_bucket < @one_unit, @count_in_bucket, 	             	IF(@count_in_bucket < @two_unit, @count_in_bucket / 2, @count_in_bucket / 3))), ''), @label := CONCAT(@label_inner := CONCAT('\n(', ROUND((@bucket_size * 12) / @unit_div), ' - ', ROUND((@bucket_size * 13) / @unit_div), @unit, ')'), REPEAT(' ', @max_label_size - LENGTH(@label_inner)), @count_in_bucket := IFNULL((SELECT SUM(cnt) FROM sys.x$ps_digest_avg_latency_distribution AS b1  WHERE b1.avg_us > @bucket_size * 12 AND b1.avg_us <= @bucket_size * 13), 0)), REPEAT(' ', @max_label_len - LENGTH(@label)), '| ', IFNULL(REPEAT(IF(@count_in_bucket < @one_unit, '.', IF(@count_in_bucket < @two_unit, '*', '#')),  	             IF(@count_in_bucket < @one_unit, @count_in_bucket, 	             	IF(@count_in_bucket < @two_unit, @count_in_bucket / 2, @count_in_bucket / 3))), ''), @label := CONCAT(@label_inner := CONCAT('\n(', ROUND((@bucket_size * 13) / @unit_div), ' - ', ROUND((@bucket_size * 14) / @unit_div), @unit, ')'), REPEAT(' ', @max_label_size - LENGTH(@label_inner)), @count_in_bucket := IFNULL((SELECT SUM(cnt) FROM sys.x$ps_digest_avg_latency_distribution AS b1  WHERE b1.avg_us > @bucket_size * 13 AND b1.avg_us <= @bucket_size * 14), 0)), REPEAT(' ', @max_label_len - LENGTH(@label)), '| ', IFNULL(REPEAT(IF(@count_in_bucket < @one_unit, '.', IF(@count_in_bucket < @two_unit, '*', '#')),  	             IF(@count_in_bucket < @one_unit, @count_in_bucket, 	             	IF(@count_in_bucket < @two_unit, @count_in_bucket / 2, @count_in_bucket / 3))), ''), @label := CONCAT(@label_inner := CONCAT('\n(', ROUND((@bucket_size * 14) / @unit_div), ' - ', ROUND((@bucket_size * 15) / @unit_div), @unit, ')'), REPEAT(' ', @max_label_size - LENGTH(@label_inner)), @count_in_bucket := IFNULL((SELECT SUM(cnt) FROM sys.x$ps_digest_avg_latency_distribution AS b1  WHERE b1.avg_us > @bucket_size * 14 AND b1.avg_us <= @bucket_size * 15), 0)), REPEAT(' ', @max_label_len - LENGTH(@label)), '| ', IFNULL(REPEAT(IF(@count_in_bucket < @one_unit, '.', IF(@count_in_bucket < @two_unit, '*', '#')),  	             IF(@count_in_bucket < @one_unit, @count_in_bucket, 	             	IF(@count_in_bucket < @two_unit, @count_in_bucket / 2, @count_in_bucket / 3))), ''), @label := CONCAT(@label_inner := CONCAT('\n(', ROUND((@bucket_size * 15) / @unit_div), ' - ', ROUND((@bucket_size * 16) / @unit_div), @unit, ')'), REPEAT(' ', @max_label_size - LENGTH(@label_inner)), @count_in_bucket := IFNULL((SELECT SUM(cnt) FROM sys.x$ps_digest_avg_latency_distribution AS b1  WHERE b1.avg_us > @bucket_size * 15 AND b1.avg_us <= @bucket_size * 16), 0)), REPEAT(' ', @max_label_len - LENGTH(@label)), '| ', IFNULL(REPEAT(IF(@count_in_bucket < @one_unit, '.', IF(@count_in_bucket < @two_unit, '*', '#')),  	             IF(@count_in_bucket < @one_unit, @count_in_bucket, 	             	IF(@count_in_bucket < @two_unit, @count_in_bucket / 2, @count_in_bucket / 3))), ''),  '\n\n  Total Statements: ', @total_queries, '; Buckets: ', @buckets , '; Bucket Size: ', ROUND(@bucket_size / @unit_div) , ' ', @unit, ';\n'  ) AS `Performance Schema Statement Digest Average Latency Histogram`;  END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_trace_statement_digest`( IN in_digest VARCHAR(32), IN in_runtime INT,  IN in_interval DECIMAL(2,2), IN in_start_fresh BOOLEAN, IN in_auto_enable BOOLEAN )
MODIFIES SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Traces all instrumentation within Performance Schema for a specific\n Statement Digest. \n \n When finding a statement of interest within the \n performance_schema.events_statements_summary_by_digest table, feed\n the DIGEST MD5 value in to this procedure, set how long to poll for, \n and at what interval to poll, and it will generate a report of all \n statistics tracked within Performance Schema for that digest for the\n interval.\n \n It will also attempt to generate an EXPLAIN for the longest running \n example of the digest during the interval. Note this may fail, as:\n \n * Performance Schema truncates long SQL_TEXT values (and hence the \n EXPLAIN will fail due to parse errors)\n * the default schema is sys (so tables that are not fully qualified\n in the query may not be found)\n * some queries such as SHOW are not supported in EXPLAIN.\n \n When the EXPLAIN fails, the error will be ignored and no EXPLAIN\n output generated.\n \n Requires the SUPER privilege for "SET sql_log_bin = 0;".\n \n Parameters\n \n in_digest (VARCHAR(32)):\n The statement digest identifier you would like to analyze\n in_runtime (INT):\n The number of seconds to run analysis for\n in_interval (DECIMAL(2,2)):\n The interval (in seconds, may be fractional) at which to try\n and take snapshots\n in_start_fresh (BOOLEAN):\n Whether to TRUNCATE the events_statements_history_long and\n events_stages_history_long tables before starting\n in_auto_enable (BOOLEAN):\n Whether to automatically turn on required consumers\n \n Example\n \n mysql> call ps_trace_statement_digest(''891ec6860f98ba46d89dd20b0c03652c'', 10, 0.1, true, true);\n +--------------------+\n | SUMMARY STATISTICS |\n +--------------------+\n | SUMMARY STATISTICS |\n +--------------------+\n 1 row in set (9.11 sec)\n \n +------------+-----------+-----------+-----------+---------------+------------+------------+\n | executions | exec_time | lock_time | rows_sent | rows_examined | tmp_tables | full_scans |\n +------------+-----------+-----------+-----------+---------------+------------+------------+\n |         21 | 4.11 ms   | 2.00 ms   |         0 |            21 |          0 |          0 |\n +------------+-----------+-----------+-----------+---------------+------------+------------+\n 1 row in set (9.11 sec)\n \n +------------------------------------------+-------+-----------+\n | event_name                               | count | latency   |\n +------------------------------------------+-------+-----------+\n | stage/sql/checking query cache for query |    16 | 724.37 us |\n | stage/sql/statistics                     |    16 | 546.92 us |\n | stage/sql/freeing items                  |    18 | 520.11 us |\n | stage/sql/init                           |    51 | 466.80 us |\n ...\n | stage/sql/cleaning up                    |    18 | 11.92 us  |\n | stage/sql/executing                      |    16 | 6.95 us   |\n +------------------------------------------+-------+-----------+\n 17 rows in set (9.12 sec)\n \n +---------------------------+\n | LONGEST RUNNING STATEMENT |\n +---------------------------+\n | LONGEST RUNNING STATEMENT |\n +---------------------------+\n 1 row in set (9.16 sec)\n \n +-----------+-----------+-----------+-----------+---------------+------------+-----------+\n | thread_id | exec_time | lock_time | rows_sent | rows_examined | tmp_tables | full_scan |\n +-----------+-----------+-----------+-----------+---------------+------------+-----------+\n |    166646 | 618.43 us | 1.00 ms   |         0 |             1 |          0 |         0 |\n +-----------+-----------+-----------+-----------+---------------+------------+-----------+\n 1 row in set (9.16 sec)\n \n // Truncated for clarity...\n +-----------------------------------------------------------------+\n | sql_text                                                        |\n +-----------------------------------------------------------------+\n | select hibeventhe0_.id as id1382_, hibeventhe0_.createdTime ... |\n +-----------------------------------------------------------------+\n 1 row in set (9.17 sec)\n \n +------------------------------------------+-----------+\n | event_name                               | latency   |\n +------------------------------------------+-----------+\n | stage/sql/init                           | 8.61 us   |\n | stage/sql/Waiting for query cache lock   | 453.23 us |\n | stage/sql/init                           | 331.07 ns |\n | stage/sql/checking query cache for query | 43.04 us  |\n ...\n | stage/sql/freeing items                  | 30.46 us  |\n | stage/sql/cleaning up                    | 662.13 ns |\n +------------------------------------------+-----------+\n 18 rows in set (9.23 sec)\n \n +----+-------------+--------------+-------+---------------+-----------+---------+-------------+------+-------+\n | id | select_type | table        | type  | possible_keys | key       | key_len | ref         | rows | Extra |\n +----+-------------+--------------+-------+---------------+-----------+---------+-------------+------+-------+\n |  1 | SIMPLE      | hibeventhe0_ | const | fixedTime     | fixedTime | 775     | const,const |    1 | NULL  |\n +----+-------------+--------------+-------+---------------+-----------+---------+-------------+------+-------+\n 1 row in set (9.27 sec)\n \n Query OK, 0 rows affected (9.28 sec)\n '
  BEGIN  DECLARE v_start_fresh BOOLEAN DEFAULT false; DECLARE v_auto_enable BOOLEAN DEFAULT false; DECLARE v_explain     BOOLEAN DEFAULT true; DECLARE v_this_thread_enabed ENUM('YES', 'NO'); DECLARE v_runtime INT DEFAULT 0; DECLARE v_start INT DEFAULT 0; DECLARE v_found_stmts INT;  SET @log_bin := @@sql_log_bin; SET sql_log_bin = 0;  SELECT INSTRUMENTED INTO v_this_thread_enabed FROM performance_schema.threads WHERE PROCESSLIST_ID = CONNECTION_ID(); CALL sys.ps_setup_disable_thread(CONNECTION_ID());  DROP TEMPORARY TABLE IF EXISTS stmt_trace; CREATE TEMPORARY TABLE stmt_trace ( thread_id BIGINT UNSIGNED, timer_start BIGINT UNSIGNED, event_id BIGINT UNSIGNED, sql_text longtext, timer_wait BIGINT UNSIGNED, lock_time BIGINT UNSIGNED, errors BIGINT UNSIGNED, mysql_errno INT, rows_sent BIGINT UNSIGNED, rows_affected BIGINT UNSIGNED, rows_examined BIGINT UNSIGNED, created_tmp_tables BIGINT UNSIGNED, created_tmp_disk_tables BIGINT UNSIGNED, no_index_used BIGINT UNSIGNED, PRIMARY KEY (thread_id, timer_start) );  DROP TEMPORARY TABLE IF EXISTS stmt_stages; CREATE TEMPORARY TABLE stmt_stages ( event_id BIGINT UNSIGNED, stmt_id BIGINT UNSIGNED, event_name VARCHAR(128), timer_wait BIGINT UNSIGNED, PRIMARY KEY (event_id) );  SET v_start_fresh = in_start_fresh; IF v_start_fresh THEN TRUNCATE TABLE performance_schema.events_statements_history_long; TRUNCATE TABLE performance_schema.events_stages_history_long; END IF;  SET v_auto_enable = in_auto_enable; IF v_auto_enable THEN CALL sys.ps_setup_save(0);  UPDATE performance_schema.threads SET INSTRUMENTED = IF(PROCESSLIST_ID IS NOT NULL, 'YES', 'NO');  UPDATE performance_schema.setup_consumers SET ENABLED = 'YES' WHERE NAME NOT LIKE '%\_history' AND NAME NOT LIKE 'events_wait%' AND NAME NOT LIKE 'events_transactions%' AND NAME <> 'statements_digest';  UPDATE performance_schema.setup_instruments SET ENABLED = 'YES', TIMED   = 'YES' WHERE NAME LIKE 'statement/%' OR NAME LIKE 'stage/%'; END IF;  WHILE v_runtime < in_runtime DO SELECT UNIX_TIMESTAMP() INTO v_start;  INSERT IGNORE INTO stmt_trace SELECT thread_id, timer_start, event_id, sql_text, timer_wait, lock_time, errors, mysql_errno,  rows_sent, rows_affected, rows_examined, created_tmp_tables, created_tmp_disk_tables, no_index_used FROM performance_schema.events_statements_history_long WHERE digest = in_digest;  INSERT IGNORE INTO stmt_stages SELECT stages.event_id, stmt_trace.event_id, stages.event_name, stages.timer_wait FROM performance_schema.events_stages_history_long AS stages JOIN stmt_trace ON stages.nesting_event_id = stmt_trace.event_id;  SELECT SLEEP(in_interval) INTO @sleep; SET v_runtime = v_runtime + (UNIX_TIMESTAMP() - v_start); END WHILE;  SELECT "SUMMARY STATISTICS";  SELECT COUNT(*) executions, sys.format_time(SUM(timer_wait)) AS exec_time, sys.format_time(SUM(lock_time)) AS lock_time, SUM(rows_sent) AS rows_sent, SUM(rows_affected) AS rows_affected, SUM(rows_examined) AS rows_examined, SUM(created_tmp_tables) AS tmp_tables, SUM(no_index_used) AS full_scans FROM stmt_trace;  SELECT event_name, COUNT(*) as count, sys.format_time(SUM(timer_wait)) as latency FROM stmt_stages GROUP BY event_name ORDER BY SUM(timer_wait) DESC;  SELECT "LONGEST RUNNING STATEMENT";  SELECT thread_id, sys.format_time(timer_wait) AS exec_time, sys.format_time(lock_time) AS lock_time, rows_sent, rows_affected, rows_examined, created_tmp_tables AS tmp_tables, no_index_used AS full_scan FROM stmt_trace ORDER BY timer_wait DESC LIMIT 1;  SELECT sql_text FROM stmt_trace ORDER BY timer_wait DESC LIMIT 1;  SELECT sql_text, event_id INTO @sql, @sql_id FROM stmt_trace ORDER BY timer_wait DESC LIMIT 1;  IF (@sql_id IS NOT NULL) THEN SELECT event_name, sys.format_time(timer_wait) as latency FROM stmt_stages WHERE stmt_id = @sql_id ORDER BY event_id; END IF;  DROP TEMPORARY TABLE stmt_trace; DROP TEMPORARY TABLE stmt_stages;  IF (@sql IS NOT NULL) THEN SET @stmt := CONCAT("EXPLAIN FORMAT=JSON ", @sql); BEGIN DECLARE CONTINUE HANDLER FOR 1064, 1146 SET v_explain = false;  PREPARE explain_stmt FROM @stmt; END;  IF (v_explain) THEN EXECUTE explain_stmt; DEALLOCATE PREPARE explain_stmt; END IF; END IF;  IF v_auto_enable THEN CALL sys.ps_setup_reload_saved(); END IF; IF (v_this_thread_enabed = 'YES') THEN CALL sys.ps_setup_enable_thread(CONNECTION_ID()); END IF;  SET sql_log_bin = @log_bin; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_trace_thread`( IN in_thread_id BIGINT UNSIGNED, IN in_outfile VARCHAR(255), IN in_max_runtime DECIMAL(20,2), IN in_interval DECIMAL(20,2), IN in_start_fresh BOOLEAN, IN in_auto_setup BOOLEAN, IN in_debug BOOLEAN )
MODIFIES SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Dumps all data within Performance Schema for an instrumented thread,\n to create a DOT formatted graph file. \n \n Each resultset returned from the procedure should be used for a complete graph\n \n Requires the SUPER privilege for "SET sql_log_bin = 0;".\n \n Parameters\n \n in_thread_id (BIGINT UNSIGNED):\n The thread that you would like a stack trace for\n in_outfile  (VARCHAR(255)):\n The filename the dot file will be written to\n in_max_runtime (DECIMAL(20,2)):\n The maximum time to keep collecting data.\n Use NULL to get the default which is 60 seconds.\n in_interval (DECIMAL(20,2)): \n How long to sleep between data collections. \n Use NULL to get the default which is 1 second.\n in_start_fresh (BOOLEAN):\n Whether to reset all Performance Schema data before tracing.\n in_auto_setup (BOOLEAN):\n Whether to disable all other threads and enable all consumers/instruments. \n This will also reset the settings at the end of the run.\n in_debug (BOOLEAN):\n Whether you would like to include file:lineno in the graph\n \n Example\n \n mysql> CALL sys.ps_trace_thread(25, CONCAT(''/tmp/stack-'', REPLACE(NOW(), '' '', ''-''), ''.dot''), NULL, NULL, TRUE, TRUE, TRUE);\n +-------------------+\n | summary           |\n +-------------------+\n | Disabled 1 thread |\n +-------------------+\n 1 row in set (0.00 sec)\n \n +---------------------------------------------+\n | Info                                        |\n +---------------------------------------------+\n | Data collection starting for THREAD_ID = 25 |\n +---------------------------------------------+\n 1 row in set (0.03 sec)\n \n +-----------------------------------------------------------+\n | Info                                                      |\n +-----------------------------------------------------------+\n | Stack trace written to /tmp/stack-2014-02-16-21:18:41.dot |\n +-----------------------------------------------------------+\n 1 row in set (60.07 sec)\n \n +-------------------------------------------------------------------+\n | Convert to PDF                                                    |\n +-------------------------------------------------------------------+\n | dot -Tpdf -o /tmp/stack_25.pdf /tmp/stack-2014-02-16-21:18:41.dot |\n +-------------------------------------------------------------------+\n 1 row in set (60.07 sec)\n \n +-------------------------------------------------------------------+\n | Convert to PNG                                                    |\n +-------------------------------------------------------------------+\n | dot -Tpng -o /tmp/stack_25.png /tmp/stack-2014-02-16-21:18:41.dot |\n +-------------------------------------------------------------------+\n 1 row in set (60.07 sec)\n \n +------------------+\n | summary          |\n +------------------+\n | Enabled 1 thread |\n +------------------+\n 1 row in set (60.32 sec)\n '
  BEGIN DECLARE v_done bool DEFAULT FALSE; DECLARE v_start, v_runtime DECIMAL(20,2) DEFAULT 0.0; DECLARE v_min_event_id bigint unsigned DEFAULT 0; DECLARE v_this_thread_enabed ENUM('YES', 'NO'); DECLARE v_event longtext; DECLARE c_stack CURSOR FOR SELECT CONCAT(IF(nesting_event_id IS NOT NULL, CONCAT(nesting_event_id, ' -> '), ''),  event_id, '; ', event_id, ' [label="', '(', sys.format_time(timer_wait), ') ', IF (event_name NOT LIKE 'wait/io%',  SUBSTRING_INDEX(event_name, '/', -2),  IF (event_name NOT LIKE 'wait/io/file%' OR event_name NOT LIKE 'wait/io/socket%', SUBSTRING_INDEX(event_name, '/', -4), event_name) ), IF (event_name LIKE 'transaction', IFNULL(CONCAT('\\n', wait_info), ''), ''), IF (event_name LIKE 'statement/%', IFNULL(CONCAT('\\n', wait_info), ''), ''), IF (in_debug AND event_name LIKE 'wait%', wait_info, ''), '", ',  CASE WHEN event_name LIKE 'wait/io/file%' THEN  'shape=box, style=filled, color=red' WHEN event_name LIKE 'wait/io/table%' THEN  'shape=box, style=filled, color=green' WHEN event_name LIKE 'wait/io/socket%' THEN 'shape=box, style=filled, color=yellow' WHEN event_name LIKE 'wait/synch/mutex%' THEN 'style=filled, color=lightskyblue' WHEN event_name LIKE 'wait/synch/cond%' THEN 'style=filled, color=darkseagreen3' WHEN event_name LIKE 'wait/synch/rwlock%' THEN 'style=filled, color=orchid' WHEN event_name LIKE 'wait/synch/sxlock%' THEN 'style=filled, color=palevioletred'  WHEN event_name LIKE 'wait/lock%' THEN 'shape=box, style=filled, color=tan' WHEN event_name LIKE 'statement/%' THEN CONCAT('shape=box, style=bold', CASE WHEN event_name LIKE 'statement/com/%' THEN ' style=filled, color=darkseagreen' ELSE IF((timer_wait/1000000000000) > @@long_query_time,  ' style=filled, color=red',  ' style=filled, color=lightblue') END ) WHEN event_name LIKE 'transaction' THEN 'shape=box, style=filled, color=lightblue3' WHEN event_name LIKE 'stage/%' THEN 'style=filled, color=slategray3' WHEN event_name LIKE '%idle%' THEN 'shape=box, style=filled, color=firebrick3' ELSE '' END, '];\n' ) event, event_id FROM ( (SELECT thread_id, event_id, event_name, timer_wait, timer_start, nesting_event_id, CONCAT('trx_id: ',  IFNULL(trx_id, ''), '\\n', 'gtid: ', IFNULL(gtid, ''), '\\n', 'state: ', state, '\\n', 'mode: ', access_mode, '\\n', 'isolation: ', isolation_level, '\\n', 'autocommit: ', autocommit, '\\n', 'savepoints: ', number_of_savepoints, '\\n' ) AS wait_info FROM performance_schema.events_transactions_history_long WHERE thread_id = in_thread_id AND event_id > v_min_event_id) UNION (SELECT thread_id, event_id, event_name, timer_wait, timer_start, nesting_event_id,  CONCAT('statement: ', sql_text, '\\n', 'errors: ', errors, '\\n', 'warnings: ', warnings, '\\n', 'lock time: ', sys.format_time(lock_time),'\\n', 'rows affected: ', rows_affected, '\\n', 'rows sent: ', rows_sent, '\\n', 'rows examined: ', rows_examined, '\\n', 'tmp tables: ', created_tmp_tables, '\\n', 'tmp disk tables: ', created_tmp_disk_tables, '\\n' 'select scan: ', select_scan, '\\n', 'select full join: ', select_full_join, '\\n', 'select full range join: ', select_full_range_join, '\\n', 'select range: ', select_range, '\\n', 'select range check: ', select_range_check, '\\n',  'sort merge passes: ', sort_merge_passes, '\\n', 'sort rows: ', sort_rows, '\\n', 'sort range: ', sort_range, '\\n', 'sort scan: ', sort_scan, '\\n', 'no index used: ', IF(no_index_used, 'TRUE', 'FALSE'), '\\n', 'no good index used: ', IF(no_good_index_used, 'TRUE', 'FALSE'), '\\n' ) AS wait_info FROM performance_schema.events_statements_history_long WHERE thread_id = in_thread_id AND event_id > v_min_event_id) UNION (SELECT thread_id, event_id, event_name, timer_wait, timer_start, nesting_event_id, null AS wait_info FROM performance_schema.events_stages_history_long  WHERE thread_id = in_thread_id AND event_id > v_min_event_id) UNION  (SELECT thread_id, event_id,  CONCAT(event_name,  IF(event_name NOT LIKE 'wait/synch/mutex%', IFNULL(CONCAT(' - ', operation), ''), ''),  IF(number_of_bytes IS NOT NULL, CONCAT(' ', number_of_bytes, ' bytes'), ''), IF(event_name LIKE 'wait/io/file%', '\\n', ''), IF(object_schema IS NOT NULL, CONCAT('\\nObject: ', object_schema, '.'), ''),  IF(object_name IS NOT NULL,  IF (event_name LIKE 'wait/io/socket%', CONCAT('\\n', IF (object_name LIKE ':0%', @@socket, object_name)), object_name), '' ), IF(index_name IS NOT NULL, CONCAT(' Index: ', index_name), ''), '\\n' ) AS event_name, timer_wait, timer_start, nesting_event_id, source AS wait_info FROM performance_schema.events_waits_history_long WHERE thread_id = in_thread_id AND event_id > v_min_event_id) ) events  ORDER BY event_id; DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;  SET @log_bin := @@sql_log_bin; SET sql_log_bin = 0;  SELECT INSTRUMENTED INTO v_this_thread_enabed FROM performance_schema.threads WHERE PROCESSLIST_ID = CONNECTION_ID(); CALL sys.ps_setup_disable_thread(CONNECTION_ID());  IF (in_auto_setup) THEN CALL sys.ps_setup_save(0);  DELETE FROM performance_schema.setup_actors;  UPDATE performance_schema.threads SET INSTRUMENTED = IF(THREAD_ID = in_thread_id, 'YES', 'NO');  UPDATE performance_schema.setup_consumers SET ENABLED = 'YES' WHERE NAME NOT LIKE '%\_history';  UPDATE performance_schema.setup_instruments SET ENABLED = 'YES', TIMED   = 'YES'; END IF;  IF (in_start_fresh) THEN TRUNCATE performance_schema.events_transactions_history_long; TRUNCATE performance_schema.events_statements_history_long; TRUNCATE performance_schema.events_stages_history_long; TRUNCATE performance_schema.events_waits_history_long; END IF;  DROP TEMPORARY TABLE IF EXISTS tmp_events; CREATE TEMPORARY TABLE tmp_events ( event_id bigint unsigned NOT NULL, event longblob, PRIMARY KEY (event_id) );  INSERT INTO tmp_events VALUES (0, CONCAT('digraph events { rankdir=LR; nodesep=0.10;\n', '// Stack created .....: ', NOW(), '\n', '// MySQL version .....: ', VERSION(), '\n', '// MySQL hostname ....: ', @@hostname, '\n', '// MySQL port ........: ', @@port, '\n', '// MySQL socket ......: ', @@socket, '\n', '// MySQL user ........: ', CURRENT_USER(), '\n'));  SELECT CONCAT('Data collection starting for THREAD_ID = ', in_thread_id) AS 'Info';  SET v_min_event_id = 0, v_start        = UNIX_TIMESTAMP(), in_interval    = IFNULL(in_interval, 1.00), in_max_runtime = IFNULL(in_max_runtime, 60.00);  WHILE (v_runtime < in_max_runtime AND (SELECT INSTRUMENTED FROM performance_schema.threads WHERE THREAD_ID = in_thread_id) = 'YES') DO SET v_done = FALSE; OPEN c_stack; c_stack_loop: LOOP FETCH c_stack INTO v_event, v_min_event_id; IF v_done THEN LEAVE c_stack_loop; END IF;  IF (LENGTH(v_event) > 0) THEN INSERT INTO tmp_events VALUES (v_min_event_id, v_event); END IF; END LOOP; CLOSE c_stack;  SELECT SLEEP(in_interval) INTO @sleep; SET v_runtime = (UNIX_TIMESTAMP() - v_start); END WHILE;  INSERT INTO tmp_events VALUES (v_min_event_id+1, '}');  SET @query = CONCAT('SELECT event FROM tmp_events ORDER BY event_id INTO OUTFILE ''', in_outfile, ''' FIELDS ESCAPED BY '''' LINES TERMINATED BY '''''); PREPARE stmt_output FROM @query; EXECUTE stmt_output; DEALLOCATE PREPARE stmt_output;  SELECT CONCAT('Stack trace written to ', in_outfile) AS 'Info'; SELECT CONCAT('dot -Tpdf -o /tmp/stack_', in_thread_id, '.pdf ', in_outfile) AS 'Convert to PDF'; SELECT CONCAT('dot -Tpng -o /tmp/stack_', in_thread_id, '.png ', in_outfile) AS 'Convert to PNG'; DROP TEMPORARY TABLE tmp_events;  IF (in_auto_setup) THEN CALL sys.ps_setup_reload_saved(); END IF; IF (v_this_thread_enabed = 'YES') THEN CALL sys.ps_setup_enable_thread(CONNECTION_ID()); END IF;  SET sql_log_bin = @log_bin; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `ps_truncate_all_tables`( IN in_verbose BOOLEAN )
MODIFIES SQL DATA
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Truncates all summary tables within Performance Schema, \n resetting all aggregated instrumentation as a snapshot.\n \n Parameters\n \n in_verbose (BOOLEAN):\n Whether to print each TRUNCATE statement before running\n \n Example\n \n mysql> CALL sys.ps_truncate_all_tables(false);\n +---------------------+\n | summary             |\n +---------------------+\n | Truncated 44 tables |\n +---------------------+\n 1 row in set (0.10 sec)\n \n Query OK, 0 rows affected (0.10 sec)\n '
  BEGIN DECLARE v_done INT DEFAULT FALSE; DECLARE v_total_tables INT DEFAULT 0; DECLARE v_ps_table VARCHAR(64); DECLARE ps_tables CURSOR FOR SELECT table_name  FROM INFORMATION_SCHEMA.TABLES  WHERE table_schema = 'performance_schema'  AND (table_name LIKE '%summary%'  OR table_name LIKE '%history%'); DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;  OPEN ps_tables;  ps_tables_loop: LOOP FETCH ps_tables INTO v_ps_table; IF v_done THEN LEAVE ps_tables_loop; END IF;  SET @truncate_stmt := CONCAT('TRUNCATE TABLE performance_schema.', v_ps_table); IF in_verbose THEN SELECT CONCAT('Running: ', @truncate_stmt) AS status; END IF;  PREPARE truncate_stmt FROM @truncate_stmt; EXECUTE truncate_stmt; DEALLOCATE PREPARE truncate_stmt;  SET v_total_tables = v_total_tables + 1; END LOOP;  CLOSE ps_tables;  SELECT CONCAT('Truncated ', v_total_tables, ' tables') AS summary;  END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `statement_performance_analyzer`( IN in_action ENUM('snapshot', 'overall', 'delta', 'create_table', 'create_tmp', 'save', 'cleanup'), IN in_table VARCHAR(129), IN in_views SET ('with_runtimes_in_95th_percentile', 'analysis', 'with_errors_or_warnings', 'with_full_table_scans', 'with_sorting', 'with_temp_tables', 'custom') )
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Create a report of the statements running on the server.\n \n The views are calculated based on the overall and/or delta activity.\n \n Requires the SUPER privilege for "SET sql_log_bin = 0;".\n \n Parameters\n \n in_action (ENUM(''snapshot'', ''overall'', ''delta'', ''create_tmp'', ''create_table'', ''save'', ''cleanup'')):\n The action to take. Supported actions are:\n * snapshot      Store a snapshot. The default is to make a snapshot of the current content of\n performance_schema.events_statements_summary_by_digest, but by setting in_table\n this can be overwritten to copy the content of the specified table.\n The snapshot is stored in the sys.tmp_digests temporary table.\n * overall       Generate analyzis based on the content specified by in_table. For the overall analyzis,\n in_table can be NOW() to use a fresh snapshot. This will overwrite an existing snapshot.\n Use NULL for in_table to use the existing snapshot. If in_table IS NULL and no snapshot\n exists, a new will be created.\n See also in_views and @sys.statement_performance_analyzer.limit.\n * delta         Generate a delta analysis. The delta will be calculated between the reference table in\n in_table and the snapshot. An existing snapshot must exist.\n The action uses the sys.tmp_digests_delta temporary table.\n See also in_views and @sys.statement_performance_analyzer.limit.\n * create_table  Create a regular table suitable for storing the snapshot for later use, e.g. for\n calculating deltas.\n * create_tmp    Create a temporary table suitable for storing the snapshot for later use, e.g. for\n calculating deltas.\n * save          Save the snapshot in the table specified by in_table. The table must exists and have\n the correct structure.\n If no snapshot exists, a new is created.\n * cleanup       Remove the temporary tables used for the snapshot and delta.\n \n in_table (VARCHAR(129)):\n The table argument used for some actions. Use the format ''db1.t1'' or ''t1'' without using any backticks (`)\n for quoting. Periods (.) are not supported in the database and table names.\n \n The meaning of the table for each action supporting the argument is:\n \n * snapshot      The snapshot is created based on the specified table. Set to NULL or NOW() to use\n the current content of performance_schema.events_statements_summary_by_digest.\n * overall       The table with the content to create the overall analyzis for. The following values\n can be used:\n - A table name - use the content of that table.\n - NOW()        - create a fresh snapshot and overwrite the existing snapshot.\n - NULL         - use the last stored snapshot.\n * delta         The table name is mandatory and specified the reference view to compare the currently\n stored snapshot against. If no snapshot exists, a new will be created.\n * create_table  The name of the regular table to create.\n * create_tmp    The name of the temporary table to create.\n * save          The name of the table to save the currently stored snapshot into.\n \n in_views (SET (''with_runtimes_in_95th_percentile'', ''analysis'', ''with_errors_or_warnings'',\n ''with_full_table_scans'', ''with_sorting'', ''with_temp_tables'', ''custom''))\n Which views to include:  * with_runtimes_in_95th_percentile  Based on the sys.statements_with_runtimes_in_95th_percentile view * analysis                          Based on the sys.statement_analysis view * with_errors_or_warnings           Based on the sys.statements_with_errors_or_warnings view * with_full_table_scans             Based on the sys.statements_with_full_table_scans view * with_sorting                      Based on the sys.statements_with_sorting view * with_temp_tables                  Based on the sys.statements_with_temp_tables view * custom                            Use a custom view. This view must be specified in @sys.statement_performance_analyzer.view to an existing view or a query  Default is to include all except ''custom''.   Configuration Options  sys.statement_performance_analyzer.limit The maximum number of rows to include for the views that does not have a built-in limit (e.g. the 95th percentile view). If not set the limit is 100.  sys.statement_performance_analyzer.view Used together with the ''custom'' view. If the value contains a space, it is considered a query, otherwise it must be an existing view querying the performance_schema.events_statements_summary_by_digest table. There cannot be any limit clause including in the query or view definition if @sys.statement_performance_analyzer.limit > 0. If specifying a view, use the same format as for in_table.  sys.debug Whether to provide debugging output. Default is ''OFF''. Set to ''ON'' to include.   Example  To create a report with the queries in the 95th percentile since last truncate of performance_schema.events_statements_summary_by_digest and the delta for a 1 minute period:  1. Create a temporary table to store the initial snapshot. 2. Create the initial snapshot. 3. Save the initial snapshot in the temporary table. 4. Wait one minute. 5. Create a new snapshot. 6. Perform analyzis based on the new snapshot. 7. Perform analyzis based on the delta between the initial and new snapshots.  mysql> CALL sys.statement_performance_analyzer(''create_tmp'', ''mydb.tmp_digests_ini'', NULL); Query OK, 0 rows affected (0.08 sec)  mysql> CALL sys.statement_performance_analyzer(''snapshot'', NULL, NULL); Query OK, 0 rows affected (0.02 sec)  mysql> CALL sys.statement_performance_analyzer(''save'', ''mydb.tmp_digests_ini'', NULL); Query OK, 0 rows affected (0.00 sec)  mysql> DO SLEEP(60); Query OK, 0 rows affected (1 min 0.00 sec)  mysql> CALL sys.statement_performance_analyzer(''snapshot'', NULL, NULL); Query OK, 0 rows affected (0.02 sec)  mysql> CALL sys.statement_performance_analyzer(''overall'', NULL, ''with_runtimes_in_95th_percentile''); +-----------------------------------------+ | Next Output                             | +-----------------------------------------+ | Queries with Runtime in 95th Percentile | +-----------------------------------------+ 1 row in set (0.05 sec)  ...  mysql> CALL sys.statement_performance_analyzer(''delta'', ''mydb.tmp_digests_ini'', ''with_runtimes_in_95th_percentile''); +-----------------------------------------+ | Next Output                             | +-----------------------------------------+ | Queries with Runtime in 95th Percentile | +-----------------------------------------+ 1 row in set (0.03 sec)  ...   To create an overall report of the 95th percentile queries and the top 10 queries with full table scans:  mysql> CALL sys.statement_performance_analyzer(''snapshot'', NULL, NULL); Query OK, 0 rows affected (0.01 sec)                                     mysql> SET @sys.statement_performance_analyzer.limit = 10; Query OK, 0 rows affected (0.00 sec)            mysql> CALL sys.statement_performance_analyzer(''overall'', NULL, ''with_runtimes_in_95th_percentile,with_full_table_scans''); +-----------------------------------------+ | Next Output                             | +-----------------------------------------+ | Queries with Runtime in 95th Percentile | +-----------------------------------------+ 1 row in set (0.01 sec)  ...  +-------------------------------------+ | Next Output                         | +-------------------------------------+ | Top 10 Queries with Full Table Scan | +-------------------------------------+ 1 row in set (0.09 sec)  ...   Use a custom view showing the top 10 query sorted by total execution time refreshing the view every minute using the watch command in Linux.  mysql> CREATE OR REPLACE VIEW mydb.my_statements AS -> SELECT sys.format_statement(DIGEST_TEXT) AS query, ->        SCHEMA_NAME AS db, ->        COUNT_STAR AS exec_count, ->        sys.format_time(SUM_TIMER_WAIT) AS total_latency, ->        sys.format_time(AVG_TIMER_WAIT) AS avg_latency, ->        ROUND(IFNULL(SUM_ROWS_SENT / NULLIF(COUNT_STAR, 0), 0)) AS rows_sent_avg, ->        ROUND(IFNULL(SUM_ROWS_EXAMINED / NULLIF(COUNT_STAR, 0), 0)) AS rows_examined_avg, ->        ROUND(IFNULL(SUM_ROWS_AFFECTED / NULLIF(COUNT_STAR, 0), 0)) AS rows_affected_avg, ->        DIGEST AS digest ->   FROM performance_schema.events_statements_summary_by_digest -> ORDER BY SUM_TIMER_WAIT DESC; Query OK, 0 rows affected (0.01 sec)  mysql> CALL sys.statement_performance_analyzer(''create_table'', ''mydb.digests_prev'', NULL); Query OK, 0 rows affected (0.10 sec)  shell$ watch -n 60 "mysql sys --table -e " > SET @sys.statement_performance_analyzer.view = ''mydb.my_statements''; > SET @sys.statement_performance_analyzer.limit = 10; > CALL statement_performance_analyzer(''snapshot'', NULL, NULL); > CALL statement_performance_analyzer(''delta'', ''mydb.digests_prev'', ''custom''); > CALL statement_performance_analyzer(''save'', ''mydb.digests_prev'', NULL); > ""  Every 60.0s: mysql sys --table -e "                                                                                                   ...  Mon Dec 22 10:58:51 2014  +----------------------------------+ | Next Output                      | +----------------------------------+ | Top 10 Queries Using Custom View | +----------------------------------+ +-------------------+-------+------------+---------------+-------------+---------------+-------------------+-------------------+----------------------------------+ | query             | db    | exec_count | total_latency | avg_latency | rows_sent_avg | rows_examined_avg | rows_affected_avg | digest                           | +-------------------+-------+------------+---------------+-------------+---------------+-------------------+-------------------+----------------------------------+ ... '
  BEGIN DECLARE v_table_exists, v_tmp_digests_table_exists, v_custom_view_exists ENUM('', 'BASE TABLE', 'VIEW', 'TEMPORARY') DEFAULT ''; DECLARE v_this_thread_enabled ENUM('YES', 'NO'); DECLARE v_force_new_snapshot BOOLEAN DEFAULT FALSE; DECLARE v_digests_table VARCHAR(133); DECLARE v_quoted_table, v_quoted_custom_view VARCHAR(133) DEFAULT ''; DECLARE v_table_db, v_table_name, v_custom_db, v_custom_name VARCHAR(64); DECLARE v_digest_table_template, v_checksum_ref, v_checksum_table text; DECLARE v_sql longtext; DECLARE v_error_msg VARCHAR(128);   SELECT INSTRUMENTED INTO v_this_thread_enabled FROM performance_schema.threads WHERE PROCESSLIST_ID = CONNECTION_ID(); IF (v_this_thread_enabled = 'YES') THEN CALL sys.ps_setup_disable_thread(CONNECTION_ID()); END IF;  SET @log_bin := @@sql_log_bin; IF (@log_bin = 1) THEN SET sql_log_bin = 0; END IF;   IF (@sys.statement_performance_analyzer.limit IS NULL) THEN SET @sys.statement_performance_analyzer.limit = sys.sys_get_config('statement_performance_analyzer.limit', '100'); END IF; IF (@sys.debug IS NULL) THEN SET @sys.debug                                = sys.sys_get_config('debug'                               , 'OFF'); END IF;   IF (in_table = 'NOW()') THEN SET v_force_new_snapshot = TRUE, in_table             = NULL; ELSEIF (in_table IS NOT NULL) THEN IF (NOT INSTR(in_table, '.')) THEN SET v_table_db   = DATABASE(), v_table_name = in_table; ELSE SET v_table_db   = SUBSTRING_INDEX(in_table, '.', 1); SET v_table_name = SUBSTRING(in_table, CHAR_LENGTH(v_table_db)+2); END IF;  SET v_quoted_table = CONCAT('`', v_table_db, '`.`', v_table_name, '`');  IF (@sys.debug = 'ON') THEN SELECT CONCAT('in_table is: db = ''', v_table_db, ''', table = ''', v_table_name, '''') AS 'Debug'; END IF;  IF (v_table_db = DATABASE() AND (v_table_name = 'tmp_digests' OR v_table_name = 'tmp_digests_delta')) THEN SET v_error_msg = CONCAT('Invalid value for in_table: ', v_quoted_table, ' is reserved table name.'); SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg; END IF;  CALL sys.table_exists(v_table_db, v_table_name, v_table_exists); IF (@sys.debug = 'ON') THEN SELECT CONCAT('v_table_exists = ', v_table_exists) AS 'Debug'; END IF;  IF (v_table_exists = 'BASE TABLE') THEN SET v_checksum_ref = ( SELECT GROUP_CONCAT(CONCAT(COLUMN_NAME, COLUMN_TYPE) ORDER BY ORDINAL_POSITION) AS Checksum FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'performance_schema' AND TABLE_NAME = 'events_statements_summary_by_digest' ), v_checksum_table = ( SELECT GROUP_CONCAT(CONCAT(COLUMN_NAME, COLUMN_TYPE) ORDER BY ORDINAL_POSITION) AS Checksum FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = v_table_db AND TABLE_NAME = v_table_name );  IF (v_checksum_ref <> v_checksum_table) THEN SET v_error_msg = CONCAT('The table ', IF(CHAR_LENGTH(v_quoted_table) > 93, CONCAT('...', SUBSTRING(v_quoted_table, -90)), v_quoted_table), ' has the wrong definition.'); SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg; END IF; END IF; END IF;   IF (in_views IS NULL OR in_views = '') THEN SET in_views = 'with_runtimes_in_95th_percentile,analysis,with_errors_or_warnings,with_full_table_scans,with_sorting,with_temp_tables'; END IF;   CALL sys.table_exists(DATABASE(), 'tmp_digests', v_tmp_digests_table_exists); IF (@sys.debug = 'ON') THEN SELECT CONCAT('v_tmp_digests_table_exists = ', v_tmp_digests_table_exists) AS 'Debug'; END IF;  CASE WHEN in_action IN ('snapshot', 'overall') THEN IF (in_table IS NOT NULL) THEN IF (NOT v_table_exists IN ('TEMPORARY', 'BASE TABLE')) THEN SET v_error_msg = CONCAT('The ', in_action, ' action requires in_table to be NULL, NOW() or specify an existing table.', ' The table ', IF(CHAR_LENGTH(v_quoted_table) > 16, CONCAT('...', SUBSTRING(v_quoted_table, -13)), v_quoted_table), ' does not exist.'); SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg; END IF; END IF;  WHEN in_action IN ('delta', 'save') THEN IF (v_table_exists NOT IN ('TEMPORARY', 'BASE TABLE')) THEN SET v_error_msg = CONCAT('The ', in_action, ' action requires in_table to be an existing table.', IF(in_table IS NOT NULL, CONCAT(' The table ', IF(CHAR_LENGTH(v_quoted_table) > 39, CONCAT('...', SUBSTRING(v_quoted_table, -36)), v_quoted_table), ' does not exist.'), '')); SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg; END IF;  IF (in_action = 'delta' AND v_tmp_digests_table_exists <> 'TEMPORARY') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'An existing snapshot generated with the statement_performance_analyzer() must exist.'; END IF; WHEN in_action = 'create_tmp' THEN IF (v_table_exists = 'TEMPORARY') THEN SET v_error_msg = CONCAT('Cannot create the table ', IF(CHAR_LENGTH(v_quoted_table) > 72, CONCAT('...', SUBSTRING(v_quoted_table, -69)), v_quoted_table), ' as it already exists.'); SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg; END IF;  WHEN in_action = 'create_table' THEN IF (v_table_exists <> '') THEN SET v_error_msg = CONCAT('Cannot create the table ', IF(CHAR_LENGTH(v_quoted_table) > 52, CONCAT('...', SUBSTRING(v_quoted_table, -49)), v_quoted_table), ' as it already exists', IF(v_table_exists = 'TEMPORARY', ' as a temporary table.', '.')); SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg; END IF;  WHEN in_action = 'cleanup' THEN DO (SELECT 1); ELSE SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Unknown action. Supported actions are: cleanup, create_table, create_tmp, delta, overall, save, snapshot'; END CASE;  SET v_digest_table_template = 'CREATE %{TEMPORARY}TABLE %{TABLE_NAME} ( `SCHEMA_NAME` varchar(64) DEFAULT NULL, `DIGEST` varchar(32) DEFAULT NULL, `DIGEST_TEXT` longtext, `COUNT_STAR` bigint(20) unsigned NOT NULL, `SUM_TIMER_WAIT` bigint(20) unsigned NOT NULL, `MIN_TIMER_WAIT` bigint(20) unsigned NOT NULL, `AVG_TIMER_WAIT` bigint(20) unsigned NOT NULL, `MAX_TIMER_WAIT` bigint(20) unsigned NOT NULL, `SUM_LOCK_TIME` bigint(20) unsigned NOT NULL, `SUM_ERRORS` bigint(20) unsigned NOT NULL, `SUM_WARNINGS` bigint(20) unsigned NOT NULL, `SUM_ROWS_AFFECTED` bigint(20) unsigned NOT NULL, `SUM_ROWS_SENT` bigint(20) unsigned NOT NULL, `SUM_ROWS_EXAMINED` bigint(20) unsigned NOT NULL, `SUM_CREATED_TMP_DISK_TABLES` bigint(20) unsigned NOT NULL, `SUM_CREATED_TMP_TABLES` bigint(20) unsigned NOT NULL, `SUM_SELECT_FULL_JOIN` bigint(20) unsigned NOT NULL, `SUM_SELECT_FULL_RANGE_JOIN` bigint(20) unsigned NOT NULL, `SUM_SELECT_RANGE` bigint(20) unsigned NOT NULL, `SUM_SELECT_RANGE_CHECK` bigint(20) unsigned NOT NULL, `SUM_SELECT_SCAN` bigint(20) unsigned NOT NULL, `SUM_SORT_MERGE_PASSES` bigint(20) unsigned NOT NULL, `SUM_SORT_RANGE` bigint(20) unsigned NOT NULL, `SUM_SORT_ROWS` bigint(20) unsigned NOT NULL, `SUM_SORT_SCAN` bigint(20) unsigned NOT NULL, `SUM_NO_INDEX_USED` bigint(20) unsigned NOT NULL, `SUM_NO_GOOD_INDEX_USED` bigint(20) unsigned NOT NULL, `FIRST_SEEN` timestamp NULL DEFAULT NULL, `LAST_SEEN` timestamp NULL DEFAULT NULL, INDEX (SCHEMA_NAME, DIGEST) ) DEFAULT CHARSET=utf8';  IF (v_force_new_snapshot OR in_action = 'snapshot' OR (in_action = 'overall' AND in_table IS NULL) OR (in_action = 'save' AND v_tmp_digests_table_exists <> 'TEMPORARY') ) THEN IF (v_tmp_digests_table_exists = 'TEMPORARY') THEN IF (@sys.debug = 'ON') THEN SELECT 'DROP TEMPORARY TABLE IF EXISTS tmp_digests' AS 'Debug'; END IF; DROP TEMPORARY TABLE IF EXISTS tmp_digests; END IF; CALL sys.execute_prepared_stmt(REPLACE(REPLACE(v_digest_table_template, '%{TEMPORARY}', 'TEMPORARY '), '%{TABLE_NAME}', 'tmp_digests'));  SET v_sql = CONCAT('INSERT INTO tmp_digests SELECT * FROM ', IF(in_table IS NULL OR in_action = 'save', 'performance_schema.events_statements_summary_by_digest', v_quoted_table)); CALL sys.execute_prepared_stmt(v_sql); END IF;  IF (in_action IN ('create_table', 'create_tmp')) THEN IF (in_action = 'create_table') THEN CALL sys.execute_prepared_stmt(REPLACE(REPLACE(v_digest_table_template, '%{TEMPORARY}', ''), '%{TABLE_NAME}', v_quoted_table)); ELSE CALL sys.execute_prepared_stmt(REPLACE(REPLACE(v_digest_table_template, '%{TEMPORARY}', 'TEMPORARY '), '%{TABLE_NAME}', v_quoted_table)); END IF; ELSEIF (in_action = 'save') THEN CALL sys.execute_prepared_stmt(CONCAT('DELETE FROM ', v_quoted_table)); CALL sys.execute_prepared_stmt(CONCAT('INSERT INTO ', v_quoted_table, ' SELECT * FROM tmp_digests')); ELSEIF (in_action = 'cleanup') THEN DROP TEMPORARY TABLE IF EXISTS sys.tmp_digests; DROP TEMPORARY TABLE IF EXISTS sys.tmp_digests_delta; ELSEIF (in_action IN ('overall', 'delta')) THEN IF (in_action = 'overall') THEN IF (in_table IS NULL) THEN SET v_digests_table = 'tmp_digests'; ELSE SET v_digests_table = v_quoted_table; END IF; ELSE SET v_digests_table = 'tmp_digests_delta'; DROP TEMPORARY TABLE IF EXISTS tmp_digests_delta; CREATE TEMPORARY TABLE tmp_digests_delta LIKE tmp_digests; SET v_sql = CONCAT('INSERT INTO tmp_digests_delta SELECT `d_end`.`SCHEMA_NAME`, `d_end`.`DIGEST`, `d_end`.`DIGEST_TEXT`, `d_end`.`COUNT_STAR`-IFNULL(`d_start`.`COUNT_STAR`, 0) AS ''COUNT_STAR'', `d_end`.`SUM_TIMER_WAIT`-IFNULL(`d_start`.`SUM_TIMER_WAIT`, 0) AS ''SUM_TIMER_WAIT'', `d_end`.`MIN_TIMER_WAIT` AS ''MIN_TIMER_WAIT'', IFNULL((`d_end`.`SUM_TIMER_WAIT`-IFNULL(`d_start`.`SUM_TIMER_WAIT`, 0))/NULLIF(`d_end`.`COUNT_STAR`-IFNULL(`d_start`.`COUNT_STAR`, 0), 0), 0) AS ''AVG_TIMER_WAIT'', `d_end`.`MAX_TIMER_WAIT` AS ''MAX_TIMER_WAIT'', `d_end`.`SUM_LOCK_TIME`-IFNULL(`d_start`.`SUM_LOCK_TIME`, 0) AS ''SUM_LOCK_TIME'', `d_end`.`SUM_ERRORS`-IFNULL(`d_start`.`SUM_ERRORS`, 0) AS ''SUM_ERRORS'', `d_end`.`SUM_WARNINGS`-IFNULL(`d_start`.`SUM_WARNINGS`, 0) AS ''SUM_WARNINGS'', `d_end`.`SUM_ROWS_AFFECTED`-IFNULL(`d_start`.`SUM_ROWS_AFFECTED`, 0) AS ''SUM_ROWS_AFFECTED'', `d_end`.`SUM_ROWS_SENT`-IFNULL(`d_start`.`SUM_ROWS_SENT`, 0) AS ''SUM_ROWS_SENT'', `d_end`.`SUM_ROWS_EXAMINED`-IFNULL(`d_start`.`SUM_ROWS_EXAMINED`, 0) AS ''SUM_ROWS_EXAMINED'', `d_end`.`SUM_CREATED_TMP_DISK_TABLES`-IFNULL(`d_start`.`SUM_CREATED_TMP_DISK_TABLES`, 0) AS ''SUM_CREATED_TMP_DISK_TABLES'', `d_end`.`SUM_CREATED_TMP_TABLES`-IFNULL(`d_start`.`SUM_CREATED_TMP_TABLES`, 0) AS ''SUM_CREATED_TMP_TABLES'', `d_end`.`SUM_SELECT_FULL_JOIN`-IFNULL(`d_start`.`SUM_SELECT_FULL_JOIN`, 0) AS ''SUM_SELECT_FULL_JOIN'', `d_end`.`SUM_SELECT_FULL_RANGE_JOIN`-IFNULL(`d_start`.`SUM_SELECT_FULL_RANGE_JOIN`, 0) AS ''SUM_SELECT_FULL_RANGE_JOIN'', `d_end`.`SUM_SELECT_RANGE`-IFNULL(`d_start`.`SUM_SELECT_RANGE`, 0) AS ''SUM_SELECT_RANGE'', `d_end`.`SUM_SELECT_RANGE_CHECK`-IFNULL(`d_start`.`SUM_SELECT_RANGE_CHECK`, 0) AS ''SUM_SELECT_RANGE_CHECK'', `d_end`.`SUM_SELECT_SCAN`-IFNULL(`d_start`.`SUM_SELECT_SCAN`, 0) AS ''SUM_SELECT_SCAN'', `d_end`.`SUM_SORT_MERGE_PASSES`-IFNULL(`d_start`.`SUM_SORT_MERGE_PASSES`, 0) AS ''SUM_SORT_MERGE_PASSES'', `d_end`.`SUM_SORT_RANGE`-IFNULL(`d_start`.`SUM_SORT_RANGE`, 0) AS ''SUM_SORT_RANGE'', `d_end`.`SUM_SORT_ROWS`-IFNULL(`d_start`.`SUM_SORT_ROWS`, 0) AS ''SUM_SORT_ROWS'', `d_end`.`SUM_SORT_SCAN`-IFNULL(`d_start`.`SUM_SORT_SCAN`, 0) AS ''SUM_SORT_SCAN'', `d_end`.`SUM_NO_INDEX_USED`-IFNULL(`d_start`.`SUM_NO_INDEX_USED`, 0) AS ''SUM_NO_INDEX_USED'', `d_end`.`SUM_NO_GOOD_INDEX_USED`-IFNULL(`d_start`.`SUM_NO_GOOD_INDEX_USED`, 0) AS ''SUM_NO_GOOD_INDEX_USED'', `d_end`.`FIRST_SEEN`, `d_end`.`LAST_SEEN` FROM tmp_digests d_end LEFT OUTER JOIN ', v_quoted_table, ' d_start ON `d_start`.`DIGEST` = `d_end`.`DIGEST` AND (`d_start`.`SCHEMA_NAME` = `d_end`.`SCHEMA_NAME` OR (`d_start`.`SCHEMA_NAME` IS NULL AND `d_end`.`SCHEMA_NAME` IS NULL) ) WHERE `d_end`.`COUNT_STAR`-IFNULL(`d_start`.`COUNT_STAR`, 0) > 0'); CALL sys.execute_prepared_stmt(v_sql); END IF;  IF (FIND_IN_SET('with_runtimes_in_95th_percentile', in_views)) THEN SELECT 'Queries with Runtime in 95th Percentile' AS 'Next Output';  DROP TEMPORARY TABLE IF EXISTS tmp_digest_avg_latency_distribution1; DROP TEMPORARY TABLE IF EXISTS tmp_digest_avg_latency_distribution2; DROP TEMPORARY TABLE IF EXISTS tmp_digest_95th_percentile_by_avg_us;  CREATE TEMPORARY TABLE tmp_digest_avg_latency_distribution1 ( cnt bigint unsigned NOT NULL, avg_us decimal(21,0) NOT NULL, PRIMARY KEY (avg_us) ) ENGINE=InnoDB;  SET v_sql = CONCAT('INSERT INTO tmp_digest_avg_latency_distribution1 SELECT COUNT(*) cnt,  ROUND(avg_timer_wait/1000000) AS avg_us FROM ', v_digests_table, ' GROUP BY avg_us'); CALL sys.execute_prepared_stmt(v_sql);  CREATE TEMPORARY TABLE tmp_digest_avg_latency_distribution2 LIKE tmp_digest_avg_latency_distribution1; INSERT INTO tmp_digest_avg_latency_distribution2 SELECT * FROM tmp_digest_avg_latency_distribution1;  CREATE TEMPORARY TABLE tmp_digest_95th_percentile_by_avg_us ( avg_us decimal(21,0) NOT NULL, percentile decimal(46,4) NOT NULL, PRIMARY KEY (avg_us) ) ENGINE=InnoDB;  SET v_sql = CONCAT('INSERT INTO tmp_digest_95th_percentile_by_avg_us SELECT s2.avg_us avg_us, IFNULL(SUM(s1.cnt)/NULLIF((SELECT COUNT(*) FROM ', v_digests_table, '), 0), 0) percentile FROM tmp_digest_avg_latency_distribution1 AS s1 JOIN tmp_digest_avg_latency_distribution2 AS s2 ON s1.avg_us <= s2.avg_us GROUP BY s2.avg_us HAVING percentile > 0.95 ORDER BY percentile LIMIT 1'); CALL sys.execute_prepared_stmt(v_sql);  SET v_sql = REPLACE( REPLACE( (SELECT VIEW_DEFINITION FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'statements_with_runtimes_in_95th_percentile' ), '`performance_schema`.`events_statements_summary_by_digest`', v_digests_table ), 'sys.x$ps_digest_95th_percentile_by_avg_us', '`sys`.`x$ps_digest_95th_percentile_by_avg_us`' ); CALL sys.execute_prepared_stmt(v_sql);  DROP TEMPORARY TABLE tmp_digest_avg_latency_distribution1; DROP TEMPORARY TABLE tmp_digest_avg_latency_distribution2; DROP TEMPORARY TABLE tmp_digest_95th_percentile_by_avg_us; END IF;  IF (FIND_IN_SET('analysis', in_views)) THEN SELECT CONCAT('Top ', @sys.statement_performance_analyzer.limit, ' Queries Ordered by Total Latency') AS 'Next Output'; SET v_sql = REPLACE( (SELECT VIEW_DEFINITION FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'statement_analysis' ), '`performance_schema`.`events_statements_summary_by_digest`', v_digests_table ); IF (@sys.statement_performance_analyzer.limit > 0) THEN SET v_sql = CONCAT(v_sql, ' LIMIT ', @sys.statement_performance_analyzer.limit); END IF; CALL sys.execute_prepared_stmt(v_sql); END IF;  IF (FIND_IN_SET('with_errors_or_warnings', in_views)) THEN SELECT CONCAT('Top ', @sys.statement_performance_analyzer.limit, ' Queries with Errors') AS 'Next Output'; SET v_sql = REPLACE( (SELECT VIEW_DEFINITION FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'statements_with_errors_or_warnings' ), '`performance_schema`.`events_statements_summary_by_digest`', v_digests_table ); IF (@sys.statement_performance_analyzer.limit > 0) THEN SET v_sql = CONCAT(v_sql, ' LIMIT ', @sys.statement_performance_analyzer.limit); END IF; CALL sys.execute_prepared_stmt(v_sql); END IF;  IF (FIND_IN_SET('with_full_table_scans', in_views)) THEN SELECT CONCAT('Top ', @sys.statement_performance_analyzer.limit, ' Queries with Full Table Scan') AS 'Next Output'; SET v_sql = REPLACE( (SELECT VIEW_DEFINITION FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'statements_with_full_table_scans' ), '`performance_schema`.`events_statements_summary_by_digest`', v_digests_table ); IF (@sys.statement_performance_analyzer.limit > 0) THEN SET v_sql = CONCAT(v_sql, ' LIMIT ', @sys.statement_performance_analyzer.limit); END IF; CALL sys.execute_prepared_stmt(v_sql); END IF;  IF (FIND_IN_SET('with_sorting', in_views)) THEN SELECT CONCAT('Top ', @sys.statement_performance_analyzer.limit, ' Queries with Sorting') AS 'Next Output'; SET v_sql = REPLACE( (SELECT VIEW_DEFINITION FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'statements_with_sorting' ), '`performance_schema`.`events_statements_summary_by_digest`', v_digests_table ); IF (@sys.statement_performance_analyzer.limit > 0) THEN SET v_sql = CONCAT(v_sql, ' LIMIT ', @sys.statement_performance_analyzer.limit); END IF; CALL sys.execute_prepared_stmt(v_sql); END IF;  IF (FIND_IN_SET('with_temp_tables', in_views)) THEN SELECT CONCAT('Top ', @sys.statement_performance_analyzer.limit, ' Queries with Internal Temporary Tables') AS 'Next Output'; SET v_sql = REPLACE( (SELECT VIEW_DEFINITION FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'statements_with_temp_tables' ), '`performance_schema`.`events_statements_summary_by_digest`', v_digests_table ); IF (@sys.statement_performance_analyzer.limit > 0) THEN SET v_sql = CONCAT(v_sql, ' LIMIT ', @sys.statement_performance_analyzer.limit); END IF; CALL sys.execute_prepared_stmt(v_sql); END IF;  IF (FIND_IN_SET('custom', in_views)) THEN SELECT CONCAT('Top ', @sys.statement_performance_analyzer.limit, ' Queries Using Custom View') AS 'Next Output';  IF (@sys.statement_performance_analyzer.view IS NULL) THEN SET @sys.statement_performance_analyzer.view = sys.sys_get_config('statement_performance_analyzer.view', NULL); END IF; IF (@sys.statement_performance_analyzer.view IS NULL) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The @sys.statement_performance_analyzer.view user variable must be set with the view or query to use.'; END IF;  IF (NOT INSTR(@sys.statement_performance_analyzer.view, ' ')) THEN IF (NOT INSTR(@sys.statement_performance_analyzer.view, '.')) THEN SET v_custom_db   = DATABASE(), v_custom_name = @sys.statement_performance_analyzer.view; ELSE SET v_custom_db   = SUBSTRING_INDEX(@sys.statement_performance_analyzer.view, '.', 1); SET v_custom_name = SUBSTRING(@sys.statement_performance_analyzer.view, CHAR_LENGTH(v_custom_db)+2); END IF;  CALL sys.table_exists(v_custom_db, v_custom_name, v_custom_view_exists); IF (v_custom_view_exists <> 'VIEW') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The @sys.statement_performance_analyzer.view user variable is set but specified neither an existing view nor a query.'; END IF;  SET v_sql = REPLACE( (SELECT VIEW_DEFINITION FROM information_schema.VIEWS WHERE TABLE_SCHEMA = v_custom_db AND TABLE_NAME = v_custom_name ), '`performance_schema`.`events_statements_summary_by_digest`', v_digests_table ); ELSE SET v_sql = REPLACE(@sys.statement_performance_analyzer.view, '`performance_schema`.`events_statements_summary_by_digest`', v_digests_table); END IF;  IF (@sys.statement_performance_analyzer.limit > 0) THEN SET v_sql = CONCAT(v_sql, ' LIMIT ', @sys.statement_performance_analyzer.limit); END IF;  CALL sys.execute_prepared_stmt(v_sql); END IF; END IF;  IF (v_this_thread_enabled = 'YES') THEN CALL sys.ps_setup_enable_thread(CONNECTION_ID()); END IF;  IF (@log_bin = 1) THEN SET sql_log_bin = @log_bin; END IF; END$$

CREATE DEFINER=`mysql.sys`@`localhost` PROCEDURE `table_exists`( IN in_db VARCHAR(64), IN in_table VARCHAR(64), OUT out_exists ENUM('', 'BASE TABLE', 'VIEW', 'TEMPORARY') )
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Tests whether the table specified in in_db and in_table exists either as a regular\n table, or as a temporary table. The returned value corresponds to the table that\n will be used, so if there''s both a temporary and a permanent table with the given\n name, then ''TEMPORARY'' will be returned.\n \n Parameters\n \n in_db (VARCHAR(64)):\n The database name to check for the existance of the table in.\n \n in_table (VARCHAR(64)):\n The name of the table to check the existance of.\n \n out_exists ENUM('''', ''BASE TABLE'', ''VIEW'', ''TEMPORARY''):\n The return value: whether the table exists. The value is one of:\n * ''''           - the table does not exist neither as a base table, view, nor temporary table.\n * ''BASE TABLE'' - the table name exists as a permanent base table table.\n * ''VIEW''       - the table name exists as a view.\n * ''TEMPORARY''  - the table name exists as a temporary table.\n \n Example\n \n mysql> CREATE DATABASE db1;\n Query OK, 1 row affected (0.07 sec)\n \n mysql> use db1;\n Database changed\n mysql> CREATE TABLE t1 (id INT PRIMARY KEY);\n Query OK, 0 rows affected (0.08 sec)\n \n mysql> CREATE TABLE t2 (id INT PRIMARY KEY);\n Query OK, 0 rows affected (0.08 sec)\n \n mysql> CREATE view v_t1 AS SELECT * FROM t1;\n Query OK, 0 rows affected (0.00 sec)\n \n mysql> CREATE TEMPORARY TABLE t1 (id INT PRIMARY KEY);\n Query OK, 0 rows affected (0.00 sec)\n \n mysql> CALL sys.table_exists(''db1'', ''t1'', @exists); SELECT @exists;\n Query OK, 0 rows affected (0.00 sec)\n \n +------------+\n | @exists    |\n +------------+\n | TEMPORARY  |\n +------------+\n 1 row in set (0.00 sec)\n \n mysql> CALL sys.table_exists(''db1'', ''t2'', @exists); SELECT @exists;\n Query OK, 0 rows affected (0.00 sec)\n \n +------------+\n | @exists    |\n +------------+\n | BASE TABLE |\n +------------+\n 1 row in set (0.01 sec)\n \n mysql> CALL sys.table_exists(''db1'', ''v_t1'', @exists); SELECT @exists;\n Query OK, 0 rows affected (0.00 sec)\n \n +---------+\n | @exists |\n +---------+\n | VIEW    |\n +---------+\n 1 row in set (0.00 sec)\n \n mysql> CALL sys.table_exists(''db1'', ''t3'', @exists); SELECT @exists;\n Query OK, 0 rows affected (0.01 sec)\n \n +---------+\n | @exists |\n +---------+\n |         |\n +---------+\n 1 row in set (0.00 sec)\n '
  BEGIN DECLARE v_error BOOLEAN DEFAULT FALSE; DECLARE CONTINUE HANDLER FOR 1050 SET v_error = TRUE; DECLARE CONTINUE HANDLER FOR 1146 SET v_error = TRUE;  SET out_exists = '';  IF (EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = in_db AND TABLE_NAME = in_table)) THEN SET @sys.tmp.table_exists.SQL = CONCAT('CREATE TEMPORARY TABLE `', in_db, '`.`', in_table, '` (id INT PRIMARY KEY)'); PREPARE stmt_create_table FROM @sys.tmp.table_exists.SQL; EXECUTE stmt_create_table; DEALLOCATE PREPARE stmt_create_table; IF (v_error) THEN SET out_exists = 'TEMPORARY'; ELSE SET @sys.tmp.table_exists.SQL = CONCAT('DROP TEMPORARY TABLE `', in_db, '`.`', in_table, '`'); PREPARE stmt_drop_table FROM @sys.tmp.table_exists.SQL; EXECUTE stmt_drop_table; DEALLOCATE PREPARE stmt_drop_table; SET out_exists = (SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = in_db AND TABLE_NAME = in_table); END IF; ELSE SET @sys.tmp.table_exists.SQL = CONCAT('SELECT COUNT(*) FROM `', in_db, '`.`', in_table, '`'); PREPARE stmt_select FROM @sys.tmp.table_exists.SQL; IF (NOT v_error) THEN DEALLOCATE PREPARE stmt_select; SET out_exists = 'TEMPORARY'; END IF; END IF; END$$

--
-- Functions
--
CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `extract_schema_from_file_name`( path VARCHAR(512) ) RETURNS varchar(64) CHARSET utf8
NO SQL
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Takes a raw file path, and attempts to extract the schema name from it.\n \n Useful for when interacting with Performance Schema data \n concerning IO statistics, for example.\n \n Currently relies on the fact that a table data file will be within a \n specified database directory (will not work with partitions or tables\n that specify an individual DATA_DIRECTORY).\n \n Parameters\n \n path (VARCHAR(512)):\n The full file path to a data file to extract the schema name from.\n \n Returns\n \n VARCHAR(64)\n \n Example\n \n mysql> SELECT sys.extract_schema_from_file_name(''/var/lib/mysql/employees/employee.ibd'');\n +----------------------------------------------------------------------------+\n | sys.extract_schema_from_file_name(''/var/lib/mysql/employees/employee.ibd'') |\n +----------------------------------------------------------------------------+\n | employees                                                                  |\n +----------------------------------------------------------------------------+\n 1 row in set (0.00 sec)\n '
  BEGIN RETURN LEFT(SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(path, '\\', '/'), '/', -2), '/', 1), 64); END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `extract_table_from_file_name`( path VARCHAR(512) ) RETURNS varchar(64) CHARSET utf8
NO SQL
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Takes a raw file path, and extracts the table name from it.\n \n Useful for when interacting with Performance Schema data \n concerning IO statistics, for example.\n \n Parameters\n \n path (VARCHAR(512)):\n The full file path to a data file to extract the table name from.\n \n Returns\n \n VARCHAR(64)\n \n Example\n \n mysql> SELECT sys.extract_table_from_file_name(''/var/lib/mysql/employees/employee.ibd'');\n +---------------------------------------------------------------------------+\n | sys.extract_table_from_file_name(''/var/lib/mysql/employees/employee.ibd'') |\n +---------------------------------------------------------------------------+\n | employee                                                                  |\n +---------------------------------------------------------------------------+\n 1 row in set (0.02 sec)\n '
  BEGIN RETURN LEFT(SUBSTRING_INDEX(REPLACE(SUBSTRING_INDEX(REPLACE(path, '\\', '/'), '/', -1), '@0024', '$'), '.', 1), 64); END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `format_bytes`( bytes TEXT ) RETURNS text CHARSET utf8
NO SQL
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Takes a raw bytes value, and converts it to a human readable format.\n \n Parameters\n \n bytes (TEXT):\n A raw bytes value.\n \n Returns\n \n TEXT\n \n Example\n \n mysql> SELECT sys.format_bytes(2348723492723746) AS size;\n +----------+\n | size     |\n +----------+\n | 2.09 PiB |\n +----------+\n 1 row in set (0.00 sec)\n \n mysql> SELECT sys.format_bytes(2348723492723) AS size;\n +----------+\n | size     |\n +----------+\n | 2.14 TiB |\n +----------+\n 1 row in set (0.00 sec)\n \n mysql> SELECT sys.format_bytes(23487234) AS size;\n +-----------+\n | size      |\n +-----------+\n | 22.40 MiB |\n +-----------+\n 1 row in set (0.00 sec)\n '
  BEGIN IF bytes IS NULL THEN RETURN NULL; ELSEIF bytes >= 1125899906842624 THEN RETURN CONCAT(ROUND(bytes / 1125899906842624, 2), ' PiB'); ELSEIF bytes >= 1099511627776 THEN RETURN CONCAT(ROUND(bytes / 1099511627776, 2), ' TiB'); ELSEIF bytes >= 1073741824 THEN RETURN CONCAT(ROUND(bytes / 1073741824, 2), ' GiB'); ELSEIF bytes >= 1048576 THEN RETURN CONCAT(ROUND(bytes / 1048576, 2), ' MiB'); ELSEIF bytes >= 1024 THEN RETURN CONCAT(ROUND(bytes / 1024, 2), ' KiB'); ELSE RETURN CONCAT(ROUND(bytes, 0), ' bytes'); END IF; END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `format_path`( in_path VARCHAR(512) ) RETURNS varchar(512) CHARSET utf8
NO SQL
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Takes a raw path value, and strips out the datadir or tmpdir\n replacing with @@datadir and @@tmpdir respectively. \n \n Also normalizes the paths across operating systems, so backslashes\n on Windows are converted to forward slashes\n \n Parameters\n \n path (VARCHAR(512)):\n The raw file path value to format.\n \n Returns\n \n VARCHAR(512) CHARSET UTF8\n \n Example\n \n mysql> select @@datadir;\n +-----------------------------------------------+\n | @@datadir                                     |\n +-----------------------------------------------+\n | /Users/mark/sandboxes/SmallTree/AMaster/data/ |\n +-----------------------------------------------+\n 1 row in set (0.06 sec)\n \n mysql> select format_path(''/Users/mark/sandboxes/SmallTree/AMaster/data/mysql/proc.MYD'') AS path;\n +--------------------------+\n | path                     |\n +--------------------------+\n | @@datadir/mysql/proc.MYD |\n +--------------------------+\n 1 row in set (0.03 sec)\n '
  BEGIN DECLARE v_path VARCHAR(512); DECLARE v_undo_dir VARCHAR(1024);  DECLARE path_separator CHAR(1) DEFAULT '/';  IF @@global.version_compile_os LIKE 'win%' THEN SET path_separator = '\\'; END IF;  IF in_path LIKE '/private/%' THEN SET v_path = REPLACE(in_path, '/private', ''); ELSE SET v_path = in_path; END IF;  SET v_undo_dir = IFNULL((SELECT VARIABLE_VALUE FROM performance_schema.global_variables WHERE VARIABLE_NAME = 'innodb_undo_directory'), '');  IF v_path IS NULL THEN RETURN NULL; ELSEIF v_path LIKE CONCAT(@@global.datadir, IF(SUBSTRING(@@global.datadir, -1) = path_separator, '%', CONCAT(path_separator, '%'))) ESCAPE '|' THEN SET v_path = REPLACE(v_path, @@global.datadir, CONCAT('@@datadir', IF(SUBSTRING(@@global.datadir, -1) = path_separator, path_separator, ''))); ELSEIF v_path LIKE CONCAT(@@global.tmpdir, IF(SUBSTRING(@@global.tmpdir, -1) = path_separator, '%', CONCAT(path_separator, '%'))) ESCAPE '|' THEN SET v_path = REPLACE(v_path, @@global.tmpdir, CONCAT('@@tmpdir', IF(SUBSTRING(@@global.tmpdir, -1) = path_separator, path_separator, ''))); ELSEIF v_path LIKE CONCAT(@@global.slave_load_tmpdir, IF(SUBSTRING(@@global.slave_load_tmpdir, -1) = path_separator, '%', CONCAT(path_separator, '%'))) ESCAPE '|' THEN SET v_path = REPLACE(v_path, @@global.slave_load_tmpdir, CONCAT('@@slave_load_tmpdir', IF(SUBSTRING(@@global.slave_load_tmpdir, -1) = path_separator, path_separator, ''))); ELSEIF v_path LIKE CONCAT(@@global.innodb_data_home_dir, IF(SUBSTRING(@@global.innodb_data_home_dir, -1) = path_separator, '%', CONCAT(path_separator, '%'))) ESCAPE '|' THEN SET v_path = REPLACE(v_path, @@global.innodb_data_home_dir, CONCAT('@@innodb_data_home_dir', IF(SUBSTRING(@@global.innodb_data_home_dir, -1) = path_separator, path_separator, ''))); ELSEIF v_path LIKE CONCAT(@@global.innodb_log_group_home_dir, IF(SUBSTRING(@@global.innodb_log_group_home_dir, -1) = path_separator, '%', CONCAT(path_separator, '%'))) ESCAPE '|' THEN SET v_path = REPLACE(v_path, @@global.innodb_log_group_home_dir, CONCAT('@@innodb_log_group_home_dir', IF(SUBSTRING(@@global.innodb_log_group_home_dir, -1) = path_separator, path_separator, ''))); ELSEIF v_path LIKE CONCAT(v_undo_dir, IF(SUBSTRING(v_undo_dir, -1) = path_separator, '%', CONCAT(path_separator, '%'))) ESCAPE '|' THEN SET v_path = REPLACE(v_path, v_undo_dir, CONCAT('@@innodb_undo_directory', IF(SUBSTRING(v_undo_dir, -1) = path_separator, path_separator, ''))); ELSEIF v_path LIKE CONCAT(@@global.basedir, IF(SUBSTRING(@@global.basedir, -1) = path_separator, '%', CONCAT(path_separator, '%'))) ESCAPE '|' THEN SET v_path = REPLACE(v_path, @@global.basedir, CONCAT('@@basedir', IF(SUBSTRING(@@global.basedir, -1) = path_separator, path_separator, ''))); END IF;  RETURN v_path; END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `format_statement`( statement LONGTEXT ) RETURNS longtext CHARSET utf8
NO SQL
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Formats a normalized statement, truncating it if it is > 64 characters long by default.\n \n To configure the length to truncate the statement to by default, update the `statement_truncate_len`\n variable with `sys_config` table to a different value. Alternatively, to change it just for just \n your particular session, use `SET @sys.statement_truncate_len := <some new value>`.\n \n Useful for printing statement related data from Performance Schema from \n the command line.\n \n Parameters\n \n statement (LONGTEXT): \n The statement to format.\n \n Returns\n \n LONGTEXT\n \n Example\n \n mysql> SELECT sys.format_statement(digest_text)\n ->   FROM performance_schema.events_statements_summary_by_digest\n ->  ORDER by sum_timer_wait DESC limit 5;\n +-------------------------------------------------------------------+\n | sys.format_statement(digest_text)                                 |\n +-------------------------------------------------------------------+\n | CREATE SQL SECURITY INVOKER VI ... KE ? AND `variable_value` > ?  |\n | CREATE SQL SECURITY INVOKER VI ... ait` IS NOT NULL , `esc` . ... |\n | CREATE SQL SECURITY INVOKER VI ... ait` IS NOT NULL , `sys` . ... |\n | CREATE SQL SECURITY INVOKER VI ...  , `compressed_size` ) ) DESC  |\n | CREATE SQL SECURITY INVOKER VI ... LIKE ? ORDER BY `timer_start`  |\n +-------------------------------------------------------------------+\n 5 rows in set (0.00 sec)\n '
  BEGIN IF @sys.statement_truncate_len IS NULL THEN SET @sys.statement_truncate_len = sys_get_config('statement_truncate_len', 64); END IF;  IF CHAR_LENGTH(statement) > @sys.statement_truncate_len THEN RETURN REPLACE(CONCAT(LEFT(statement, (@sys.statement_truncate_len/2)-2), ' ... ', RIGHT(statement, (@sys.statement_truncate_len/2)-2)), '\n', ' '); ELSE  RETURN REPLACE(statement, '\n', ' '); END IF; END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `format_time`( picoseconds TEXT ) RETURNS text CHARSET utf8
NO SQL
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Takes a raw picoseconds value, and converts it to a human readable form.\n \n Picoseconds are the precision that all latency values are printed in \n within Performance Schema, however are not user friendly when wanting\n to scan output from the command line.\n \n Parameters\n \n picoseconds (TEXT): \n The raw picoseconds value to convert.\n \n Returns\n \n TEXT\n \n Example\n \n mysql> select format_time(342342342342345);\n +------------------------------+\n | format_time(342342342342345) |\n +------------------------------+\n | 00:05:42                     |\n +------------------------------+\n 1 row in set (0.00 sec)\n \n mysql> select format_time(342342342);\n +------------------------+\n | format_time(342342342) |\n +------------------------+\n | 342.34 us              |\n +------------------------+\n 1 row in set (0.00 sec)\n \n mysql> select format_time(34234);\n +--------------------+\n | format_time(34234) |\n +--------------------+\n | 34.23 ns           |\n +--------------------+\n 1 row in set (0.00 sec)\n '
  BEGIN IF picoseconds IS NULL THEN RETURN NULL; ELSEIF picoseconds >= 604800000000000000 THEN RETURN CONCAT(ROUND(picoseconds / 604800000000000000, 2), ' w'); ELSEIF picoseconds >= 86400000000000000 THEN RETURN CONCAT(ROUND(picoseconds / 86400000000000000, 2), ' d'); ELSEIF picoseconds >= 3600000000000000 THEN RETURN CONCAT(ROUND(picoseconds / 3600000000000000, 2), ' h'); ELSEIF picoseconds >= 60000000000000 THEN RETURN CONCAT(ROUND(picoseconds / 60000000000000, 2), ' m'); ELSEIF picoseconds >= 1000000000000 THEN RETURN CONCAT(ROUND(picoseconds / 1000000000000, 2), ' s'); ELSEIF picoseconds >= 1000000000 THEN RETURN CONCAT(ROUND(picoseconds / 1000000000, 2), ' ms'); ELSEIF picoseconds >= 1000000 THEN RETURN CONCAT(ROUND(picoseconds / 1000000, 2), ' us'); ELSEIF picoseconds >= 1000 THEN RETURN CONCAT(ROUND(picoseconds / 1000, 2), ' ns'); ELSE RETURN CONCAT(picoseconds, ' ps'); END IF; END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `list_add`( in_list TEXT, in_add_value TEXT ) RETURNS text CHARSET utf8
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Takes a list, and a value to add to the list, and returns the resulting list.\n \n Useful for altering certain session variables, like sql_mode or optimizer_switch for instance.\n \n Parameters\n \n in_list (TEXT):\n The comma separated list to add a value to\n \n in_add_value (TEXT):\n The value to add to the input list\n \n Returns\n \n TEXT\n \n Example\n \n mysql> select @@sql_mode;\n +-----------------------------------------------------------------------------------+\n | @@sql_mode                                                                        |\n +-----------------------------------------------------------------------------------+\n | ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION |\n +-----------------------------------------------------------------------------------+\n 1 row in set (0.00 sec)\n \n mysql> set sql_mode = sys.list_add(@@sql_mode, ''ANSI_QUOTES'');\n Query OK, 0 rows affected (0.06 sec)\n \n mysql> select @@sql_mode;\n +-----------------------------------------------------------------------------------------------+\n | @@sql_mode                                                                                    |\n +-----------------------------------------------------------------------------------------------+\n | ANSI_QUOTES,ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION |\n +-----------------------------------------------------------------------------------------------+\n 1 row in set (0.00 sec)\n \n '
  BEGIN  IF (in_add_value IS NULL) THEN SIGNAL SQLSTATE '02200' SET MESSAGE_TEXT = 'Function sys.list_add: in_add_value input variable should not be NULL', MYSQL_ERRNO = 1138; END IF;  IF (in_list IS NULL OR LENGTH(in_list) = 0) THEN RETURN in_add_value; END IF;  RETURN (SELECT CONCAT(TRIM(BOTH ',' FROM TRIM(in_list)), ',', in_add_value));  END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `list_drop`( in_list TEXT, in_drop_value TEXT ) RETURNS text CHARSET utf8
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Takes a list, and a value to attempt to remove from the list, and returns the resulting list.\n \n Useful for altering certain session variables, like sql_mode or optimizer_switch for instance.\n \n Parameters\n \n in_list (TEXT):\n The comma separated list to drop a value from\n \n in_drop_value (TEXT):\n The value to drop from the input list\n \n Returns\n \n TEXT\n \n Example\n \n mysql> select @@sql_mode;\n +-----------------------------------------------------------------------------------------------+\n | @@sql_mode                                                                                    |\n +-----------------------------------------------------------------------------------------------+\n | ANSI_QUOTES,ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION |\n +-----------------------------------------------------------------------------------------------+\n 1 row in set (0.00 sec)\n \n mysql> set sql_mode = sys.list_drop(@@sql_mode, ''ONLY_FULL_GROUP_BY'');\n Query OK, 0 rows affected (0.03 sec)\n \n mysql> select @@sql_mode;\n +----------------------------------------------------------------------------+\n | @@sql_mode                                                                 |\n +----------------------------------------------------------------------------+\n | ANSI_QUOTES,STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION |\n +----------------------------------------------------------------------------+\n 1 row in set (0.00 sec)\n \n '
  BEGIN  IF (in_drop_value IS NULL) THEN SIGNAL SQLSTATE '02200' SET MESSAGE_TEXT = 'Function sys.list_drop: in_drop_value input variable should not be NULL', MYSQL_ERRNO = 1138; END IF;  IF (in_list IS NULL OR LENGTH(in_list) = 0) THEN RETURN in_list; END IF;  RETURN (SELECT TRIM(BOTH ',' FROM REPLACE(REPLACE(CONCAT(',', in_list), CONCAT(',', in_drop_value), ''), CONCAT(', ', in_drop_value), '')));  END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `ps_is_account_enabled`( in_host VARCHAR(60),  in_user VARCHAR(32) ) RETURNS enum('YES','NO') CHARSET utf8
READS SQL DATA
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Determines whether instrumentation of an account is enabled \n within Performance Schema.\n \n Parameters\n \n in_host VARCHAR(60): \n The hostname of the account to check.\n in_user VARCHAR(32):\n The username of the account to check.\n \n Returns\n \n ENUM(''YES'', ''NO'', ''PARTIAL'')\n \n Example\n \n mysql> SELECT sys.ps_is_account_enabled(''localhost'', ''root'');\n +------------------------------------------------+\n | sys.ps_is_account_enabled(''localhost'', ''root'') |\n +------------------------------------------------+\n | YES                                            |\n +------------------------------------------------+\n 1 row in set (0.01 sec)\n '
  BEGIN RETURN IF(EXISTS(SELECT 1 FROM performance_schema.setup_actors WHERE (`HOST` = '%' OR in_host LIKE `HOST`) AND (`USER` = '%' OR `USER` = in_user) AND (`ENABLED` = 'YES') ), 'YES', 'NO' ); END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `ps_is_consumer_enabled`( in_consumer varchar(64) ) RETURNS enum('YES','NO') CHARSET utf8
READS SQL DATA
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Determines whether a consumer is enabled (taking the consumer hierarchy into consideration)\n within the Performance Schema.\n \n Parameters\n \n in_consumer VARCHAR(64): \n The name of the consumer to check.\n \n Returns\n \n ENUM(''YES'', ''NO'')\n \n Example\n \n mysql> SELECT sys.ps_is_consumer_enabled(''events_stages_history'');\n +-----------------------------------------------------+\n | sys.ps_is_consumer_enabled(''events_stages_history'') |\n +-----------------------------------------------------+\n | NO                                                  |\n +-----------------------------------------------------+\n 1 row in set (0.00 sec)\n '
  BEGIN RETURN ( SELECT (CASE WHEN c.NAME = 'global_instrumentation' THEN c.ENABLED WHEN c.NAME = 'thread_instrumentation' THEN IF(cg.ENABLED = 'YES' AND c.ENABLED = 'YES', 'YES', 'NO') WHEN c.NAME LIKE '%\_digest'           THEN IF(cg.ENABLED = 'YES' AND c.ENABLED = 'YES', 'YES', 'NO') WHEN c.NAME LIKE '%\_current'          THEN IF(cg.ENABLED = 'YES' AND ct.ENABLED = 'YES' AND c.ENABLED = 'YES', 'YES', 'NO') ELSE IF(cg.ENABLED = 'YES' AND ct.ENABLED = 'YES' AND c.ENABLED = 'YES' AND ( SELECT cc.ENABLED FROM performance_schema.setup_consumers cc WHERE NAME = CONCAT(SUBSTRING_INDEX(c.NAME, '_', 2), '_current') ) = 'YES', 'YES', 'NO') END) AS IsEnabled FROM performance_schema.setup_consumers c INNER JOIN performance_schema.setup_consumers cg INNER JOIN performance_schema.setup_consumers ct WHERE cg.NAME       = 'global_instrumentation' AND ct.NAME   = 'thread_instrumentation' AND c.NAME    = in_consumer ); END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `ps_is_instrument_default_enabled`( in_instrument VARCHAR(128) ) RETURNS enum('YES','NO') CHARSET utf8
READS SQL DATA
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Returns whether an instrument is enabled by default in this version of MySQL.\n \n Parameters\n \n in_instrument VARCHAR(128): \n The instrument to check.\n \n Returns\n \n ENUM(''YES'', ''NO'')\n \n Example\n \n mysql> SELECT sys.ps_is_instrument_default_enabled(''statement/sql/select'');\n +--------------------------------------------------------------+\n | sys.ps_is_instrument_default_enabled(''statement/sql/select'') |\n +--------------------------------------------------------------+\n | YES                                                          |\n +--------------------------------------------------------------+\n 1 row in set (0.00 sec)\n '
  BEGIN DECLARE v_enabled ENUM('YES', 'NO');  SET v_enabled = IF(in_instrument LIKE 'wait/io/file/%' OR in_instrument LIKE 'wait/io/table/%' OR in_instrument LIKE 'statement/%' OR in_instrument LIKE 'memory/performance_schema/%' OR in_instrument IN ('wait/lock/table/sql/handler', 'idle')  OR in_instrument LIKE 'stage/innodb/%' OR in_instrument = 'stage/sql/copy to tmp table'  , 'YES', 'NO' );  RETURN v_enabled; END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `ps_is_instrument_default_timed`( in_instrument VARCHAR(128) ) RETURNS enum('YES','NO') CHARSET utf8
READS SQL DATA
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Returns whether an instrument is timed by default in this version of MySQL.\n \n Parameters\n \n in_instrument VARCHAR(128): \n The instrument to check.\n \n Returns\n \n ENUM(''YES'', ''NO'')\n \n Example\n \n mysql> SELECT sys.ps_is_instrument_default_timed(''statement/sql/select'');\n +------------------------------------------------------------+\n | sys.ps_is_instrument_default_timed(''statement/sql/select'') |\n +------------------------------------------------------------+\n | YES                                                        |\n +------------------------------------------------------------+\n 1 row in set (0.00 sec)\n '
  BEGIN DECLARE v_timed ENUM('YES', 'NO');  SET v_timed = IF(in_instrument LIKE 'wait/io/file/%' OR in_instrument LIKE 'wait/io/table/%' OR in_instrument LIKE 'statement/%' OR in_instrument IN ('wait/lock/table/sql/handler', 'idle')  OR in_instrument LIKE 'stage/innodb/%' OR in_instrument = 'stage/sql/copy to tmp table'  , 'YES', 'NO' );  RETURN v_timed; END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `ps_is_thread_instrumented`( in_connection_id BIGINT UNSIGNED ) RETURNS enum('YES','NO','UNKNOWN') CHARSET utf8
READS SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Checks whether the provided connection id is instrumented within Performance Schema.\n \n Parameters\n \n in_connection_id (BIGINT UNSIGNED):\n The id of the connection to check.\n \n Returns\n \n ENUM(''YES'', ''NO'', ''UNKNOWN'')\n \n Example\n \n mysql> SELECT sys.ps_is_thread_instrumented(CONNECTION_ID());\n +------------------------------------------------+\n | sys.ps_is_thread_instrumented(CONNECTION_ID()) |\n +------------------------------------------------+\n | YES                                            |\n +------------------------------------------------+\n '
  BEGIN DECLARE v_enabled ENUM('YES', 'NO', 'UNKNOWN');  IF (in_connection_id IS NULL) THEN RETURN NULL; END IF;  SELECT INSTRUMENTED INTO v_enabled FROM performance_schema.threads  WHERE PROCESSLIST_ID = in_connection_id;  IF (v_enabled IS NULL) THEN RETURN 'UNKNOWN'; ELSE RETURN v_enabled; END IF; END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `ps_thread_account`( in_thread_id BIGINT UNSIGNED ) RETURNS text CHARSET utf8
READS SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Return the user@host account for the given Performance Schema thread id.\n \n Parameters\n \n in_thread_id (BIGINT UNSIGNED):\n The id of the thread to return the account for.\n \n Example\n \n mysql> select thread_id, processlist_user, processlist_host from performance_schema.threads where type = ''foreground'';\n +-----------+------------------+------------------+\n | thread_id | processlist_user | processlist_host |\n +-----------+------------------+------------------+\n |        23 | NULL             | NULL             |\n |        30 | root             | localhost        |\n |        31 | msandbox         | localhost        |\n |        32 | msandbox         | localhost        |\n +-----------+------------------+------------------+\n 4 rows in set (0.00 sec)\n \n mysql> select sys.ps_thread_account(31);\n +---------------------------+\n | sys.ps_thread_account(31) |\n +---------------------------+\n | msandbox@localhost        |\n +---------------------------+\n 1 row in set (0.00 sec)\n '
  BEGIN RETURN (SELECT IF( type = 'FOREGROUND', CONCAT(processlist_user, '@', processlist_host), type ) AS account FROM `performance_schema`.`threads` WHERE thread_id = in_thread_id); END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `ps_thread_id`( in_connection_id BIGINT UNSIGNED ) RETURNS bigint(20) unsigned
READS SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Return the Performance Schema THREAD_ID for the specified connection ID.\n \n Parameters\n \n in_connection_id (BIGINT UNSIGNED):\n The id of the connection to return the thread id for. If NULL, the current\n connection thread id is returned.\n \n Example\n \n mysql> SELECT sys.ps_thread_id(79);\n +----------------------+\n | sys.ps_thread_id(79) |\n +----------------------+\n |                   98 |\n +----------------------+\n 1 row in set (0.00 sec)\n \n mysql> SELECT sys.ps_thread_id(CONNECTION_ID());\n +-----------------------------------+\n | sys.ps_thread_id(CONNECTION_ID()) |\n +-----------------------------------+\n |                                98 |\n +-----------------------------------+\n 1 row in set (0.00 sec)\n '
  BEGIN RETURN (SELECT THREAD_ID FROM `performance_schema`.`threads` WHERE PROCESSLIST_ID = IFNULL(in_connection_id, CONNECTION_ID()) ); END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `ps_thread_stack`( thd_id BIGINT UNSIGNED, debug BOOLEAN ) RETURNS longtext CHARSET latin1
READS SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Outputs a JSON formatted stack of all statements, stages and events\n within Performance Schema for the specified thread.\n \n Parameters\n \n thd_id (BIGINT UNSIGNED):\n The id of the thread to trace. This should match the thread_id\n column from the performance_schema.threads table.\n in_verbose (BOOLEAN):\n Include file:lineno information in the events.\n \n Example\n \n (line separation added for output)\n \n mysql> SELECT sys.ps_thread_stack(37, FALSE) AS thread_stack\\G\n *************************** 1. row ***************************\n thread_stack: {"rankdir": "LR","nodesep": "0.10","stack_created": "2014-02-19 13:39:03",\n "mysql_version": "5.7.3-m13","mysql_user": "root@localhost","events": \n [{"nesting_event_id": "0", "event_id": "10", "timer_wait": 256.35, "event_info": \n "sql/select", "wait_info": "select @@version_comment limit 1\\nerrors: 0\\nwarnings: 0\\nlock time:\n ...\n '
  BEGIN  DECLARE json_objects LONGTEXT;   UPDATE performance_schema.threads SET instrumented = 'NO' WHERE processlist_id = CONNECTION_ID();   SET SESSION group_concat_max_len=@@global.max_allowed_packet;  SELECT GROUP_CONCAT(CONCAT( '{' , CONCAT_WS( ', ' , CONCAT('"nesting_event_id": "', IF(nesting_event_id IS NULL, '0', nesting_event_id), '"') , CONCAT('"event_id": "', event_id, '"') , CONCAT( '"timer_wait": ', ROUND(timer_wait/1000000, 2))   , CONCAT( '"event_info": "' , CASE WHEN event_name NOT LIKE 'wait/io%' THEN REPLACE(SUBSTRING_INDEX(event_name, '/', -2), '\\', '\\\\') WHEN event_name NOT LIKE 'wait/io/file%' OR event_name NOT LIKE 'wait/io/socket%' THEN REPLACE(SUBSTRING_INDEX(event_name, '/', -4), '\\', '\\\\') ELSE event_name END , '"' ) , CONCAT( '"wait_info": "', IFNULL(wait_info, ''), '"') , CONCAT( '"source": "', IF(true AND event_name LIKE 'wait%', IFNULL(wait_info, ''), ''), '"') , CASE  WHEN event_name LIKE 'wait/io/file%'      THEN '"event_type": "io/file"' WHEN event_name LIKE 'wait/io/table%'     THEN '"event_type": "io/table"' WHEN event_name LIKE 'wait/io/socket%'    THEN '"event_type": "io/socket"' WHEN event_name LIKE 'wait/synch/mutex%'  THEN '"event_type": "synch/mutex"' WHEN event_name LIKE 'wait/synch/cond%'   THEN '"event_type": "synch/cond"' WHEN event_name LIKE 'wait/synch/rwlock%' THEN '"event_type": "synch/rwlock"' WHEN event_name LIKE 'wait/lock%'         THEN '"event_type": "lock"' WHEN event_name LIKE 'statement/%'        THEN '"event_type": "stmt"' WHEN event_name LIKE 'stage/%'            THEN '"event_type": "stage"' WHEN event_name LIKE '%idle%'             THEN '"event_type": "idle"' ELSE ''  END                    ) , '}' ) ORDER BY event_id ASC SEPARATOR ',') event INTO json_objects FROM (  (SELECT thread_id, event_id, event_name, timer_wait, timer_start, nesting_event_id,  CONCAT(sql_text, '\\n', 'errors: ', errors, '\\n', 'warnings: ', warnings, '\\n', 'lock time: ', ROUND(lock_time/1000000, 2),'us\\n', 'rows affected: ', rows_affected, '\\n', 'rows sent: ', rows_sent, '\\n', 'rows examined: ', rows_examined, '\\n', 'tmp tables: ', created_tmp_tables, '\\n', 'tmp disk tables: ', created_tmp_disk_tables, '\\n', 'select scan: ', select_scan, '\\n', 'select full join: ', select_full_join, '\\n', 'select full range join: ', select_full_range_join, '\\n', 'select range: ', select_range, '\\n', 'select range check: ', select_range_check, '\\n',  'sort merge passes: ', sort_merge_passes, '\\n', 'sort rows: ', sort_rows, '\\n', 'sort range: ', sort_range, '\\n', 'sort scan: ', sort_scan, '\\n', 'no index used: ', IF(no_index_used, 'TRUE', 'FALSE'), '\\n', 'no good index used: ', IF(no_good_index_used, 'TRUE', 'FALSE'), '\\n' ) AS wait_info FROM performance_schema.events_statements_history_long WHERE thread_id = thd_id) UNION  (SELECT thread_id, event_id, event_name, timer_wait, timer_start, nesting_event_id, null AS wait_info FROM performance_schema.events_stages_history_long WHERE thread_id = thd_id)  UNION  (SELECT thread_id, event_id,  CONCAT(event_name ,  IF(event_name NOT LIKE 'wait/synch/mutex%', IFNULL(CONCAT(' - ', operation), ''), ''),  IF(number_of_bytes IS NOT NULL, CONCAT(' ', number_of_bytes, ' bytes'), ''), IF(event_name LIKE 'wait/io/file%', '\\n', ''), IF(object_schema IS NOT NULL, CONCAT('\\nObject: ', object_schema, '.'), ''),  IF(object_name IS NOT NULL,  IF (event_name LIKE 'wait/io/socket%', CONCAT(IF (object_name LIKE ':0%', @@socket, object_name)), object_name), ''),  IF(index_name IS NOT NULL, CONCAT(' Index: ', index_name), ''), '\\n' ) AS event_name, timer_wait, timer_start, nesting_event_id, source AS wait_info FROM performance_schema.events_waits_history_long WHERE thread_id = thd_id)) events  ORDER BY event_id;  RETURN CONCAT('{',  CONCAT_WS(',',  '"rankdir": "LR"', '"nodesep": "0.10"', CONCAT('"stack_created": "', NOW(), '"'), CONCAT('"mysql_version": "', VERSION(), '"'), CONCAT('"mysql_user": "', CURRENT_USER(), '"'), CONCAT('"events": [', IFNULL(json_objects,''), ']') ), '}');  END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `ps_thread_trx_info`( in_thread_id BIGINT UNSIGNED ) RETURNS longtext CHARSET utf8
READS SQL DATA
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Returns a JSON object with info on the given threads current transaction, \n and the statements it has already executed, derived from the\n performance_schema.events_transactions_current and\n performance_schema.events_statements_history tables (so the consumers \n for these also have to be enabled within Performance Schema to get full\n data in the object).\n \n When the output exceeds the default truncation length (65535), a JSON error\n object is returned, such as:\n \n { "error": "Trx info truncated: Row 6 was cut by GROUP_CONCAT()" }\n \n Similar error objects are returned for other warnings/and exceptions raised\n when calling the function.\n \n The max length of the output of this function can be controlled with the\n ps_thread_trx_info.max_length variable set via sys_config, or the\n @sys.ps_thread_trx_info.max_length user variable, as appropriate.\n \n Parameters\n \n in_thread_id (BIGINT UNSIGNED):\n The id of the thread to return the transaction info for.\n \n Example\n \n SELECT sys.ps_thread_trx_info(48)\\G\n *************************** 1. row ***************************\n sys.ps_thread_trx_info(48): [\n {\n "time": "790.70 us",\n "state": "COMMITTED",\n "mode": "READ WRITE",\n "autocommitted": "NO",\n "gtid": "AUTOMATIC",\n "isolation": "REPEATABLE READ",\n "statements_executed": [\n {\n "sql_text": "INSERT INTO info VALUES (1, ''foo'')",\n "time": "471.02 us",\n "schema": "trx",\n "rows_examined": 0,\n "rows_affected": 1,\n "rows_sent": 0,\n "tmp_tables": 0,\n "tmp_disk_tables": 0,\n "sort_rows": 0,\n "sort_merge_passes": 0\n },\n {\n "sql_text": "COMMIT",\n "time": "254.42 us",\n "schema": "trx",\n "rows_examined": 0,\n "rows_affected": 0,\n "rows_sent": 0,\n "tmp_tables": 0,\n "tmp_disk_tables": 0,\n "sort_rows": 0,\n "sort_merge_passes": 0\n }\n ]\n },\n {\n "time": "426.20 us",\n "state": "COMMITTED",\n "mode": "READ WRITE",\n "autocommitted": "NO",\n "gtid": "AUTOMATIC",\n "isolation": "REPEATABLE READ",\n "statements_executed": [\n {\n "sql_text": "INSERT INTO info VALUES (2, ''bar'')",\n "time": "107.33 us",\n "schema": "trx",\n "rows_examined": 0,\n "rows_affected": 1,\n "rows_sent": 0,\n "tmp_tables": 0,\n "tmp_disk_tables": 0,\n "sort_rows": 0,\n "sort_merge_passes": 0\n },\n {\n "sql_text": "COMMIT",\n "time": "213.23 us",\n "schema": "trx",\n "rows_examined": 0,\n "rows_affected": 0,\n "rows_sent": 0,\n "tmp_tables": 0,\n "tmp_disk_tables": 0,\n "sort_rows": 0,\n "sort_merge_passes": 0\n }\n ]\n }\n ]\n 1 row in set (0.03 sec)\n '
  BEGIN DECLARE v_output LONGTEXT DEFAULT '{}'; DECLARE v_msg_text TEXT DEFAULT ''; DECLARE v_signal_msg TEXT DEFAULT ''; DECLARE v_mysql_errno INT; DECLARE v_max_output_len BIGINT; DECLARE EXIT HANDLER FOR SQLWARNING, SQLEXCEPTION BEGIN GET DIAGNOSTICS CONDITION 1 v_msg_text = MESSAGE_TEXT, v_mysql_errno = MYSQL_ERRNO;  IF v_mysql_errno = 1260 THEN SET v_signal_msg = CONCAT('{ "error": "Trx info truncated: ', v_msg_text, '" }'); ELSE SET v_signal_msg = CONCAT('{ "error": "', v_msg_text, '" }'); END IF;  RETURN v_signal_msg; END;  IF (@sys.ps_thread_trx_info.max_length IS NULL) THEN SET @sys.ps_thread_trx_info.max_length = sys.sys_get_config('ps_thread_trx_info.max_length', 65535); END IF;  IF (@sys.ps_thread_trx_info.max_length != @@session.group_concat_max_len) THEN SET @old_group_concat_max_len = @@session.group_concat_max_len; SET v_max_output_len = (@sys.ps_thread_trx_info.max_length - 5); SET SESSION group_concat_max_len = v_max_output_len; END IF;  SET v_output = ( SELECT CONCAT('[', IFNULL(GROUP_CONCAT(trx_info ORDER BY event_id), ''), '\n]') AS trx_info FROM (SELECT trxi.thread_id,  trxi.event_id, GROUP_CONCAT( IFNULL( CONCAT('\n  {\n', '    "time": "', IFNULL(sys.format_time(trxi.timer_wait), ''), '",\n', '    "state": "', IFNULL(trxi.state, ''), '",\n', '    "mode": "', IFNULL(trxi.access_mode, ''), '",\n', '    "autocommitted": "', IFNULL(trxi.autocommit, ''), '",\n', '    "gtid": "', IFNULL(trxi.gtid, ''), '",\n', '    "isolation": "', IFNULL(trxi.isolation_level, ''), '",\n', '    "statements_executed": [', IFNULL(s.stmts, ''), IF(s.stmts IS NULL, ' ]\n', '\n    ]\n'), '  }' ),  '')  ORDER BY event_id) AS trx_info  FROM ( (SELECT thread_id, event_id, timer_wait, state,access_mode, autocommit, gtid, isolation_level FROM performance_schema.events_transactions_current WHERE thread_id = in_thread_id AND end_event_id IS NULL) UNION (SELECT thread_id, event_id, timer_wait, state,access_mode, autocommit, gtid, isolation_level FROM performance_schema.events_transactions_history WHERE thread_id = in_thread_id) ) AS trxi LEFT JOIN (SELECT thread_id, nesting_event_id, GROUP_CONCAT( IFNULL( CONCAT('\n      {\n', '        "sql_text": "', IFNULL(sys.format_statement(REPLACE(sql_text, '\\', '\\\\')), ''), '",\n', '        "time": "', IFNULL(sys.format_time(timer_wait), ''), '",\n', '        "schema": "', IFNULL(current_schema, ''), '",\n', '        "rows_examined": ', IFNULL(rows_examined, ''), ',\n', '        "rows_affected": ', IFNULL(rows_affected, ''), ',\n', '        "rows_sent": ', IFNULL(rows_sent, ''), ',\n', '        "tmp_tables": ', IFNULL(created_tmp_tables, ''), ',\n', '        "tmp_disk_tables": ', IFNULL(created_tmp_disk_tables, ''), ',\n', '        "sort_rows": ', IFNULL(sort_rows, ''), ',\n', '        "sort_merge_passes": ', IFNULL(sort_merge_passes, ''), '\n', '      }'), '') ORDER BY event_id) AS stmts FROM performance_schema.events_statements_history WHERE sql_text IS NOT NULL AND thread_id = in_thread_id GROUP BY thread_id, nesting_event_id ) AS s  ON trxi.thread_id = s.thread_id  AND trxi.event_id = s.nesting_event_id WHERE trxi.thread_id = in_thread_id GROUP BY trxi.thread_id, trxi.event_id ) trxs GROUP BY thread_id );  IF (@old_group_concat_max_len IS NOT NULL) THEN SET SESSION group_concat_max_len = @old_group_concat_max_len; END IF;  RETURN v_output; END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `quote_identifier`(in_identifier TEXT) RETURNS text CHARSET utf8
NO SQL
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Takes an unquoted identifier (schema name, table name, etc.) and\n returns the identifier quoted with backticks.\n \n Parameters\n \n in_identifier (TEXT):\n The identifier to quote.\n \n Returns\n \n TEXT\n \n Example\n \n mysql> SELECT sys.quote_identifier(''my_identifier'') AS Identifier;\n +-----------------+\n | Identifier      |\n +-----------------+\n | `my_identifier` |\n +-----------------+\n 1 row in set (0.00 sec)\n \n mysql> SELECT sys.quote_identifier(''my`idenfier'') AS Identifier;\n +----------------+\n | Identifier     |\n +----------------+\n | `my``idenfier` |\n +----------------+\n 1 row in set (0.00 sec)\n '
  BEGIN RETURN CONCAT('`', REPLACE(in_identifier, '`', '``'), '`'); END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `sys_get_config`( in_variable_name VARCHAR(128), in_default_value VARCHAR(128) ) RETURNS varchar(128) CHARSET utf8
READS SQL DATA
DETERMINISTIC
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Returns the value for the requested variable using the following logic:\n \n 1. If the option exists in sys.sys_config return the value from there.\n 2. Else fall back on the provided default value.\n \n Notes for using sys_get_config():\n \n * If the default value argument to sys_get_config() is NULL and case 2. is reached, NULL is returned.\n It is then expected that the caller is able to handle NULL for the given configuration option.\n * The convention is to name the user variables @sys.<name of variable>. It is <name of variable> that\n is stored in the sys_config table and is what is expected as the argument to sys_get_config().\n * If you want to check whether the configuration option has already been set and if not assign with\n the return value of sys_get_config() you can use IFNULL(...) (see example below). However this should\n not be done inside a loop (e.g. for each row in a result set) as for repeated calls where assignment\n is only needed in the first iteration using IFNULL(...) is expected to be significantly slower than\n using an IF (...) THEN ... END IF; block (see example below).\n \n Parameters\n \n in_variable_name (VARCHAR(128)):\n The name of the config option to return the value for.\n \n in_default_value (VARCHAR(128)):\n The default value to return if the variable does not exist in sys.sys_config.\n \n Returns\n \n VARCHAR(128)\n \n Example\n \n mysql> SELECT sys.sys_get_config(''statement_truncate_len'', 128) AS Value;\n +-------+\n | Value |\n +-------+\n | 64    |\n +-------+\n 1 row in set (0.00 sec)\n \n mysql> SET @sys.statement_truncate_len = IFNULL(@sys.statement_truncate_len, sys.sys_get_config(''statement_truncate_len'', 64));\n Query OK, 0 rows affected (0.00 sec)\n \n IF (@sys.statement_truncate_len IS NULL) THEN\n SET @sys.statement_truncate_len = sys.sys_get_config(''statement_truncate_len'', 64);\n END IF;\n '
  BEGIN DECLARE v_value VARCHAR(128) DEFAULT NULL;  SET v_value = (SELECT value FROM sys.sys_config WHERE variable = in_variable_name);  IF (v_value IS NULL) THEN SET v_value = in_default_value; END IF;  RETURN v_value; END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `version_major`() RETURNS tinyint(3) unsigned
NO SQL
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Returns the major version of MySQL Server.\n \n Returns\n \n TINYINT UNSIGNED\n \n Example\n \n mysql> SELECT VERSION(), sys.version_major();\n +--------------------------------------+---------------------+\n | VERSION()                            | sys.version_major() |\n +--------------------------------------+---------------------+\n | 5.7.9-enterprise-commercial-advanced | 5                   |\n +--------------------------------------+---------------------+\n 1 row in set (0.00 sec)\n '
  BEGIN RETURN SUBSTRING_INDEX(SUBSTRING_INDEX(VERSION(), '-', 1), '.', 1); END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `version_minor`() RETURNS tinyint(3) unsigned
NO SQL
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Returns the minor (release series) version of MySQL Server.\n \n Returns\n \n TINYINT UNSIGNED\n \n Example\n \n mysql> SELECT VERSION(), sys.server_minor();\n +--------------------------------------+---------------------+\n | VERSION()                            | sys.version_minor() |\n +--------------------------------------+---------------------+\n | 5.7.9-enterprise-commercial-advanced | 7                   |\n +--------------------------------------+---------------------+\n 1 row in set (0.00 sec)\n '
  BEGIN RETURN SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(VERSION(), '-', 1), '.', 2), '.', -1); END$$

CREATE DEFINER=`mysql.sys`@`localhost` FUNCTION `version_patch`() RETURNS tinyint(3) unsigned
NO SQL
  SQL SECURITY INVOKER
  COMMENT '\n Description\n \n Returns the patch release version of MySQL Server.\n \n Returns\n \n TINYINT UNSIGNED\n \n Example\n \n mysql> SELECT VERSION(), sys.version_patch();\n +--------------------------------------+---------------------+\n | VERSION()                            | sys.version_patch() |\n +--------------------------------------+---------------------+\n | 5.7.9-enterprise-commercial-advanced | 9                   |\n +--------------------------------------+---------------------+\n 1 row in set (0.00 sec)\n '
  BEGIN RETURN SUBSTRING_INDEX(SUBSTRING_INDEX(VERSION(), '-', 1), '.', -1); END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `host_summary`
--
CREATE TABLE IF NOT EXISTS `host_summary` (
   `host` varchar(60)
  ,`statements` decimal(64,0)
  ,`statement_latency` text
  ,`statement_avg_latency` text
  ,`table_scans` decimal(65,0)
  ,`file_ios` decimal(64,0)
  ,`file_io_latency` text
  ,`current_connections` decimal(41,0)
  ,`total_connections` decimal(41,0)
  ,`unique_users` bigint(21)
  ,`current_memory` text
  ,`total_memory_allocated` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `host_summary_by_file_io`
--
CREATE TABLE IF NOT EXISTS `host_summary_by_file_io` (
   `host` varchar(60)
  ,`ios` decimal(42,0)
  ,`io_latency` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `host_summary_by_file_io_type`
--
CREATE TABLE IF NOT EXISTS `host_summary_by_file_io_type` (
   `host` varchar(60)
  ,`event_name` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` text
  ,`max_latency` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `host_summary_by_stages`
--
CREATE TABLE IF NOT EXISTS `host_summary_by_stages` (
   `host` varchar(60)
  ,`event_name` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` text
  ,`avg_latency` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `host_summary_by_statement_latency`
--
CREATE TABLE IF NOT EXISTS `host_summary_by_statement_latency` (
   `host` varchar(60)
  ,`total` decimal(42,0)
  ,`total_latency` text
  ,`max_latency` text
  ,`lock_latency` text
  ,`rows_sent` decimal(42,0)
  ,`rows_examined` decimal(42,0)
  ,`rows_affected` decimal(42,0)
  ,`full_scans` decimal(43,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `host_summary_by_statement_type`
--
CREATE TABLE IF NOT EXISTS `host_summary_by_statement_type` (
   `host` varchar(60)
  ,`statement` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` text
  ,`max_latency` text
  ,`lock_latency` text
  ,`rows_sent` bigint(20) unsigned
  ,`rows_examined` bigint(20) unsigned
  ,`rows_affected` bigint(20) unsigned
  ,`full_scans` bigint(21) unsigned
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `innodb_buffer_stats_by_schema`
--
CREATE TABLE IF NOT EXISTS `innodb_buffer_stats_by_schema` (
   `object_schema` text
  ,`allocated` text
  ,`data` text
  ,`pages` bigint(21)
  ,`pages_hashed` bigint(21)
  ,`pages_old` bigint(21)
  ,`rows_cached` decimal(44,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `innodb_buffer_stats_by_table`
--
CREATE TABLE IF NOT EXISTS `innodb_buffer_stats_by_table` (
   `object_schema` text
  ,`object_name` text
  ,`allocated` text
  ,`data` text
  ,`pages` bigint(21)
  ,`pages_hashed` bigint(21)
  ,`pages_old` bigint(21)
  ,`rows_cached` decimal(44,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `innodb_lock_waits`
--
CREATE TABLE IF NOT EXISTS `innodb_lock_waits` (
   `wait_started` datetime
  ,`wait_age` time
  ,`wait_age_secs` bigint(21)
  ,`locked_table` varchar(1024)
  ,`locked_index` varchar(1024)
  ,`locked_type` varchar(32)
  ,`waiting_trx_id` varchar(18)
  ,`waiting_trx_started` datetime
  ,`waiting_trx_age` time
  ,`waiting_trx_rows_locked` bigint(21) unsigned
  ,`waiting_trx_rows_modified` bigint(21) unsigned
  ,`waiting_pid` bigint(21) unsigned
  ,`waiting_query` longtext
  ,`waiting_lock_id` varchar(81)
  ,`waiting_lock_mode` varchar(32)
  ,`blocking_trx_id` varchar(18)
  ,`blocking_pid` bigint(21) unsigned
  ,`blocking_query` longtext
  ,`blocking_lock_id` varchar(81)
  ,`blocking_lock_mode` varchar(32)
  ,`blocking_trx_started` datetime
  ,`blocking_trx_age` time
  ,`blocking_trx_rows_locked` bigint(21) unsigned
  ,`blocking_trx_rows_modified` bigint(21) unsigned
  ,`sql_kill_blocking_query` varchar(32)
  ,`sql_kill_blocking_connection` varchar(26)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `io_by_thread_by_latency`
--
CREATE TABLE IF NOT EXISTS `io_by_thread_by_latency` (
   `user` varchar(128)
  ,`total` decimal(42,0)
  ,`total_latency` text
  ,`min_latency` text
  ,`avg_latency` text
  ,`max_latency` text
  ,`thread_id` bigint(20) unsigned
  ,`processlist_id` bigint(20) unsigned
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `io_global_by_file_by_bytes`
--
CREATE TABLE IF NOT EXISTS `io_global_by_file_by_bytes` (
   `file` varchar(512)
  ,`count_read` bigint(20) unsigned
  ,`total_read` text
  ,`avg_read` text
  ,`count_write` bigint(20) unsigned
  ,`total_written` text
  ,`avg_write` text
  ,`total` text
  ,`write_pct` decimal(26,2)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `io_global_by_file_by_latency`
--
CREATE TABLE IF NOT EXISTS `io_global_by_file_by_latency` (
   `file` varchar(512)
  ,`total` bigint(20) unsigned
  ,`total_latency` text
  ,`count_read` bigint(20) unsigned
  ,`read_latency` text
  ,`count_write` bigint(20) unsigned
  ,`write_latency` text
  ,`count_misc` bigint(20) unsigned
  ,`misc_latency` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `io_global_by_wait_by_bytes`
--
CREATE TABLE IF NOT EXISTS `io_global_by_wait_by_bytes` (
   `event_name` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` text
  ,`min_latency` text
  ,`avg_latency` text
  ,`max_latency` text
  ,`count_read` bigint(20) unsigned
  ,`total_read` text
  ,`avg_read` text
  ,`count_write` bigint(20) unsigned
  ,`total_written` text
  ,`avg_written` text
  ,`total_requested` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `io_global_by_wait_by_latency`
--
CREATE TABLE IF NOT EXISTS `io_global_by_wait_by_latency` (
   `event_name` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` text
  ,`avg_latency` text
  ,`max_latency` text
  ,`read_latency` text
  ,`write_latency` text
  ,`misc_latency` text
  ,`count_read` bigint(20) unsigned
  ,`total_read` text
  ,`avg_read` text
  ,`count_write` bigint(20) unsigned
  ,`total_written` text
  ,`avg_written` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `latest_file_io`
--
CREATE TABLE IF NOT EXISTS `latest_file_io` (
   `thread` varchar(149)
  ,`file` varchar(512)
  ,`latency` text
  ,`operation` varchar(32)
  ,`requested` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `memory_by_host_by_current_bytes`
--
CREATE TABLE IF NOT EXISTS `memory_by_host_by_current_bytes` (
   `host` varchar(60)
  ,`current_count_used` decimal(41,0)
  ,`current_allocated` text
  ,`current_avg_alloc` text
  ,`current_max_alloc` text
  ,`total_allocated` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `memory_by_thread_by_current_bytes`
--
CREATE TABLE IF NOT EXISTS `memory_by_thread_by_current_bytes` (
   `thread_id` bigint(20) unsigned
  ,`user` varchar(128)
  ,`current_count_used` decimal(41,0)
  ,`current_allocated` text
  ,`current_avg_alloc` text
  ,`current_max_alloc` text
  ,`total_allocated` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `memory_by_user_by_current_bytes`
--
CREATE TABLE IF NOT EXISTS `memory_by_user_by_current_bytes` (
   `user` varchar(32)
  ,`current_count_used` decimal(41,0)
  ,`current_allocated` text
  ,`current_avg_alloc` text
  ,`current_max_alloc` text
  ,`total_allocated` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `memory_global_by_current_bytes`
--
CREATE TABLE IF NOT EXISTS `memory_global_by_current_bytes` (
   `event_name` varchar(128)
  ,`current_count` bigint(20)
  ,`current_alloc` text
  ,`current_avg_alloc` text
  ,`high_count` bigint(20)
  ,`high_alloc` text
  ,`high_avg_alloc` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `memory_global_total`
--
CREATE TABLE IF NOT EXISTS `memory_global_total` (
  `total_allocated` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `metrics`
--
CREATE TABLE IF NOT EXISTS `metrics` (
   `Variable_name` varchar(193)
  ,`Variable_value` text
  ,`Type` varchar(210)
  ,`Enabled` varchar(7)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `processlist`
--
CREATE TABLE IF NOT EXISTS `processlist` (
   `thd_id` bigint(20) unsigned
  ,`conn_id` bigint(20) unsigned
  ,`user` varchar(128)
  ,`db` varchar(64)
  ,`command` varchar(16)
  ,`state` varchar(64)
  ,`time` bigint(20)
  ,`current_statement` longtext
  ,`statement_latency` text
  ,`progress` decimal(26,2)
  ,`lock_latency` text
  ,`rows_examined` bigint(20) unsigned
  ,`rows_sent` bigint(20) unsigned
  ,`rows_affected` bigint(20) unsigned
  ,`tmp_tables` bigint(20) unsigned
  ,`tmp_disk_tables` bigint(20) unsigned
  ,`full_scan` varchar(3)
  ,`last_statement` longtext
  ,`last_statement_latency` text
  ,`current_memory` text
  ,`last_wait` varchar(128)
  ,`last_wait_latency` text
  ,`source` varchar(64)
  ,`trx_latency` text
  ,`trx_state` enum('ACTIVE','COMMITTED','ROLLED BACK')
  ,`trx_autocommit` enum('YES','NO')
  ,`pid` varchar(1024)
  ,`program_name` varchar(1024)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `ps_check_lost_instrumentation`
--
CREATE TABLE IF NOT EXISTS `ps_check_lost_instrumentation` (
   `variable_name` varchar(64)
  ,`variable_value` varchar(1024)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `schema_auto_increment_columns`
--
CREATE TABLE IF NOT EXISTS `schema_auto_increment_columns` (
   `table_schema` varchar(64)
  ,`table_name` varchar(64)
  ,`column_name` varchar(64)
  ,`data_type` varchar(64)
  ,`column_type` longtext
  ,`is_signed` int(1)
  ,`is_unsigned` int(1)
  ,`max_value` bigint(21) unsigned
  ,`auto_increment` bigint(21) unsigned
  ,`auto_increment_ratio` decimal(25,4) unsigned
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `schema_index_statistics`
--
CREATE TABLE IF NOT EXISTS `schema_index_statistics` (
   `table_schema` varchar(64)
  ,`table_name` varchar(64)
  ,`index_name` varchar(64)
  ,`rows_selected` bigint(20) unsigned
  ,`select_latency` text
  ,`rows_inserted` bigint(20) unsigned
  ,`insert_latency` text
  ,`rows_updated` bigint(20) unsigned
  ,`update_latency` text
  ,`rows_deleted` bigint(20) unsigned
  ,`delete_latency` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `schema_object_overview`
--
CREATE TABLE IF NOT EXISTS `schema_object_overview` (
   `db` varchar(64)
  ,`object_type` varchar(64)
  ,`count` bigint(21)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `schema_redundant_indexes`
--
CREATE TABLE IF NOT EXISTS `schema_redundant_indexes` (
   `table_schema` varchar(64)
  ,`table_name` varchar(64)
  ,`redundant_index_name` varchar(64)
  ,`redundant_index_columns` text
  ,`redundant_index_non_unique` bigint(1)
  ,`dominant_index_name` varchar(64)
  ,`dominant_index_columns` text
  ,`dominant_index_non_unique` bigint(1)
  ,`subpart_exists` int(1)
  ,`sql_drop_index` varchar(223)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `schema_table_lock_waits`
--
CREATE TABLE IF NOT EXISTS `schema_table_lock_waits` (
   `object_schema` varchar(64)
  ,`object_name` varchar(64)
  ,`waiting_thread_id` bigint(20) unsigned
  ,`waiting_pid` bigint(20) unsigned
  ,`waiting_account` text
  ,`waiting_lock_type` varchar(32)
  ,`waiting_lock_duration` varchar(32)
  ,`waiting_query` longtext
  ,`waiting_query_secs` bigint(20)
  ,`waiting_query_rows_affected` bigint(20) unsigned
  ,`waiting_query_rows_examined` bigint(20) unsigned
  ,`blocking_thread_id` bigint(20) unsigned
  ,`blocking_pid` bigint(20) unsigned
  ,`blocking_account` text
  ,`blocking_lock_type` varchar(32)
  ,`blocking_lock_duration` varchar(32)
  ,`sql_kill_blocking_query` varchar(31)
  ,`sql_kill_blocking_connection` varchar(25)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `schema_table_statistics`
--
CREATE TABLE IF NOT EXISTS `schema_table_statistics` (
   `table_schema` varchar(64)
  ,`table_name` varchar(64)
  ,`total_latency` text
  ,`rows_fetched` bigint(20) unsigned
  ,`fetch_latency` text
  ,`rows_inserted` bigint(20) unsigned
  ,`insert_latency` text
  ,`rows_updated` bigint(20) unsigned
  ,`update_latency` text
  ,`rows_deleted` bigint(20) unsigned
  ,`delete_latency` text
  ,`io_read_requests` decimal(42,0)
  ,`io_read` text
  ,`io_read_latency` text
  ,`io_write_requests` decimal(42,0)
  ,`io_write` text
  ,`io_write_latency` text
  ,`io_misc_requests` decimal(42,0)
  ,`io_misc_latency` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `schema_table_statistics_with_buffer`
--
CREATE TABLE IF NOT EXISTS `schema_table_statistics_with_buffer` (
   `table_schema` varchar(64)
  ,`table_name` varchar(64)
  ,`rows_fetched` bigint(20) unsigned
  ,`fetch_latency` text
  ,`rows_inserted` bigint(20) unsigned
  ,`insert_latency` text
  ,`rows_updated` bigint(20) unsigned
  ,`update_latency` text
  ,`rows_deleted` bigint(20) unsigned
  ,`delete_latency` text
  ,`io_read_requests` decimal(42,0)
  ,`io_read` text
  ,`io_read_latency` text
  ,`io_write_requests` decimal(42,0)
  ,`io_write` text
  ,`io_write_latency` text
  ,`io_misc_requests` decimal(42,0)
  ,`io_misc_latency` text
  ,`innodb_buffer_allocated` text
  ,`innodb_buffer_data` text
  ,`innodb_buffer_free` text
  ,`innodb_buffer_pages` bigint(21)
  ,`innodb_buffer_pages_hashed` bigint(21)
  ,`innodb_buffer_pages_old` bigint(21)
  ,`innodb_buffer_rows_cached` decimal(44,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `schema_tables_with_full_table_scans`
--
CREATE TABLE IF NOT EXISTS `schema_tables_with_full_table_scans` (
   `object_schema` varchar(64)
  ,`object_name` varchar(64)
  ,`rows_full_scanned` bigint(20) unsigned
  ,`latency` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `schema_unused_indexes`
--
CREATE TABLE IF NOT EXISTS `schema_unused_indexes` (
   `object_schema` varchar(64)
  ,`object_name` varchar(64)
  ,`index_name` varchar(64)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `session`
--
CREATE TABLE IF NOT EXISTS `session` (
   `thd_id` bigint(20) unsigned
  ,`conn_id` bigint(20) unsigned
  ,`user` varchar(128)
  ,`db` varchar(64)
  ,`command` varchar(16)
  ,`state` varchar(64)
  ,`time` bigint(20)
  ,`current_statement` longtext
  ,`statement_latency` text
  ,`progress` decimal(26,2)
  ,`lock_latency` text
  ,`rows_examined` bigint(20) unsigned
  ,`rows_sent` bigint(20) unsigned
  ,`rows_affected` bigint(20) unsigned
  ,`tmp_tables` bigint(20) unsigned
  ,`tmp_disk_tables` bigint(20) unsigned
  ,`full_scan` varchar(3)
  ,`last_statement` longtext
  ,`last_statement_latency` text
  ,`current_memory` text
  ,`last_wait` varchar(128)
  ,`last_wait_latency` text
  ,`source` varchar(64)
  ,`trx_latency` text
  ,`trx_state` enum('ACTIVE','COMMITTED','ROLLED BACK')
  ,`trx_autocommit` enum('YES','NO')
  ,`pid` varchar(1024)
  ,`program_name` varchar(1024)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `session_ssl_status`
--
CREATE TABLE IF NOT EXISTS `session_ssl_status` (
   `thread_id` bigint(20) unsigned
  ,`ssl_version` varchar(1024)
  ,`ssl_cipher` varchar(1024)
  ,`ssl_sessions_reused` varchar(1024)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `statement_analysis`
--
CREATE TABLE IF NOT EXISTS `statement_analysis` (
   `query` longtext
  ,`db` varchar(64)
  ,`full_scan` varchar(1)
  ,`exec_count` bigint(20) unsigned
  ,`err_count` bigint(20) unsigned
  ,`warn_count` bigint(20) unsigned
  ,`total_latency` text
  ,`max_latency` text
  ,`avg_latency` text
  ,`lock_latency` text
  ,`rows_sent` bigint(20) unsigned
  ,`rows_sent_avg` decimal(21,0)
  ,`rows_examined` bigint(20) unsigned
  ,`rows_examined_avg` decimal(21,0)
  ,`rows_affected` bigint(20) unsigned
  ,`rows_affected_avg` decimal(21,0)
  ,`tmp_tables` bigint(20) unsigned
  ,`tmp_disk_tables` bigint(20) unsigned
  ,`rows_sorted` bigint(20) unsigned
  ,`sort_merge_passes` bigint(20) unsigned
  ,`digest` varchar(32)
  ,`first_seen` timestamp
  ,`last_seen` timestamp
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `statements_with_errors_or_warnings`
--
CREATE TABLE IF NOT EXISTS `statements_with_errors_or_warnings` (
   `query` longtext
  ,`db` varchar(64)
  ,`exec_count` bigint(20) unsigned
  ,`errors` bigint(20) unsigned
  ,`error_pct` decimal(27,4)
  ,`warnings` bigint(20) unsigned
  ,`warning_pct` decimal(27,4)
  ,`first_seen` timestamp
  ,`last_seen` timestamp
  ,`digest` varchar(32)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `statements_with_full_table_scans`
--
CREATE TABLE IF NOT EXISTS `statements_with_full_table_scans` (
   `query` longtext
  ,`db` varchar(64)
  ,`exec_count` bigint(20) unsigned
  ,`total_latency` text
  ,`no_index_used_count` bigint(20) unsigned
  ,`no_good_index_used_count` bigint(20) unsigned
  ,`no_index_used_pct` decimal(24,0)
  ,`rows_sent` bigint(20) unsigned
  ,`rows_examined` bigint(20) unsigned
  ,`rows_sent_avg` decimal(21,0) unsigned
  ,`rows_examined_avg` decimal(21,0) unsigned
  ,`first_seen` timestamp
  ,`last_seen` timestamp
  ,`digest` varchar(32)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `statements_with_runtimes_in_95th_percentile`
--
CREATE TABLE IF NOT EXISTS `statements_with_runtimes_in_95th_percentile` (
   `query` longtext
  ,`db` varchar(64)
  ,`full_scan` varchar(1)
  ,`exec_count` bigint(20) unsigned
  ,`err_count` bigint(20) unsigned
  ,`warn_count` bigint(20) unsigned
  ,`total_latency` text
  ,`max_latency` text
  ,`avg_latency` text
  ,`rows_sent` bigint(20) unsigned
  ,`rows_sent_avg` decimal(21,0)
  ,`rows_examined` bigint(20) unsigned
  ,`rows_examined_avg` decimal(21,0)
  ,`first_seen` timestamp
  ,`last_seen` timestamp
  ,`digest` varchar(32)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `statements_with_sorting`
--
CREATE TABLE IF NOT EXISTS `statements_with_sorting` (
   `query` longtext
  ,`db` varchar(64)
  ,`exec_count` bigint(20) unsigned
  ,`total_latency` text
  ,`sort_merge_passes` bigint(20) unsigned
  ,`avg_sort_merges` decimal(21,0)
  ,`sorts_using_scans` bigint(20) unsigned
  ,`sort_using_range` bigint(20) unsigned
  ,`rows_sorted` bigint(20) unsigned
  ,`avg_rows_sorted` decimal(21,0)
  ,`first_seen` timestamp
  ,`last_seen` timestamp
  ,`digest` varchar(32)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `statements_with_temp_tables`
--
CREATE TABLE IF NOT EXISTS `statements_with_temp_tables` (
   `query` longtext
  ,`db` varchar(64)
  ,`exec_count` bigint(20) unsigned
  ,`total_latency` text
  ,`memory_tmp_tables` bigint(20) unsigned
  ,`disk_tmp_tables` bigint(20) unsigned
  ,`avg_tmp_tables_per_query` decimal(21,0)
  ,`tmp_tables_to_disk_pct` decimal(24,0)
  ,`first_seen` timestamp
  ,`last_seen` timestamp
  ,`digest` varchar(32)
);
-- --------------------------------------------------------

--
-- Table structure for table `sys_config`
--

CREATE TABLE IF NOT EXISTS `sys_config` (
  `variable` varchar(128) NOT NULL,
  `value` varchar(128) DEFAULT NULL,
  `set_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `set_by` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`variable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Triggers `sys_config`
--
DROP TRIGGER IF EXISTS `sys_config_insert_set_user`;
DELIMITER //
CREATE TRIGGER `sys_config_insert_set_user` BEFORE INSERT ON `sys_config`
FOR EACH ROW BEGIN IF @sys.ignore_sys_config_triggers != true AND NEW.set_by IS NULL THEN SET NEW.set_by = USER(); END IF; END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `sys_config_update_set_user`;
DELIMITER //
CREATE TRIGGER `sys_config_update_set_user` BEFORE UPDATE ON `sys_config`
FOR EACH ROW BEGIN IF @sys.ignore_sys_config_triggers != true AND NEW.set_by IS NULL THEN SET NEW.set_by = USER(); END IF; END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_summary`
--
CREATE TABLE IF NOT EXISTS `user_summary` (
   `user` varchar(32)
  ,`statements` decimal(64,0)
  ,`statement_latency` text
  ,`statement_avg_latency` text
  ,`table_scans` decimal(65,0)
  ,`file_ios` decimal(64,0)
  ,`file_io_latency` text
  ,`current_connections` decimal(41,0)
  ,`total_connections` decimal(41,0)
  ,`unique_hosts` bigint(21)
  ,`current_memory` text
  ,`total_memory_allocated` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `user_summary_by_file_io`
--
CREATE TABLE IF NOT EXISTS `user_summary_by_file_io` (
   `user` varchar(32)
  ,`ios` decimal(42,0)
  ,`io_latency` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `user_summary_by_file_io_type`
--
CREATE TABLE IF NOT EXISTS `user_summary_by_file_io_type` (
   `user` varchar(32)
  ,`event_name` varchar(128)
  ,`total` bigint(20) unsigned
  ,`latency` text
  ,`max_latency` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `user_summary_by_stages`
--
CREATE TABLE IF NOT EXISTS `user_summary_by_stages` (
   `user` varchar(32)
  ,`event_name` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` text
  ,`avg_latency` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `user_summary_by_statement_latency`
--
CREATE TABLE IF NOT EXISTS `user_summary_by_statement_latency` (
   `user` varchar(32)
  ,`total` decimal(42,0)
  ,`total_latency` text
  ,`max_latency` text
  ,`lock_latency` text
  ,`rows_sent` decimal(42,0)
  ,`rows_examined` decimal(42,0)
  ,`rows_affected` decimal(42,0)
  ,`full_scans` decimal(43,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `user_summary_by_statement_type`
--
CREATE TABLE IF NOT EXISTS `user_summary_by_statement_type` (
   `user` varchar(32)
  ,`statement` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` text
  ,`max_latency` text
  ,`lock_latency` text
  ,`rows_sent` bigint(20) unsigned
  ,`rows_examined` bigint(20) unsigned
  ,`rows_affected` bigint(20) unsigned
  ,`full_scans` bigint(21) unsigned
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `version`
--
CREATE TABLE IF NOT EXISTS `version` (
   `sys_version` varchar(5)
  ,`mysql_version` varchar(11)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `wait_classes_global_by_avg_latency`
--
CREATE TABLE IF NOT EXISTS `wait_classes_global_by_avg_latency` (
   `event_class` varchar(128)
  ,`total` decimal(42,0)
  ,`total_latency` text
  ,`min_latency` text
  ,`avg_latency` text
  ,`max_latency` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `wait_classes_global_by_latency`
--
CREATE TABLE IF NOT EXISTS `wait_classes_global_by_latency` (
   `event_class` varchar(128)
  ,`total` decimal(42,0)
  ,`total_latency` text
  ,`min_latency` text
  ,`avg_latency` text
  ,`max_latency` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `waits_by_host_by_latency`
--
CREATE TABLE IF NOT EXISTS `waits_by_host_by_latency` (
   `host` varchar(60)
  ,`event` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` text
  ,`avg_latency` text
  ,`max_latency` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `waits_by_user_by_latency`
--
CREATE TABLE IF NOT EXISTS `waits_by_user_by_latency` (
   `user` varchar(32)
  ,`event` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` text
  ,`avg_latency` text
  ,`max_latency` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `waits_global_by_latency`
--
CREATE TABLE IF NOT EXISTS `waits_global_by_latency` (
   `events` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` text
  ,`avg_latency` text
  ,`max_latency` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$host_summary`
--
CREATE TABLE IF NOT EXISTS `x$host_summary` (
   `host` varchar(60)
  ,`statements` decimal(64,0)
  ,`statement_latency` decimal(64,0)
  ,`statement_avg_latency` decimal(65,4)
  ,`table_scans` decimal(65,0)
  ,`file_ios` decimal(64,0)
  ,`file_io_latency` decimal(64,0)
  ,`current_connections` decimal(41,0)
  ,`total_connections` decimal(41,0)
  ,`unique_users` bigint(21)
  ,`current_memory` decimal(63,0)
  ,`total_memory_allocated` decimal(64,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$host_summary_by_file_io`
--
CREATE TABLE IF NOT EXISTS `x$host_summary_by_file_io` (
   `host` varchar(60)
  ,`ios` decimal(42,0)
  ,`io_latency` decimal(42,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$host_summary_by_file_io_type`
--
CREATE TABLE IF NOT EXISTS `x$host_summary_by_file_io_type` (
   `host` varchar(60)
  ,`event_name` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` bigint(20) unsigned
  ,`max_latency` bigint(20) unsigned
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$host_summary_by_stages`
--
CREATE TABLE IF NOT EXISTS `x$host_summary_by_stages` (
   `host` varchar(60)
  ,`event_name` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` bigint(20) unsigned
  ,`avg_latency` bigint(20) unsigned
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$host_summary_by_statement_latency`
--
CREATE TABLE IF NOT EXISTS `x$host_summary_by_statement_latency` (
   `host` varchar(60)
  ,`total` decimal(42,0)
  ,`total_latency` decimal(42,0)
  ,`max_latency` bigint(20) unsigned
  ,`lock_latency` decimal(42,0)
  ,`rows_sent` decimal(42,0)
  ,`rows_examined` decimal(42,0)
  ,`rows_affected` decimal(42,0)
  ,`full_scans` decimal(43,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$host_summary_by_statement_type`
--
CREATE TABLE IF NOT EXISTS `x$host_summary_by_statement_type` (
   `host` varchar(60)
  ,`statement` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` bigint(20) unsigned
  ,`max_latency` bigint(20) unsigned
  ,`lock_latency` bigint(20) unsigned
  ,`rows_sent` bigint(20) unsigned
  ,`rows_examined` bigint(20) unsigned
  ,`rows_affected` bigint(20) unsigned
  ,`full_scans` bigint(21) unsigned
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$innodb_buffer_stats_by_schema`
--
CREATE TABLE IF NOT EXISTS `x$innodb_buffer_stats_by_schema` (
   `object_schema` text
  ,`allocated` decimal(43,0)
  ,`data` decimal(43,0)
  ,`pages` bigint(21)
  ,`pages_hashed` bigint(21)
  ,`pages_old` bigint(21)
  ,`rows_cached` decimal(44,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$innodb_buffer_stats_by_table`
--
CREATE TABLE IF NOT EXISTS `x$innodb_buffer_stats_by_table` (
   `object_schema` text
  ,`object_name` text
  ,`allocated` decimal(43,0)
  ,`data` decimal(43,0)
  ,`pages` bigint(21)
  ,`pages_hashed` bigint(21)
  ,`pages_old` bigint(21)
  ,`rows_cached` decimal(44,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$innodb_lock_waits`
--
CREATE TABLE IF NOT EXISTS `x$innodb_lock_waits` (
   `wait_started` datetime
  ,`wait_age` time
  ,`wait_age_secs` bigint(21)
  ,`locked_table` varchar(1024)
  ,`locked_index` varchar(1024)
  ,`locked_type` varchar(32)
  ,`waiting_trx_id` varchar(18)
  ,`waiting_trx_started` datetime
  ,`waiting_trx_age` time
  ,`waiting_trx_rows_locked` bigint(21) unsigned
  ,`waiting_trx_rows_modified` bigint(21) unsigned
  ,`waiting_pid` bigint(21) unsigned
  ,`waiting_query` varchar(1024)
  ,`waiting_lock_id` varchar(81)
  ,`waiting_lock_mode` varchar(32)
  ,`blocking_trx_id` varchar(18)
  ,`blocking_pid` bigint(21) unsigned
  ,`blocking_query` varchar(1024)
  ,`blocking_lock_id` varchar(81)
  ,`blocking_lock_mode` varchar(32)
  ,`blocking_trx_started` datetime
  ,`blocking_trx_age` time
  ,`blocking_trx_rows_locked` bigint(21) unsigned
  ,`blocking_trx_rows_modified` bigint(21) unsigned
  ,`sql_kill_blocking_query` varchar(32)
  ,`sql_kill_blocking_connection` varchar(26)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$io_by_thread_by_latency`
--
CREATE TABLE IF NOT EXISTS `x$io_by_thread_by_latency` (
   `user` varchar(128)
  ,`total` decimal(42,0)
  ,`total_latency` decimal(42,0)
  ,`min_latency` bigint(20) unsigned
  ,`avg_latency` decimal(24,4)
  ,`max_latency` bigint(20) unsigned
  ,`thread_id` bigint(20) unsigned
  ,`processlist_id` bigint(20) unsigned
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$io_global_by_file_by_bytes`
--
CREATE TABLE IF NOT EXISTS `x$io_global_by_file_by_bytes` (
   `file` varchar(512)
  ,`count_read` bigint(20) unsigned
  ,`total_read` bigint(20)
  ,`avg_read` decimal(23,4)
  ,`count_write` bigint(20) unsigned
  ,`total_written` bigint(20)
  ,`avg_write` decimal(23,4)
  ,`total` bigint(21)
  ,`write_pct` decimal(26,2)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$io_global_by_file_by_latency`
--
CREATE TABLE IF NOT EXISTS `x$io_global_by_file_by_latency` (
   `file` varchar(512)
  ,`total` bigint(20) unsigned
  ,`total_latency` bigint(20) unsigned
  ,`count_read` bigint(20) unsigned
  ,`read_latency` bigint(20) unsigned
  ,`count_write` bigint(20) unsigned
  ,`write_latency` bigint(20) unsigned
  ,`count_misc` bigint(20) unsigned
  ,`misc_latency` bigint(20) unsigned
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$io_global_by_wait_by_bytes`
--
CREATE TABLE IF NOT EXISTS `x$io_global_by_wait_by_bytes` (
   `event_name` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` bigint(20) unsigned
  ,`min_latency` bigint(20) unsigned
  ,`avg_latency` bigint(20) unsigned
  ,`max_latency` bigint(20) unsigned
  ,`count_read` bigint(20) unsigned
  ,`total_read` bigint(20)
  ,`avg_read` decimal(23,4)
  ,`count_write` bigint(20) unsigned
  ,`total_written` bigint(20)
  ,`avg_written` decimal(23,4)
  ,`total_requested` bigint(21)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$io_global_by_wait_by_latency`
--
CREATE TABLE IF NOT EXISTS `x$io_global_by_wait_by_latency` (
   `event_name` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` bigint(20) unsigned
  ,`avg_latency` bigint(20) unsigned
  ,`max_latency` bigint(20) unsigned
  ,`read_latency` bigint(20) unsigned
  ,`write_latency` bigint(20) unsigned
  ,`misc_latency` bigint(20) unsigned
  ,`count_read` bigint(20) unsigned
  ,`total_read` bigint(20)
  ,`avg_read` decimal(23,4)
  ,`count_write` bigint(20) unsigned
  ,`total_written` bigint(20)
  ,`avg_written` decimal(23,4)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$latest_file_io`
--
CREATE TABLE IF NOT EXISTS `x$latest_file_io` (
   `thread` varchar(149)
  ,`file` varchar(512)
  ,`latency` bigint(20) unsigned
  ,`operation` varchar(32)
  ,`requested` bigint(20)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$memory_by_host_by_current_bytes`
--
CREATE TABLE IF NOT EXISTS `x$memory_by_host_by_current_bytes` (
   `host` varchar(60)
  ,`current_count_used` decimal(41,0)
  ,`current_allocated` decimal(41,0)
  ,`current_avg_alloc` decimal(45,4)
  ,`current_max_alloc` bigint(20)
  ,`total_allocated` decimal(42,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$memory_by_thread_by_current_bytes`
--
CREATE TABLE IF NOT EXISTS `x$memory_by_thread_by_current_bytes` (
   `thread_id` bigint(20) unsigned
  ,`user` varchar(128)
  ,`current_count_used` decimal(41,0)
  ,`current_allocated` decimal(41,0)
  ,`current_avg_alloc` decimal(45,4)
  ,`current_max_alloc` bigint(20)
  ,`total_allocated` decimal(42,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$memory_by_user_by_current_bytes`
--
CREATE TABLE IF NOT EXISTS `x$memory_by_user_by_current_bytes` (
   `user` varchar(32)
  ,`current_count_used` decimal(41,0)
  ,`current_allocated` decimal(41,0)
  ,`current_avg_alloc` decimal(45,4)
  ,`current_max_alloc` bigint(20)
  ,`total_allocated` decimal(42,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$memory_global_by_current_bytes`
--
CREATE TABLE IF NOT EXISTS `x$memory_global_by_current_bytes` (
   `event_name` varchar(128)
  ,`current_count` bigint(20)
  ,`current_alloc` bigint(20)
  ,`current_avg_alloc` decimal(23,4)
  ,`high_count` bigint(20)
  ,`high_alloc` bigint(20)
  ,`high_avg_alloc` decimal(23,4)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$memory_global_total`
--
CREATE TABLE IF NOT EXISTS `x$memory_global_total` (
  `total_allocated` decimal(41,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$processlist`
--
CREATE TABLE IF NOT EXISTS `x$processlist` (
   `thd_id` bigint(20) unsigned
  ,`conn_id` bigint(20) unsigned
  ,`user` varchar(128)
  ,`db` varchar(64)
  ,`command` varchar(16)
  ,`state` varchar(64)
  ,`time` bigint(20)
  ,`current_statement` longtext
  ,`statement_latency` bigint(20) unsigned
  ,`progress` decimal(26,2)
  ,`lock_latency` bigint(20) unsigned
  ,`rows_examined` bigint(20) unsigned
  ,`rows_sent` bigint(20) unsigned
  ,`rows_affected` bigint(20) unsigned
  ,`tmp_tables` bigint(20) unsigned
  ,`tmp_disk_tables` bigint(20) unsigned
  ,`full_scan` varchar(3)
  ,`last_statement` longtext
  ,`last_statement_latency` bigint(20) unsigned
  ,`current_memory` decimal(41,0)
  ,`last_wait` varchar(128)
  ,`last_wait_latency` varchar(20)
  ,`source` varchar(64)
  ,`trx_latency` bigint(20) unsigned
  ,`trx_state` enum('ACTIVE','COMMITTED','ROLLED BACK')
  ,`trx_autocommit` enum('YES','NO')
  ,`pid` varchar(1024)
  ,`program_name` varchar(1024)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$ps_digest_95th_percentile_by_avg_us`
--
CREATE TABLE IF NOT EXISTS `x$ps_digest_95th_percentile_by_avg_us` (
   `avg_us` decimal(21,0)
  ,`percentile` decimal(46,4)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$ps_digest_avg_latency_distribution`
--
CREATE TABLE IF NOT EXISTS `x$ps_digest_avg_latency_distribution` (
   `cnt` bigint(21)
  ,`avg_us` decimal(21,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$ps_schema_table_statistics_io`
--
CREATE TABLE IF NOT EXISTS `x$ps_schema_table_statistics_io` (
   `table_schema` varchar(64)
  ,`table_name` varchar(64)
  ,`count_read` decimal(42,0)
  ,`sum_number_of_bytes_read` decimal(41,0)
  ,`sum_timer_read` decimal(42,0)
  ,`count_write` decimal(42,0)
  ,`sum_number_of_bytes_write` decimal(41,0)
  ,`sum_timer_write` decimal(42,0)
  ,`count_misc` decimal(42,0)
  ,`sum_timer_misc` decimal(42,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$schema_flattened_keys`
--
CREATE TABLE IF NOT EXISTS `x$schema_flattened_keys` (
   `table_schema` varchar(64)
  ,`table_name` varchar(64)
  ,`index_name` varchar(64)
  ,`non_unique` bigint(1)
  ,`subpart_exists` bigint(1)
  ,`index_columns` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$schema_index_statistics`
--
CREATE TABLE IF NOT EXISTS `x$schema_index_statistics` (
   `table_schema` varchar(64)
  ,`table_name` varchar(64)
  ,`index_name` varchar(64)
  ,`rows_selected` bigint(20) unsigned
  ,`select_latency` bigint(20) unsigned
  ,`rows_inserted` bigint(20) unsigned
  ,`insert_latency` bigint(20) unsigned
  ,`rows_updated` bigint(20) unsigned
  ,`update_latency` bigint(20) unsigned
  ,`rows_deleted` bigint(20) unsigned
  ,`delete_latency` bigint(20) unsigned
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$schema_table_lock_waits`
--
CREATE TABLE IF NOT EXISTS `x$schema_table_lock_waits` (
   `object_schema` varchar(64)
  ,`object_name` varchar(64)
  ,`waiting_thread_id` bigint(20) unsigned
  ,`waiting_pid` bigint(20) unsigned
  ,`waiting_account` text
  ,`waiting_lock_type` varchar(32)
  ,`waiting_lock_duration` varchar(32)
  ,`waiting_query` longtext
  ,`waiting_query_secs` bigint(20)
  ,`waiting_query_rows_affected` bigint(20) unsigned
  ,`waiting_query_rows_examined` bigint(20) unsigned
  ,`blocking_thread_id` bigint(20) unsigned
  ,`blocking_pid` bigint(20) unsigned
  ,`blocking_account` text
  ,`blocking_lock_type` varchar(32)
  ,`blocking_lock_duration` varchar(32)
  ,`sql_kill_blocking_query` varchar(31)
  ,`sql_kill_blocking_connection` varchar(25)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$schema_table_statistics`
--
CREATE TABLE IF NOT EXISTS `x$schema_table_statistics` (
   `table_schema` varchar(64)
  ,`table_name` varchar(64)
  ,`total_latency` bigint(20) unsigned
  ,`rows_fetched` bigint(20) unsigned
  ,`fetch_latency` bigint(20) unsigned
  ,`rows_inserted` bigint(20) unsigned
  ,`insert_latency` bigint(20) unsigned
  ,`rows_updated` bigint(20) unsigned
  ,`update_latency` bigint(20) unsigned
  ,`rows_deleted` bigint(20) unsigned
  ,`delete_latency` bigint(20) unsigned
  ,`io_read_requests` decimal(42,0)
  ,`io_read` decimal(41,0)
  ,`io_read_latency` decimal(42,0)
  ,`io_write_requests` decimal(42,0)
  ,`io_write` decimal(41,0)
  ,`io_write_latency` decimal(42,0)
  ,`io_misc_requests` decimal(42,0)
  ,`io_misc_latency` decimal(42,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$schema_table_statistics_with_buffer`
--
CREATE TABLE IF NOT EXISTS `x$schema_table_statistics_with_buffer` (
   `table_schema` varchar(64)
  ,`table_name` varchar(64)
  ,`rows_fetched` bigint(20) unsigned
  ,`fetch_latency` bigint(20) unsigned
  ,`rows_inserted` bigint(20) unsigned
  ,`insert_latency` bigint(20) unsigned
  ,`rows_updated` bigint(20) unsigned
  ,`update_latency` bigint(20) unsigned
  ,`rows_deleted` bigint(20) unsigned
  ,`delete_latency` bigint(20) unsigned
  ,`io_read_requests` decimal(42,0)
  ,`io_read` decimal(41,0)
  ,`io_read_latency` decimal(42,0)
  ,`io_write_requests` decimal(42,0)
  ,`io_write` decimal(41,0)
  ,`io_write_latency` decimal(42,0)
  ,`io_misc_requests` decimal(42,0)
  ,`io_misc_latency` decimal(42,0)
  ,`innodb_buffer_allocated` decimal(43,0)
  ,`innodb_buffer_data` decimal(43,0)
  ,`innodb_buffer_free` decimal(44,0)
  ,`innodb_buffer_pages` bigint(21)
  ,`innodb_buffer_pages_hashed` bigint(21)
  ,`innodb_buffer_pages_old` bigint(21)
  ,`innodb_buffer_rows_cached` decimal(44,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$schema_tables_with_full_table_scans`
--
CREATE TABLE IF NOT EXISTS `x$schema_tables_with_full_table_scans` (
   `object_schema` varchar(64)
  ,`object_name` varchar(64)
  ,`rows_full_scanned` bigint(20) unsigned
  ,`latency` bigint(20) unsigned
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$session`
--
CREATE TABLE IF NOT EXISTS `x$session` (
   `thd_id` bigint(20) unsigned
  ,`conn_id` bigint(20) unsigned
  ,`user` varchar(128)
  ,`db` varchar(64)
  ,`command` varchar(16)
  ,`state` varchar(64)
  ,`time` bigint(20)
  ,`current_statement` longtext
  ,`statement_latency` bigint(20) unsigned
  ,`progress` decimal(26,2)
  ,`lock_latency` bigint(20) unsigned
  ,`rows_examined` bigint(20) unsigned
  ,`rows_sent` bigint(20) unsigned
  ,`rows_affected` bigint(20) unsigned
  ,`tmp_tables` bigint(20) unsigned
  ,`tmp_disk_tables` bigint(20) unsigned
  ,`full_scan` varchar(3)
  ,`last_statement` longtext
  ,`last_statement_latency` bigint(20) unsigned
  ,`current_memory` decimal(41,0)
  ,`last_wait` varchar(128)
  ,`last_wait_latency` varchar(20)
  ,`source` varchar(64)
  ,`trx_latency` bigint(20) unsigned
  ,`trx_state` enum('ACTIVE','COMMITTED','ROLLED BACK')
  ,`trx_autocommit` enum('YES','NO')
  ,`pid` varchar(1024)
  ,`program_name` varchar(1024)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$statement_analysis`
--
CREATE TABLE IF NOT EXISTS `x$statement_analysis` (
   `query` longtext
  ,`db` varchar(64)
  ,`full_scan` varchar(1)
  ,`exec_count` bigint(20) unsigned
  ,`err_count` bigint(20) unsigned
  ,`warn_count` bigint(20) unsigned
  ,`total_latency` bigint(20) unsigned
  ,`max_latency` bigint(20) unsigned
  ,`avg_latency` bigint(20) unsigned
  ,`lock_latency` bigint(20) unsigned
  ,`rows_sent` bigint(20) unsigned
  ,`rows_sent_avg` decimal(21,0)
  ,`rows_examined` bigint(20) unsigned
  ,`rows_examined_avg` decimal(21,0)
  ,`rows_affected` bigint(20) unsigned
  ,`rows_affected_avg` decimal(21,0)
  ,`tmp_tables` bigint(20) unsigned
  ,`tmp_disk_tables` bigint(20) unsigned
  ,`rows_sorted` bigint(20) unsigned
  ,`sort_merge_passes` bigint(20) unsigned
  ,`digest` varchar(32)
  ,`first_seen` timestamp
  ,`last_seen` timestamp
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$statements_with_errors_or_warnings`
--
CREATE TABLE IF NOT EXISTS `x$statements_with_errors_or_warnings` (
   `query` longtext
  ,`db` varchar(64)
  ,`exec_count` bigint(20) unsigned
  ,`errors` bigint(20) unsigned
  ,`error_pct` decimal(27,4)
  ,`warnings` bigint(20) unsigned
  ,`warning_pct` decimal(27,4)
  ,`first_seen` timestamp
  ,`last_seen` timestamp
  ,`digest` varchar(32)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$statements_with_full_table_scans`
--
CREATE TABLE IF NOT EXISTS `x$statements_with_full_table_scans` (
   `query` longtext
  ,`db` varchar(64)
  ,`exec_count` bigint(20) unsigned
  ,`total_latency` bigint(20) unsigned
  ,`no_index_used_count` bigint(20) unsigned
  ,`no_good_index_used_count` bigint(20) unsigned
  ,`no_index_used_pct` decimal(24,0)
  ,`rows_sent` bigint(20) unsigned
  ,`rows_examined` bigint(20) unsigned
  ,`rows_sent_avg` decimal(21,0) unsigned
  ,`rows_examined_avg` decimal(21,0) unsigned
  ,`first_seen` timestamp
  ,`last_seen` timestamp
  ,`digest` varchar(32)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$statements_with_runtimes_in_95th_percentile`
--
CREATE TABLE IF NOT EXISTS `x$statements_with_runtimes_in_95th_percentile` (
   `query` longtext
  ,`db` varchar(64)
  ,`full_scan` varchar(1)
  ,`exec_count` bigint(20) unsigned
  ,`err_count` bigint(20) unsigned
  ,`warn_count` bigint(20) unsigned
  ,`total_latency` bigint(20) unsigned
  ,`max_latency` bigint(20) unsigned
  ,`avg_latency` bigint(20) unsigned
  ,`rows_sent` bigint(20) unsigned
  ,`rows_sent_avg` decimal(21,0)
  ,`rows_examined` bigint(20) unsigned
  ,`rows_examined_avg` decimal(21,0)
  ,`first_seen` timestamp
  ,`last_seen` timestamp
  ,`digest` varchar(32)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$statements_with_sorting`
--
CREATE TABLE IF NOT EXISTS `x$statements_with_sorting` (
   `query` longtext
  ,`db` varchar(64)
  ,`exec_count` bigint(20) unsigned
  ,`total_latency` bigint(20) unsigned
  ,`sort_merge_passes` bigint(20) unsigned
  ,`avg_sort_merges` decimal(21,0)
  ,`sorts_using_scans` bigint(20) unsigned
  ,`sort_using_range` bigint(20) unsigned
  ,`rows_sorted` bigint(20) unsigned
  ,`avg_rows_sorted` decimal(21,0)
  ,`first_seen` timestamp
  ,`last_seen` timestamp
  ,`digest` varchar(32)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$statements_with_temp_tables`
--
CREATE TABLE IF NOT EXISTS `x$statements_with_temp_tables` (
   `query` longtext
  ,`db` varchar(64)
  ,`exec_count` bigint(20) unsigned
  ,`total_latency` bigint(20) unsigned
  ,`memory_tmp_tables` bigint(20) unsigned
  ,`disk_tmp_tables` bigint(20) unsigned
  ,`avg_tmp_tables_per_query` decimal(21,0)
  ,`tmp_tables_to_disk_pct` decimal(24,0)
  ,`first_seen` timestamp
  ,`last_seen` timestamp
  ,`digest` varchar(32)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$user_summary`
--
CREATE TABLE IF NOT EXISTS `x$user_summary` (
   `user` varchar(32)
  ,`statements` decimal(64,0)
  ,`statement_latency` decimal(64,0)
  ,`statement_avg_latency` decimal(65,4)
  ,`table_scans` decimal(65,0)
  ,`file_ios` decimal(64,0)
  ,`file_io_latency` decimal(64,0)
  ,`current_connections` decimal(41,0)
  ,`total_connections` decimal(41,0)
  ,`unique_hosts` bigint(21)
  ,`current_memory` decimal(63,0)
  ,`total_memory_allocated` decimal(64,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$user_summary_by_file_io`
--
CREATE TABLE IF NOT EXISTS `x$user_summary_by_file_io` (
   `user` varchar(32)
  ,`ios` decimal(42,0)
  ,`io_latency` decimal(42,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$user_summary_by_file_io_type`
--
CREATE TABLE IF NOT EXISTS `x$user_summary_by_file_io_type` (
   `user` varchar(32)
  ,`event_name` varchar(128)
  ,`total` bigint(20) unsigned
  ,`latency` bigint(20) unsigned
  ,`max_latency` bigint(20) unsigned
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$user_summary_by_stages`
--
CREATE TABLE IF NOT EXISTS `x$user_summary_by_stages` (
   `user` varchar(32)
  ,`event_name` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` bigint(20) unsigned
  ,`avg_latency` bigint(20) unsigned
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$user_summary_by_statement_latency`
--
CREATE TABLE IF NOT EXISTS `x$user_summary_by_statement_latency` (
   `user` varchar(32)
  ,`total` decimal(42,0)
  ,`total_latency` decimal(42,0)
  ,`max_latency` decimal(42,0)
  ,`lock_latency` decimal(42,0)
  ,`rows_sent` decimal(42,0)
  ,`rows_examined` decimal(42,0)
  ,`rows_affected` decimal(42,0)
  ,`full_scans` decimal(43,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$user_summary_by_statement_type`
--
CREATE TABLE IF NOT EXISTS `x$user_summary_by_statement_type` (
   `user` varchar(32)
  ,`statement` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` bigint(20) unsigned
  ,`max_latency` bigint(20) unsigned
  ,`lock_latency` bigint(20) unsigned
  ,`rows_sent` bigint(20) unsigned
  ,`rows_examined` bigint(20) unsigned
  ,`rows_affected` bigint(20) unsigned
  ,`full_scans` bigint(21) unsigned
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$wait_classes_global_by_avg_latency`
--
CREATE TABLE IF NOT EXISTS `x$wait_classes_global_by_avg_latency` (
   `event_class` varchar(128)
  ,`total` decimal(42,0)
  ,`total_latency` decimal(42,0)
  ,`min_latency` bigint(20) unsigned
  ,`avg_latency` decimal(46,4)
  ,`max_latency` bigint(20) unsigned
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$wait_classes_global_by_latency`
--
CREATE TABLE IF NOT EXISTS `x$wait_classes_global_by_latency` (
   `event_class` varchar(128)
  ,`total` decimal(42,0)
  ,`total_latency` decimal(42,0)
  ,`min_latency` bigint(20) unsigned
  ,`avg_latency` decimal(46,4)
  ,`max_latency` bigint(20) unsigned
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$waits_by_host_by_latency`
--
CREATE TABLE IF NOT EXISTS `x$waits_by_host_by_latency` (
   `host` varchar(60)
  ,`event` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` bigint(20) unsigned
  ,`avg_latency` bigint(20) unsigned
  ,`max_latency` bigint(20) unsigned
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$waits_by_user_by_latency`
--
CREATE TABLE IF NOT EXISTS `x$waits_by_user_by_latency` (
   `user` varchar(32)
  ,`event` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` bigint(20) unsigned
  ,`avg_latency` bigint(20) unsigned
  ,`max_latency` bigint(20) unsigned
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `x$waits_global_by_latency`
--
CREATE TABLE IF NOT EXISTS `x$waits_global_by_latency` (
   `events` varchar(128)
  ,`total` bigint(20) unsigned
  ,`total_latency` bigint(20) unsigned
  ,`avg_latency` bigint(20) unsigned
  ,`max_latency` bigint(20) unsigned
);
-- --------------------------------------------------------

--
-- Structure for view `host_summary`
--
DROP TABLE IF EXISTS `host_summary`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `host_summary` AS select if(isnull(`performance_schema`.`accounts`.`HOST`),'background',`performance_schema`.`accounts`.`HOST`) AS `host`,sum(`stmt`.`total`) AS `statements`,`format_time`(sum(`stmt`.`total_latency`)) AS `statement_latency`,`format_time`(ifnull((sum(`stmt`.`total_latency`) / nullif(sum(`stmt`.`total`),0)),0)) AS `statement_avg_latency`,sum(`stmt`.`full_scans`) AS `table_scans`,sum(`io`.`ios`) AS `file_ios`,`format_time`(sum(`io`.`io_latency`)) AS `file_io_latency`,sum(`performance_schema`.`accounts`.`CURRENT_CONNECTIONS`) AS `current_connections`,sum(`performance_schema`.`accounts`.`TOTAL_CONNECTIONS`) AS `total_connections`,count(distinct `performance_schema`.`accounts`.`USER`) AS `unique_users`,`format_bytes`(sum(`mem`.`current_allocated`)) AS `current_memory`,`format_bytes`(sum(`mem`.`total_allocated`)) AS `total_memory_allocated` from (((`performance_schema`.`accounts` join `x$host_summary_by_statement_latency` `stmt` on((`performance_schema`.`accounts`.`HOST` = `stmt`.`host`))) join `x$host_summary_by_file_io` `io` on((`performance_schema`.`accounts`.`HOST` = `io`.`host`))) join `x$memory_by_host_by_current_bytes` `mem` on((`performance_schema`.`accounts`.`HOST` = `mem`.`host`))) group by if(isnull(`performance_schema`.`accounts`.`HOST`),'background',`performance_schema`.`accounts`.`HOST`);

-- --------------------------------------------------------

--
-- Structure for view `host_summary_by_file_io`
--
DROP TABLE IF EXISTS `host_summary_by_file_io`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `host_summary_by_file_io` AS select if(isnull(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`) AS `host`,sum(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`COUNT_STAR`) AS `ios`,`format_time`(sum(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`SUM_TIMER_WAIT`)) AS `io_latency` from `performance_schema`.`events_waits_summary_by_host_by_event_name` where (`performance_schema`.`events_waits_summary_by_host_by_event_name`.`EVENT_NAME` like 'wait/io/file/%') group by if(isnull(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`) order by sum(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`SUM_TIMER_WAIT`) desc;

-- --------------------------------------------------------

--
-- Structure for view `host_summary_by_file_io_type`
--
DROP TABLE IF EXISTS `host_summary_by_file_io_type`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `host_summary_by_file_io_type` AS select if(isnull(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`) AS `host`,`performance_schema`.`events_waits_summary_by_host_by_event_name`.`EVENT_NAME` AS `event_name`,`performance_schema`.`events_waits_summary_by_host_by_event_name`.`COUNT_STAR` AS `total`,`format_time`(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`SUM_TIMER_WAIT`) AS `total_latency`,`format_time`(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`MAX_TIMER_WAIT`) AS `max_latency` from `performance_schema`.`events_waits_summary_by_host_by_event_name` where ((`performance_schema`.`events_waits_summary_by_host_by_event_name`.`EVENT_NAME` like 'wait/io/file%') and (`performance_schema`.`events_waits_summary_by_host_by_event_name`.`COUNT_STAR` > 0)) order by if(isnull(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`),`performance_schema`.`events_waits_summary_by_host_by_event_name`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `host_summary_by_stages`
--
DROP TABLE IF EXISTS `host_summary_by_stages`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `host_summary_by_stages` AS select if(isnull(`performance_schema`.`events_stages_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_stages_summary_by_host_by_event_name`.`HOST`) AS `host`,`performance_schema`.`events_stages_summary_by_host_by_event_name`.`EVENT_NAME` AS `event_name`,`performance_schema`.`events_stages_summary_by_host_by_event_name`.`COUNT_STAR` AS `total`,`format_time`(`performance_schema`.`events_stages_summary_by_host_by_event_name`.`SUM_TIMER_WAIT`) AS `total_latency`,`format_time`(`performance_schema`.`events_stages_summary_by_host_by_event_name`.`AVG_TIMER_WAIT`) AS `avg_latency` from `performance_schema`.`events_stages_summary_by_host_by_event_name` where (`performance_schema`.`events_stages_summary_by_host_by_event_name`.`SUM_TIMER_WAIT` <> 0) order by if(isnull(`performance_schema`.`events_stages_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_stages_summary_by_host_by_event_name`.`HOST`),`performance_schema`.`events_stages_summary_by_host_by_event_name`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `host_summary_by_statement_latency`
--
DROP TABLE IF EXISTS `host_summary_by_statement_latency`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `host_summary_by_statement_latency` AS select if(isnull(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_statements_summary_by_host_by_event_name`.`HOST`) AS `host`,sum(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`COUNT_STAR`) AS `total`,`format_time`(sum(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_TIMER_WAIT`)) AS `total_latency`,`format_time`(max(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`MAX_TIMER_WAIT`)) AS `max_latency`,`format_time`(sum(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_LOCK_TIME`)) AS `lock_latency`,sum(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_ROWS_SENT`) AS `rows_sent`,sum(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_ROWS_EXAMINED`) AS `rows_examined`,sum(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_ROWS_AFFECTED`) AS `rows_affected`,(sum(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_NO_INDEX_USED`) + sum(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_NO_GOOD_INDEX_USED`)) AS `full_scans` from `performance_schema`.`events_statements_summary_by_host_by_event_name` group by if(isnull(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_statements_summary_by_host_by_event_name`.`HOST`) order by sum(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_TIMER_WAIT`) desc;

-- --------------------------------------------------------

--
-- Structure for view `host_summary_by_statement_type`
--
DROP TABLE IF EXISTS `host_summary_by_statement_type`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `host_summary_by_statement_type` AS select if(isnull(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_statements_summary_by_host_by_event_name`.`HOST`) AS `host`,substring_index(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`EVENT_NAME`,'/',-(1)) AS `statement`,`performance_schema`.`events_statements_summary_by_host_by_event_name`.`COUNT_STAR` AS `total`,`format_time`(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_TIMER_WAIT`) AS `total_latency`,`format_time`(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`MAX_TIMER_WAIT`) AS `max_latency`,`format_time`(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_LOCK_TIME`) AS `lock_latency`,`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_ROWS_SENT` AS `rows_sent`,`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_ROWS_EXAMINED` AS `rows_examined`,`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_ROWS_AFFECTED` AS `rows_affected`,(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_NO_INDEX_USED` + `performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_NO_GOOD_INDEX_USED`) AS `full_scans` from `performance_schema`.`events_statements_summary_by_host_by_event_name` where (`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_TIMER_WAIT` <> 0) order by if(isnull(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_statements_summary_by_host_by_event_name`.`HOST`),`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `innodb_buffer_stats_by_schema`
--
DROP TABLE IF EXISTS `innodb_buffer_stats_by_schema`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `innodb_buffer_stats_by_schema` AS select if((locate('.',`ibp`.`TABLE_NAME`) = 0),'InnoDB System',replace(substring_index(`ibp`.`TABLE_NAME`,'.',1),'`','')) AS `object_schema`,`format_bytes`(sum(if((`ibp`.`COMPRESSED_SIZE` = 0),16384,`ibp`.`COMPRESSED_SIZE`))) AS `allocated`,`format_bytes`(sum(`ibp`.`DATA_SIZE`)) AS `data`,count(`ibp`.`PAGE_NUMBER`) AS `pages`,count(if((`ibp`.`IS_HASHED` = 'YES'),1,NULL)) AS `pages_hashed`,count(if((`ibp`.`IS_OLD` = 'YES'),1,NULL)) AS `pages_old`,round((sum(`ibp`.`NUMBER_RECORDS`) / count(distinct `ibp`.`INDEX_NAME`)),0) AS `rows_cached` from `information_schema`.`innodb_buffer_page` `ibp` where (`ibp`.`TABLE_NAME` is not null) group by `object_schema` order by sum(if((`ibp`.`COMPRESSED_SIZE` = 0),16384,`ibp`.`COMPRESSED_SIZE`)) desc;

-- --------------------------------------------------------

--
-- Structure for view `innodb_buffer_stats_by_table`
--
DROP TABLE IF EXISTS `innodb_buffer_stats_by_table`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `innodb_buffer_stats_by_table` AS select if((locate('.',`ibp`.`TABLE_NAME`) = 0),'InnoDB System',replace(substring_index(`ibp`.`TABLE_NAME`,'.',1),'`','')) AS `object_schema`,replace(substring_index(`ibp`.`TABLE_NAME`,'.',-(1)),'`','') AS `object_name`,`format_bytes`(sum(if((`ibp`.`COMPRESSED_SIZE` = 0),16384,`ibp`.`COMPRESSED_SIZE`))) AS `allocated`,`format_bytes`(sum(`ibp`.`DATA_SIZE`)) AS `data`,count(`ibp`.`PAGE_NUMBER`) AS `pages`,count(if((`ibp`.`IS_HASHED` = 'YES'),1,NULL)) AS `pages_hashed`,count(if((`ibp`.`IS_OLD` = 'YES'),1,NULL)) AS `pages_old`,round((sum(`ibp`.`NUMBER_RECORDS`) / count(distinct `ibp`.`INDEX_NAME`)),0) AS `rows_cached` from `information_schema`.`innodb_buffer_page` `ibp` where (`ibp`.`TABLE_NAME` is not null) group by `object_schema`,`object_name` order by sum(if((`ibp`.`COMPRESSED_SIZE` = 0),16384,`ibp`.`COMPRESSED_SIZE`)) desc;

-- --------------------------------------------------------

--
-- Structure for view `innodb_lock_waits`
--
DROP TABLE IF EXISTS `innodb_lock_waits`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `innodb_lock_waits` AS select `r`.`trx_wait_started` AS `wait_started`,timediff(now(),`r`.`trx_wait_started`) AS `wait_age`,timestampdiff(SECOND,`r`.`trx_wait_started`,now()) AS `wait_age_secs`,`rl`.`lock_table` AS `locked_table`,`rl`.`lock_index` AS `locked_index`,`rl`.`lock_type` AS `locked_type`,`r`.`trx_id` AS `waiting_trx_id`,`r`.`trx_started` AS `waiting_trx_started`,timediff(now(),`r`.`trx_started`) AS `waiting_trx_age`,`r`.`trx_rows_locked` AS `waiting_trx_rows_locked`,`r`.`trx_rows_modified` AS `waiting_trx_rows_modified`,`r`.`trx_mysql_thread_id` AS `waiting_pid`,`format_statement`(`r`.`trx_query`) AS `waiting_query`,`rl`.`lock_id` AS `waiting_lock_id`,`rl`.`lock_mode` AS `waiting_lock_mode`,`b`.`trx_id` AS `blocking_trx_id`,`b`.`trx_mysql_thread_id` AS `blocking_pid`,`format_statement`(`b`.`trx_query`) AS `blocking_query`,`bl`.`lock_id` AS `blocking_lock_id`,`bl`.`lock_mode` AS `blocking_lock_mode`,`b`.`trx_started` AS `blocking_trx_started`,timediff(now(),`b`.`trx_started`) AS `blocking_trx_age`,`b`.`trx_rows_locked` AS `blocking_trx_rows_locked`,`b`.`trx_rows_modified` AS `blocking_trx_rows_modified`,concat('KILL QUERY ',`b`.`trx_mysql_thread_id`) AS `sql_kill_blocking_query`,concat('KILL ',`b`.`trx_mysql_thread_id`) AS `sql_kill_blocking_connection` from ((((`information_schema`.`innodb_lock_waits` `w` join `information_schema`.`innodb_trx` `b` on((`b`.`trx_id` = `w`.`blocking_trx_id`))) join `information_schema`.`innodb_trx` `r` on((`r`.`trx_id` = `w`.`requesting_trx_id`))) join `information_schema`.`innodb_locks` `bl` on((`bl`.`lock_id` = `w`.`blocking_lock_id`))) join `information_schema`.`innodb_locks` `rl` on((`rl`.`lock_id` = `w`.`requested_lock_id`))) order by `r`.`trx_wait_started`;

-- --------------------------------------------------------

--
-- Structure for view `io_by_thread_by_latency`
--
DROP TABLE IF EXISTS `io_by_thread_by_latency`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `io_by_thread_by_latency` AS select if(isnull(`performance_schema`.`threads`.`PROCESSLIST_ID`),substring_index(`performance_schema`.`threads`.`NAME`,'/',-(1)),concat(`performance_schema`.`threads`.`PROCESSLIST_USER`,'@',`performance_schema`.`threads`.`PROCESSLIST_HOST`)) AS `user`,sum(`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`COUNT_STAR`) AS `total`,`format_time`(sum(`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`SUM_TIMER_WAIT`)) AS `total_latency`,`format_time`(min(`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`MIN_TIMER_WAIT`)) AS `min_latency`,`format_time`(avg(`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`AVG_TIMER_WAIT`)) AS `avg_latency`,`format_time`(max(`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`MAX_TIMER_WAIT`)) AS `max_latency`,`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`THREAD_ID` AS `thread_id`,`performance_schema`.`threads`.`PROCESSLIST_ID` AS `processlist_id` from (`performance_schema`.`events_waits_summary_by_thread_by_event_name` left join `performance_schema`.`threads` on((`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`THREAD_ID` = `performance_schema`.`threads`.`THREAD_ID`))) where ((`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`EVENT_NAME` like 'wait/io/file/%') and (`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`SUM_TIMER_WAIT` > 0)) group by `performance_schema`.`events_waits_summary_by_thread_by_event_name`.`THREAD_ID`,`performance_schema`.`threads`.`PROCESSLIST_ID`,`user` order by sum(`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`SUM_TIMER_WAIT`) desc;

-- --------------------------------------------------------

--
-- Structure for view `io_global_by_file_by_bytes`
--
DROP TABLE IF EXISTS `io_global_by_file_by_bytes`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `io_global_by_file_by_bytes` AS select `format_path`(`performance_schema`.`file_summary_by_instance`.`FILE_NAME`) AS `file`,`performance_schema`.`file_summary_by_instance`.`COUNT_READ` AS `count_read`,`format_bytes`(`performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_READ`) AS `total_read`,`format_bytes`(ifnull((`performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_READ` / nullif(`performance_schema`.`file_summary_by_instance`.`COUNT_READ`,0)),0)) AS `avg_read`,`performance_schema`.`file_summary_by_instance`.`COUNT_WRITE` AS `count_write`,`format_bytes`(`performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_WRITE`) AS `total_written`,`format_bytes`(ifnull((`performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_WRITE` / nullif(`performance_schema`.`file_summary_by_instance`.`COUNT_WRITE`,0)),0.00)) AS `avg_write`,`format_bytes`((`performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_READ` + `performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_WRITE`)) AS `total`,ifnull(round((100 - ((`performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_READ` / nullif((`performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_READ` + `performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_WRITE`),0)) * 100)),2),0.00) AS `write_pct` from `performance_schema`.`file_summary_by_instance` order by (`performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_READ` + `performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_WRITE`) desc;

-- --------------------------------------------------------

--
-- Structure for view `io_global_by_file_by_latency`
--
DROP TABLE IF EXISTS `io_global_by_file_by_latency`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `io_global_by_file_by_latency` AS select `format_path`(`performance_schema`.`file_summary_by_instance`.`FILE_NAME`) AS `file`,`performance_schema`.`file_summary_by_instance`.`COUNT_STAR` AS `total`,`format_time`(`performance_schema`.`file_summary_by_instance`.`SUM_TIMER_WAIT`) AS `total_latency`,`performance_schema`.`file_summary_by_instance`.`COUNT_READ` AS `count_read`,`format_time`(`performance_schema`.`file_summary_by_instance`.`SUM_TIMER_READ`) AS `read_latency`,`performance_schema`.`file_summary_by_instance`.`COUNT_WRITE` AS `count_write`,`format_time`(`performance_schema`.`file_summary_by_instance`.`SUM_TIMER_WRITE`) AS `write_latency`,`performance_schema`.`file_summary_by_instance`.`COUNT_MISC` AS `count_misc`,`format_time`(`performance_schema`.`file_summary_by_instance`.`SUM_TIMER_MISC`) AS `misc_latency` from `performance_schema`.`file_summary_by_instance` order by `performance_schema`.`file_summary_by_instance`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `io_global_by_wait_by_bytes`
--
DROP TABLE IF EXISTS `io_global_by_wait_by_bytes`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `io_global_by_wait_by_bytes` AS select substring_index(`performance_schema`.`file_summary_by_event_name`.`EVENT_NAME`,'/',-(2)) AS `event_name`,`performance_schema`.`file_summary_by_event_name`.`COUNT_STAR` AS `total`,`format_time`(`performance_schema`.`file_summary_by_event_name`.`SUM_TIMER_WAIT`) AS `total_latency`,`format_time`(`performance_schema`.`file_summary_by_event_name`.`MIN_TIMER_WAIT`) AS `min_latency`,`format_time`(`performance_schema`.`file_summary_by_event_name`.`AVG_TIMER_WAIT`) AS `avg_latency`,`format_time`(`performance_schema`.`file_summary_by_event_name`.`MAX_TIMER_WAIT`) AS `max_latency`,`performance_schema`.`file_summary_by_event_name`.`COUNT_READ` AS `count_read`,`format_bytes`(`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_READ`) AS `total_read`,`format_bytes`(ifnull((`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_READ` / nullif(`performance_schema`.`file_summary_by_event_name`.`COUNT_READ`,0)),0)) AS `avg_read`,`performance_schema`.`file_summary_by_event_name`.`COUNT_WRITE` AS `count_write`,`format_bytes`(`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_WRITE`) AS `total_written`,`format_bytes`(ifnull((`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_WRITE` / nullif(`performance_schema`.`file_summary_by_event_name`.`COUNT_WRITE`,0)),0)) AS `avg_written`,`format_bytes`((`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_WRITE` + `performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_READ`)) AS `total_requested` from `performance_schema`.`file_summary_by_event_name` where ((`performance_schema`.`file_summary_by_event_name`.`EVENT_NAME` like 'wait/io/file/%') and (`performance_schema`.`file_summary_by_event_name`.`COUNT_STAR` > 0)) order by (`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_WRITE` + `performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_READ`) desc;

-- --------------------------------------------------------

--
-- Structure for view `io_global_by_wait_by_latency`
--
DROP TABLE IF EXISTS `io_global_by_wait_by_latency`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `io_global_by_wait_by_latency` AS select substring_index(`performance_schema`.`file_summary_by_event_name`.`EVENT_NAME`,'/',-(2)) AS `event_name`,`performance_schema`.`file_summary_by_event_name`.`COUNT_STAR` AS `total`,`format_time`(`performance_schema`.`file_summary_by_event_name`.`SUM_TIMER_WAIT`) AS `total_latency`,`format_time`(`performance_schema`.`file_summary_by_event_name`.`AVG_TIMER_WAIT`) AS `avg_latency`,`format_time`(`performance_schema`.`file_summary_by_event_name`.`MAX_TIMER_WAIT`) AS `max_latency`,`format_time`(`performance_schema`.`file_summary_by_event_name`.`SUM_TIMER_READ`) AS `read_latency`,`format_time`(`performance_schema`.`file_summary_by_event_name`.`SUM_TIMER_WRITE`) AS `write_latency`,`format_time`(`performance_schema`.`file_summary_by_event_name`.`SUM_TIMER_MISC`) AS `misc_latency`,`performance_schema`.`file_summary_by_event_name`.`COUNT_READ` AS `count_read`,`format_bytes`(`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_READ`) AS `total_read`,`format_bytes`(ifnull((`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_READ` / nullif(`performance_schema`.`file_summary_by_event_name`.`COUNT_READ`,0)),0)) AS `avg_read`,`performance_schema`.`file_summary_by_event_name`.`COUNT_WRITE` AS `count_write`,`format_bytes`(`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_WRITE`) AS `total_written`,`format_bytes`(ifnull((`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_WRITE` / nullif(`performance_schema`.`file_summary_by_event_name`.`COUNT_WRITE`,0)),0)) AS `avg_written` from `performance_schema`.`file_summary_by_event_name` where ((`performance_schema`.`file_summary_by_event_name`.`EVENT_NAME` like 'wait/io/file/%') and (`performance_schema`.`file_summary_by_event_name`.`COUNT_STAR` > 0)) order by `performance_schema`.`file_summary_by_event_name`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `latest_file_io`
--
DROP TABLE IF EXISTS `latest_file_io`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `latest_file_io` AS select if(isnull(`information_schema`.`processlist`.`ID`),concat(substring_index(`performance_schema`.`threads`.`NAME`,'/',-(1)),':',`performance_schema`.`events_waits_history_long`.`THREAD_ID`),concat(`information_schema`.`processlist`.`USER`,'@',`information_schema`.`processlist`.`HOST`,':',`information_schema`.`processlist`.`ID`)) AS `thread`,`format_path`(`performance_schema`.`events_waits_history_long`.`OBJECT_NAME`) AS `file`,`format_time`(`performance_schema`.`events_waits_history_long`.`TIMER_WAIT`) AS `latency`,`performance_schema`.`events_waits_history_long`.`OPERATION` AS `operation`,`format_bytes`(`performance_schema`.`events_waits_history_long`.`NUMBER_OF_BYTES`) AS `requested` from ((`performance_schema`.`events_waits_history_long` join `performance_schema`.`threads` on((`performance_schema`.`events_waits_history_long`.`THREAD_ID` = `performance_schema`.`threads`.`THREAD_ID`))) left join `information_schema`.`processlist` on((`performance_schema`.`threads`.`PROCESSLIST_ID` = `information_schema`.`processlist`.`ID`))) where ((`performance_schema`.`events_waits_history_long`.`OBJECT_NAME` is not null) and (`performance_schema`.`events_waits_history_long`.`EVENT_NAME` like 'wait/io/file/%')) order by `performance_schema`.`events_waits_history_long`.`TIMER_START`;

-- --------------------------------------------------------

--
-- Structure for view `memory_by_host_by_current_bytes`
--
DROP TABLE IF EXISTS `memory_by_host_by_current_bytes`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `memory_by_host_by_current_bytes` AS select if(isnull(`performance_schema`.`memory_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`memory_summary_by_host_by_event_name`.`HOST`) AS `host`,sum(`performance_schema`.`memory_summary_by_host_by_event_name`.`CURRENT_COUNT_USED`) AS `current_count_used`,`format_bytes`(sum(`performance_schema`.`memory_summary_by_host_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`)) AS `current_allocated`,`format_bytes`(ifnull((sum(`performance_schema`.`memory_summary_by_host_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`) / nullif(sum(`performance_schema`.`memory_summary_by_host_by_event_name`.`CURRENT_COUNT_USED`),0)),0)) AS `current_avg_alloc`,`format_bytes`(max(`performance_schema`.`memory_summary_by_host_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`)) AS `current_max_alloc`,`format_bytes`(sum(`performance_schema`.`memory_summary_by_host_by_event_name`.`SUM_NUMBER_OF_BYTES_ALLOC`)) AS `total_allocated` from `performance_schema`.`memory_summary_by_host_by_event_name` group by if(isnull(`performance_schema`.`memory_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`memory_summary_by_host_by_event_name`.`HOST`) order by sum(`performance_schema`.`memory_summary_by_host_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`) desc;

-- --------------------------------------------------------

--
-- Structure for view `memory_by_thread_by_current_bytes`
--
DROP TABLE IF EXISTS `memory_by_thread_by_current_bytes`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `memory_by_thread_by_current_bytes` AS select `mt`.`THREAD_ID` AS `thread_id`,if((`t`.`NAME` = 'thread/sql/one_connection'),concat(`t`.`PROCESSLIST_USER`,'@',`t`.`PROCESSLIST_HOST`),replace(`t`.`NAME`,'thread/','')) AS `user`,sum(`mt`.`CURRENT_COUNT_USED`) AS `current_count_used`,`format_bytes`(sum(`mt`.`CURRENT_NUMBER_OF_BYTES_USED`)) AS `current_allocated`,`format_bytes`(ifnull((sum(`mt`.`CURRENT_NUMBER_OF_BYTES_USED`) / nullif(sum(`mt`.`CURRENT_COUNT_USED`),0)),0)) AS `current_avg_alloc`,`format_bytes`(max(`mt`.`CURRENT_NUMBER_OF_BYTES_USED`)) AS `current_max_alloc`,`format_bytes`(sum(`mt`.`SUM_NUMBER_OF_BYTES_ALLOC`)) AS `total_allocated` from (`performance_schema`.`memory_summary_by_thread_by_event_name` `mt` join `performance_schema`.`threads` `t` on((`mt`.`THREAD_ID` = `t`.`THREAD_ID`))) group by `mt`.`THREAD_ID`,if((`t`.`NAME` = 'thread/sql/one_connection'),concat(`t`.`PROCESSLIST_USER`,'@',`t`.`PROCESSLIST_HOST`),replace(`t`.`NAME`,'thread/','')) order by sum(`mt`.`CURRENT_NUMBER_OF_BYTES_USED`) desc;

-- --------------------------------------------------------

--
-- Structure for view `memory_by_user_by_current_bytes`
--
DROP TABLE IF EXISTS `memory_by_user_by_current_bytes`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `memory_by_user_by_current_bytes` AS select if(isnull(`performance_schema`.`memory_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`memory_summary_by_user_by_event_name`.`USER`) AS `user`,sum(`performance_schema`.`memory_summary_by_user_by_event_name`.`CURRENT_COUNT_USED`) AS `current_count_used`,`format_bytes`(sum(`performance_schema`.`memory_summary_by_user_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`)) AS `current_allocated`,`format_bytes`(ifnull((sum(`performance_schema`.`memory_summary_by_user_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`) / nullif(sum(`performance_schema`.`memory_summary_by_user_by_event_name`.`CURRENT_COUNT_USED`),0)),0)) AS `current_avg_alloc`,`format_bytes`(max(`performance_schema`.`memory_summary_by_user_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`)) AS `current_max_alloc`,`format_bytes`(sum(`performance_schema`.`memory_summary_by_user_by_event_name`.`SUM_NUMBER_OF_BYTES_ALLOC`)) AS `total_allocated` from `performance_schema`.`memory_summary_by_user_by_event_name` group by if(isnull(`performance_schema`.`memory_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`memory_summary_by_user_by_event_name`.`USER`) order by sum(`performance_schema`.`memory_summary_by_user_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`) desc;

-- --------------------------------------------------------

--
-- Structure for view `memory_global_by_current_bytes`
--
DROP TABLE IF EXISTS `memory_global_by_current_bytes`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `memory_global_by_current_bytes` AS select `performance_schema`.`memory_summary_global_by_event_name`.`EVENT_NAME` AS `event_name`,`performance_schema`.`memory_summary_global_by_event_name`.`CURRENT_COUNT_USED` AS `current_count`,`format_bytes`(`performance_schema`.`memory_summary_global_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`) AS `current_alloc`,`format_bytes`(ifnull((`performance_schema`.`memory_summary_global_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED` / nullif(`performance_schema`.`memory_summary_global_by_event_name`.`CURRENT_COUNT_USED`,0)),0)) AS `current_avg_alloc`,`performance_schema`.`memory_summary_global_by_event_name`.`HIGH_COUNT_USED` AS `high_count`,`format_bytes`(`performance_schema`.`memory_summary_global_by_event_name`.`HIGH_NUMBER_OF_BYTES_USED`) AS `high_alloc`,`format_bytes`(ifnull((`performance_schema`.`memory_summary_global_by_event_name`.`HIGH_NUMBER_OF_BYTES_USED` / nullif(`performance_schema`.`memory_summary_global_by_event_name`.`HIGH_COUNT_USED`,0)),0)) AS `high_avg_alloc` from `performance_schema`.`memory_summary_global_by_event_name` where (`performance_schema`.`memory_summary_global_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED` > 0) order by `performance_schema`.`memory_summary_global_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED` desc;

-- --------------------------------------------------------

--
-- Structure for view `memory_global_total`
--
DROP TABLE IF EXISTS `memory_global_total`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `memory_global_total` AS select `format_bytes`(sum(`performance_schema`.`memory_summary_global_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`)) AS `total_allocated` from `performance_schema`.`memory_summary_global_by_event_name`;

-- --------------------------------------------------------

--
-- Structure for view `metrics`
--
DROP TABLE IF EXISTS `metrics`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `metrics` AS (select lower(`performance_schema`.`global_status`.`VARIABLE_NAME`) AS `Variable_name`,`performance_schema`.`global_status`.`VARIABLE_VALUE` AS `Variable_value`,'Global Status' AS `Type`,'YES' AS `Enabled` from `performance_schema`.`global_status`) union all (select `information_schema`.`INNODB_METRICS`.`NAME` AS `Variable_name`,`information_schema`.`INNODB_METRICS`.`COUNT` AS `Variable_value`,concat('InnoDB Metrics - ',`information_schema`.`INNODB_METRICS`.`SUBSYSTEM`) AS `Type`,if((`information_schema`.`INNODB_METRICS`.`STATUS` = 'enabled'),'YES','NO') AS `Enabled` from `information_schema`.`INNODB_METRICS` where (`information_schema`.`INNODB_METRICS`.`NAME` not in ('lock_row_lock_time','lock_row_lock_time_avg','lock_row_lock_time_max','lock_row_lock_waits','buffer_pool_reads','buffer_pool_read_requests','buffer_pool_write_requests','buffer_pool_wait_free','buffer_pool_read_ahead','buffer_pool_read_ahead_evicted','buffer_pool_pages_total','buffer_pool_pages_misc','buffer_pool_pages_data','buffer_pool_bytes_data','buffer_pool_pages_dirty','buffer_pool_bytes_dirty','buffer_pool_pages_free','buffer_pages_created','buffer_pages_written','buffer_pages_read','buffer_data_reads','buffer_data_written','file_num_open_files','os_log_bytes_written','os_log_fsyncs','os_log_pending_fsyncs','os_log_pending_writes','log_waits','log_write_requests','log_writes','innodb_dblwr_writes','innodb_dblwr_pages_written','innodb_page_size'))) union all (select 'memory_current_allocated' AS `Variable_name`,sum(`performance_schema`.`memory_summary_global_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`) AS `Variable_value`,'Performance Schema' AS `Type`,if(((select count(0) from `performance_schema`.`setup_instruments` where ((`performance_schema`.`setup_instruments`.`NAME` like 'memory/%') and (`performance_schema`.`setup_instruments`.`ENABLED` = 'YES'))) = 0),'NO',if(((select count(0) from `performance_schema`.`setup_instruments` where ((`performance_schema`.`setup_instruments`.`NAME` like 'memory/%') and (`performance_schema`.`setup_instruments`.`ENABLED` = 'YES'))) = (select count(0) from `performance_schema`.`setup_instruments` where (`performance_schema`.`setup_instruments`.`NAME` like 'memory/%'))),'YES','PARTIAL')) AS `Enabled` from `performance_schema`.`memory_summary_global_by_event_name`) union all (select 'memory_total_allocated' AS `Variable_name`,sum(`performance_schema`.`memory_summary_global_by_event_name`.`SUM_NUMBER_OF_BYTES_ALLOC`) AS `Variable_value`,'Performance Schema' AS `Type`,if(((select count(0) from `performance_schema`.`setup_instruments` where ((`performance_schema`.`setup_instruments`.`NAME` like 'memory/%') and (`performance_schema`.`setup_instruments`.`ENABLED` = 'YES'))) = 0),'NO',if(((select count(0) from `performance_schema`.`setup_instruments` where ((`performance_schema`.`setup_instruments`.`NAME` like 'memory/%') and (`performance_schema`.`setup_instruments`.`ENABLED` = 'YES'))) = (select count(0) from `performance_schema`.`setup_instruments` where (`performance_schema`.`setup_instruments`.`NAME` like 'memory/%'))),'YES','PARTIAL')) AS `Enabled` from `performance_schema`.`memory_summary_global_by_event_name`) union all (select 'NOW()' AS `Variable_name`,now(3) AS `Variable_value`,'System Time' AS `Type`,'YES' AS `Enabled`) union all (select 'UNIX_TIMESTAMP()' AS `Variable_name`,round(unix_timestamp(now(3)),3) AS `Variable_value`,'System Time' AS `Type`,'YES' AS `Enabled`) order by `Type`,`Variable_name`;

-- --------------------------------------------------------

--
-- Structure for view `processlist`
--
DROP TABLE IF EXISTS `processlist`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `processlist` AS select `pps`.`THREAD_ID` AS `thd_id`,`pps`.`PROCESSLIST_ID` AS `conn_id`,if((`pps`.`NAME` = 'thread/sql/one_connection'),concat(`pps`.`PROCESSLIST_USER`,'@',`pps`.`PROCESSLIST_HOST`),replace(`pps`.`NAME`,'thread/','')) AS `user`,`pps`.`PROCESSLIST_DB` AS `db`,`pps`.`PROCESSLIST_COMMAND` AS `command`,`pps`.`PROCESSLIST_STATE` AS `state`,`pps`.`PROCESSLIST_TIME` AS `time`,`format_statement`(`pps`.`PROCESSLIST_INFO`) AS `current_statement`,if(isnull(`esc`.`END_EVENT_ID`),`format_time`(`esc`.`TIMER_WAIT`),NULL) AS `statement_latency`,if(isnull(`esc`.`END_EVENT_ID`),round((100 * (`estc`.`WORK_COMPLETED` / `estc`.`WORK_ESTIMATED`)),2),NULL) AS `progress`,`format_time`(`esc`.`LOCK_TIME`) AS `lock_latency`,`esc`.`ROWS_EXAMINED` AS `rows_examined`,`esc`.`ROWS_SENT` AS `rows_sent`,`esc`.`ROWS_AFFECTED` AS `rows_affected`,`esc`.`CREATED_TMP_TABLES` AS `tmp_tables`,`esc`.`CREATED_TMP_DISK_TABLES` AS `tmp_disk_tables`,if(((`esc`.`NO_GOOD_INDEX_USED` > 0) or (`esc`.`NO_INDEX_USED` > 0)),'YES','NO') AS `full_scan`,if((`esc`.`END_EVENT_ID` is not null),`format_statement`(`esc`.`SQL_TEXT`),NULL) AS `last_statement`,if((`esc`.`END_EVENT_ID` is not null),`format_time`(`esc`.`TIMER_WAIT`),NULL) AS `last_statement_latency`,`format_bytes`(`mem`.`current_allocated`) AS `current_memory`,`ewc`.`EVENT_NAME` AS `last_wait`,if((isnull(`ewc`.`END_EVENT_ID`) and (`ewc`.`EVENT_NAME` is not null)),'Still Waiting',`format_time`(`ewc`.`TIMER_WAIT`)) AS `last_wait_latency`,`ewc`.`SOURCE` AS `source`,`format_time`(`etc`.`TIMER_WAIT`) AS `trx_latency`,`etc`.`STATE` AS `trx_state`,`etc`.`AUTOCOMMIT` AS `trx_autocommit`,`conattr_pid`.`ATTR_VALUE` AS `pid`,`conattr_progname`.`ATTR_VALUE` AS `program_name` from (((((((`performance_schema`.`threads` `pps` left join `performance_schema`.`events_waits_current` `ewc` on((`pps`.`THREAD_ID` = `ewc`.`THREAD_ID`))) left join `performance_schema`.`events_stages_current` `estc` on((`pps`.`THREAD_ID` = `estc`.`THREAD_ID`))) left join `performance_schema`.`events_statements_current` `esc` on((`pps`.`THREAD_ID` = `esc`.`THREAD_ID`))) left join `performance_schema`.`events_transactions_current` `etc` on((`pps`.`THREAD_ID` = `etc`.`THREAD_ID`))) left join `x$memory_by_thread_by_current_bytes` `mem` on((`pps`.`THREAD_ID` = `mem`.`thread_id`))) left join `performance_schema`.`session_connect_attrs` `conattr_pid` on(((`conattr_pid`.`PROCESSLIST_ID` = `pps`.`PROCESSLIST_ID`) and (`conattr_pid`.`ATTR_NAME` = '_pid')))) left join `performance_schema`.`session_connect_attrs` `conattr_progname` on(((`conattr_progname`.`PROCESSLIST_ID` = `pps`.`PROCESSLIST_ID`) and (`conattr_progname`.`ATTR_NAME` = 'program_name')))) order by `pps`.`PROCESSLIST_TIME` desc,`last_wait_latency` desc;

-- --------------------------------------------------------

--
-- Structure for view `ps_check_lost_instrumentation`
--
DROP TABLE IF EXISTS `ps_check_lost_instrumentation`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `ps_check_lost_instrumentation` AS select `performance_schema`.`global_status`.`VARIABLE_NAME` AS `variable_name`,`performance_schema`.`global_status`.`VARIABLE_VALUE` AS `variable_value` from `performance_schema`.`global_status` where ((`performance_schema`.`global_status`.`VARIABLE_NAME` like 'perf%lost') and (`performance_schema`.`global_status`.`VARIABLE_VALUE` > 0));

-- --------------------------------------------------------

--
-- Structure for view `schema_auto_increment_columns`
--
DROP TABLE IF EXISTS `schema_auto_increment_columns`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `schema_auto_increment_columns` AS select `information_schema`.`COLUMNS`.`TABLE_SCHEMA` AS `table_schema`,`information_schema`.`COLUMNS`.`TABLE_NAME` AS `table_name`,`information_schema`.`COLUMNS`.`COLUMN_NAME` AS `column_name`,`information_schema`.`COLUMNS`.`DATA_TYPE` AS `data_type`,`information_schema`.`COLUMNS`.`COLUMN_TYPE` AS `column_type`,(locate('unsigned',`information_schema`.`COLUMNS`.`COLUMN_TYPE`) = 0) AS `is_signed`,(locate('unsigned',`information_schema`.`COLUMNS`.`COLUMN_TYPE`) > 0) AS `is_unsigned`,((case `information_schema`.`COLUMNS`.`DATA_TYPE` when 'tinyint' then 255 when 'smallint' then 65535 when 'mediumint' then 16777215 when 'int' then 4294967295 when 'bigint' then 18446744073709551615 end) >> if((locate('unsigned',`information_schema`.`COLUMNS`.`COLUMN_TYPE`) > 0),0,1)) AS `max_value`,`information_schema`.`TABLES`.`AUTO_INCREMENT` AS `auto_increment`,(`information_schema`.`TABLES`.`AUTO_INCREMENT` / ((case `information_schema`.`COLUMNS`.`DATA_TYPE` when 'tinyint' then 255 when 'smallint' then 65535 when 'mediumint' then 16777215 when 'int' then 4294967295 when 'bigint' then 18446744073709551615 end) >> if((locate('unsigned',`information_schema`.`COLUMNS`.`COLUMN_TYPE`) > 0),0,1))) AS `auto_increment_ratio` from (`INFORMATION_SCHEMA`.`COLUMNS` join `INFORMATION_SCHEMA`.`TABLES` on(((`information_schema`.`COLUMNS`.`TABLE_SCHEMA` = `information_schema`.`TABLES`.`TABLE_SCHEMA`) and (`information_schema`.`COLUMNS`.`TABLE_NAME` = `information_schema`.`TABLES`.`TABLE_NAME`)))) where ((`information_schema`.`COLUMNS`.`TABLE_SCHEMA` not in ('mysql','sys','INFORMATION_SCHEMA','performance_schema')) and (`information_schema`.`TABLES`.`TABLE_TYPE` = 'BASE TABLE') and (`information_schema`.`COLUMNS`.`EXTRA` = 'auto_increment')) order by (`information_schema`.`TABLES`.`AUTO_INCREMENT` / ((case `information_schema`.`COLUMNS`.`DATA_TYPE` when 'tinyint' then 255 when 'smallint' then 65535 when 'mediumint' then 16777215 when 'int' then 4294967295 when 'bigint' then 18446744073709551615 end) >> if((locate('unsigned',`information_schema`.`COLUMNS`.`COLUMN_TYPE`) > 0),0,1))) desc,((case `information_schema`.`COLUMNS`.`DATA_TYPE` when 'tinyint' then 255 when 'smallint' then 65535 when 'mediumint' then 16777215 when 'int' then 4294967295 when 'bigint' then 18446744073709551615 end) >> if((locate('unsigned',`information_schema`.`COLUMNS`.`COLUMN_TYPE`) > 0),0,1));

-- --------------------------------------------------------

--
-- Structure for view `schema_index_statistics`
--
DROP TABLE IF EXISTS `schema_index_statistics`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `schema_index_statistics` AS select `performance_schema`.`table_io_waits_summary_by_index_usage`.`OBJECT_SCHEMA` AS `table_schema`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`OBJECT_NAME` AS `table_name`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`INDEX_NAME` AS `index_name`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`COUNT_FETCH` AS `rows_selected`,`format_time`(`performance_schema`.`table_io_waits_summary_by_index_usage`.`SUM_TIMER_FETCH`) AS `select_latency`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`COUNT_INSERT` AS `rows_inserted`,`format_time`(`performance_schema`.`table_io_waits_summary_by_index_usage`.`SUM_TIMER_INSERT`) AS `insert_latency`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`COUNT_UPDATE` AS `rows_updated`,`format_time`(`performance_schema`.`table_io_waits_summary_by_index_usage`.`SUM_TIMER_UPDATE`) AS `update_latency`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`COUNT_DELETE` AS `rows_deleted`,`format_time`(`performance_schema`.`table_io_waits_summary_by_index_usage`.`SUM_TIMER_INSERT`) AS `delete_latency` from `performance_schema`.`table_io_waits_summary_by_index_usage` where (`performance_schema`.`table_io_waits_summary_by_index_usage`.`INDEX_NAME` is not null) order by `performance_schema`.`table_io_waits_summary_by_index_usage`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `schema_object_overview`
--
DROP TABLE IF EXISTS `schema_object_overview`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `schema_object_overview` AS select `information_schema`.`routines`.`ROUTINE_SCHEMA` AS `db`,`information_schema`.`routines`.`ROUTINE_TYPE` AS `object_type`,count(0) AS `count` from `information_schema`.`routines` group by `information_schema`.`routines`.`ROUTINE_SCHEMA`,`information_schema`.`routines`.`ROUTINE_TYPE` union select `information_schema`.`tables`.`TABLE_SCHEMA` AS `TABLE_SCHEMA`,`information_schema`.`tables`.`TABLE_TYPE` AS `TABLE_TYPE`,count(0) AS `COUNT(*)` from `information_schema`.`tables` group by `information_schema`.`tables`.`TABLE_SCHEMA`,`information_schema`.`tables`.`TABLE_TYPE` union select `information_schema`.`statistics`.`TABLE_SCHEMA` AS `TABLE_SCHEMA`,concat('INDEX (',`information_schema`.`statistics`.`INDEX_TYPE`,')') AS `CONCAT('INDEX (', INDEX_TYPE, ')')`,count(0) AS `COUNT(*)` from `information_schema`.`statistics` group by `information_schema`.`statistics`.`TABLE_SCHEMA`,`information_schema`.`statistics`.`INDEX_TYPE` union select `information_schema`.`triggers`.`TRIGGER_SCHEMA` AS `TRIGGER_SCHEMA`,'TRIGGER' AS `TRIGGER`,count(0) AS `COUNT(*)` from `information_schema`.`triggers` group by `information_schema`.`triggers`.`TRIGGER_SCHEMA` union select `information_schema`.`events`.`EVENT_SCHEMA` AS `EVENT_SCHEMA`,'EVENT' AS `EVENT`,count(0) AS `COUNT(*)` from `information_schema`.`events` group by `information_schema`.`events`.`EVENT_SCHEMA` order by `db`,`object_type`;

-- --------------------------------------------------------

--
-- Structure for view `schema_redundant_indexes`
--
DROP TABLE IF EXISTS `schema_redundant_indexes`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `schema_redundant_indexes` AS select `redundant_keys`.`table_schema` AS `table_schema`,`redundant_keys`.`table_name` AS `table_name`,`redundant_keys`.`index_name` AS `redundant_index_name`,`redundant_keys`.`index_columns` AS `redundant_index_columns`,`redundant_keys`.`non_unique` AS `redundant_index_non_unique`,`dominant_keys`.`index_name` AS `dominant_index_name`,`dominant_keys`.`index_columns` AS `dominant_index_columns`,`dominant_keys`.`non_unique` AS `dominant_index_non_unique`,if((`redundant_keys`.`subpart_exists` or `dominant_keys`.`subpart_exists`),1,0) AS `subpart_exists`,concat('ALTER TABLE `',`redundant_keys`.`table_schema`,'`.`',`redundant_keys`.`table_name`,'` DROP INDEX `',`redundant_keys`.`index_name`,'`') AS `sql_drop_index` from (`x$schema_flattened_keys` `redundant_keys` join `x$schema_flattened_keys` `dominant_keys` on(((`redundant_keys`.`table_schema` = `dominant_keys`.`table_schema`) and (`redundant_keys`.`table_name` = `dominant_keys`.`table_name`)))) where ((`redundant_keys`.`index_name` <> `dominant_keys`.`index_name`) and (((`redundant_keys`.`index_columns` = `dominant_keys`.`index_columns`) and ((`redundant_keys`.`non_unique` > `dominant_keys`.`non_unique`) or ((`redundant_keys`.`non_unique` = `dominant_keys`.`non_unique`) and (if((`redundant_keys`.`index_name` = 'PRIMARY'),'',`redundant_keys`.`index_name`) > if((`dominant_keys`.`index_name` = 'PRIMARY'),'',`dominant_keys`.`index_name`))))) or ((locate(concat(`redundant_keys`.`index_columns`,','),`dominant_keys`.`index_columns`) = 1) and (`redundant_keys`.`non_unique` = 1)) or ((locate(concat(`dominant_keys`.`index_columns`,','),`redundant_keys`.`index_columns`) = 1) and (`dominant_keys`.`non_unique` = 0))));

-- --------------------------------------------------------

--
-- Structure for view `schema_table_lock_waits`
--
DROP TABLE IF EXISTS `schema_table_lock_waits`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `schema_table_lock_waits` AS select `g`.`OBJECT_SCHEMA` AS `object_schema`,`g`.`OBJECT_NAME` AS `object_name`,`pt`.`THREAD_ID` AS `waiting_thread_id`,`pt`.`PROCESSLIST_ID` AS `waiting_pid`,`ps_thread_account`(`p`.`OWNER_THREAD_ID`) AS `waiting_account`,`p`.`LOCK_TYPE` AS `waiting_lock_type`,`p`.`LOCK_DURATION` AS `waiting_lock_duration`,`format_statement`(`pt`.`PROCESSLIST_INFO`) AS `waiting_query`,`pt`.`PROCESSLIST_TIME` AS `waiting_query_secs`,`ps`.`ROWS_AFFECTED` AS `waiting_query_rows_affected`,`ps`.`ROWS_EXAMINED` AS `waiting_query_rows_examined`,`gt`.`THREAD_ID` AS `blocking_thread_id`,`gt`.`PROCESSLIST_ID` AS `blocking_pid`,`ps_thread_account`(`g`.`OWNER_THREAD_ID`) AS `blocking_account`,`g`.`LOCK_TYPE` AS `blocking_lock_type`,`g`.`LOCK_DURATION` AS `blocking_lock_duration`,concat('KILL QUERY ',`gt`.`PROCESSLIST_ID`) AS `sql_kill_blocking_query`,concat('KILL ',`gt`.`PROCESSLIST_ID`) AS `sql_kill_blocking_connection` from (((((`performance_schema`.`metadata_locks` `g` join `performance_schema`.`metadata_locks` `p` on(((`g`.`OBJECT_TYPE` = `p`.`OBJECT_TYPE`) and (`g`.`OBJECT_SCHEMA` = `p`.`OBJECT_SCHEMA`) and (`g`.`OBJECT_NAME` = `p`.`OBJECT_NAME`) and (`g`.`LOCK_STATUS` = 'GRANTED') and (`p`.`LOCK_STATUS` = 'PENDING')))) join `performance_schema`.`threads` `gt` on((`g`.`OWNER_THREAD_ID` = `gt`.`THREAD_ID`))) join `performance_schema`.`threads` `pt` on((`p`.`OWNER_THREAD_ID` = `pt`.`THREAD_ID`))) left join `performance_schema`.`events_statements_current` `gs` on((`g`.`OWNER_THREAD_ID` = `gs`.`THREAD_ID`))) left join `performance_schema`.`events_statements_current` `ps` on((`p`.`OWNER_THREAD_ID` = `ps`.`THREAD_ID`))) where (`g`.`OBJECT_TYPE` = 'TABLE');

-- --------------------------------------------------------

--
-- Structure for view `schema_table_statistics`
--
DROP TABLE IF EXISTS `schema_table_statistics`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `schema_table_statistics` AS select `pst`.`OBJECT_SCHEMA` AS `table_schema`,`pst`.`OBJECT_NAME` AS `table_name`,`format_time`(`pst`.`SUM_TIMER_WAIT`) AS `total_latency`,`pst`.`COUNT_FETCH` AS `rows_fetched`,`format_time`(`pst`.`SUM_TIMER_FETCH`) AS `fetch_latency`,`pst`.`COUNT_INSERT` AS `rows_inserted`,`format_time`(`pst`.`SUM_TIMER_INSERT`) AS `insert_latency`,`pst`.`COUNT_UPDATE` AS `rows_updated`,`format_time`(`pst`.`SUM_TIMER_UPDATE`) AS `update_latency`,`pst`.`COUNT_DELETE` AS `rows_deleted`,`format_time`(`pst`.`SUM_TIMER_DELETE`) AS `delete_latency`,`fsbi`.`count_read` AS `io_read_requests`,`format_bytes`(`fsbi`.`sum_number_of_bytes_read`) AS `io_read`,`format_time`(`fsbi`.`sum_timer_read`) AS `io_read_latency`,`fsbi`.`count_write` AS `io_write_requests`,`format_bytes`(`fsbi`.`sum_number_of_bytes_write`) AS `io_write`,`format_time`(`fsbi`.`sum_timer_write`) AS `io_write_latency`,`fsbi`.`count_misc` AS `io_misc_requests`,`format_time`(`fsbi`.`sum_timer_misc`) AS `io_misc_latency` from (`performance_schema`.`table_io_waits_summary_by_table` `pst` left join `x$ps_schema_table_statistics_io` `fsbi` on(((`pst`.`OBJECT_SCHEMA` = `fsbi`.`table_schema`) and (`pst`.`OBJECT_NAME` = `fsbi`.`table_name`)))) order by `pst`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `schema_table_statistics_with_buffer`
--
DROP TABLE IF EXISTS `schema_table_statistics_with_buffer`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `schema_table_statistics_with_buffer` AS select `pst`.`OBJECT_SCHEMA` AS `table_schema`,`pst`.`OBJECT_NAME` AS `table_name`,`pst`.`COUNT_FETCH` AS `rows_fetched`,`format_time`(`pst`.`SUM_TIMER_FETCH`) AS `fetch_latency`,`pst`.`COUNT_INSERT` AS `rows_inserted`,`format_time`(`pst`.`SUM_TIMER_INSERT`) AS `insert_latency`,`pst`.`COUNT_UPDATE` AS `rows_updated`,`format_time`(`pst`.`SUM_TIMER_UPDATE`) AS `update_latency`,`pst`.`COUNT_DELETE` AS `rows_deleted`,`format_time`(`pst`.`SUM_TIMER_DELETE`) AS `delete_latency`,`fsbi`.`count_read` AS `io_read_requests`,`format_bytes`(`fsbi`.`sum_number_of_bytes_read`) AS `io_read`,`format_time`(`fsbi`.`sum_timer_read`) AS `io_read_latency`,`fsbi`.`count_write` AS `io_write_requests`,`format_bytes`(`fsbi`.`sum_number_of_bytes_write`) AS `io_write`,`format_time`(`fsbi`.`sum_timer_write`) AS `io_write_latency`,`fsbi`.`count_misc` AS `io_misc_requests`,`format_time`(`fsbi`.`sum_timer_misc`) AS `io_misc_latency`,`format_bytes`(`ibp`.`allocated`) AS `innodb_buffer_allocated`,`format_bytes`(`ibp`.`data`) AS `innodb_buffer_data`,`format_bytes`((`ibp`.`allocated` - `ibp`.`data`)) AS `innodb_buffer_free`,`ibp`.`pages` AS `innodb_buffer_pages`,`ibp`.`pages_hashed` AS `innodb_buffer_pages_hashed`,`ibp`.`pages_old` AS `innodb_buffer_pages_old`,`ibp`.`rows_cached` AS `innodb_buffer_rows_cached` from ((`performance_schema`.`table_io_waits_summary_by_table` `pst` left join `x$ps_schema_table_statistics_io` `fsbi` on(((`pst`.`OBJECT_SCHEMA` = `fsbi`.`table_schema`) and (`pst`.`OBJECT_NAME` = `fsbi`.`table_name`)))) left join `x$innodb_buffer_stats_by_table` `ibp` on(((`pst`.`OBJECT_SCHEMA` = `ibp`.`object_schema`) and (`pst`.`OBJECT_NAME` = `ibp`.`object_name`)))) order by `pst`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `schema_tables_with_full_table_scans`
--
DROP TABLE IF EXISTS `schema_tables_with_full_table_scans`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `schema_tables_with_full_table_scans` AS select `performance_schema`.`table_io_waits_summary_by_index_usage`.`OBJECT_SCHEMA` AS `object_schema`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`OBJECT_NAME` AS `object_name`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`COUNT_READ` AS `rows_full_scanned`,`format_time`(`performance_schema`.`table_io_waits_summary_by_index_usage`.`SUM_TIMER_WAIT`) AS `latency` from `performance_schema`.`table_io_waits_summary_by_index_usage` where (isnull(`performance_schema`.`table_io_waits_summary_by_index_usage`.`INDEX_NAME`) and (`performance_schema`.`table_io_waits_summary_by_index_usage`.`COUNT_READ` > 0)) order by `performance_schema`.`table_io_waits_summary_by_index_usage`.`COUNT_READ` desc;

-- --------------------------------------------------------

--
-- Structure for view `schema_unused_indexes`
--
DROP TABLE IF EXISTS `schema_unused_indexes`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `schema_unused_indexes` AS select `performance_schema`.`table_io_waits_summary_by_index_usage`.`OBJECT_SCHEMA` AS `object_schema`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`OBJECT_NAME` AS `object_name`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`INDEX_NAME` AS `index_name` from `performance_schema`.`table_io_waits_summary_by_index_usage` where ((`performance_schema`.`table_io_waits_summary_by_index_usage`.`INDEX_NAME` is not null) and (`performance_schema`.`table_io_waits_summary_by_index_usage`.`COUNT_STAR` = 0) and (`performance_schema`.`table_io_waits_summary_by_index_usage`.`OBJECT_SCHEMA` <> 'mysql') and (`performance_schema`.`table_io_waits_summary_by_index_usage`.`INDEX_NAME` <> 'PRIMARY')) order by `performance_schema`.`table_io_waits_summary_by_index_usage`.`OBJECT_SCHEMA`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`OBJECT_NAME`;

-- --------------------------------------------------------

--
-- Structure for view `session`
--
DROP TABLE IF EXISTS `session`;

CREATE ALGORITHM=UNDEFINED DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `session` AS select `processlist`.`thd_id` AS `thd_id`,`processlist`.`conn_id` AS `conn_id`,`processlist`.`user` AS `user`,`processlist`.`db` AS `db`,`processlist`.`command` AS `command`,`processlist`.`state` AS `state`,`processlist`.`time` AS `time`,`processlist`.`current_statement` AS `current_statement`,`processlist`.`statement_latency` AS `statement_latency`,`processlist`.`progress` AS `progress`,`processlist`.`lock_latency` AS `lock_latency`,`processlist`.`rows_examined` AS `rows_examined`,`processlist`.`rows_sent` AS `rows_sent`,`processlist`.`rows_affected` AS `rows_affected`,`processlist`.`tmp_tables` AS `tmp_tables`,`processlist`.`tmp_disk_tables` AS `tmp_disk_tables`,`processlist`.`full_scan` AS `full_scan`,`processlist`.`last_statement` AS `last_statement`,`processlist`.`last_statement_latency` AS `last_statement_latency`,`processlist`.`current_memory` AS `current_memory`,`processlist`.`last_wait` AS `last_wait`,`processlist`.`last_wait_latency` AS `last_wait_latency`,`processlist`.`source` AS `source`,`processlist`.`trx_latency` AS `trx_latency`,`processlist`.`trx_state` AS `trx_state`,`processlist`.`trx_autocommit` AS `trx_autocommit`,`processlist`.`pid` AS `pid`,`processlist`.`program_name` AS `program_name` from `processlist` where ((`processlist`.`conn_id` is not null) and (`processlist`.`command` <> 'Daemon'));

-- --------------------------------------------------------

--
-- Structure for view `session_ssl_status`
--
DROP TABLE IF EXISTS `session_ssl_status`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `session_ssl_status` AS select `sslver`.`THREAD_ID` AS `thread_id`,`sslver`.`VARIABLE_VALUE` AS `ssl_version`,`sslcip`.`VARIABLE_VALUE` AS `ssl_cipher`,`sslreuse`.`VARIABLE_VALUE` AS `ssl_sessions_reused` from ((`performance_schema`.`status_by_thread` `sslver` left join `performance_schema`.`status_by_thread` `sslcip` on(((`sslcip`.`THREAD_ID` = `sslver`.`THREAD_ID`) and (`sslcip`.`VARIABLE_NAME` = 'Ssl_cipher')))) left join `performance_schema`.`status_by_thread` `sslreuse` on(((`sslreuse`.`THREAD_ID` = `sslver`.`THREAD_ID`) and (`sslreuse`.`VARIABLE_NAME` = 'Ssl_sessions_reused')))) where (`sslver`.`VARIABLE_NAME` = 'Ssl_version');

-- --------------------------------------------------------

--
-- Structure for view `statement_analysis`
--
DROP TABLE IF EXISTS `statement_analysis`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `statement_analysis` AS select `format_statement`(`performance_schema`.`events_statements_summary_by_digest`.`DIGEST_TEXT`) AS `query`,`performance_schema`.`events_statements_summary_by_digest`.`SCHEMA_NAME` AS `db`,if(((`performance_schema`.`events_statements_summary_by_digest`.`SUM_NO_GOOD_INDEX_USED` > 0) or (`performance_schema`.`events_statements_summary_by_digest`.`SUM_NO_INDEX_USED` > 0)),'*','') AS `full_scan`,`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR` AS `exec_count`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_ERRORS` AS `err_count`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_WARNINGS` AS `warn_count`,`format_time`(`performance_schema`.`events_statements_summary_by_digest`.`SUM_TIMER_WAIT`) AS `total_latency`,`format_time`(`performance_schema`.`events_statements_summary_by_digest`.`MAX_TIMER_WAIT`) AS `max_latency`,`format_time`(`performance_schema`.`events_statements_summary_by_digest`.`AVG_TIMER_WAIT`) AS `avg_latency`,`format_time`(`performance_schema`.`events_statements_summary_by_digest`.`SUM_LOCK_TIME`) AS `lock_latency`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_SENT` AS `rows_sent`,round(ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_SENT` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0),0) AS `rows_sent_avg`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_EXAMINED` AS `rows_examined`,round(ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_EXAMINED` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0),0) AS `rows_examined_avg`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_AFFECTED` AS `rows_affected`,round(ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_AFFECTED` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0),0) AS `rows_affected_avg`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_TABLES` AS `tmp_tables`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_DISK_TABLES` AS `tmp_disk_tables`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_SORT_ROWS` AS `rows_sorted`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_SORT_MERGE_PASSES` AS `sort_merge_passes`,`performance_schema`.`events_statements_summary_by_digest`.`DIGEST` AS `digest`,`performance_schema`.`events_statements_summary_by_digest`.`FIRST_SEEN` AS `first_seen`,`performance_schema`.`events_statements_summary_by_digest`.`LAST_SEEN` AS `last_seen` from `performance_schema`.`events_statements_summary_by_digest` order by `performance_schema`.`events_statements_summary_by_digest`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `statements_with_errors_or_warnings`
--
DROP TABLE IF EXISTS `statements_with_errors_or_warnings`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `statements_with_errors_or_warnings` AS select `format_statement`(`performance_schema`.`events_statements_summary_by_digest`.`DIGEST_TEXT`) AS `query`,`performance_schema`.`events_statements_summary_by_digest`.`SCHEMA_NAME` AS `db`,`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR` AS `exec_count`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_ERRORS` AS `errors`,(ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_ERRORS` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0) * 100) AS `error_pct`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_WARNINGS` AS `warnings`,(ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_WARNINGS` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0) * 100) AS `warning_pct`,`performance_schema`.`events_statements_summary_by_digest`.`FIRST_SEEN` AS `first_seen`,`performance_schema`.`events_statements_summary_by_digest`.`LAST_SEEN` AS `last_seen`,`performance_schema`.`events_statements_summary_by_digest`.`DIGEST` AS `digest` from `performance_schema`.`events_statements_summary_by_digest` where ((`performance_schema`.`events_statements_summary_by_digest`.`SUM_ERRORS` > 0) or (`performance_schema`.`events_statements_summary_by_digest`.`SUM_WARNINGS` > 0)) order by `performance_schema`.`events_statements_summary_by_digest`.`SUM_ERRORS` desc,`performance_schema`.`events_statements_summary_by_digest`.`SUM_WARNINGS` desc;

-- --------------------------------------------------------

--
-- Structure for view `statements_with_full_table_scans`
--
DROP TABLE IF EXISTS `statements_with_full_table_scans`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `statements_with_full_table_scans` AS select `format_statement`(`performance_schema`.`events_statements_summary_by_digest`.`DIGEST_TEXT`) AS `query`,`performance_schema`.`events_statements_summary_by_digest`.`SCHEMA_NAME` AS `db`,`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR` AS `exec_count`,`format_time`(`performance_schema`.`events_statements_summary_by_digest`.`SUM_TIMER_WAIT`) AS `total_latency`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_NO_INDEX_USED` AS `no_index_used_count`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_NO_GOOD_INDEX_USED` AS `no_good_index_used_count`,round((ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_NO_INDEX_USED` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0) * 100),0) AS `no_index_used_pct`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_SENT` AS `rows_sent`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_EXAMINED` AS `rows_examined`,round((`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_SENT` / `performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`),0) AS `rows_sent_avg`,round((`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_EXAMINED` / `performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`),0) AS `rows_examined_avg`,`performance_schema`.`events_statements_summary_by_digest`.`FIRST_SEEN` AS `first_seen`,`performance_schema`.`events_statements_summary_by_digest`.`LAST_SEEN` AS `last_seen`,`performance_schema`.`events_statements_summary_by_digest`.`DIGEST` AS `digest` from `performance_schema`.`events_statements_summary_by_digest` where (((`performance_schema`.`events_statements_summary_by_digest`.`SUM_NO_INDEX_USED` > 0) or (`performance_schema`.`events_statements_summary_by_digest`.`SUM_NO_GOOD_INDEX_USED` > 0)) and (not((`performance_schema`.`events_statements_summary_by_digest`.`DIGEST_TEXT` like 'SHOW%')))) order by round((ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_NO_INDEX_USED` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0) * 100),0) desc,`format_time`(`performance_schema`.`events_statements_summary_by_digest`.`SUM_TIMER_WAIT`) desc;

-- --------------------------------------------------------

--
-- Structure for view `statements_with_runtimes_in_95th_percentile`
--
DROP TABLE IF EXISTS `statements_with_runtimes_in_95th_percentile`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `statements_with_runtimes_in_95th_percentile` AS select `format_statement`(`stmts`.`DIGEST_TEXT`) AS `query`,`stmts`.`SCHEMA_NAME` AS `db`,if(((`stmts`.`SUM_NO_GOOD_INDEX_USED` > 0) or (`stmts`.`SUM_NO_INDEX_USED` > 0)),'*','') AS `full_scan`,`stmts`.`COUNT_STAR` AS `exec_count`,`stmts`.`SUM_ERRORS` AS `err_count`,`stmts`.`SUM_WARNINGS` AS `warn_count`,`format_time`(`stmts`.`SUM_TIMER_WAIT`) AS `total_latency`,`format_time`(`stmts`.`MAX_TIMER_WAIT`) AS `max_latency`,`format_time`(`stmts`.`AVG_TIMER_WAIT`) AS `avg_latency`,`stmts`.`SUM_ROWS_SENT` AS `rows_sent`,round(ifnull((`stmts`.`SUM_ROWS_SENT` / nullif(`stmts`.`COUNT_STAR`,0)),0),0) AS `rows_sent_avg`,`stmts`.`SUM_ROWS_EXAMINED` AS `rows_examined`,round(ifnull((`stmts`.`SUM_ROWS_EXAMINED` / nullif(`stmts`.`COUNT_STAR`,0)),0),0) AS `rows_examined_avg`,`stmts`.`FIRST_SEEN` AS `first_seen`,`stmts`.`LAST_SEEN` AS `last_seen`,`stmts`.`DIGEST` AS `digest` from (`performance_schema`.`events_statements_summary_by_digest` `stmts` join `x$ps_digest_95th_percentile_by_avg_us` `top_percentile` on((round((`stmts`.`AVG_TIMER_WAIT` / 1000000),0) >= `top_percentile`.`avg_us`))) order by `stmts`.`AVG_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `statements_with_sorting`
--
DROP TABLE IF EXISTS `statements_with_sorting`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `statements_with_sorting` AS select `format_statement`(`performance_schema`.`events_statements_summary_by_digest`.`DIGEST_TEXT`) AS `query`,`performance_schema`.`events_statements_summary_by_digest`.`SCHEMA_NAME` AS `db`,`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR` AS `exec_count`,`format_time`(`performance_schema`.`events_statements_summary_by_digest`.`SUM_TIMER_WAIT`) AS `total_latency`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_SORT_MERGE_PASSES` AS `sort_merge_passes`,round(ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_SORT_MERGE_PASSES` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0),0) AS `avg_sort_merges`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_SORT_SCAN` AS `sorts_using_scans`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_SORT_RANGE` AS `sort_using_range`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_SORT_ROWS` AS `rows_sorted`,round(ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_SORT_ROWS` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0),0) AS `avg_rows_sorted`,`performance_schema`.`events_statements_summary_by_digest`.`FIRST_SEEN` AS `first_seen`,`performance_schema`.`events_statements_summary_by_digest`.`LAST_SEEN` AS `last_seen`,`performance_schema`.`events_statements_summary_by_digest`.`DIGEST` AS `digest` from `performance_schema`.`events_statements_summary_by_digest` where (`performance_schema`.`events_statements_summary_by_digest`.`SUM_SORT_ROWS` > 0) order by `performance_schema`.`events_statements_summary_by_digest`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `statements_with_temp_tables`
--
DROP TABLE IF EXISTS `statements_with_temp_tables`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `statements_with_temp_tables` AS select `format_statement`(`performance_schema`.`events_statements_summary_by_digest`.`DIGEST_TEXT`) AS `query`,`performance_schema`.`events_statements_summary_by_digest`.`SCHEMA_NAME` AS `db`,`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR` AS `exec_count`,`format_time`(`performance_schema`.`events_statements_summary_by_digest`.`SUM_TIMER_WAIT`) AS `total_latency`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_TABLES` AS `memory_tmp_tables`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_DISK_TABLES` AS `disk_tmp_tables`,round(ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_TABLES` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0),0) AS `avg_tmp_tables_per_query`,round((ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_DISK_TABLES` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_TABLES`,0)),0) * 100),0) AS `tmp_tables_to_disk_pct`,`performance_schema`.`events_statements_summary_by_digest`.`FIRST_SEEN` AS `first_seen`,`performance_schema`.`events_statements_summary_by_digest`.`LAST_SEEN` AS `last_seen`,`performance_schema`.`events_statements_summary_by_digest`.`DIGEST` AS `digest` from `performance_schema`.`events_statements_summary_by_digest` where (`performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_TABLES` > 0) order by `performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_DISK_TABLES` desc,`performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_TABLES` desc;

-- --------------------------------------------------------

--
-- Structure for view `user_summary`
--
DROP TABLE IF EXISTS `user_summary`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `user_summary` AS select if(isnull(`performance_schema`.`accounts`.`USER`),'background',`performance_schema`.`accounts`.`USER`) AS `user`,sum(`stmt`.`total`) AS `statements`,`format_time`(sum(`stmt`.`total_latency`)) AS `statement_latency`,`format_time`(ifnull((sum(`stmt`.`total_latency`) / nullif(sum(`stmt`.`total`),0)),0)) AS `statement_avg_latency`,sum(`stmt`.`full_scans`) AS `table_scans`,sum(`io`.`ios`) AS `file_ios`,`format_time`(sum(`io`.`io_latency`)) AS `file_io_latency`,sum(`performance_schema`.`accounts`.`CURRENT_CONNECTIONS`) AS `current_connections`,sum(`performance_schema`.`accounts`.`TOTAL_CONNECTIONS`) AS `total_connections`,count(distinct `performance_schema`.`accounts`.`HOST`) AS `unique_hosts`,`format_bytes`(sum(`mem`.`current_allocated`)) AS `current_memory`,`format_bytes`(sum(`mem`.`total_allocated`)) AS `total_memory_allocated` from (((`performance_schema`.`accounts` left join `x$user_summary_by_statement_latency` `stmt` on((if(isnull(`performance_schema`.`accounts`.`USER`),'background',`performance_schema`.`accounts`.`USER`) = `stmt`.`user`))) left join `x$user_summary_by_file_io` `io` on((if(isnull(`performance_schema`.`accounts`.`USER`),'background',`performance_schema`.`accounts`.`USER`) = `io`.`user`))) left join `x$memory_by_user_by_current_bytes` `mem` on((if(isnull(`performance_schema`.`accounts`.`USER`),'background',`performance_schema`.`accounts`.`USER`) = `mem`.`user`))) group by if(isnull(`performance_schema`.`accounts`.`USER`),'background',`performance_schema`.`accounts`.`USER`) order by sum(`stmt`.`total_latency`) desc;

-- --------------------------------------------------------

--
-- Structure for view `user_summary_by_file_io`
--
DROP TABLE IF EXISTS `user_summary_by_file_io`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `user_summary_by_file_io` AS select if(isnull(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`) AS `user`,sum(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`COUNT_STAR`) AS `ios`,`format_time`(sum(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`SUM_TIMER_WAIT`)) AS `io_latency` from `performance_schema`.`events_waits_summary_by_user_by_event_name` where (`performance_schema`.`events_waits_summary_by_user_by_event_name`.`EVENT_NAME` like 'wait/io/file/%') group by if(isnull(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`) order by sum(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`SUM_TIMER_WAIT`) desc;

-- --------------------------------------------------------

--
-- Structure for view `user_summary_by_file_io_type`
--
DROP TABLE IF EXISTS `user_summary_by_file_io_type`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `user_summary_by_file_io_type` AS select if(isnull(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`) AS `user`,`performance_schema`.`events_waits_summary_by_user_by_event_name`.`EVENT_NAME` AS `event_name`,`performance_schema`.`events_waits_summary_by_user_by_event_name`.`COUNT_STAR` AS `total`,`format_time`(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`SUM_TIMER_WAIT`) AS `latency`,`format_time`(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`MAX_TIMER_WAIT`) AS `max_latency` from `performance_schema`.`events_waits_summary_by_user_by_event_name` where ((`performance_schema`.`events_waits_summary_by_user_by_event_name`.`EVENT_NAME` like 'wait/io/file%') and (`performance_schema`.`events_waits_summary_by_user_by_event_name`.`COUNT_STAR` > 0)) order by if(isnull(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`),`performance_schema`.`events_waits_summary_by_user_by_event_name`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `user_summary_by_stages`
--
DROP TABLE IF EXISTS `user_summary_by_stages`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `user_summary_by_stages` AS select if(isnull(`performance_schema`.`events_stages_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_stages_summary_by_user_by_event_name`.`USER`) AS `user`,`performance_schema`.`events_stages_summary_by_user_by_event_name`.`EVENT_NAME` AS `event_name`,`performance_schema`.`events_stages_summary_by_user_by_event_name`.`COUNT_STAR` AS `total`,`format_time`(`performance_schema`.`events_stages_summary_by_user_by_event_name`.`SUM_TIMER_WAIT`) AS `total_latency`,`format_time`(`performance_schema`.`events_stages_summary_by_user_by_event_name`.`AVG_TIMER_WAIT`) AS `avg_latency` from `performance_schema`.`events_stages_summary_by_user_by_event_name` where (`performance_schema`.`events_stages_summary_by_user_by_event_name`.`SUM_TIMER_WAIT` <> 0) order by if(isnull(`performance_schema`.`events_stages_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_stages_summary_by_user_by_event_name`.`USER`),`performance_schema`.`events_stages_summary_by_user_by_event_name`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `user_summary_by_statement_latency`
--
DROP TABLE IF EXISTS `user_summary_by_statement_latency`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `user_summary_by_statement_latency` AS select if(isnull(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_statements_summary_by_user_by_event_name`.`USER`) AS `user`,sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`COUNT_STAR`) AS `total`,`format_time`(sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_TIMER_WAIT`)) AS `total_latency`,`format_time`(sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`MAX_TIMER_WAIT`)) AS `max_latency`,`format_time`(sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_LOCK_TIME`)) AS `lock_latency`,sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_ROWS_SENT`) AS `rows_sent`,sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_ROWS_EXAMINED`) AS `rows_examined`,sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_ROWS_AFFECTED`) AS `rows_affected`,(sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_NO_INDEX_USED`) + sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_NO_GOOD_INDEX_USED`)) AS `full_scans` from `performance_schema`.`events_statements_summary_by_user_by_event_name` group by if(isnull(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_statements_summary_by_user_by_event_name`.`USER`) order by sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_TIMER_WAIT`) desc;

-- --------------------------------------------------------

--
-- Structure for view `user_summary_by_statement_type`
--
DROP TABLE IF EXISTS `user_summary_by_statement_type`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `user_summary_by_statement_type` AS select if(isnull(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_statements_summary_by_user_by_event_name`.`USER`) AS `user`,substring_index(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`EVENT_NAME`,'/',-(1)) AS `statement`,`performance_schema`.`events_statements_summary_by_user_by_event_name`.`COUNT_STAR` AS `total`,`format_time`(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_TIMER_WAIT`) AS `total_latency`,`format_time`(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`MAX_TIMER_WAIT`) AS `max_latency`,`format_time`(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_LOCK_TIME`) AS `lock_latency`,`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_ROWS_SENT` AS `rows_sent`,`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_ROWS_EXAMINED` AS `rows_examined`,`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_ROWS_AFFECTED` AS `rows_affected`,(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_NO_INDEX_USED` + `performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_NO_GOOD_INDEX_USED`) AS `full_scans` from `performance_schema`.`events_statements_summary_by_user_by_event_name` where (`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_TIMER_WAIT` <> 0) order by if(isnull(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_statements_summary_by_user_by_event_name`.`USER`),`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `version`
--
DROP TABLE IF EXISTS `version`;

CREATE ALGORITHM=UNDEFINED DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `version` AS select '1.5.1' AS `sys_version`,version() AS `mysql_version`;

-- --------------------------------------------------------

--
-- Structure for view `wait_classes_global_by_avg_latency`
--
DROP TABLE IF EXISTS `wait_classes_global_by_avg_latency`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `wait_classes_global_by_avg_latency` AS select substring_index(`performance_schema`.`events_waits_summary_global_by_event_name`.`EVENT_NAME`,'/',3) AS `event_class`,sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`COUNT_STAR`) AS `total`,`format_time`(cast(sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT`) as unsigned)) AS `total_latency`,`format_time`(min(`performance_schema`.`events_waits_summary_global_by_event_name`.`MIN_TIMER_WAIT`)) AS `min_latency`,`format_time`(ifnull((sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT`) / nullif(sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`COUNT_STAR`),0)),0)) AS `avg_latency`,`format_time`(cast(max(`performance_schema`.`events_waits_summary_global_by_event_name`.`MAX_TIMER_WAIT`) as unsigned)) AS `max_latency` from `performance_schema`.`events_waits_summary_global_by_event_name` where ((`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT` > 0) and (`performance_schema`.`events_waits_summary_global_by_event_name`.`EVENT_NAME` <> 'idle')) group by `event_class` order by ifnull((sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT`) / nullif(sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`COUNT_STAR`),0)),0) desc;

-- --------------------------------------------------------

--
-- Structure for view `wait_classes_global_by_latency`
--
DROP TABLE IF EXISTS `wait_classes_global_by_latency`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `wait_classes_global_by_latency` AS select substring_index(`performance_schema`.`events_waits_summary_global_by_event_name`.`EVENT_NAME`,'/',3) AS `event_class`,sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`COUNT_STAR`) AS `total`,`format_time`(sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT`)) AS `total_latency`,`format_time`(min(`performance_schema`.`events_waits_summary_global_by_event_name`.`MIN_TIMER_WAIT`)) AS `min_latency`,`format_time`(ifnull((sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT`) / nullif(sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`COUNT_STAR`),0)),0)) AS `avg_latency`,`format_time`(max(`performance_schema`.`events_waits_summary_global_by_event_name`.`MAX_TIMER_WAIT`)) AS `max_latency` from `performance_schema`.`events_waits_summary_global_by_event_name` where ((`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT` > 0) and (`performance_schema`.`events_waits_summary_global_by_event_name`.`EVENT_NAME` <> 'idle')) group by substring_index(`performance_schema`.`events_waits_summary_global_by_event_name`.`EVENT_NAME`,'/',3) order by sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT`) desc;

-- --------------------------------------------------------

--
-- Structure for view `waits_by_host_by_latency`
--
DROP TABLE IF EXISTS `waits_by_host_by_latency`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `waits_by_host_by_latency` AS select if(isnull(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`) AS `host`,`performance_schema`.`events_waits_summary_by_host_by_event_name`.`EVENT_NAME` AS `event`,`performance_schema`.`events_waits_summary_by_host_by_event_name`.`COUNT_STAR` AS `total`,`format_time`(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`SUM_TIMER_WAIT`) AS `total_latency`,`format_time`(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`AVG_TIMER_WAIT`) AS `avg_latency`,`format_time`(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`MAX_TIMER_WAIT`) AS `max_latency` from `performance_schema`.`events_waits_summary_by_host_by_event_name` where ((`performance_schema`.`events_waits_summary_by_host_by_event_name`.`EVENT_NAME` <> 'idle') and (`performance_schema`.`events_waits_summary_by_host_by_event_name`.`SUM_TIMER_WAIT` > 0)) order by if(isnull(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`),`performance_schema`.`events_waits_summary_by_host_by_event_name`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `waits_by_user_by_latency`
--
DROP TABLE IF EXISTS `waits_by_user_by_latency`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `waits_by_user_by_latency` AS select if(isnull(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`) AS `user`,`performance_schema`.`events_waits_summary_by_user_by_event_name`.`EVENT_NAME` AS `event`,`performance_schema`.`events_waits_summary_by_user_by_event_name`.`COUNT_STAR` AS `total`,`format_time`(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`SUM_TIMER_WAIT`) AS `total_latency`,`format_time`(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`AVG_TIMER_WAIT`) AS `avg_latency`,`format_time`(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`MAX_TIMER_WAIT`) AS `max_latency` from `performance_schema`.`events_waits_summary_by_user_by_event_name` where ((`performance_schema`.`events_waits_summary_by_user_by_event_name`.`EVENT_NAME` <> 'idle') and (`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER` is not null) and (`performance_schema`.`events_waits_summary_by_user_by_event_name`.`SUM_TIMER_WAIT` > 0)) order by if(isnull(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`),`performance_schema`.`events_waits_summary_by_user_by_event_name`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `waits_global_by_latency`
--
DROP TABLE IF EXISTS `waits_global_by_latency`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `waits_global_by_latency` AS select `performance_schema`.`events_waits_summary_global_by_event_name`.`EVENT_NAME` AS `events`,`performance_schema`.`events_waits_summary_global_by_event_name`.`COUNT_STAR` AS `total`,`format_time`(`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT`) AS `total_latency`,`format_time`(`performance_schema`.`events_waits_summary_global_by_event_name`.`AVG_TIMER_WAIT`) AS `avg_latency`,`format_time`(`performance_schema`.`events_waits_summary_global_by_event_name`.`MAX_TIMER_WAIT`) AS `max_latency` from `performance_schema`.`events_waits_summary_global_by_event_name` where ((`performance_schema`.`events_waits_summary_global_by_event_name`.`EVENT_NAME` <> 'idle') and (`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT` > 0)) order by `performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$host_summary`
--
DROP TABLE IF EXISTS `x$host_summary`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$host_summary` AS select if(isnull(`performance_schema`.`accounts`.`HOST`),'background',`performance_schema`.`accounts`.`HOST`) AS `host`,sum(`stmt`.`total`) AS `statements`,sum(`stmt`.`total_latency`) AS `statement_latency`,(sum(`stmt`.`total_latency`) / sum(`stmt`.`total`)) AS `statement_avg_latency`,sum(`stmt`.`full_scans`) AS `table_scans`,sum(`io`.`ios`) AS `file_ios`,sum(`io`.`io_latency`) AS `file_io_latency`,sum(`performance_schema`.`accounts`.`CURRENT_CONNECTIONS`) AS `current_connections`,sum(`performance_schema`.`accounts`.`TOTAL_CONNECTIONS`) AS `total_connections`,count(distinct `performance_schema`.`accounts`.`USER`) AS `unique_users`,sum(`mem`.`current_allocated`) AS `current_memory`,sum(`mem`.`total_allocated`) AS `total_memory_allocated` from (((`performance_schema`.`accounts` join `x$host_summary_by_statement_latency` `stmt` on((`performance_schema`.`accounts`.`HOST` = `stmt`.`host`))) join `x$host_summary_by_file_io` `io` on((`performance_schema`.`accounts`.`HOST` = `io`.`host`))) join `x$memory_by_host_by_current_bytes` `mem` on((`performance_schema`.`accounts`.`HOST` = `mem`.`host`))) group by if(isnull(`performance_schema`.`accounts`.`HOST`),'background',`performance_schema`.`accounts`.`HOST`);

-- --------------------------------------------------------

--
-- Structure for view `x$host_summary_by_file_io`
--
DROP TABLE IF EXISTS `x$host_summary_by_file_io`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$host_summary_by_file_io` AS select if(isnull(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`) AS `host`,sum(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`COUNT_STAR`) AS `ios`,sum(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`SUM_TIMER_WAIT`) AS `io_latency` from `performance_schema`.`events_waits_summary_by_host_by_event_name` where (`performance_schema`.`events_waits_summary_by_host_by_event_name`.`EVENT_NAME` like 'wait/io/file/%') group by if(isnull(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`) order by sum(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`SUM_TIMER_WAIT`) desc;

-- --------------------------------------------------------

--
-- Structure for view `x$host_summary_by_file_io_type`
--
DROP TABLE IF EXISTS `x$host_summary_by_file_io_type`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$host_summary_by_file_io_type` AS select if(isnull(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`) AS `host`,`performance_schema`.`events_waits_summary_by_host_by_event_name`.`EVENT_NAME` AS `event_name`,`performance_schema`.`events_waits_summary_by_host_by_event_name`.`COUNT_STAR` AS `total`,`performance_schema`.`events_waits_summary_by_host_by_event_name`.`SUM_TIMER_WAIT` AS `total_latency`,`performance_schema`.`events_waits_summary_by_host_by_event_name`.`MAX_TIMER_WAIT` AS `max_latency` from `performance_schema`.`events_waits_summary_by_host_by_event_name` where ((`performance_schema`.`events_waits_summary_by_host_by_event_name`.`EVENT_NAME` like 'wait/io/file%') and (`performance_schema`.`events_waits_summary_by_host_by_event_name`.`COUNT_STAR` > 0)) order by if(isnull(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`),`performance_schema`.`events_waits_summary_by_host_by_event_name`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$host_summary_by_stages`
--
DROP TABLE IF EXISTS `x$host_summary_by_stages`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$host_summary_by_stages` AS select if(isnull(`performance_schema`.`events_stages_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_stages_summary_by_host_by_event_name`.`HOST`) AS `host`,`performance_schema`.`events_stages_summary_by_host_by_event_name`.`EVENT_NAME` AS `event_name`,`performance_schema`.`events_stages_summary_by_host_by_event_name`.`COUNT_STAR` AS `total`,`performance_schema`.`events_stages_summary_by_host_by_event_name`.`SUM_TIMER_WAIT` AS `total_latency`,`performance_schema`.`events_stages_summary_by_host_by_event_name`.`AVG_TIMER_WAIT` AS `avg_latency` from `performance_schema`.`events_stages_summary_by_host_by_event_name` where (`performance_schema`.`events_stages_summary_by_host_by_event_name`.`SUM_TIMER_WAIT` <> 0) order by if(isnull(`performance_schema`.`events_stages_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_stages_summary_by_host_by_event_name`.`HOST`),`performance_schema`.`events_stages_summary_by_host_by_event_name`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$host_summary_by_statement_latency`
--
DROP TABLE IF EXISTS `x$host_summary_by_statement_latency`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$host_summary_by_statement_latency` AS select if(isnull(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_statements_summary_by_host_by_event_name`.`HOST`) AS `host`,sum(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`COUNT_STAR`) AS `total`,sum(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_TIMER_WAIT`) AS `total_latency`,max(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`MAX_TIMER_WAIT`) AS `max_latency`,sum(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_LOCK_TIME`) AS `lock_latency`,sum(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_ROWS_SENT`) AS `rows_sent`,sum(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_ROWS_EXAMINED`) AS `rows_examined`,sum(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_ROWS_AFFECTED`) AS `rows_affected`,(sum(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_NO_INDEX_USED`) + sum(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_NO_GOOD_INDEX_USED`)) AS `full_scans` from `performance_schema`.`events_statements_summary_by_host_by_event_name` group by if(isnull(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_statements_summary_by_host_by_event_name`.`HOST`) order by sum(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_TIMER_WAIT`) desc;

-- --------------------------------------------------------

--
-- Structure for view `x$host_summary_by_statement_type`
--
DROP TABLE IF EXISTS `x$host_summary_by_statement_type`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$host_summary_by_statement_type` AS select if(isnull(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_statements_summary_by_host_by_event_name`.`HOST`) AS `host`,substring_index(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`EVENT_NAME`,'/',-(1)) AS `statement`,`performance_schema`.`events_statements_summary_by_host_by_event_name`.`COUNT_STAR` AS `total`,`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_TIMER_WAIT` AS `total_latency`,`performance_schema`.`events_statements_summary_by_host_by_event_name`.`MAX_TIMER_WAIT` AS `max_latency`,`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_LOCK_TIME` AS `lock_latency`,`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_ROWS_SENT` AS `rows_sent`,`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_ROWS_EXAMINED` AS `rows_examined`,`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_ROWS_AFFECTED` AS `rows_affected`,(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_NO_INDEX_USED` + `performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_NO_GOOD_INDEX_USED`) AS `full_scans` from `performance_schema`.`events_statements_summary_by_host_by_event_name` where (`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_TIMER_WAIT` <> 0) order by if(isnull(`performance_schema`.`events_statements_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_statements_summary_by_host_by_event_name`.`HOST`),`performance_schema`.`events_statements_summary_by_host_by_event_name`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$innodb_buffer_stats_by_schema`
--
DROP TABLE IF EXISTS `x$innodb_buffer_stats_by_schema`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$innodb_buffer_stats_by_schema` AS select if((locate('.',`ibp`.`TABLE_NAME`) = 0),'InnoDB System',replace(substring_index(`ibp`.`TABLE_NAME`,'.',1),'`','')) AS `object_schema`,sum(if((`ibp`.`COMPRESSED_SIZE` = 0),16384,`ibp`.`COMPRESSED_SIZE`)) AS `allocated`,sum(`ibp`.`DATA_SIZE`) AS `data`,count(`ibp`.`PAGE_NUMBER`) AS `pages`,count(if((`ibp`.`IS_HASHED` = 'YES'),1,NULL)) AS `pages_hashed`,count(if((`ibp`.`IS_OLD` = 'YES'),1,NULL)) AS `pages_old`,round(ifnull((sum(`ibp`.`NUMBER_RECORDS`) / nullif(count(distinct `ibp`.`INDEX_NAME`),0)),0),0) AS `rows_cached` from `information_schema`.`innodb_buffer_page` `ibp` where (`ibp`.`TABLE_NAME` is not null) group by `object_schema` order by sum(if((`ibp`.`COMPRESSED_SIZE` = 0),16384,`ibp`.`COMPRESSED_SIZE`)) desc;

-- --------------------------------------------------------

--
-- Structure for view `x$innodb_buffer_stats_by_table`
--
DROP TABLE IF EXISTS `x$innodb_buffer_stats_by_table`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$innodb_buffer_stats_by_table` AS select if((locate('.',`ibp`.`TABLE_NAME`) = 0),'InnoDB System',replace(substring_index(`ibp`.`TABLE_NAME`,'.',1),'`','')) AS `object_schema`,replace(substring_index(`ibp`.`TABLE_NAME`,'.',-(1)),'`','') AS `object_name`,sum(if((`ibp`.`COMPRESSED_SIZE` = 0),16384,`ibp`.`COMPRESSED_SIZE`)) AS `allocated`,sum(`ibp`.`DATA_SIZE`) AS `data`,count(`ibp`.`PAGE_NUMBER`) AS `pages`,count(if((`ibp`.`IS_HASHED` = 'YES'),1,NULL)) AS `pages_hashed`,count(if((`ibp`.`IS_OLD` = 'YES'),1,NULL)) AS `pages_old`,round(ifnull((sum(`ibp`.`NUMBER_RECORDS`) / nullif(count(distinct `ibp`.`INDEX_NAME`),0)),0),0) AS `rows_cached` from `information_schema`.`innodb_buffer_page` `ibp` where (`ibp`.`TABLE_NAME` is not null) group by `object_schema`,`object_name` order by sum(if((`ibp`.`COMPRESSED_SIZE` = 0),16384,`ibp`.`COMPRESSED_SIZE`)) desc;

-- --------------------------------------------------------

--
-- Structure for view `x$innodb_lock_waits`
--
DROP TABLE IF EXISTS `x$innodb_lock_waits`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$innodb_lock_waits` AS select `r`.`trx_wait_started` AS `wait_started`,timediff(now(),`r`.`trx_wait_started`) AS `wait_age`,timestampdiff(SECOND,`r`.`trx_wait_started`,now()) AS `wait_age_secs`,`rl`.`lock_table` AS `locked_table`,`rl`.`lock_index` AS `locked_index`,`rl`.`lock_type` AS `locked_type`,`r`.`trx_id` AS `waiting_trx_id`,`r`.`trx_started` AS `waiting_trx_started`,timediff(now(),`r`.`trx_started`) AS `waiting_trx_age`,`r`.`trx_rows_locked` AS `waiting_trx_rows_locked`,`r`.`trx_rows_modified` AS `waiting_trx_rows_modified`,`r`.`trx_mysql_thread_id` AS `waiting_pid`,`r`.`trx_query` AS `waiting_query`,`rl`.`lock_id` AS `waiting_lock_id`,`rl`.`lock_mode` AS `waiting_lock_mode`,`b`.`trx_id` AS `blocking_trx_id`,`b`.`trx_mysql_thread_id` AS `blocking_pid`,`b`.`trx_query` AS `blocking_query`,`bl`.`lock_id` AS `blocking_lock_id`,`bl`.`lock_mode` AS `blocking_lock_mode`,`b`.`trx_started` AS `blocking_trx_started`,timediff(now(),`b`.`trx_started`) AS `blocking_trx_age`,`b`.`trx_rows_locked` AS `blocking_trx_rows_locked`,`b`.`trx_rows_modified` AS `blocking_trx_rows_modified`,concat('KILL QUERY ',`b`.`trx_mysql_thread_id`) AS `sql_kill_blocking_query`,concat('KILL ',`b`.`trx_mysql_thread_id`) AS `sql_kill_blocking_connection` from ((((`information_schema`.`innodb_lock_waits` `w` join `information_schema`.`innodb_trx` `b` on((`b`.`trx_id` = `w`.`blocking_trx_id`))) join `information_schema`.`innodb_trx` `r` on((`r`.`trx_id` = `w`.`requesting_trx_id`))) join `information_schema`.`innodb_locks` `bl` on((`bl`.`lock_id` = `w`.`blocking_lock_id`))) join `information_schema`.`innodb_locks` `rl` on((`rl`.`lock_id` = `w`.`requested_lock_id`))) order by `r`.`trx_wait_started`;

-- --------------------------------------------------------

--
-- Structure for view `x$io_by_thread_by_latency`
--
DROP TABLE IF EXISTS `x$io_by_thread_by_latency`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$io_by_thread_by_latency` AS select if(isnull(`performance_schema`.`threads`.`PROCESSLIST_ID`),substring_index(`performance_schema`.`threads`.`NAME`,'/',-(1)),concat(`performance_schema`.`threads`.`PROCESSLIST_USER`,'@',`performance_schema`.`threads`.`PROCESSLIST_HOST`)) AS `user`,sum(`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`COUNT_STAR`) AS `total`,sum(`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`SUM_TIMER_WAIT`) AS `total_latency`,min(`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`MIN_TIMER_WAIT`) AS `min_latency`,avg(`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`AVG_TIMER_WAIT`) AS `avg_latency`,max(`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`MAX_TIMER_WAIT`) AS `max_latency`,`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`THREAD_ID` AS `thread_id`,`performance_schema`.`threads`.`PROCESSLIST_ID` AS `processlist_id` from (`performance_schema`.`events_waits_summary_by_thread_by_event_name` left join `performance_schema`.`threads` on((`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`THREAD_ID` = `performance_schema`.`threads`.`THREAD_ID`))) where ((`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`EVENT_NAME` like 'wait/io/file/%') and (`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`SUM_TIMER_WAIT` > 0)) group by `performance_schema`.`events_waits_summary_by_thread_by_event_name`.`THREAD_ID`,`performance_schema`.`threads`.`PROCESSLIST_ID`,`user` order by sum(`performance_schema`.`events_waits_summary_by_thread_by_event_name`.`SUM_TIMER_WAIT`) desc;

-- --------------------------------------------------------

--
-- Structure for view `x$io_global_by_file_by_bytes`
--
DROP TABLE IF EXISTS `x$io_global_by_file_by_bytes`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$io_global_by_file_by_bytes` AS select `performance_schema`.`file_summary_by_instance`.`FILE_NAME` AS `file`,`performance_schema`.`file_summary_by_instance`.`COUNT_READ` AS `count_read`,`performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_READ` AS `total_read`,ifnull((`performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_READ` / nullif(`performance_schema`.`file_summary_by_instance`.`COUNT_READ`,0)),0) AS `avg_read`,`performance_schema`.`file_summary_by_instance`.`COUNT_WRITE` AS `count_write`,`performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_WRITE` AS `total_written`,ifnull((`performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_WRITE` / nullif(`performance_schema`.`file_summary_by_instance`.`COUNT_WRITE`,0)),0.00) AS `avg_write`,(`performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_READ` + `performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_WRITE`) AS `total`,ifnull(round((100 - ((`performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_READ` / nullif((`performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_READ` + `performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_WRITE`),0)) * 100)),2),0.00) AS `write_pct` from `performance_schema`.`file_summary_by_instance` order by (`performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_READ` + `performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_WRITE`) desc;

-- --------------------------------------------------------

--
-- Structure for view `x$io_global_by_file_by_latency`
--
DROP TABLE IF EXISTS `x$io_global_by_file_by_latency`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$io_global_by_file_by_latency` AS select `performance_schema`.`file_summary_by_instance`.`FILE_NAME` AS `file`,`performance_schema`.`file_summary_by_instance`.`COUNT_STAR` AS `total`,`performance_schema`.`file_summary_by_instance`.`SUM_TIMER_WAIT` AS `total_latency`,`performance_schema`.`file_summary_by_instance`.`COUNT_READ` AS `count_read`,`performance_schema`.`file_summary_by_instance`.`SUM_TIMER_READ` AS `read_latency`,`performance_schema`.`file_summary_by_instance`.`COUNT_WRITE` AS `count_write`,`performance_schema`.`file_summary_by_instance`.`SUM_TIMER_WRITE` AS `write_latency`,`performance_schema`.`file_summary_by_instance`.`COUNT_MISC` AS `count_misc`,`performance_schema`.`file_summary_by_instance`.`SUM_TIMER_MISC` AS `misc_latency` from `performance_schema`.`file_summary_by_instance` order by `performance_schema`.`file_summary_by_instance`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$io_global_by_wait_by_bytes`
--
DROP TABLE IF EXISTS `x$io_global_by_wait_by_bytes`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$io_global_by_wait_by_bytes` AS select substring_index(`performance_schema`.`file_summary_by_event_name`.`EVENT_NAME`,'/',-(2)) AS `event_name`,`performance_schema`.`file_summary_by_event_name`.`COUNT_STAR` AS `total`,`performance_schema`.`file_summary_by_event_name`.`SUM_TIMER_WAIT` AS `total_latency`,`performance_schema`.`file_summary_by_event_name`.`MIN_TIMER_WAIT` AS `min_latency`,`performance_schema`.`file_summary_by_event_name`.`AVG_TIMER_WAIT` AS `avg_latency`,`performance_schema`.`file_summary_by_event_name`.`MAX_TIMER_WAIT` AS `max_latency`,`performance_schema`.`file_summary_by_event_name`.`COUNT_READ` AS `count_read`,`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_READ` AS `total_read`,ifnull((`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_READ` / nullif(`performance_schema`.`file_summary_by_event_name`.`COUNT_READ`,0)),0) AS `avg_read`,`performance_schema`.`file_summary_by_event_name`.`COUNT_WRITE` AS `count_write`,`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_WRITE` AS `total_written`,ifnull((`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_WRITE` / nullif(`performance_schema`.`file_summary_by_event_name`.`COUNT_WRITE`,0)),0) AS `avg_written`,(`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_WRITE` + `performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_READ`) AS `total_requested` from `performance_schema`.`file_summary_by_event_name` where ((`performance_schema`.`file_summary_by_event_name`.`EVENT_NAME` like 'wait/io/file/%') and (`performance_schema`.`file_summary_by_event_name`.`COUNT_STAR` > 0)) order by (`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_WRITE` + `performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_READ`) desc;

-- --------------------------------------------------------

--
-- Structure for view `x$io_global_by_wait_by_latency`
--
DROP TABLE IF EXISTS `x$io_global_by_wait_by_latency`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$io_global_by_wait_by_latency` AS select substring_index(`performance_schema`.`file_summary_by_event_name`.`EVENT_NAME`,'/',-(2)) AS `event_name`,`performance_schema`.`file_summary_by_event_name`.`COUNT_STAR` AS `total`,`performance_schema`.`file_summary_by_event_name`.`SUM_TIMER_WAIT` AS `total_latency`,`performance_schema`.`file_summary_by_event_name`.`AVG_TIMER_WAIT` AS `avg_latency`,`performance_schema`.`file_summary_by_event_name`.`MAX_TIMER_WAIT` AS `max_latency`,`performance_schema`.`file_summary_by_event_name`.`SUM_TIMER_READ` AS `read_latency`,`performance_schema`.`file_summary_by_event_name`.`SUM_TIMER_WRITE` AS `write_latency`,`performance_schema`.`file_summary_by_event_name`.`SUM_TIMER_MISC` AS `misc_latency`,`performance_schema`.`file_summary_by_event_name`.`COUNT_READ` AS `count_read`,`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_READ` AS `total_read`,ifnull((`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_READ` / nullif(`performance_schema`.`file_summary_by_event_name`.`COUNT_READ`,0)),0) AS `avg_read`,`performance_schema`.`file_summary_by_event_name`.`COUNT_WRITE` AS `count_write`,`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_WRITE` AS `total_written`,ifnull((`performance_schema`.`file_summary_by_event_name`.`SUM_NUMBER_OF_BYTES_WRITE` / nullif(`performance_schema`.`file_summary_by_event_name`.`COUNT_WRITE`,0)),0) AS `avg_written` from `performance_schema`.`file_summary_by_event_name` where ((`performance_schema`.`file_summary_by_event_name`.`EVENT_NAME` like 'wait/io/file/%') and (`performance_schema`.`file_summary_by_event_name`.`COUNT_STAR` > 0)) order by `performance_schema`.`file_summary_by_event_name`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$latest_file_io`
--
DROP TABLE IF EXISTS `x$latest_file_io`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$latest_file_io` AS select if(isnull(`information_schema`.`processlist`.`ID`),concat(substring_index(`performance_schema`.`threads`.`NAME`,'/',-(1)),':',`performance_schema`.`events_waits_history_long`.`THREAD_ID`),concat(`information_schema`.`processlist`.`USER`,'@',`information_schema`.`processlist`.`HOST`,':',`information_schema`.`processlist`.`ID`)) AS `thread`,`performance_schema`.`events_waits_history_long`.`OBJECT_NAME` AS `file`,`performance_schema`.`events_waits_history_long`.`TIMER_WAIT` AS `latency`,`performance_schema`.`events_waits_history_long`.`OPERATION` AS `operation`,`performance_schema`.`events_waits_history_long`.`NUMBER_OF_BYTES` AS `requested` from ((`performance_schema`.`events_waits_history_long` join `performance_schema`.`threads` on((`performance_schema`.`events_waits_history_long`.`THREAD_ID` = `performance_schema`.`threads`.`THREAD_ID`))) left join `information_schema`.`processlist` on((`performance_schema`.`threads`.`PROCESSLIST_ID` = `information_schema`.`processlist`.`ID`))) where ((`performance_schema`.`events_waits_history_long`.`OBJECT_NAME` is not null) and (`performance_schema`.`events_waits_history_long`.`EVENT_NAME` like 'wait/io/file/%')) order by `performance_schema`.`events_waits_history_long`.`TIMER_START`;

-- --------------------------------------------------------

--
-- Structure for view `x$memory_by_host_by_current_bytes`
--
DROP TABLE IF EXISTS `x$memory_by_host_by_current_bytes`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$memory_by_host_by_current_bytes` AS select if(isnull(`performance_schema`.`memory_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`memory_summary_by_host_by_event_name`.`HOST`) AS `host`,sum(`performance_schema`.`memory_summary_by_host_by_event_name`.`CURRENT_COUNT_USED`) AS `current_count_used`,sum(`performance_schema`.`memory_summary_by_host_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`) AS `current_allocated`,ifnull((sum(`performance_schema`.`memory_summary_by_host_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`) / nullif(sum(`performance_schema`.`memory_summary_by_host_by_event_name`.`CURRENT_COUNT_USED`),0)),0) AS `current_avg_alloc`,max(`performance_schema`.`memory_summary_by_host_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`) AS `current_max_alloc`,sum(`performance_schema`.`memory_summary_by_host_by_event_name`.`SUM_NUMBER_OF_BYTES_ALLOC`) AS `total_allocated` from `performance_schema`.`memory_summary_by_host_by_event_name` group by if(isnull(`performance_schema`.`memory_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`memory_summary_by_host_by_event_name`.`HOST`) order by sum(`performance_schema`.`memory_summary_by_host_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`) desc;

-- --------------------------------------------------------

--
-- Structure for view `x$memory_by_thread_by_current_bytes`
--
DROP TABLE IF EXISTS `x$memory_by_thread_by_current_bytes`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$memory_by_thread_by_current_bytes` AS select `t`.`THREAD_ID` AS `thread_id`,if((`t`.`NAME` = 'thread/sql/one_connection'),concat(`t`.`PROCESSLIST_USER`,'@',`t`.`PROCESSLIST_HOST`),replace(`t`.`NAME`,'thread/','')) AS `user`,sum(`mt`.`CURRENT_COUNT_USED`) AS `current_count_used`,sum(`mt`.`CURRENT_NUMBER_OF_BYTES_USED`) AS `current_allocated`,ifnull((sum(`mt`.`CURRENT_NUMBER_OF_BYTES_USED`) / nullif(sum(`mt`.`CURRENT_COUNT_USED`),0)),0) AS `current_avg_alloc`,max(`mt`.`CURRENT_NUMBER_OF_BYTES_USED`) AS `current_max_alloc`,sum(`mt`.`SUM_NUMBER_OF_BYTES_ALLOC`) AS `total_allocated` from (`performance_schema`.`memory_summary_by_thread_by_event_name` `mt` join `performance_schema`.`threads` `t` on((`mt`.`THREAD_ID` = `t`.`THREAD_ID`))) group by `t`.`THREAD_ID`,if((`t`.`NAME` = 'thread/sql/one_connection'),concat(`t`.`PROCESSLIST_USER`,'@',`t`.`PROCESSLIST_HOST`),replace(`t`.`NAME`,'thread/','')) order by sum(`mt`.`CURRENT_NUMBER_OF_BYTES_USED`) desc;

-- --------------------------------------------------------

--
-- Structure for view `x$memory_by_user_by_current_bytes`
--
DROP TABLE IF EXISTS `x$memory_by_user_by_current_bytes`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$memory_by_user_by_current_bytes` AS select if(isnull(`performance_schema`.`memory_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`memory_summary_by_user_by_event_name`.`USER`) AS `user`,sum(`performance_schema`.`memory_summary_by_user_by_event_name`.`CURRENT_COUNT_USED`) AS `current_count_used`,sum(`performance_schema`.`memory_summary_by_user_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`) AS `current_allocated`,ifnull((sum(`performance_schema`.`memory_summary_by_user_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`) / nullif(sum(`performance_schema`.`memory_summary_by_user_by_event_name`.`CURRENT_COUNT_USED`),0)),0) AS `current_avg_alloc`,max(`performance_schema`.`memory_summary_by_user_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`) AS `current_max_alloc`,sum(`performance_schema`.`memory_summary_by_user_by_event_name`.`SUM_NUMBER_OF_BYTES_ALLOC`) AS `total_allocated` from `performance_schema`.`memory_summary_by_user_by_event_name` group by if(isnull(`performance_schema`.`memory_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`memory_summary_by_user_by_event_name`.`USER`) order by sum(`performance_schema`.`memory_summary_by_user_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`) desc;

-- --------------------------------------------------------

--
-- Structure for view `x$memory_global_by_current_bytes`
--
DROP TABLE IF EXISTS `x$memory_global_by_current_bytes`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$memory_global_by_current_bytes` AS select `performance_schema`.`memory_summary_global_by_event_name`.`EVENT_NAME` AS `event_name`,`performance_schema`.`memory_summary_global_by_event_name`.`CURRENT_COUNT_USED` AS `current_count`,`performance_schema`.`memory_summary_global_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED` AS `current_alloc`,ifnull((`performance_schema`.`memory_summary_global_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED` / nullif(`performance_schema`.`memory_summary_global_by_event_name`.`CURRENT_COUNT_USED`,0)),0) AS `current_avg_alloc`,`performance_schema`.`memory_summary_global_by_event_name`.`HIGH_COUNT_USED` AS `high_count`,`performance_schema`.`memory_summary_global_by_event_name`.`HIGH_NUMBER_OF_BYTES_USED` AS `high_alloc`,ifnull((`performance_schema`.`memory_summary_global_by_event_name`.`HIGH_NUMBER_OF_BYTES_USED` / nullif(`performance_schema`.`memory_summary_global_by_event_name`.`HIGH_COUNT_USED`,0)),0) AS `high_avg_alloc` from `performance_schema`.`memory_summary_global_by_event_name` where (`performance_schema`.`memory_summary_global_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED` > 0) order by `performance_schema`.`memory_summary_global_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$memory_global_total`
--
DROP TABLE IF EXISTS `x$memory_global_total`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$memory_global_total` AS select sum(`performance_schema`.`memory_summary_global_by_event_name`.`CURRENT_NUMBER_OF_BYTES_USED`) AS `total_allocated` from `performance_schema`.`memory_summary_global_by_event_name`;

-- --------------------------------------------------------

--
-- Structure for view `x$processlist`
--
DROP TABLE IF EXISTS `x$processlist`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$processlist` AS select `pps`.`THREAD_ID` AS `thd_id`,`pps`.`PROCESSLIST_ID` AS `conn_id`,if((`pps`.`NAME` = 'thread/sql/one_connection'),concat(`pps`.`PROCESSLIST_USER`,'@',`pps`.`PROCESSLIST_HOST`),replace(`pps`.`NAME`,'thread/','')) AS `user`,`pps`.`PROCESSLIST_DB` AS `db`,`pps`.`PROCESSLIST_COMMAND` AS `command`,`pps`.`PROCESSLIST_STATE` AS `state`,`pps`.`PROCESSLIST_TIME` AS `time`,`pps`.`PROCESSLIST_INFO` AS `current_statement`,if(isnull(`esc`.`END_EVENT_ID`),`esc`.`TIMER_WAIT`,NULL) AS `statement_latency`,if(isnull(`esc`.`END_EVENT_ID`),round((100 * (`estc`.`WORK_COMPLETED` / `estc`.`WORK_ESTIMATED`)),2),NULL) AS `progress`,`esc`.`LOCK_TIME` AS `lock_latency`,`esc`.`ROWS_EXAMINED` AS `rows_examined`,`esc`.`ROWS_SENT` AS `rows_sent`,`esc`.`ROWS_AFFECTED` AS `rows_affected`,`esc`.`CREATED_TMP_TABLES` AS `tmp_tables`,`esc`.`CREATED_TMP_DISK_TABLES` AS `tmp_disk_tables`,if(((`esc`.`NO_GOOD_INDEX_USED` > 0) or (`esc`.`NO_INDEX_USED` > 0)),'YES','NO') AS `full_scan`,if((`esc`.`END_EVENT_ID` is not null),`esc`.`SQL_TEXT`,NULL) AS `last_statement`,if((`esc`.`END_EVENT_ID` is not null),`esc`.`TIMER_WAIT`,NULL) AS `last_statement_latency`,`mem`.`current_allocated` AS `current_memory`,`ewc`.`EVENT_NAME` AS `last_wait`,if((isnull(`ewc`.`END_EVENT_ID`) and (`ewc`.`EVENT_NAME` is not null)),'Still Waiting',`ewc`.`TIMER_WAIT`) AS `last_wait_latency`,`ewc`.`SOURCE` AS `source`,`etc`.`TIMER_WAIT` AS `trx_latency`,`etc`.`STATE` AS `trx_state`,`etc`.`AUTOCOMMIT` AS `trx_autocommit`,`conattr_pid`.`ATTR_VALUE` AS `pid`,`conattr_progname`.`ATTR_VALUE` AS `program_name` from (((((((`performance_schema`.`threads` `pps` left join `performance_schema`.`events_waits_current` `ewc` on((`pps`.`THREAD_ID` = `ewc`.`THREAD_ID`))) left join `performance_schema`.`events_stages_current` `estc` on((`pps`.`THREAD_ID` = `estc`.`THREAD_ID`))) left join `performance_schema`.`events_statements_current` `esc` on((`pps`.`THREAD_ID` = `esc`.`THREAD_ID`))) left join `performance_schema`.`events_transactions_current` `etc` on((`pps`.`THREAD_ID` = `etc`.`THREAD_ID`))) left join `x$memory_by_thread_by_current_bytes` `mem` on((`pps`.`THREAD_ID` = `mem`.`thread_id`))) left join `performance_schema`.`session_connect_attrs` `conattr_pid` on(((`conattr_pid`.`PROCESSLIST_ID` = `pps`.`PROCESSLIST_ID`) and (`conattr_pid`.`ATTR_NAME` = '_pid')))) left join `performance_schema`.`session_connect_attrs` `conattr_progname` on(((`conattr_progname`.`PROCESSLIST_ID` = `pps`.`PROCESSLIST_ID`) and (`conattr_progname`.`ATTR_NAME` = 'program_name')))) order by `pps`.`PROCESSLIST_TIME` desc,`last_wait_latency` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$ps_digest_95th_percentile_by_avg_us`
--
DROP TABLE IF EXISTS `x$ps_digest_95th_percentile_by_avg_us`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$ps_digest_95th_percentile_by_avg_us` AS select `s2`.`avg_us` AS `avg_us`,ifnull((sum(`s1`.`cnt`) / nullif((select count(0) from `performance_schema`.`events_statements_summary_by_digest`),0)),0) AS `percentile` from (`x$ps_digest_avg_latency_distribution` `s1` join `x$ps_digest_avg_latency_distribution` `s2` on((`s1`.`avg_us` <= `s2`.`avg_us`))) group by `s2`.`avg_us` having (ifnull((sum(`s1`.`cnt`) / nullif((select count(0) from `performance_schema`.`events_statements_summary_by_digest`),0)),0) > 0.95) order by `percentile` limit 1;

-- --------------------------------------------------------

--
-- Structure for view `x$ps_digest_avg_latency_distribution`
--
DROP TABLE IF EXISTS `x$ps_digest_avg_latency_distribution`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$ps_digest_avg_latency_distribution` AS select count(0) AS `cnt`,round((`performance_schema`.`events_statements_summary_by_digest`.`AVG_TIMER_WAIT` / 1000000),0) AS `avg_us` from `performance_schema`.`events_statements_summary_by_digest` group by `avg_us`;

-- --------------------------------------------------------

--
-- Structure for view `x$ps_schema_table_statistics_io`
--
DROP TABLE IF EXISTS `x$ps_schema_table_statistics_io`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$ps_schema_table_statistics_io` AS select `extract_schema_from_file_name`(`performance_schema`.`file_summary_by_instance`.`FILE_NAME`) AS `table_schema`,`extract_table_from_file_name`(`performance_schema`.`file_summary_by_instance`.`FILE_NAME`) AS `table_name`,sum(`performance_schema`.`file_summary_by_instance`.`COUNT_READ`) AS `count_read`,sum(`performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_READ`) AS `sum_number_of_bytes_read`,sum(`performance_schema`.`file_summary_by_instance`.`SUM_TIMER_READ`) AS `sum_timer_read`,sum(`performance_schema`.`file_summary_by_instance`.`COUNT_WRITE`) AS `count_write`,sum(`performance_schema`.`file_summary_by_instance`.`SUM_NUMBER_OF_BYTES_WRITE`) AS `sum_number_of_bytes_write`,sum(`performance_schema`.`file_summary_by_instance`.`SUM_TIMER_WRITE`) AS `sum_timer_write`,sum(`performance_schema`.`file_summary_by_instance`.`COUNT_MISC`) AS `count_misc`,sum(`performance_schema`.`file_summary_by_instance`.`SUM_TIMER_MISC`) AS `sum_timer_misc` from `performance_schema`.`file_summary_by_instance` group by `table_schema`,`table_name`;

-- --------------------------------------------------------

--
-- Structure for view `x$schema_flattened_keys`
--
DROP TABLE IF EXISTS `x$schema_flattened_keys`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$schema_flattened_keys` AS select `information_schema`.`STATISTICS`.`TABLE_SCHEMA` AS `table_schema`,`information_schema`.`STATISTICS`.`TABLE_NAME` AS `table_name`,`information_schema`.`STATISTICS`.`INDEX_NAME` AS `index_name`,max(`information_schema`.`STATISTICS`.`NON_UNIQUE`) AS `non_unique`,max(if(isnull(`information_schema`.`STATISTICS`.`SUB_PART`),0,1)) AS `subpart_exists`,group_concat(`information_schema`.`STATISTICS`.`COLUMN_NAME` order by `information_schema`.`STATISTICS`.`SEQ_IN_INDEX` ASC separator ',') AS `index_columns` from `INFORMATION_SCHEMA`.`STATISTICS` where ((`information_schema`.`STATISTICS`.`INDEX_TYPE` = 'BTREE') and (`information_schema`.`STATISTICS`.`TABLE_SCHEMA` not in ('mysql','sys','INFORMATION_SCHEMA','PERFORMANCE_SCHEMA'))) group by `information_schema`.`STATISTICS`.`TABLE_SCHEMA`,`information_schema`.`STATISTICS`.`TABLE_NAME`,`information_schema`.`STATISTICS`.`INDEX_NAME`;

-- --------------------------------------------------------

--
-- Structure for view `x$schema_index_statistics`
--
DROP TABLE IF EXISTS `x$schema_index_statistics`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$schema_index_statistics` AS select `performance_schema`.`table_io_waits_summary_by_index_usage`.`OBJECT_SCHEMA` AS `table_schema`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`OBJECT_NAME` AS `table_name`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`INDEX_NAME` AS `index_name`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`COUNT_FETCH` AS `rows_selected`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`SUM_TIMER_FETCH` AS `select_latency`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`COUNT_INSERT` AS `rows_inserted`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`SUM_TIMER_INSERT` AS `insert_latency`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`COUNT_UPDATE` AS `rows_updated`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`SUM_TIMER_UPDATE` AS `update_latency`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`COUNT_DELETE` AS `rows_deleted`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`SUM_TIMER_INSERT` AS `delete_latency` from `performance_schema`.`table_io_waits_summary_by_index_usage` where (`performance_schema`.`table_io_waits_summary_by_index_usage`.`INDEX_NAME` is not null) order by `performance_schema`.`table_io_waits_summary_by_index_usage`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$schema_table_lock_waits`
--
DROP TABLE IF EXISTS `x$schema_table_lock_waits`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$schema_table_lock_waits` AS select `g`.`OBJECT_SCHEMA` AS `object_schema`,`g`.`OBJECT_NAME` AS `object_name`,`pt`.`THREAD_ID` AS `waiting_thread_id`,`pt`.`PROCESSLIST_ID` AS `waiting_pid`,`ps_thread_account`(`p`.`OWNER_THREAD_ID`) AS `waiting_account`,`p`.`LOCK_TYPE` AS `waiting_lock_type`,`p`.`LOCK_DURATION` AS `waiting_lock_duration`,`pt`.`PROCESSLIST_INFO` AS `waiting_query`,`pt`.`PROCESSLIST_TIME` AS `waiting_query_secs`,`ps`.`ROWS_AFFECTED` AS `waiting_query_rows_affected`,`ps`.`ROWS_EXAMINED` AS `waiting_query_rows_examined`,`gt`.`THREAD_ID` AS `blocking_thread_id`,`gt`.`PROCESSLIST_ID` AS `blocking_pid`,`ps_thread_account`(`g`.`OWNER_THREAD_ID`) AS `blocking_account`,`g`.`LOCK_TYPE` AS `blocking_lock_type`,`g`.`LOCK_DURATION` AS `blocking_lock_duration`,concat('KILL QUERY ',`gt`.`PROCESSLIST_ID`) AS `sql_kill_blocking_query`,concat('KILL ',`gt`.`PROCESSLIST_ID`) AS `sql_kill_blocking_connection` from (((((`performance_schema`.`metadata_locks` `g` join `performance_schema`.`metadata_locks` `p` on(((`g`.`OBJECT_TYPE` = `p`.`OBJECT_TYPE`) and (`g`.`OBJECT_SCHEMA` = `p`.`OBJECT_SCHEMA`) and (`g`.`OBJECT_NAME` = `p`.`OBJECT_NAME`) and (`g`.`LOCK_STATUS` = 'GRANTED') and (`p`.`LOCK_STATUS` = 'PENDING')))) join `performance_schema`.`threads` `gt` on((`g`.`OWNER_THREAD_ID` = `gt`.`THREAD_ID`))) join `performance_schema`.`threads` `pt` on((`p`.`OWNER_THREAD_ID` = `pt`.`THREAD_ID`))) left join `performance_schema`.`events_statements_current` `gs` on((`g`.`OWNER_THREAD_ID` = `gs`.`THREAD_ID`))) left join `performance_schema`.`events_statements_current` `ps` on((`p`.`OWNER_THREAD_ID` = `ps`.`THREAD_ID`))) where (`g`.`OBJECT_TYPE` = 'TABLE');

-- --------------------------------------------------------

--
-- Structure for view `x$schema_table_statistics`
--
DROP TABLE IF EXISTS `x$schema_table_statistics`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$schema_table_statistics` AS select `pst`.`OBJECT_SCHEMA` AS `table_schema`,`pst`.`OBJECT_NAME` AS `table_name`,`pst`.`SUM_TIMER_WAIT` AS `total_latency`,`pst`.`COUNT_FETCH` AS `rows_fetched`,`pst`.`SUM_TIMER_FETCH` AS `fetch_latency`,`pst`.`COUNT_INSERT` AS `rows_inserted`,`pst`.`SUM_TIMER_INSERT` AS `insert_latency`,`pst`.`COUNT_UPDATE` AS `rows_updated`,`pst`.`SUM_TIMER_UPDATE` AS `update_latency`,`pst`.`COUNT_DELETE` AS `rows_deleted`,`pst`.`SUM_TIMER_DELETE` AS `delete_latency`,`fsbi`.`count_read` AS `io_read_requests`,`fsbi`.`sum_number_of_bytes_read` AS `io_read`,`fsbi`.`sum_timer_read` AS `io_read_latency`,`fsbi`.`count_write` AS `io_write_requests`,`fsbi`.`sum_number_of_bytes_write` AS `io_write`,`fsbi`.`sum_timer_write` AS `io_write_latency`,`fsbi`.`count_misc` AS `io_misc_requests`,`fsbi`.`sum_timer_misc` AS `io_misc_latency` from (`performance_schema`.`table_io_waits_summary_by_table` `pst` left join `x$ps_schema_table_statistics_io` `fsbi` on(((`pst`.`OBJECT_SCHEMA` = `fsbi`.`table_schema`) and (`pst`.`OBJECT_NAME` = `fsbi`.`table_name`)))) order by `pst`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$schema_table_statistics_with_buffer`
--
DROP TABLE IF EXISTS `x$schema_table_statistics_with_buffer`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$schema_table_statistics_with_buffer` AS select `pst`.`OBJECT_SCHEMA` AS `table_schema`,`pst`.`OBJECT_NAME` AS `table_name`,`pst`.`COUNT_FETCH` AS `rows_fetched`,`pst`.`SUM_TIMER_FETCH` AS `fetch_latency`,`pst`.`COUNT_INSERT` AS `rows_inserted`,`pst`.`SUM_TIMER_INSERT` AS `insert_latency`,`pst`.`COUNT_UPDATE` AS `rows_updated`,`pst`.`SUM_TIMER_UPDATE` AS `update_latency`,`pst`.`COUNT_DELETE` AS `rows_deleted`,`pst`.`SUM_TIMER_DELETE` AS `delete_latency`,`fsbi`.`count_read` AS `io_read_requests`,`fsbi`.`sum_number_of_bytes_read` AS `io_read`,`fsbi`.`sum_timer_read` AS `io_read_latency`,`fsbi`.`count_write` AS `io_write_requests`,`fsbi`.`sum_number_of_bytes_write` AS `io_write`,`fsbi`.`sum_timer_write` AS `io_write_latency`,`fsbi`.`count_misc` AS `io_misc_requests`,`fsbi`.`sum_timer_misc` AS `io_misc_latency`,`ibp`.`allocated` AS `innodb_buffer_allocated`,`ibp`.`data` AS `innodb_buffer_data`,(`ibp`.`allocated` - `ibp`.`data`) AS `innodb_buffer_free`,`ibp`.`pages` AS `innodb_buffer_pages`,`ibp`.`pages_hashed` AS `innodb_buffer_pages_hashed`,`ibp`.`pages_old` AS `innodb_buffer_pages_old`,`ibp`.`rows_cached` AS `innodb_buffer_rows_cached` from ((`performance_schema`.`table_io_waits_summary_by_table` `pst` left join `x$ps_schema_table_statistics_io` `fsbi` on(((`pst`.`OBJECT_SCHEMA` = `fsbi`.`table_schema`) and (`pst`.`OBJECT_NAME` = `fsbi`.`table_name`)))) left join `x$innodb_buffer_stats_by_table` `ibp` on(((`pst`.`OBJECT_SCHEMA` = `ibp`.`object_schema`) and (`pst`.`OBJECT_NAME` = `ibp`.`object_name`)))) order by `pst`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$schema_tables_with_full_table_scans`
--
DROP TABLE IF EXISTS `x$schema_tables_with_full_table_scans`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$schema_tables_with_full_table_scans` AS select `performance_schema`.`table_io_waits_summary_by_index_usage`.`OBJECT_SCHEMA` AS `object_schema`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`OBJECT_NAME` AS `object_name`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`COUNT_READ` AS `rows_full_scanned`,`performance_schema`.`table_io_waits_summary_by_index_usage`.`SUM_TIMER_WAIT` AS `latency` from `performance_schema`.`table_io_waits_summary_by_index_usage` where (isnull(`performance_schema`.`table_io_waits_summary_by_index_usage`.`INDEX_NAME`) and (`performance_schema`.`table_io_waits_summary_by_index_usage`.`COUNT_READ` > 0)) order by `performance_schema`.`table_io_waits_summary_by_index_usage`.`COUNT_READ` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$session`
--
DROP TABLE IF EXISTS `x$session`;

CREATE ALGORITHM=UNDEFINED DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$session` AS select `x$processlist`.`thd_id` AS `thd_id`,`x$processlist`.`conn_id` AS `conn_id`,`x$processlist`.`user` AS `user`,`x$processlist`.`db` AS `db`,`x$processlist`.`command` AS `command`,`x$processlist`.`state` AS `state`,`x$processlist`.`time` AS `time`,`x$processlist`.`current_statement` AS `current_statement`,`x$processlist`.`statement_latency` AS `statement_latency`,`x$processlist`.`progress` AS `progress`,`x$processlist`.`lock_latency` AS `lock_latency`,`x$processlist`.`rows_examined` AS `rows_examined`,`x$processlist`.`rows_sent` AS `rows_sent`,`x$processlist`.`rows_affected` AS `rows_affected`,`x$processlist`.`tmp_tables` AS `tmp_tables`,`x$processlist`.`tmp_disk_tables` AS `tmp_disk_tables`,`x$processlist`.`full_scan` AS `full_scan`,`x$processlist`.`last_statement` AS `last_statement`,`x$processlist`.`last_statement_latency` AS `last_statement_latency`,`x$processlist`.`current_memory` AS `current_memory`,`x$processlist`.`last_wait` AS `last_wait`,`x$processlist`.`last_wait_latency` AS `last_wait_latency`,`x$processlist`.`source` AS `source`,`x$processlist`.`trx_latency` AS `trx_latency`,`x$processlist`.`trx_state` AS `trx_state`,`x$processlist`.`trx_autocommit` AS `trx_autocommit`,`x$processlist`.`pid` AS `pid`,`x$processlist`.`program_name` AS `program_name` from `x$processlist` where ((`x$processlist`.`conn_id` is not null) and (`x$processlist`.`command` <> 'Daemon'));

-- --------------------------------------------------------

--
-- Structure for view `x$statement_analysis`
--
DROP TABLE IF EXISTS `x$statement_analysis`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$statement_analysis` AS select `performance_schema`.`events_statements_summary_by_digest`.`DIGEST_TEXT` AS `query`,`performance_schema`.`events_statements_summary_by_digest`.`SCHEMA_NAME` AS `db`,if(((`performance_schema`.`events_statements_summary_by_digest`.`SUM_NO_GOOD_INDEX_USED` > 0) or (`performance_schema`.`events_statements_summary_by_digest`.`SUM_NO_INDEX_USED` > 0)),'*','') AS `full_scan`,`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR` AS `exec_count`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_ERRORS` AS `err_count`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_WARNINGS` AS `warn_count`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_TIMER_WAIT` AS `total_latency`,`performance_schema`.`events_statements_summary_by_digest`.`MAX_TIMER_WAIT` AS `max_latency`,`performance_schema`.`events_statements_summary_by_digest`.`AVG_TIMER_WAIT` AS `avg_latency`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_LOCK_TIME` AS `lock_latency`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_SENT` AS `rows_sent`,round(ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_SENT` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0),0) AS `rows_sent_avg`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_EXAMINED` AS `rows_examined`,round(ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_EXAMINED` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0),0) AS `rows_examined_avg`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_AFFECTED` AS `rows_affected`,round(ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_AFFECTED` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0),0) AS `rows_affected_avg`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_TABLES` AS `tmp_tables`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_DISK_TABLES` AS `tmp_disk_tables`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_SORT_ROWS` AS `rows_sorted`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_SORT_MERGE_PASSES` AS `sort_merge_passes`,`performance_schema`.`events_statements_summary_by_digest`.`DIGEST` AS `digest`,`performance_schema`.`events_statements_summary_by_digest`.`FIRST_SEEN` AS `first_seen`,`performance_schema`.`events_statements_summary_by_digest`.`LAST_SEEN` AS `last_seen` from `performance_schema`.`events_statements_summary_by_digest` order by `performance_schema`.`events_statements_summary_by_digest`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$statements_with_errors_or_warnings`
--
DROP TABLE IF EXISTS `x$statements_with_errors_or_warnings`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$statements_with_errors_or_warnings` AS select `performance_schema`.`events_statements_summary_by_digest`.`DIGEST_TEXT` AS `query`,`performance_schema`.`events_statements_summary_by_digest`.`SCHEMA_NAME` AS `db`,`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR` AS `exec_count`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_ERRORS` AS `errors`,(ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_ERRORS` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0) * 100) AS `error_pct`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_WARNINGS` AS `warnings`,(ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_WARNINGS` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0) * 100) AS `warning_pct`,`performance_schema`.`events_statements_summary_by_digest`.`FIRST_SEEN` AS `first_seen`,`performance_schema`.`events_statements_summary_by_digest`.`LAST_SEEN` AS `last_seen`,`performance_schema`.`events_statements_summary_by_digest`.`DIGEST` AS `digest` from `performance_schema`.`events_statements_summary_by_digest` where ((`performance_schema`.`events_statements_summary_by_digest`.`SUM_ERRORS` > 0) or (`performance_schema`.`events_statements_summary_by_digest`.`SUM_WARNINGS` > 0)) order by `performance_schema`.`events_statements_summary_by_digest`.`SUM_ERRORS` desc,`performance_schema`.`events_statements_summary_by_digest`.`SUM_WARNINGS` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$statements_with_full_table_scans`
--
DROP TABLE IF EXISTS `x$statements_with_full_table_scans`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$statements_with_full_table_scans` AS select `performance_schema`.`events_statements_summary_by_digest`.`DIGEST_TEXT` AS `query`,`performance_schema`.`events_statements_summary_by_digest`.`SCHEMA_NAME` AS `db`,`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR` AS `exec_count`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_TIMER_WAIT` AS `total_latency`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_NO_INDEX_USED` AS `no_index_used_count`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_NO_GOOD_INDEX_USED` AS `no_good_index_used_count`,round((ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_NO_INDEX_USED` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0) * 100),0) AS `no_index_used_pct`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_SENT` AS `rows_sent`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_EXAMINED` AS `rows_examined`,round((`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_SENT` / `performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`),0) AS `rows_sent_avg`,round((`performance_schema`.`events_statements_summary_by_digest`.`SUM_ROWS_EXAMINED` / `performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`),0) AS `rows_examined_avg`,`performance_schema`.`events_statements_summary_by_digest`.`FIRST_SEEN` AS `first_seen`,`performance_schema`.`events_statements_summary_by_digest`.`LAST_SEEN` AS `last_seen`,`performance_schema`.`events_statements_summary_by_digest`.`DIGEST` AS `digest` from `performance_schema`.`events_statements_summary_by_digest` where (((`performance_schema`.`events_statements_summary_by_digest`.`SUM_NO_INDEX_USED` > 0) or (`performance_schema`.`events_statements_summary_by_digest`.`SUM_NO_GOOD_INDEX_USED` > 0)) and (not((`performance_schema`.`events_statements_summary_by_digest`.`DIGEST_TEXT` like 'SHOW%')))) order by round((ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_NO_INDEX_USED` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0) * 100),0) desc,`performance_schema`.`events_statements_summary_by_digest`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$statements_with_runtimes_in_95th_percentile`
--
DROP TABLE IF EXISTS `x$statements_with_runtimes_in_95th_percentile`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$statements_with_runtimes_in_95th_percentile` AS select `stmts`.`DIGEST_TEXT` AS `query`,`stmts`.`SCHEMA_NAME` AS `db`,if(((`stmts`.`SUM_NO_GOOD_INDEX_USED` > 0) or (`stmts`.`SUM_NO_INDEX_USED` > 0)),'*','') AS `full_scan`,`stmts`.`COUNT_STAR` AS `exec_count`,`stmts`.`SUM_ERRORS` AS `err_count`,`stmts`.`SUM_WARNINGS` AS `warn_count`,`stmts`.`SUM_TIMER_WAIT` AS `total_latency`,`stmts`.`MAX_TIMER_WAIT` AS `max_latency`,`stmts`.`AVG_TIMER_WAIT` AS `avg_latency`,`stmts`.`SUM_ROWS_SENT` AS `rows_sent`,round(ifnull((`stmts`.`SUM_ROWS_SENT` / nullif(`stmts`.`COUNT_STAR`,0)),0),0) AS `rows_sent_avg`,`stmts`.`SUM_ROWS_EXAMINED` AS `rows_examined`,round(ifnull((`stmts`.`SUM_ROWS_EXAMINED` / nullif(`stmts`.`COUNT_STAR`,0)),0),0) AS `rows_examined_avg`,`stmts`.`FIRST_SEEN` AS `first_seen`,`stmts`.`LAST_SEEN` AS `last_seen`,`stmts`.`DIGEST` AS `digest` from (`performance_schema`.`events_statements_summary_by_digest` `stmts` join `x$ps_digest_95th_percentile_by_avg_us` `top_percentile` on((round((`stmts`.`AVG_TIMER_WAIT` / 1000000),0) >= `top_percentile`.`avg_us`))) order by `stmts`.`AVG_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$statements_with_sorting`
--
DROP TABLE IF EXISTS `x$statements_with_sorting`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$statements_with_sorting` AS select `performance_schema`.`events_statements_summary_by_digest`.`DIGEST_TEXT` AS `query`,`performance_schema`.`events_statements_summary_by_digest`.`SCHEMA_NAME` AS `db`,`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR` AS `exec_count`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_TIMER_WAIT` AS `total_latency`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_SORT_MERGE_PASSES` AS `sort_merge_passes`,round(ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_SORT_MERGE_PASSES` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0),0) AS `avg_sort_merges`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_SORT_SCAN` AS `sorts_using_scans`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_SORT_RANGE` AS `sort_using_range`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_SORT_ROWS` AS `rows_sorted`,round(ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_SORT_ROWS` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0),0) AS `avg_rows_sorted`,`performance_schema`.`events_statements_summary_by_digest`.`FIRST_SEEN` AS `first_seen`,`performance_schema`.`events_statements_summary_by_digest`.`LAST_SEEN` AS `last_seen`,`performance_schema`.`events_statements_summary_by_digest`.`DIGEST` AS `digest` from `performance_schema`.`events_statements_summary_by_digest` where (`performance_schema`.`events_statements_summary_by_digest`.`SUM_SORT_ROWS` > 0) order by `performance_schema`.`events_statements_summary_by_digest`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$statements_with_temp_tables`
--
DROP TABLE IF EXISTS `x$statements_with_temp_tables`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$statements_with_temp_tables` AS select `performance_schema`.`events_statements_summary_by_digest`.`DIGEST_TEXT` AS `query`,`performance_schema`.`events_statements_summary_by_digest`.`SCHEMA_NAME` AS `db`,`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR` AS `exec_count`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_TIMER_WAIT` AS `total_latency`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_TABLES` AS `memory_tmp_tables`,`performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_DISK_TABLES` AS `disk_tmp_tables`,round(ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_TABLES` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`COUNT_STAR`,0)),0),0) AS `avg_tmp_tables_per_query`,round((ifnull((`performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_DISK_TABLES` / nullif(`performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_TABLES`,0)),0) * 100),0) AS `tmp_tables_to_disk_pct`,`performance_schema`.`events_statements_summary_by_digest`.`FIRST_SEEN` AS `first_seen`,`performance_schema`.`events_statements_summary_by_digest`.`LAST_SEEN` AS `last_seen`,`performance_schema`.`events_statements_summary_by_digest`.`DIGEST` AS `digest` from `performance_schema`.`events_statements_summary_by_digest` where (`performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_TABLES` > 0) order by `performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_DISK_TABLES` desc,`performance_schema`.`events_statements_summary_by_digest`.`SUM_CREATED_TMP_TABLES` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$user_summary`
--
DROP TABLE IF EXISTS `x$user_summary`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$user_summary` AS select if(isnull(`performance_schema`.`accounts`.`USER`),'background',`performance_schema`.`accounts`.`USER`) AS `user`,sum(`stmt`.`total`) AS `statements`,sum(`stmt`.`total_latency`) AS `statement_latency`,ifnull((sum(`stmt`.`total_latency`) / nullif(sum(`stmt`.`total`),0)),0) AS `statement_avg_latency`,sum(`stmt`.`full_scans`) AS `table_scans`,sum(`io`.`ios`) AS `file_ios`,sum(`io`.`io_latency`) AS `file_io_latency`,sum(`performance_schema`.`accounts`.`CURRENT_CONNECTIONS`) AS `current_connections`,sum(`performance_schema`.`accounts`.`TOTAL_CONNECTIONS`) AS `total_connections`,count(distinct `performance_schema`.`accounts`.`HOST`) AS `unique_hosts`,sum(`mem`.`current_allocated`) AS `current_memory`,sum(`mem`.`total_allocated`) AS `total_memory_allocated` from (((`performance_schema`.`accounts` left join `x$user_summary_by_statement_latency` `stmt` on((if(isnull(`performance_schema`.`accounts`.`USER`),'background',`performance_schema`.`accounts`.`USER`) = `stmt`.`user`))) left join `x$user_summary_by_file_io` `io` on((if(isnull(`performance_schema`.`accounts`.`USER`),'background',`performance_schema`.`accounts`.`USER`) = `io`.`user`))) left join `x$memory_by_user_by_current_bytes` `mem` on((if(isnull(`performance_schema`.`accounts`.`USER`),'background',`performance_schema`.`accounts`.`USER`) = `mem`.`user`))) group by if(isnull(`performance_schema`.`accounts`.`USER`),'background',`performance_schema`.`accounts`.`USER`) order by sum(`stmt`.`total_latency`) desc;

-- --------------------------------------------------------

--
-- Structure for view `x$user_summary_by_file_io`
--
DROP TABLE IF EXISTS `x$user_summary_by_file_io`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$user_summary_by_file_io` AS select if(isnull(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`) AS `user`,sum(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`COUNT_STAR`) AS `ios`,sum(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`SUM_TIMER_WAIT`) AS `io_latency` from `performance_schema`.`events_waits_summary_by_user_by_event_name` where (`performance_schema`.`events_waits_summary_by_user_by_event_name`.`EVENT_NAME` like 'wait/io/file/%') group by if(isnull(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`) order by sum(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`SUM_TIMER_WAIT`) desc;

-- --------------------------------------------------------

--
-- Structure for view `x$user_summary_by_file_io_type`
--
DROP TABLE IF EXISTS `x$user_summary_by_file_io_type`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$user_summary_by_file_io_type` AS select if(isnull(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`) AS `user`,`performance_schema`.`events_waits_summary_by_user_by_event_name`.`EVENT_NAME` AS `event_name`,`performance_schema`.`events_waits_summary_by_user_by_event_name`.`COUNT_STAR` AS `total`,`performance_schema`.`events_waits_summary_by_user_by_event_name`.`SUM_TIMER_WAIT` AS `latency`,`performance_schema`.`events_waits_summary_by_user_by_event_name`.`MAX_TIMER_WAIT` AS `max_latency` from `performance_schema`.`events_waits_summary_by_user_by_event_name` where ((`performance_schema`.`events_waits_summary_by_user_by_event_name`.`EVENT_NAME` like 'wait/io/file%') and (`performance_schema`.`events_waits_summary_by_user_by_event_name`.`COUNT_STAR` > 0)) order by if(isnull(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`),`performance_schema`.`events_waits_summary_by_user_by_event_name`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$user_summary_by_stages`
--
DROP TABLE IF EXISTS `x$user_summary_by_stages`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$user_summary_by_stages` AS select if(isnull(`performance_schema`.`events_stages_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_stages_summary_by_user_by_event_name`.`USER`) AS `user`,`performance_schema`.`events_stages_summary_by_user_by_event_name`.`EVENT_NAME` AS `event_name`,`performance_schema`.`events_stages_summary_by_user_by_event_name`.`COUNT_STAR` AS `total`,`performance_schema`.`events_stages_summary_by_user_by_event_name`.`SUM_TIMER_WAIT` AS `total_latency`,`performance_schema`.`events_stages_summary_by_user_by_event_name`.`AVG_TIMER_WAIT` AS `avg_latency` from `performance_schema`.`events_stages_summary_by_user_by_event_name` where (`performance_schema`.`events_stages_summary_by_user_by_event_name`.`SUM_TIMER_WAIT` <> 0) order by if(isnull(`performance_schema`.`events_stages_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_stages_summary_by_user_by_event_name`.`USER`),`performance_schema`.`events_stages_summary_by_user_by_event_name`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$user_summary_by_statement_latency`
--
DROP TABLE IF EXISTS `x$user_summary_by_statement_latency`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$user_summary_by_statement_latency` AS select if(isnull(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_statements_summary_by_user_by_event_name`.`USER`) AS `user`,sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`COUNT_STAR`) AS `total`,sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_TIMER_WAIT`) AS `total_latency`,sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`MAX_TIMER_WAIT`) AS `max_latency`,sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_LOCK_TIME`) AS `lock_latency`,sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_ROWS_SENT`) AS `rows_sent`,sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_ROWS_EXAMINED`) AS `rows_examined`,sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_ROWS_AFFECTED`) AS `rows_affected`,(sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_NO_INDEX_USED`) + sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_NO_GOOD_INDEX_USED`)) AS `full_scans` from `performance_schema`.`events_statements_summary_by_user_by_event_name` group by if(isnull(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_statements_summary_by_user_by_event_name`.`USER`) order by sum(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_TIMER_WAIT`) desc;

-- --------------------------------------------------------

--
-- Structure for view `x$user_summary_by_statement_type`
--
DROP TABLE IF EXISTS `x$user_summary_by_statement_type`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$user_summary_by_statement_type` AS select if(isnull(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_statements_summary_by_user_by_event_name`.`USER`) AS `user`,substring_index(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`EVENT_NAME`,'/',-(1)) AS `statement`,`performance_schema`.`events_statements_summary_by_user_by_event_name`.`COUNT_STAR` AS `total`,`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_TIMER_WAIT` AS `total_latency`,`performance_schema`.`events_statements_summary_by_user_by_event_name`.`MAX_TIMER_WAIT` AS `max_latency`,`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_LOCK_TIME` AS `lock_latency`,`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_ROWS_SENT` AS `rows_sent`,`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_ROWS_EXAMINED` AS `rows_examined`,`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_ROWS_AFFECTED` AS `rows_affected`,(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_NO_INDEX_USED` + `performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_NO_GOOD_INDEX_USED`) AS `full_scans` from `performance_schema`.`events_statements_summary_by_user_by_event_name` where (`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_TIMER_WAIT` <> 0) order by if(isnull(`performance_schema`.`events_statements_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_statements_summary_by_user_by_event_name`.`USER`),`performance_schema`.`events_statements_summary_by_user_by_event_name`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$wait_classes_global_by_avg_latency`
--
DROP TABLE IF EXISTS `x$wait_classes_global_by_avg_latency`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$wait_classes_global_by_avg_latency` AS select substring_index(`performance_schema`.`events_waits_summary_global_by_event_name`.`EVENT_NAME`,'/',3) AS `event_class`,sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`COUNT_STAR`) AS `total`,sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT`) AS `total_latency`,min(`performance_schema`.`events_waits_summary_global_by_event_name`.`MIN_TIMER_WAIT`) AS `min_latency`,ifnull((sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT`) / nullif(sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`COUNT_STAR`),0)),0) AS `avg_latency`,max(`performance_schema`.`events_waits_summary_global_by_event_name`.`MAX_TIMER_WAIT`) AS `max_latency` from `performance_schema`.`events_waits_summary_global_by_event_name` where ((`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT` > 0) and (`performance_schema`.`events_waits_summary_global_by_event_name`.`EVENT_NAME` <> 'idle')) group by `event_class` order by ifnull((sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT`) / nullif(sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`COUNT_STAR`),0)),0) desc;

-- --------------------------------------------------------

--
-- Structure for view `x$wait_classes_global_by_latency`
--
DROP TABLE IF EXISTS `x$wait_classes_global_by_latency`;

CREATE ALGORITHM=TEMPTABLE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$wait_classes_global_by_latency` AS select substring_index(`performance_schema`.`events_waits_summary_global_by_event_name`.`EVENT_NAME`,'/',3) AS `event_class`,sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`COUNT_STAR`) AS `total`,sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT`) AS `total_latency`,min(`performance_schema`.`events_waits_summary_global_by_event_name`.`MIN_TIMER_WAIT`) AS `min_latency`,ifnull((sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT`) / nullif(sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`COUNT_STAR`),0)),0) AS `avg_latency`,max(`performance_schema`.`events_waits_summary_global_by_event_name`.`MAX_TIMER_WAIT`) AS `max_latency` from `performance_schema`.`events_waits_summary_global_by_event_name` where ((`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT` > 0) and (`performance_schema`.`events_waits_summary_global_by_event_name`.`EVENT_NAME` <> 'idle')) group by substring_index(`performance_schema`.`events_waits_summary_global_by_event_name`.`EVENT_NAME`,'/',3) order by sum(`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT`) desc;

-- --------------------------------------------------------

--
-- Structure for view `x$waits_by_host_by_latency`
--
DROP TABLE IF EXISTS `x$waits_by_host_by_latency`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$waits_by_host_by_latency` AS select if(isnull(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`) AS `host`,`performance_schema`.`events_waits_summary_by_host_by_event_name`.`EVENT_NAME` AS `event`,`performance_schema`.`events_waits_summary_by_host_by_event_name`.`COUNT_STAR` AS `total`,`performance_schema`.`events_waits_summary_by_host_by_event_name`.`SUM_TIMER_WAIT` AS `total_latency`,`performance_schema`.`events_waits_summary_by_host_by_event_name`.`AVG_TIMER_WAIT` AS `avg_latency`,`performance_schema`.`events_waits_summary_by_host_by_event_name`.`MAX_TIMER_WAIT` AS `max_latency` from `performance_schema`.`events_waits_summary_by_host_by_event_name` where ((`performance_schema`.`events_waits_summary_by_host_by_event_name`.`EVENT_NAME` <> 'idle') and (`performance_schema`.`events_waits_summary_by_host_by_event_name`.`SUM_TIMER_WAIT` > 0)) order by if(isnull(`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`),'background',`performance_schema`.`events_waits_summary_by_host_by_event_name`.`HOST`),`performance_schema`.`events_waits_summary_by_host_by_event_name`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$waits_by_user_by_latency`
--
DROP TABLE IF EXISTS `x$waits_by_user_by_latency`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$waits_by_user_by_latency` AS select if(isnull(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`) AS `user`,`performance_schema`.`events_waits_summary_by_user_by_event_name`.`EVENT_NAME` AS `event`,`performance_schema`.`events_waits_summary_by_user_by_event_name`.`COUNT_STAR` AS `total`,`performance_schema`.`events_waits_summary_by_user_by_event_name`.`SUM_TIMER_WAIT` AS `total_latency`,`performance_schema`.`events_waits_summary_by_user_by_event_name`.`AVG_TIMER_WAIT` AS `avg_latency`,`performance_schema`.`events_waits_summary_by_user_by_event_name`.`MAX_TIMER_WAIT` AS `max_latency` from `performance_schema`.`events_waits_summary_by_user_by_event_name` where ((`performance_schema`.`events_waits_summary_by_user_by_event_name`.`EVENT_NAME` <> 'idle') and (`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER` is not null) and (`performance_schema`.`events_waits_summary_by_user_by_event_name`.`SUM_TIMER_WAIT` > 0)) order by if(isnull(`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`),'background',`performance_schema`.`events_waits_summary_by_user_by_event_name`.`USER`),`performance_schema`.`events_waits_summary_by_user_by_event_name`.`SUM_TIMER_WAIT` desc;

-- --------------------------------------------------------

--
-- Structure for view `x$waits_global_by_latency`
--
DROP TABLE IF EXISTS `x$waits_global_by_latency`;

CREATE ALGORITHM=MERGE DEFINER=`mysql.sys`@`localhost` SQL SECURITY INVOKER VIEW `x$waits_global_by_latency` AS select `performance_schema`.`events_waits_summary_global_by_event_name`.`EVENT_NAME` AS `events`,`performance_schema`.`events_waits_summary_global_by_event_name`.`COUNT_STAR` AS `total`,`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT` AS `total_latency`,`performance_schema`.`events_waits_summary_global_by_event_name`.`AVG_TIMER_WAIT` AS `avg_latency`,`performance_schema`.`events_waits_summary_global_by_event_name`.`MAX_TIMER_WAIT` AS `max_latency` from `performance_schema`.`events_waits_summary_global_by_event_name` where ((`performance_schema`.`events_waits_summary_global_by_event_name`.`EVENT_NAME` <> 'idle') and (`performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT` > 0)) order by `performance_schema`.`events_waits_summary_global_by_event_name`.`SUM_TIMER_WAIT` desc;
--
-- Database: `test`
--

-- --------------------------------------------------------

--
-- Table structure for table `test`
--

CREATE TABLE IF NOT EXISTS `test` (
  `test` int(11) NOT NULL,
  `val` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
--
-- Database: `wiki`
--

-- --------------------------------------------------------

--
-- Table structure for table `test`
--

CREATE TABLE IF NOT EXISTS `test` (
  `test` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_account_credentials`
--

CREATE TABLE IF NOT EXISTS `wiki_account_credentials` (
  `acd_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `acd_user_id` int(10) unsigned NOT NULL,
  `acd_real_name` varchar(255) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '',
  `acd_email` tinytext NOT NULL,
  `acd_email_authenticated` binary(14) DEFAULT NULL,
  `acd_bio` mediumblob NOT NULL,
  `acd_notes` mediumblob NOT NULL,
  `acd_urls` mediumblob NOT NULL,
  `acd_ip` varchar(255) DEFAULT '',
  `acd_filename` varchar(255) DEFAULT NULL,
  `acd_storage_key` varchar(64) DEFAULT NULL,
  `acd_areas` mediumblob NOT NULL,
  `acd_registration` char(14) NOT NULL,
  `acd_accepted` binary(14) DEFAULT NULL,
  `acd_user` int(10) unsigned NOT NULL DEFAULT '0',
  `acd_comment` varchar(255) NOT NULL DEFAULT '',
  `acd_xff` varchar(255) DEFAULT '',
  `acd_agent` varchar(255) DEFAULT '',
  PRIMARY KEY (`acd_user_id`,`acd_id`),
  UNIQUE KEY `acd_id` (`acd_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=137 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_account_requests`
--

CREATE TABLE IF NOT EXISTS `wiki_account_requests` (
  `acr_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `acr_name` varchar(255) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '',
  `acr_real_name` varchar(255) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '',
  `acr_email` varchar(255) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `acr_email_authenticated` binary(14) DEFAULT NULL,
  `acr_email_token` binary(32) DEFAULT NULL,
  `acr_email_token_expires` binary(14) DEFAULT NULL,
  `acr_bio` mediumblob NOT NULL,
  `acr_notes` mediumblob NOT NULL,
  `acr_urls` mediumblob NOT NULL,
  `acr_ip` varchar(255) DEFAULT '',
  `acr_filename` varchar(255) DEFAULT NULL,
  `acr_storage_key` varchar(64) DEFAULT NULL,
  `acr_type` tinyint(255) unsigned DEFAULT '0',
  `acr_areas` mediumblob NOT NULL,
  `acr_registration` char(14) NOT NULL,
  `acr_deleted` tinyint(1) NOT NULL,
  `acr_rejected` binary(14) DEFAULT NULL,
  `acr_held` binary(14) DEFAULT NULL,
  `acr_user` int(10) unsigned NOT NULL DEFAULT '0',
  `acr_comment` varchar(255) NOT NULL DEFAULT '',
  `acr_xff` varchar(255) DEFAULT '',
  `acr_agent` varchar(255) DEFAULT '',
  PRIMARY KEY (`acr_id`),
  UNIQUE KEY `acr_name` (`acr_name`),
  UNIQUE KEY `acr_email` (`acr_email`),
  KEY `acr_email_token` (`acr_email_token`),
  KEY `acr_type_del_reg` (`acr_type`,`acr_deleted`,`acr_registration`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_archive`
--

CREATE TABLE IF NOT EXISTS `wiki_archive` (
  `ar_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ar_namespace` int(11) NOT NULL DEFAULT '0',
  `ar_title` varbinary(255) NOT NULL DEFAULT '',
  `ar_text` mediumblob NOT NULL,
  `ar_comment` varbinary(767) NOT NULL,
  `ar_user` int(10) unsigned NOT NULL DEFAULT '0',
  `ar_user_text` varbinary(255) NOT NULL,
  `ar_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `ar_minor_edit` tinyint(4) NOT NULL DEFAULT '0',
  `ar_flags` tinyblob NOT NULL,
  `ar_rev_id` int(10) unsigned DEFAULT NULL,
  `ar_text_id` int(10) unsigned DEFAULT NULL,
  `ar_deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `ar_len` int(10) unsigned DEFAULT NULL,
  `ar_page_id` int(10) unsigned DEFAULT NULL,
  `ar_parent_id` int(10) unsigned DEFAULT NULL,
  `ar_sha1` varbinary(32) NOT NULL DEFAULT '',
  `ar_content_format` varbinary(64) DEFAULT NULL,
  `ar_content_model` varbinary(32) DEFAULT NULL,
  PRIMARY KEY (`ar_id`),
  KEY `name_title_timestamp` (`ar_namespace`,`ar_title`,`ar_timestamp`),
  KEY `usertext_timestamp` (`ar_user_text`,`ar_timestamp`),
  KEY `ar_revid` (`ar_rev_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=708 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_category`
--

CREATE TABLE IF NOT EXISTS `wiki_category` (
  `cat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cat_title` varbinary(255) NOT NULL,
  `cat_pages` int(11) NOT NULL DEFAULT '0',
  `cat_subcats` int(11) NOT NULL DEFAULT '0',
  `cat_files` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cat_id`),
  UNIQUE KEY `cat_title` (`cat_title`),
  KEY `cat_pages` (`cat_pages`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=1767 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_categorylinks`
--

CREATE TABLE IF NOT EXISTS `wiki_categorylinks` (
  `cl_from` int(10) unsigned NOT NULL DEFAULT '0',
  `cl_to` varbinary(255) NOT NULL DEFAULT '',
  `cl_sortkey` varbinary(230) NOT NULL DEFAULT '',
  `cl_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `cl_sortkey_prefix` varbinary(255) NOT NULL DEFAULT '',
  `cl_collation` varbinary(32) NOT NULL DEFAULT '',
  `cl_type` enum('page','subcat','file') NOT NULL DEFAULT 'page',
  UNIQUE KEY `cl_from` (`cl_from`,`cl_to`),
  KEY `cl_timestamp` (`cl_to`,`cl_timestamp`),
  KEY `cl_collation` (`cl_collation`),
  KEY `cl_sortkey` (`cl_to`,`cl_type`,`cl_sortkey`,`cl_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_change_tag`
--

CREATE TABLE IF NOT EXISTS `wiki_change_tag` (
  `ct_rc_id` int(11) DEFAULT NULL,
  `ct_log_id` int(11) DEFAULT NULL,
  `ct_rev_id` int(11) DEFAULT NULL,
  `ct_tag` varbinary(255) NOT NULL,
  `ct_params` blob,
  UNIQUE KEY `change_tag_rc_tag` (`ct_rc_id`,`ct_tag`),
  UNIQUE KEY `change_tag_log_tag` (`ct_log_id`,`ct_tag`),
  UNIQUE KEY `change_tag_rev_tag` (`ct_rev_id`,`ct_tag`),
  KEY `change_tag_tag_id` (`ct_tag`,`ct_rc_id`,`ct_rev_id`,`ct_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_externallinks`
--

CREATE TABLE IF NOT EXISTS `wiki_externallinks` (
  `el_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `el_from` int(10) unsigned NOT NULL DEFAULT '0',
  `el_to` blob NOT NULL,
  `el_index` blob NOT NULL,
  PRIMARY KEY (`el_id`),
  KEY `el_from` (`el_from`,`el_to`(40)),
  KEY `el_to` (`el_to`(60),`el_from`),
  KEY `el_index` (`el_index`(60))
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=3680 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_filearchive`
--

CREATE TABLE IF NOT EXISTS `wiki_filearchive` (
  `fa_id` int(11) NOT NULL AUTO_INCREMENT,
  `fa_name` varbinary(255) NOT NULL DEFAULT '',
  `fa_archive_name` varbinary(255) DEFAULT '',
  `fa_storage_group` varbinary(16) DEFAULT NULL,
  `fa_storage_key` varbinary(64) DEFAULT '',
  `fa_deleted_user` int(11) DEFAULT NULL,
  `fa_deleted_timestamp` binary(14) DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `fa_deleted_reason` varbinary(767) DEFAULT '',
  `fa_size` int(10) unsigned DEFAULT '0',
  `fa_width` int(11) DEFAULT '0',
  `fa_height` int(11) DEFAULT '0',
  `fa_metadata` mediumblob,
  `fa_bits` int(11) DEFAULT '0',
  `fa_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE') DEFAULT NULL,
  `fa_major_mime` enum('unknown','application','audio','image','text','video','message','model','multipart','chemical') DEFAULT NULL,
  `fa_minor_mime` varbinary(100) DEFAULT 'unknown',
  `fa_description` varbinary(767) DEFAULT NULL,
  `fa_user` int(10) unsigned DEFAULT '0',
  `fa_user_text` varbinary(255) DEFAULT NULL,
  `fa_timestamp` binary(14) DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `fa_deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `fa_sha1` varbinary(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`fa_id`),
  KEY `fa_name` (`fa_name`,`fa_timestamp`),
  KEY `fa_storage_group` (`fa_storage_group`,`fa_storage_key`),
  KEY `fa_deleted_timestamp` (`fa_deleted_timestamp`),
  KEY `fa_user_timestamp` (`fa_user_text`,`fa_timestamp`),
  KEY `fa_sha1` (`fa_sha1`(10))
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=62 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_hitcounter`
--

CREATE TABLE IF NOT EXISTS `wiki_hitcounter` (
  `hc_id` int(10) unsigned NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=latin1 MAX_ROWS=25000;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_image`
--

CREATE TABLE IF NOT EXISTS `wiki_image` (
  `img_name` varbinary(255) NOT NULL DEFAULT '',
  `img_size` int(10) unsigned NOT NULL DEFAULT '0',
  `img_width` int(11) NOT NULL DEFAULT '0',
  `img_height` int(11) NOT NULL DEFAULT '0',
  `img_metadata` mediumblob NOT NULL,
  `img_bits` int(11) NOT NULL DEFAULT '0',
  `img_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE') DEFAULT NULL,
  `img_major_mime` enum('unknown','application','audio','image','text','video','message','model','multipart','chemical') DEFAULT NULL,
  `img_minor_mime` varbinary(100) NOT NULL DEFAULT 'unknown',
  `img_description` varbinary(767) NOT NULL,
  `img_user` int(10) unsigned NOT NULL DEFAULT '0',
  `img_user_text` varbinary(255) NOT NULL,
  `img_timestamp` varbinary(14) NOT NULL DEFAULT '',
  `img_sha1` varbinary(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`img_name`),
  KEY `img_usertext_timestamp` (`img_user_text`,`img_timestamp`),
  KEY `img_size` (`img_size`),
  KEY `img_timestamp` (`img_timestamp`),
  KEY `img_sha1` (`img_sha1`),
  KEY `img_media_mime` (`img_media_type`,`img_major_mime`,`img_minor_mime`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_imagelinks`
--

CREATE TABLE IF NOT EXISTS `wiki_imagelinks` (
  `il_from` int(10) unsigned NOT NULL DEFAULT '0',
  `il_to` varbinary(255) NOT NULL DEFAULT '',
  `il_from_namespace` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `il_from` (`il_from`,`il_to`),
  UNIQUE KEY `il_to` (`il_to`,`il_from`),
  KEY `il_backlinks_namespace` (`il_to`,`il_from_namespace`,`il_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_interwiki`
--

CREATE TABLE IF NOT EXISTS `wiki_interwiki` (
  `iw_prefix` varbinary(32) NOT NULL,
  `iw_url` blob NOT NULL,
  `iw_local` tinyint(1) NOT NULL,
  `iw_trans` tinyint(4) NOT NULL DEFAULT '0',
  `iw_api` blob NOT NULL,
  `iw_wikiid` varbinary(64) NOT NULL,
  UNIQUE KEY `iw_prefix` (`iw_prefix`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_ipblocks`
--

CREATE TABLE IF NOT EXISTS `wiki_ipblocks` (
  `ipb_id` int(11) NOT NULL AUTO_INCREMENT,
  `ipb_address` tinyblob NOT NULL,
  `ipb_user` int(10) unsigned NOT NULL DEFAULT '0',
  `ipb_by` int(10) unsigned NOT NULL DEFAULT '0',
  `ipb_by_text` varbinary(255) NOT NULL DEFAULT '',
  `ipb_reason` varbinary(767) NOT NULL,
  `ipb_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `ipb_auto` tinyint(1) NOT NULL DEFAULT '0',
  `ipb_anon_only` tinyint(1) NOT NULL DEFAULT '0',
  `ipb_create_account` tinyint(1) NOT NULL DEFAULT '1',
  `ipb_enable_autoblock` tinyint(1) NOT NULL DEFAULT '1',
  `ipb_expiry` varbinary(14) NOT NULL DEFAULT '',
  `ipb_range_start` tinyblob NOT NULL,
  `ipb_range_end` tinyblob NOT NULL,
  `ipb_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `ipb_block_email` tinyint(1) NOT NULL DEFAULT '0',
  `ipb_allow_usertalk` tinyint(1) NOT NULL DEFAULT '0',
  `ipb_parent_block_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`ipb_id`),
  UNIQUE KEY `ipb_address` (`ipb_address`(255),`ipb_user`,`ipb_auto`,`ipb_anon_only`),
  KEY `ipb_user` (`ipb_user`),
  KEY `ipb_range` (`ipb_range_start`(8),`ipb_range_end`(8)),
  KEY `ipb_timestamp` (`ipb_timestamp`),
  KEY `ipb_expiry` (`ipb_expiry`),
  KEY `ipb_parent_block_id` (`ipb_parent_block_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=12 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_iwlinks`
--

CREATE TABLE IF NOT EXISTS `wiki_iwlinks` (
  `iwl_from` int(10) unsigned NOT NULL DEFAULT '0',
  `iwl_prefix` varbinary(20) NOT NULL DEFAULT '',
  `iwl_title` varbinary(255) NOT NULL DEFAULT '',
  UNIQUE KEY `iwl_from` (`iwl_from`,`iwl_prefix`,`iwl_title`),
  KEY `iwl_prefix_title_from` (`iwl_prefix`,`iwl_title`,`iwl_from`),
  KEY `iwl_prefix_from_title` (`iwl_prefix`,`iwl_from`,`iwl_title`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_job`
--

CREATE TABLE IF NOT EXISTS `wiki_job` (
  `job_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_cmd` varbinary(60) NOT NULL DEFAULT '',
  `job_namespace` int(11) NOT NULL,
  `job_title` varbinary(255) NOT NULL,
  `job_params` blob NOT NULL,
  `job_timestamp` varbinary(14) DEFAULT NULL,
  `job_random` int(10) unsigned NOT NULL DEFAULT '0',
  `job_token` varbinary(32) NOT NULL DEFAULT '',
  `job_token_timestamp` varbinary(14) DEFAULT NULL,
  `job_sha1` varbinary(32) NOT NULL DEFAULT '',
  `job_attempts` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`job_id`),
  KEY `job_cmd` (`job_cmd`,`job_namespace`,`job_title`),
  KEY `job_timestamp` (`job_timestamp`),
  KEY `job_sha1` (`job_sha1`),
  KEY `job_cmd_token` (`job_cmd`,`job_token`,`job_random`),
  KEY `job_cmd_token_id` (`job_cmd`,`job_token`,`job_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=203 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_l10n_cache`
--

CREATE TABLE IF NOT EXISTS `wiki_l10n_cache` (
  `lc_lang` varbinary(32) NOT NULL,
  `lc_key` varbinary(255) NOT NULL,
  `lc_value` mediumblob NOT NULL,
  KEY `lc_lang_key` (`lc_lang`,`lc_key`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_langlinks`
--

CREATE TABLE IF NOT EXISTS `wiki_langlinks` (
  `ll_from` int(10) unsigned NOT NULL DEFAULT '0',
  `ll_lang` varbinary(20) NOT NULL DEFAULT '',
  `ll_title` varbinary(255) NOT NULL DEFAULT '',
  UNIQUE KEY `ll_from` (`ll_from`,`ll_lang`),
  KEY `ll_lang` (`ll_lang`,`ll_title`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_log_search`
--

CREATE TABLE IF NOT EXISTS `wiki_log_search` (
  `ls_field` varbinary(32) NOT NULL,
  `ls_value` varbinary(255) NOT NULL,
  `ls_log_id` int(10) unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `ls_field_val` (`ls_field`,`ls_value`,`ls_log_id`),
  KEY `ls_log_id` (`ls_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_logging`
--

CREATE TABLE IF NOT EXISTS `wiki_logging` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `log_type` varbinary(32) NOT NULL,
  `log_action` varbinary(32) NOT NULL,
  `log_timestamp` binary(14) NOT NULL DEFAULT '19700101000000',
  `log_user` int(10) unsigned NOT NULL DEFAULT '0',
  `log_namespace` int(11) NOT NULL DEFAULT '0',
  `log_title` varbinary(255) NOT NULL DEFAULT '',
  `log_comment` varbinary(767) NOT NULL DEFAULT '',
  `log_params` blob NOT NULL,
  `log_deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `log_user_text` varbinary(255) NOT NULL DEFAULT '',
  `log_page` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `type_time` (`log_type`,`log_timestamp`),
  KEY `user_time` (`log_user`,`log_timestamp`),
  KEY `page_time` (`log_namespace`,`log_title`,`log_timestamp`),
  KEY `times` (`log_timestamp`),
  KEY `log_user_type_time` (`log_user`,`log_type`,`log_timestamp`),
  KEY `log_page_id_time` (`log_page`,`log_timestamp`),
  KEY `type_action` (`log_type`,`log_action`,`log_timestamp`),
  KEY `log_user_text_type_time` (`log_user_text`,`log_type`,`log_timestamp`),
  KEY `log_user_text_time` (`log_user_text`,`log_timestamp`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=31361 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_math`
--

CREATE TABLE IF NOT EXISTS `wiki_math` (
  `math_inputhash` varbinary(16) NOT NULL,
  `math_outputhash` varbinary(16) NOT NULL,
  `math_html_conservativeness` tinyint(4) NOT NULL,
  `math_html` blob,
  `math_mathml` blob,
  UNIQUE KEY `math_inputhash` (`math_inputhash`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_module_deps`
--

CREATE TABLE IF NOT EXISTS `wiki_module_deps` (
  `md_module` varbinary(255) NOT NULL,
  `md_skin` varbinary(32) NOT NULL,
  `md_deps` mediumblob NOT NULL,
  UNIQUE KEY `md_module_skin` (`md_module`,`md_skin`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_msg_resource`
--

CREATE TABLE IF NOT EXISTS `wiki_msg_resource` (
  `mr_resource` varbinary(255) NOT NULL,
  `mr_lang` varbinary(32) NOT NULL,
  `mr_blob` mediumblob NOT NULL,
  `mr_timestamp` binary(14) NOT NULL,
  UNIQUE KEY `mr_resource_lang` (`mr_resource`,`mr_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_msg_resource_links`
--

CREATE TABLE IF NOT EXISTS `wiki_msg_resource_links` (
  `mrl_resource` varbinary(255) NOT NULL,
  `mrl_message` varbinary(255) NOT NULL,
  UNIQUE KEY `mrl_message_resource` (`mrl_message`,`mrl_resource`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_objectcache`
--

CREATE TABLE IF NOT EXISTS `wiki_objectcache` (
  `keyname` varbinary(255) NOT NULL DEFAULT '',
  `value` mediumblob,
  `exptime` datetime DEFAULT NULL,
  PRIMARY KEY (`keyname`),
  KEY `exptime` (`exptime`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_oldimage`
--

CREATE TABLE IF NOT EXISTS `wiki_oldimage` (
  `oi_name` varbinary(255) NOT NULL DEFAULT '',
  `oi_archive_name` varbinary(255) NOT NULL DEFAULT '',
  `oi_size` int(10) unsigned NOT NULL DEFAULT '0',
  `oi_width` int(11) NOT NULL DEFAULT '0',
  `oi_height` int(11) NOT NULL DEFAULT '0',
  `oi_bits` int(11) NOT NULL DEFAULT '0',
  `oi_description` varbinary(767) NOT NULL,
  `oi_user` int(10) unsigned NOT NULL DEFAULT '0',
  `oi_user_text` varbinary(255) NOT NULL,
  `oi_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `oi_metadata` mediumblob NOT NULL,
  `oi_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE') DEFAULT NULL,
  `oi_major_mime` enum('unknown','application','audio','image','text','video','message','model','multipart','chemical') DEFAULT NULL,
  `oi_minor_mime` varbinary(100) NOT NULL DEFAULT 'unknown',
  `oi_deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `oi_sha1` varbinary(32) NOT NULL DEFAULT '',
  KEY `oi_usertext_timestamp` (`oi_user_text`,`oi_timestamp`),
  KEY `oi_name_timestamp` (`oi_name`,`oi_timestamp`),
  KEY `oi_name_archive_name` (`oi_name`,`oi_archive_name`(14)),
  KEY `oi_sha1` (`oi_sha1`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_page`
--

CREATE TABLE IF NOT EXISTS `wiki_page` (
  `page_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_namespace` int(11) NOT NULL,
  `page_title` varbinary(255) NOT NULL,
  `page_restrictions` tinyblob NOT NULL,
  `page_counter` bigint(20) unsigned NOT NULL DEFAULT '0',
  `page_is_redirect` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `page_is_new` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `page_random` double unsigned NOT NULL,
  `page_touched` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `page_latest` int(10) unsigned NOT NULL,
  `page_len` int(10) unsigned NOT NULL,
  `page_content_model` varbinary(32) DEFAULT NULL,
  `page_links_updated` varbinary(14) DEFAULT NULL,
  `page_lang` varbinary(35) DEFAULT NULL,
  PRIMARY KEY (`page_id`),
  UNIQUE KEY `name_title` (`page_namespace`,`page_title`),
  KEY `page_random` (`page_random`),
  KEY `page_len` (`page_len`),
  KEY `page_redirect_namespace_len` (`page_is_redirect`,`page_namespace`,`page_len`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=6194 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_page_props`
--

CREATE TABLE IF NOT EXISTS `wiki_page_props` (
  `pp_page` int(11) NOT NULL,
  `pp_propname` varbinary(60) NOT NULL,
  `pp_value` blob NOT NULL,
  `pp_sortkey` float DEFAULT NULL,
  UNIQUE KEY `pp_page_propname` (`pp_page`,`pp_propname`),
  UNIQUE KEY `pp_propname_page` (`pp_propname`,`pp_page`),
  UNIQUE KEY `pp_propname_sortkey_page` (`pp_propname`,`pp_sortkey`,`pp_page`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_page_restrictions`
--

CREATE TABLE IF NOT EXISTS `wiki_page_restrictions` (
  `pr_page` int(11) NOT NULL,
  `pr_type` varbinary(60) NOT NULL,
  `pr_level` varbinary(60) NOT NULL,
  `pr_cascade` tinyint(4) NOT NULL,
  `pr_user` int(11) DEFAULT NULL,
  `pr_expiry` varbinary(14) DEFAULT NULL,
  `pr_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`pr_id`),
  UNIQUE KEY `pr_pagetype` (`pr_page`,`pr_type`),
  KEY `pr_typelevel` (`pr_type`,`pr_level`),
  KEY `pr_level` (`pr_level`),
  KEY `pr_cascade` (`pr_cascade`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=242 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_pagelinks`
--

CREATE TABLE IF NOT EXISTS `wiki_pagelinks` (
  `pl_from` int(10) unsigned NOT NULL DEFAULT '0',
  `pl_namespace` int(11) NOT NULL DEFAULT '0',
  `pl_title` varbinary(255) NOT NULL DEFAULT '',
  `pl_from_namespace` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `pl_from` (`pl_from`,`pl_namespace`,`pl_title`),
  UNIQUE KEY `pl_namespace` (`pl_namespace`,`pl_title`,`pl_from`),
  KEY `pl_backlinks_namespace` (`pl_namespace`,`pl_title`,`pl_from_namespace`,`pl_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_protected_titles`
--

CREATE TABLE IF NOT EXISTS `wiki_protected_titles` (
  `pt_namespace` int(11) NOT NULL,
  `pt_title` varbinary(255) NOT NULL,
  `pt_user` int(10) unsigned NOT NULL,
  `pt_reason` varbinary(767) DEFAULT NULL,
  `pt_timestamp` binary(14) NOT NULL,
  `pt_expiry` varbinary(14) NOT NULL DEFAULT '',
  `pt_create_perm` varbinary(60) NOT NULL,
  UNIQUE KEY `pt_namespace_title` (`pt_namespace`,`pt_title`),
  KEY `pt_timestamp` (`pt_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_querycache`
--

CREATE TABLE IF NOT EXISTS `wiki_querycache` (
  `qc_type` varbinary(32) NOT NULL,
  `qc_value` int(10) unsigned NOT NULL DEFAULT '0',
  `qc_namespace` int(11) NOT NULL DEFAULT '0',
  `qc_title` varbinary(255) NOT NULL DEFAULT '',
  KEY `qc_type` (`qc_type`,`qc_value`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_querycache_info`
--

CREATE TABLE IF NOT EXISTS `wiki_querycache_info` (
  `qci_type` varbinary(32) NOT NULL DEFAULT '',
  `qci_timestamp` binary(14) NOT NULL DEFAULT '19700101000000',
  UNIQUE KEY `qci_type` (`qci_type`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_querycachetwo`
--

CREATE TABLE IF NOT EXISTS `wiki_querycachetwo` (
  `qcc_type` varbinary(32) NOT NULL,
  `qcc_value` int(10) unsigned NOT NULL DEFAULT '0',
  `qcc_namespace` int(11) NOT NULL DEFAULT '0',
  `qcc_title` varbinary(255) NOT NULL DEFAULT '',
  `qcc_namespacetwo` int(11) NOT NULL DEFAULT '0',
  `qcc_titletwo` varbinary(255) NOT NULL DEFAULT '',
  KEY `qcc_type` (`qcc_type`,`qcc_value`),
  KEY `qcc_title` (`qcc_type`,`qcc_namespace`,`qcc_title`),
  KEY `qcc_titletwo` (`qcc_type`,`qcc_namespacetwo`,`qcc_titletwo`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_recentchanges`
--

CREATE TABLE IF NOT EXISTS `wiki_recentchanges` (
  `rc_id` int(11) NOT NULL AUTO_INCREMENT,
  `rc_timestamp` varbinary(14) NOT NULL DEFAULT '',
  `rc_user` int(10) unsigned NOT NULL DEFAULT '0',
  `rc_user_text` varbinary(255) NOT NULL,
  `rc_namespace` int(11) NOT NULL DEFAULT '0',
  `rc_title` varbinary(255) NOT NULL DEFAULT '',
  `rc_comment` varbinary(767) NOT NULL DEFAULT '',
  `rc_minor` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rc_bot` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rc_new` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rc_cur_id` int(10) unsigned NOT NULL DEFAULT '0',
  `rc_this_oldid` int(10) unsigned NOT NULL DEFAULT '0',
  `rc_last_oldid` int(10) unsigned NOT NULL DEFAULT '0',
  `rc_type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rc_patrolled` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rc_ip` varbinary(40) NOT NULL DEFAULT '',
  `rc_old_len` int(11) DEFAULT NULL,
  `rc_new_len` int(11) DEFAULT NULL,
  `rc_deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rc_logid` int(10) unsigned NOT NULL DEFAULT '0',
  `rc_log_type` varbinary(255) DEFAULT NULL,
  `rc_log_action` varbinary(255) DEFAULT NULL,
  `rc_params` blob,
  `rc_source` varbinary(16) NOT NULL DEFAULT '',
  PRIMARY KEY (`rc_id`),
  KEY `rc_timestamp` (`rc_timestamp`),
  KEY `rc_namespace_title` (`rc_namespace`,`rc_title`),
  KEY `rc_cur_id` (`rc_cur_id`),
  KEY `new_name_timestamp` (`rc_new`,`rc_namespace`,`rc_timestamp`),
  KEY `rc_ip` (`rc_ip`),
  KEY `rc_ns_usertext` (`rc_namespace`,`rc_user_text`),
  KEY `rc_user_text` (`rc_user_text`,`rc_timestamp`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=44393 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_redirect`
--

CREATE TABLE IF NOT EXISTS `wiki_redirect` (
  `rd_from` int(10) unsigned NOT NULL DEFAULT '0',
  `rd_namespace` int(11) NOT NULL DEFAULT '0',
  `rd_title` varbinary(255) NOT NULL DEFAULT '',
  `rd_interwiki` varbinary(32) DEFAULT NULL,
  `rd_fragment` varbinary(255) DEFAULT NULL,
  PRIMARY KEY (`rd_from`),
  KEY `rd_ns_title` (`rd_namespace`,`rd_title`,`rd_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_revision`
--

CREATE TABLE IF NOT EXISTS `wiki_revision` (
  `rev_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rev_page` int(10) unsigned NOT NULL,
  `rev_text_id` int(10) unsigned NOT NULL,
  `rev_comment` varbinary(767) NOT NULL,
  `rev_user` int(10) unsigned NOT NULL DEFAULT '0',
  `rev_user_text` varbinary(255) NOT NULL DEFAULT '',
  `rev_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `rev_minor_edit` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rev_deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rev_len` int(10) unsigned DEFAULT NULL,
  `rev_parent_id` int(10) unsigned DEFAULT NULL,
  `rev_sha1` varbinary(32) NOT NULL DEFAULT '',
  `rev_content_format` varbinary(64) DEFAULT NULL,
  `rev_content_model` varbinary(32) DEFAULT NULL,
  PRIMARY KEY (`rev_id`),
  UNIQUE KEY `rev_page_id` (`rev_page`,`rev_id`),
  KEY `rev_timestamp` (`rev_timestamp`),
  KEY `page_timestamp` (`rev_page`,`rev_timestamp`),
  KEY `user_timestamp` (`rev_user`,`rev_timestamp`),
  KEY `usertext_timestamp` (`rev_user_text`,`rev_timestamp`),
  KEY `page_user_timestamp` (`rev_page`,`rev_user`,`rev_timestamp`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary MAX_ROWS=10000000 AVG_ROW_LENGTH=1024 AUTO_INCREMENT=43187 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_searchindex`
--

CREATE TABLE IF NOT EXISTS `wiki_searchindex` (
  `si_page` int(10) unsigned NOT NULL,
  `si_title` varchar(255) NOT NULL DEFAULT '',
  `si_text` mediumtext NOT NULL,
  UNIQUE KEY `si_page` (`si_page`),
  FULLTEXT KEY `si_title` (`si_title`),
  FULLTEXT KEY `si_text` (`si_text`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_site_identifiers`
--

CREATE TABLE IF NOT EXISTS `wiki_site_identifiers` (
  `si_site` int(10) unsigned NOT NULL,
  `si_type` varbinary(32) NOT NULL,
  `si_key` varbinary(32) NOT NULL,
  UNIQUE KEY `site_ids_type` (`si_type`,`si_key`),
  KEY `site_ids_site` (`si_site`),
  KEY `site_ids_key` (`si_key`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_site_stats`
--

CREATE TABLE IF NOT EXISTS `wiki_site_stats` (
  `ss_row_id` int(10) unsigned NOT NULL,
  `ss_total_views` bigint(20) unsigned DEFAULT '0',
  `ss_total_edits` bigint(20) unsigned DEFAULT '0',
  `ss_good_articles` bigint(20) unsigned DEFAULT '0',
  `ss_total_pages` bigint(20) DEFAULT '-1',
  `ss_users` bigint(20) DEFAULT '-1',
  `ss_active_users` bigint(20) DEFAULT '-1',
  `ss_images` int(11) DEFAULT '0',
  UNIQUE KEY `ss_row_id` (`ss_row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_sites`
--

CREATE TABLE IF NOT EXISTS `wiki_sites` (
  `site_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_global_key` varbinary(32) NOT NULL,
  `site_type` varbinary(32) NOT NULL,
  `site_group` varbinary(32) NOT NULL,
  `site_source` varbinary(32) NOT NULL,
  `site_language` varbinary(32) NOT NULL,
  `site_protocol` varbinary(32) NOT NULL,
  `site_domain` varbinary(255) NOT NULL,
  `site_data` blob NOT NULL,
  `site_forward` tinyint(1) NOT NULL,
  `site_config` blob NOT NULL,
  PRIMARY KEY (`site_id`),
  UNIQUE KEY `sites_global_key` (`site_global_key`),
  KEY `sites_type` (`site_type`),
  KEY `sites_group` (`site_group`),
  KEY `sites_source` (`site_source`),
  KEY `sites_language` (`site_language`),
  KEY `sites_protocol` (`site_protocol`),
  KEY `sites_domain` (`site_domain`),
  KEY `sites_forward` (`site_forward`)
) ENGINE=InnoDB DEFAULT CHARSET=binary AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_tag_summary`
--

CREATE TABLE IF NOT EXISTS `wiki_tag_summary` (
  `ts_rc_id` int(11) DEFAULT NULL,
  `ts_log_id` int(11) DEFAULT NULL,
  `ts_rev_id` int(11) DEFAULT NULL,
  `ts_tags` blob NOT NULL,
  UNIQUE KEY `tag_summary_rc_id` (`ts_rc_id`),
  UNIQUE KEY `tag_summary_log_id` (`ts_log_id`),
  UNIQUE KEY `tag_summary_rev_id` (`ts_rev_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_templatelinks`
--

CREATE TABLE IF NOT EXISTS `wiki_templatelinks` (
  `tl_from` int(10) unsigned NOT NULL DEFAULT '0',
  `tl_namespace` int(11) NOT NULL DEFAULT '0',
  `tl_title` varbinary(255) NOT NULL DEFAULT '',
  `tl_from_namespace` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `tl_from` (`tl_from`,`tl_namespace`,`tl_title`),
  UNIQUE KEY `tl_namespace` (`tl_namespace`,`tl_title`,`tl_from`),
  KEY `tl_backlinks_namespace` (`tl_namespace`,`tl_title`,`tl_from_namespace`,`tl_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_text`
--

CREATE TABLE IF NOT EXISTS `wiki_text` (
  `old_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `old_text` mediumblob NOT NULL,
  `old_flags` tinyblob NOT NULL,
  PRIMARY KEY (`old_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary MAX_ROWS=10000000 AVG_ROW_LENGTH=10240 AUTO_INCREMENT=42500 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_trackbacks`
--

CREATE TABLE IF NOT EXISTS `wiki_trackbacks` (
  `tb_id` int(11) NOT NULL AUTO_INCREMENT,
  `tb_page` int(11) DEFAULT NULL,
  `tb_title` varbinary(255) NOT NULL,
  `tb_url` blob NOT NULL,
  `tb_ex` blob,
  `tb_name` varbinary(255) DEFAULT NULL,
  PRIMARY KEY (`tb_id`),
  KEY `tb_page` (`tb_page`)
) ENGINE=InnoDB DEFAULT CHARSET=binary AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_transcache`
--

CREATE TABLE IF NOT EXISTS `wiki_transcache` (
  `tc_url` varbinary(255) NOT NULL,
  `tc_contents` blob,
  `tc_time` binary(14) DEFAULT NULL,
  UNIQUE KEY `tc_url_idx` (`tc_url`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_updatelog`
--

CREATE TABLE IF NOT EXISTS `wiki_updatelog` (
  `ul_key` varbinary(255) NOT NULL,
  `ul_value` blob,
  PRIMARY KEY (`ul_key`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_uploadstash`
--

CREATE TABLE IF NOT EXISTS `wiki_uploadstash` (
  `us_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `us_user` int(10) unsigned NOT NULL,
  `us_key` varbinary(255) NOT NULL,
  `us_orig_path` varbinary(255) NOT NULL,
  `us_path` varbinary(255) NOT NULL,
  `us_source_type` varbinary(50) DEFAULT NULL,
  `us_timestamp` varbinary(14) NOT NULL,
  `us_status` varbinary(50) NOT NULL,
  `us_size` int(10) unsigned NOT NULL,
  `us_sha1` varbinary(31) NOT NULL,
  `us_mime` varbinary(255) DEFAULT NULL,
  `us_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE') DEFAULT NULL,
  `us_image_width` int(10) unsigned DEFAULT NULL,
  `us_image_height` int(10) unsigned DEFAULT NULL,
  `us_image_bits` smallint(5) unsigned DEFAULT NULL,
  `us_chunk_inx` int(10) unsigned DEFAULT NULL,
  `us_props` blob,
  PRIMARY KEY (`us_id`),
  UNIQUE KEY `us_key` (`us_key`),
  KEY `us_user` (`us_user`),
  KEY `us_timestamp` (`us_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_user`
--

CREATE TABLE IF NOT EXISTS `wiki_user` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varbinary(255) NOT NULL DEFAULT '',
  `user_real_name` varbinary(255) NOT NULL DEFAULT '',
  `user_password` tinyblob NOT NULL,
  `user_newpassword` tinyblob NOT NULL,
  `user_newpass_time` binary(14) DEFAULT NULL,
  `user_email` tinyblob NOT NULL,
  `user_touched` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `user_token` binary(32) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `user_email_authenticated` binary(14) DEFAULT NULL,
  `user_email_token` binary(32) DEFAULT NULL,
  `user_email_token_expires` binary(14) DEFAULT NULL,
  `user_registration` binary(14) DEFAULT NULL,
  `user_editcount` int(11) DEFAULT NULL,
  `user_password_expires` varbinary(14) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `user_email_token` (`user_email_token`),
  KEY `user_email` (`user_email`(50))
) ENGINE=InnoDB  DEFAULT CHARSET=binary AUTO_INCREMENT=188 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_user_former_groups`
--

CREATE TABLE IF NOT EXISTS `wiki_user_former_groups` (
  `ufg_user` int(10) unsigned NOT NULL DEFAULT '0',
  `ufg_group` varbinary(255) NOT NULL DEFAULT '',
  UNIQUE KEY `ufg_user_group` (`ufg_user`,`ufg_group`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_user_groups`
--

CREATE TABLE IF NOT EXISTS `wiki_user_groups` (
  `ug_user` int(10) unsigned NOT NULL DEFAULT '0',
  `ug_group` varbinary(255) NOT NULL DEFAULT '',
  UNIQUE KEY `ug_user_group` (`ug_user`,`ug_group`),
  KEY `ug_group` (`ug_group`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_user_newtalk`
--

CREATE TABLE IF NOT EXISTS `wiki_user_newtalk` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_ip` varbinary(40) NOT NULL DEFAULT '',
  `user_last_timestamp` varbinary(14) DEFAULT NULL,
  KEY `user_id` (`user_id`),
  KEY `user_ip` (`user_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_user_properties`
--

CREATE TABLE IF NOT EXISTS `wiki_user_properties` (
  `up_user` int(11) NOT NULL,
  `up_property` varbinary(255) DEFAULT NULL,
  `up_value` blob,
  UNIQUE KEY `user_properties_user_property` (`up_user`,`up_property`),
  KEY `user_properties_property` (`up_property`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_valid_tag`
--

CREATE TABLE IF NOT EXISTS `wiki_valid_tag` (
  `vt_tag` varbinary(255) NOT NULL,
  PRIMARY KEY (`vt_tag`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_watchlist`
--

CREATE TABLE IF NOT EXISTS `wiki_watchlist` (
  `wl_user` int(10) unsigned NOT NULL,
  `wl_namespace` int(11) NOT NULL DEFAULT '0',
  `wl_title` varbinary(255) NOT NULL DEFAULT '',
  `wl_notificationtimestamp` varbinary(14) DEFAULT NULL,
  UNIQUE KEY `wl_user` (`wl_user`,`wl_namespace`,`wl_title`),
  KEY `namespace_title` (`wl_namespace`,`wl_title`),
  KEY `wl_user_notificationtimestamp` (`wl_user`,`wl_notificationtimestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
--
-- Database: `zabbix`
--

-- --------------------------------------------------------

--
-- Table structure for table `acknowledges`
--

CREATE TABLE IF NOT EXISTS `acknowledges` (
  `acknowledgeid` bigint(20) unsigned NOT NULL,
  `userid` bigint(20) unsigned NOT NULL,
  `eventid` bigint(20) unsigned NOT NULL,
  `clock` int(11) NOT NULL DEFAULT '0',
  `message` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`acknowledgeid`),
  KEY `acknowledges_1` (`userid`),
  KEY `acknowledges_2` (`eventid`),
  KEY `acknowledges_3` (`clock`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `actions`
--

CREATE TABLE IF NOT EXISTS `actions` (
  `actionid` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `eventsource` int(11) NOT NULL DEFAULT '0',
  `evaltype` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '0',
  `esc_period` int(11) NOT NULL DEFAULT '0',
  `def_shortdata` varchar(255) NOT NULL DEFAULT '',
  `def_longdata` text NOT NULL,
  `recovery_msg` int(11) NOT NULL DEFAULT '0',
  `r_shortdata` varchar(255) NOT NULL DEFAULT '',
  `r_longdata` text NOT NULL,
  PRIMARY KEY (`actionid`),
  KEY `actions_1` (`eventsource`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE IF NOT EXISTS `alerts` (
  `alertid` bigint(20) unsigned NOT NULL,
  `actionid` bigint(20) unsigned NOT NULL,
  `eventid` bigint(20) unsigned NOT NULL,
  `userid` bigint(20) unsigned DEFAULT NULL,
  `clock` int(11) NOT NULL DEFAULT '0',
  `mediatypeid` bigint(20) unsigned DEFAULT NULL,
  `sendto` varchar(100) NOT NULL DEFAULT '',
  `subject` varchar(255) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `retries` int(11) NOT NULL DEFAULT '0',
  `error` varchar(128) NOT NULL DEFAULT '',
  `esc_step` int(11) NOT NULL DEFAULT '0',
  `alerttype` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`alertid`),
  KEY `alerts_1` (`actionid`),
  KEY `alerts_2` (`clock`),
  KEY `alerts_3` (`eventid`),
  KEY `alerts_4` (`status`,`retries`),
  KEY `alerts_5` (`mediatypeid`),
  KEY `alerts_6` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `application_template`
--

CREATE TABLE IF NOT EXISTS `application_template` (
  `application_templateid` bigint(20) unsigned NOT NULL,
  `applicationid` bigint(20) unsigned NOT NULL,
  `templateid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`application_templateid`),
  UNIQUE KEY `application_template_1` (`applicationid`,`templateid`),
  KEY `application_template_2` (`templateid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE IF NOT EXISTS `applications` (
  `applicationid` bigint(20) unsigned NOT NULL,
  `hostid` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`applicationid`),
  UNIQUE KEY `applications_2` (`hostid`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `auditlog`
--

CREATE TABLE IF NOT EXISTS `auditlog` (
  `auditid` bigint(20) unsigned NOT NULL,
  `userid` bigint(20) unsigned NOT NULL,
  `clock` int(11) NOT NULL DEFAULT '0',
  `action` int(11) NOT NULL DEFAULT '0',
  `resourcetype` int(11) NOT NULL DEFAULT '0',
  `details` varchar(128) NOT NULL DEFAULT '0',
  `ip` varchar(39) NOT NULL DEFAULT '',
  `resourceid` bigint(20) unsigned NOT NULL DEFAULT '0',
  `resourcename` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`auditid`),
  KEY `auditlog_1` (`userid`,`clock`),
  KEY `auditlog_2` (`clock`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `auditlog_details`
--

CREATE TABLE IF NOT EXISTS `auditlog_details` (
  `auditdetailid` bigint(20) unsigned NOT NULL,
  `auditid` bigint(20) unsigned NOT NULL,
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `field_name` varchar(64) NOT NULL DEFAULT '',
  `oldvalue` text NOT NULL,
  `newvalue` text NOT NULL,
  PRIMARY KEY (`auditdetailid`),
  KEY `auditlog_details_1` (`auditid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `autoreg_host`
--

CREATE TABLE IF NOT EXISTS `autoreg_host` (
  `autoreg_hostid` bigint(20) unsigned NOT NULL,
  `proxy_hostid` bigint(20) unsigned DEFAULT NULL,
  `host` varchar(64) NOT NULL DEFAULT '',
  `listen_ip` varchar(39) NOT NULL DEFAULT '',
  `listen_port` int(11) NOT NULL DEFAULT '0',
  `listen_dns` varchar(64) NOT NULL DEFAULT '',
  `host_metadata` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`autoreg_hostid`),
  KEY `autoreg_host_1` (`proxy_hostid`,`host`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `conditions`
--

CREATE TABLE IF NOT EXISTS `conditions` (
  `conditionid` bigint(20) unsigned NOT NULL,
  `actionid` bigint(20) unsigned NOT NULL,
  `conditiontype` int(11) NOT NULL DEFAULT '0',
  `operator` int(11) NOT NULL DEFAULT '0',
  `value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`conditionid`),
  KEY `conditions_1` (`actionid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE IF NOT EXISTS `config` (
  `configid` bigint(20) unsigned NOT NULL,
  `refresh_unsupported` int(11) NOT NULL DEFAULT '0',
  `work_period` varchar(100) NOT NULL DEFAULT '1-5,00:00-24:00',
  `alert_usrgrpid` bigint(20) unsigned DEFAULT NULL,
  `event_ack_enable` int(11) NOT NULL DEFAULT '1',
  `event_expire` int(11) NOT NULL DEFAULT '7',
  `event_show_max` int(11) NOT NULL DEFAULT '100',
  `default_theme` varchar(128) NOT NULL DEFAULT 'originalblue',
  `authentication_type` int(11) NOT NULL DEFAULT '0',
  `ldap_host` varchar(255) NOT NULL DEFAULT '',
  `ldap_port` int(11) NOT NULL DEFAULT '389',
  `ldap_base_dn` varchar(255) NOT NULL DEFAULT '',
  `ldap_bind_dn` varchar(255) NOT NULL DEFAULT '',
  `ldap_bind_password` varchar(128) NOT NULL DEFAULT '',
  `ldap_search_attribute` varchar(128) NOT NULL DEFAULT '',
  `dropdown_first_entry` int(11) NOT NULL DEFAULT '1',
  `dropdown_first_remember` int(11) NOT NULL DEFAULT '1',
  `discovery_groupid` bigint(20) unsigned NOT NULL,
  `max_in_table` int(11) NOT NULL DEFAULT '50',
  `search_limit` int(11) NOT NULL DEFAULT '1000',
  `severity_color_0` varchar(6) NOT NULL DEFAULT 'DBDBDB',
  `severity_color_1` varchar(6) NOT NULL DEFAULT 'D6F6FF',
  `severity_color_2` varchar(6) NOT NULL DEFAULT 'FFF6A5',
  `severity_color_3` varchar(6) NOT NULL DEFAULT 'FFB689',
  `severity_color_4` varchar(6) NOT NULL DEFAULT 'FF9999',
  `severity_color_5` varchar(6) NOT NULL DEFAULT 'FF3838',
  `severity_name_0` varchar(32) NOT NULL DEFAULT 'Not classified',
  `severity_name_1` varchar(32) NOT NULL DEFAULT 'Information',
  `severity_name_2` varchar(32) NOT NULL DEFAULT 'Warning',
  `severity_name_3` varchar(32) NOT NULL DEFAULT 'Average',
  `severity_name_4` varchar(32) NOT NULL DEFAULT 'High',
  `severity_name_5` varchar(32) NOT NULL DEFAULT 'Disaster',
  `ok_period` int(11) NOT NULL DEFAULT '1800',
  `blink_period` int(11) NOT NULL DEFAULT '1800',
  `problem_unack_color` varchar(6) NOT NULL DEFAULT 'DC0000',
  `problem_ack_color` varchar(6) NOT NULL DEFAULT 'DC0000',
  `ok_unack_color` varchar(6) NOT NULL DEFAULT '00AA00',
  `ok_ack_color` varchar(6) NOT NULL DEFAULT '00AA00',
  `problem_unack_style` int(11) NOT NULL DEFAULT '1',
  `problem_ack_style` int(11) NOT NULL DEFAULT '1',
  `ok_unack_style` int(11) NOT NULL DEFAULT '1',
  `ok_ack_style` int(11) NOT NULL DEFAULT '1',
  `snmptrap_logging` int(11) NOT NULL DEFAULT '1',
  `server_check_interval` int(11) NOT NULL DEFAULT '10',
  `hk_events_mode` int(11) NOT NULL DEFAULT '1',
  `hk_events_trigger` int(11) NOT NULL DEFAULT '365',
  `hk_events_internal` int(11) NOT NULL DEFAULT '365',
  `hk_events_discovery` int(11) NOT NULL DEFAULT '365',
  `hk_events_autoreg` int(11) NOT NULL DEFAULT '365',
  `hk_services_mode` int(11) NOT NULL DEFAULT '1',
  `hk_services` int(11) NOT NULL DEFAULT '365',
  `hk_audit_mode` int(11) NOT NULL DEFAULT '1',
  `hk_audit` int(11) NOT NULL DEFAULT '365',
  `hk_sessions_mode` int(11) NOT NULL DEFAULT '1',
  `hk_sessions` int(11) NOT NULL DEFAULT '365',
  `hk_history_mode` int(11) NOT NULL DEFAULT '1',
  `hk_history_global` int(11) NOT NULL DEFAULT '0',
  `hk_history` int(11) NOT NULL DEFAULT '90',
  `hk_trends_mode` int(11) NOT NULL DEFAULT '1',
  `hk_trends_global` int(11) NOT NULL DEFAULT '0',
  `hk_trends` int(11) NOT NULL DEFAULT '365',
  PRIMARY KEY (`configid`),
  KEY `config_1` (`alert_usrgrpid`),
  KEY `config_2` (`discovery_groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `dbversion`
--

CREATE TABLE IF NOT EXISTS `dbversion` (
  `mandatory` int(11) NOT NULL DEFAULT '0',
  `optional` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `dchecks`
--

CREATE TABLE IF NOT EXISTS `dchecks` (
  `dcheckid` bigint(20) unsigned NOT NULL,
  `druleid` bigint(20) unsigned NOT NULL,
  `type` int(11) NOT NULL DEFAULT '0',
  `key_` varchar(255) NOT NULL DEFAULT '',
  `snmp_community` varchar(255) NOT NULL DEFAULT '',
  `ports` varchar(255) NOT NULL DEFAULT '0',
  `snmpv3_securityname` varchar(64) NOT NULL DEFAULT '',
  `snmpv3_securitylevel` int(11) NOT NULL DEFAULT '0',
  `snmpv3_authpassphrase` varchar(64) NOT NULL DEFAULT '',
  `snmpv3_privpassphrase` varchar(64) NOT NULL DEFAULT '',
  `uniq` int(11) NOT NULL DEFAULT '0',
  `snmpv3_authprotocol` int(11) NOT NULL DEFAULT '0',
  `snmpv3_privprotocol` int(11) NOT NULL DEFAULT '0',
  `snmpv3_contextname` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`dcheckid`),
  KEY `dchecks_1` (`druleid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `dhosts`
--

CREATE TABLE IF NOT EXISTS `dhosts` (
  `dhostid` bigint(20) unsigned NOT NULL,
  `druleid` bigint(20) unsigned NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `lastup` int(11) NOT NULL DEFAULT '0',
  `lastdown` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`dhostid`),
  KEY `dhosts_1` (`druleid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `drules`
--

CREATE TABLE IF NOT EXISTS `drules` (
  `druleid` bigint(20) unsigned NOT NULL,
  `proxy_hostid` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `iprange` varchar(255) NOT NULL DEFAULT '',
  `delay` int(11) NOT NULL DEFAULT '3600',
  `nextcheck` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`druleid`),
  KEY `drules_1` (`proxy_hostid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `dservices`
--

CREATE TABLE IF NOT EXISTS `dservices` (
  `dserviceid` bigint(20) unsigned NOT NULL,
  `dhostid` bigint(20) unsigned NOT NULL,
  `type` int(11) NOT NULL DEFAULT '0',
  `key_` varchar(255) NOT NULL DEFAULT '',
  `value` varchar(255) NOT NULL DEFAULT '',
  `port` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '0',
  `lastup` int(11) NOT NULL DEFAULT '0',
  `lastdown` int(11) NOT NULL DEFAULT '0',
  `dcheckid` bigint(20) unsigned NOT NULL,
  `ip` varchar(39) NOT NULL DEFAULT '',
  `dns` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`dserviceid`),
  UNIQUE KEY `dservices_1` (`dcheckid`,`type`,`key_`,`ip`,`port`),
  KEY `dservices_2` (`dhostid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `escalations`
--

CREATE TABLE IF NOT EXISTS `escalations` (
  `escalationid` bigint(20) unsigned NOT NULL,
  `actionid` bigint(20) unsigned NOT NULL,
  `triggerid` bigint(20) unsigned DEFAULT NULL,
  `eventid` bigint(20) unsigned DEFAULT NULL,
  `r_eventid` bigint(20) unsigned DEFAULT NULL,
  `nextcheck` int(11) NOT NULL DEFAULT '0',
  `esc_step` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '0',
  `itemid` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`escalationid`),
  UNIQUE KEY `escalations_1` (`actionid`,`triggerid`,`itemid`,`escalationid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE IF NOT EXISTS `events` (
  `eventid` bigint(20) unsigned NOT NULL,
  `source` int(11) NOT NULL DEFAULT '0',
  `object` int(11) NOT NULL DEFAULT '0',
  `objectid` bigint(20) unsigned NOT NULL DEFAULT '0',
  `clock` int(11) NOT NULL DEFAULT '0',
  `value` int(11) NOT NULL DEFAULT '0',
  `acknowledged` int(11) NOT NULL DEFAULT '0',
  `ns` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`eventid`),
  KEY `events_1` (`source`,`object`,`objectid`,`clock`),
  KEY `events_2` (`source`,`object`,`clock`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `expressions`
--

CREATE TABLE IF NOT EXISTS `expressions` (
  `expressionid` bigint(20) unsigned NOT NULL,
  `regexpid` bigint(20) unsigned NOT NULL,
  `expression` varchar(255) NOT NULL DEFAULT '',
  `expression_type` int(11) NOT NULL DEFAULT '0',
  `exp_delimiter` varchar(1) NOT NULL DEFAULT '',
  `case_sensitive` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`expressionid`),
  KEY `expressions_1` (`regexpid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `functions`
--

CREATE TABLE IF NOT EXISTS `functions` (
  `functionid` bigint(20) unsigned NOT NULL,
  `itemid` bigint(20) unsigned NOT NULL,
  `triggerid` bigint(20) unsigned NOT NULL,
  `function` varchar(12) NOT NULL DEFAULT '',
  `parameter` varchar(255) NOT NULL DEFAULT '0',
  PRIMARY KEY (`functionid`),
  KEY `functions_1` (`triggerid`),
  KEY `functions_2` (`itemid`,`function`,`parameter`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `globalmacro`
--

CREATE TABLE IF NOT EXISTS `globalmacro` (
  `globalmacroid` bigint(20) unsigned NOT NULL,
  `macro` varchar(64) NOT NULL DEFAULT '',
  `value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`globalmacroid`),
  KEY `globalmacro_1` (`macro`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `globalvars`
--

CREATE TABLE IF NOT EXISTS `globalvars` (
  `globalvarid` bigint(20) unsigned NOT NULL,
  `snmp_lastsize` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`globalvarid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `graph_discovery`
--

CREATE TABLE IF NOT EXISTS `graph_discovery` (
  `graphdiscoveryid` bigint(20) unsigned NOT NULL,
  `graphid` bigint(20) unsigned NOT NULL,
  `parent_graphid` bigint(20) unsigned NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`graphdiscoveryid`),
  UNIQUE KEY `graph_discovery_1` (`graphid`,`parent_graphid`),
  KEY `graph_discovery_2` (`parent_graphid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `graph_theme`
--

CREATE TABLE IF NOT EXISTS `graph_theme` (
  `graphthemeid` bigint(20) unsigned NOT NULL,
  `description` varchar(64) NOT NULL DEFAULT '',
  `theme` varchar(64) NOT NULL DEFAULT '',
  `backgroundcolor` varchar(6) NOT NULL DEFAULT 'F0F0F0',
  `graphcolor` varchar(6) NOT NULL DEFAULT 'FFFFFF',
  `graphbordercolor` varchar(6) NOT NULL DEFAULT '222222',
  `gridcolor` varchar(6) NOT NULL DEFAULT 'CCCCCC',
  `maingridcolor` varchar(6) NOT NULL DEFAULT 'AAAAAA',
  `gridbordercolor` varchar(6) NOT NULL DEFAULT '000000',
  `textcolor` varchar(6) NOT NULL DEFAULT '202020',
  `highlightcolor` varchar(6) NOT NULL DEFAULT 'AA4444',
  `leftpercentilecolor` varchar(6) NOT NULL DEFAULT '11CC11',
  `rightpercentilecolor` varchar(6) NOT NULL DEFAULT 'CC1111',
  `nonworktimecolor` varchar(6) NOT NULL DEFAULT 'CCCCCC',
  `gridview` int(11) NOT NULL DEFAULT '1',
  `legendview` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`graphthemeid`),
  KEY `graph_theme_1` (`description`),
  KEY `graph_theme_2` (`theme`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `graphs`
--

CREATE TABLE IF NOT EXISTS `graphs` (
  `graphid` bigint(20) unsigned NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  `width` int(11) NOT NULL DEFAULT '900',
  `height` int(11) NOT NULL DEFAULT '200',
  `yaxismin` double(16,4) NOT NULL DEFAULT '0.0000',
  `yaxismax` double(16,4) NOT NULL DEFAULT '100.0000',
  `templateid` bigint(20) unsigned DEFAULT NULL,
  `show_work_period` int(11) NOT NULL DEFAULT '1',
  `show_triggers` int(11) NOT NULL DEFAULT '1',
  `graphtype` int(11) NOT NULL DEFAULT '0',
  `show_legend` int(11) NOT NULL DEFAULT '1',
  `show_3d` int(11) NOT NULL DEFAULT '0',
  `percent_left` double(16,4) NOT NULL DEFAULT '0.0000',
  `percent_right` double(16,4) NOT NULL DEFAULT '0.0000',
  `ymin_type` int(11) NOT NULL DEFAULT '0',
  `ymax_type` int(11) NOT NULL DEFAULT '0',
  `ymin_itemid` bigint(20) unsigned DEFAULT NULL,
  `ymax_itemid` bigint(20) unsigned DEFAULT NULL,
  `flags` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`graphid`),
  KEY `graphs_1` (`name`),
  KEY `graphs_2` (`templateid`),
  KEY `graphs_3` (`ymin_itemid`),
  KEY `graphs_4` (`ymax_itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `graphs_items`
--

CREATE TABLE IF NOT EXISTS `graphs_items` (
  `gitemid` bigint(20) unsigned NOT NULL,
  `graphid` bigint(20) unsigned NOT NULL,
  `itemid` bigint(20) unsigned NOT NULL,
  `drawtype` int(11) NOT NULL DEFAULT '0',
  `sortorder` int(11) NOT NULL DEFAULT '0',
  `color` varchar(6) NOT NULL DEFAULT '009600',
  `yaxisside` int(11) NOT NULL DEFAULT '0',
  `calc_fnc` int(11) NOT NULL DEFAULT '2',
  `type` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`gitemid`),
  KEY `graphs_items_1` (`itemid`),
  KEY `graphs_items_2` (`graphid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `group_discovery`
--

CREATE TABLE IF NOT EXISTS `group_discovery` (
  `groupid` bigint(20) unsigned NOT NULL,
  `parent_group_prototypeid` bigint(20) unsigned NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  `lastcheck` int(11) NOT NULL DEFAULT '0',
  `ts_delete` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`groupid`),
  KEY `c_group_discovery_2` (`parent_group_prototypeid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `group_prototype`
--

CREATE TABLE IF NOT EXISTS `group_prototype` (
  `group_prototypeid` bigint(20) unsigned NOT NULL,
  `hostid` bigint(20) unsigned NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  `groupid` bigint(20) unsigned DEFAULT NULL,
  `templateid` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`group_prototypeid`),
  KEY `group_prototype_1` (`hostid`),
  KEY `c_group_prototype_2` (`groupid`),
  KEY `c_group_prototype_3` (`templateid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE IF NOT EXISTS `groups` (
  `groupid` bigint(20) unsigned NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  `internal` int(11) NOT NULL DEFAULT '0',
  `flags` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`groupid`),
  KEY `groups_1` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `history`
--

CREATE TABLE IF NOT EXISTS `history` (
  `itemid` bigint(20) unsigned NOT NULL,
  `clock` int(11) NOT NULL DEFAULT '0',
  `value` double(16,4) NOT NULL DEFAULT '0.0000',
  `ns` int(11) NOT NULL DEFAULT '0',
  KEY `history_1` (`itemid`,`clock`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `history_log`
--

CREATE TABLE IF NOT EXISTS `history_log` (
  `id` bigint(20) unsigned NOT NULL,
  `itemid` bigint(20) unsigned NOT NULL,
  `clock` int(11) NOT NULL DEFAULT '0',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `source` varchar(64) NOT NULL DEFAULT '',
  `severity` int(11) NOT NULL DEFAULT '0',
  `value` text NOT NULL,
  `logeventid` int(11) NOT NULL DEFAULT '0',
  `ns` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `history_log_2` (`itemid`,`id`),
  KEY `history_log_1` (`itemid`,`clock`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `history_str`
--

CREATE TABLE IF NOT EXISTS `history_str` (
  `itemid` bigint(20) unsigned NOT NULL,
  `clock` int(11) NOT NULL DEFAULT '0',
  `value` varchar(255) NOT NULL DEFAULT '',
  `ns` int(11) NOT NULL DEFAULT '0',
  KEY `history_str_1` (`itemid`,`clock`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `history_str_sync`
--

CREATE TABLE IF NOT EXISTS `history_str_sync` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nodeid` int(11) NOT NULL,
  `itemid` bigint(20) unsigned NOT NULL,
  `clock` int(11) NOT NULL DEFAULT '0',
  `value` varchar(255) NOT NULL DEFAULT '',
  `ns` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `history_str_sync_1` (`nodeid`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `history_sync`
--

CREATE TABLE IF NOT EXISTS `history_sync` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nodeid` int(11) NOT NULL,
  `itemid` bigint(20) unsigned NOT NULL,
  `clock` int(11) NOT NULL DEFAULT '0',
  `value` double(16,4) NOT NULL DEFAULT '0.0000',
  `ns` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `history_sync_1` (`nodeid`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `history_text`
--

CREATE TABLE IF NOT EXISTS `history_text` (
  `id` bigint(20) unsigned NOT NULL,
  `itemid` bigint(20) unsigned NOT NULL,
  `clock` int(11) NOT NULL DEFAULT '0',
  `value` text NOT NULL,
  `ns` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `history_text_2` (`itemid`,`id`),
  KEY `history_text_1` (`itemid`,`clock`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `history_uint`
--

CREATE TABLE IF NOT EXISTS `history_uint` (
  `itemid` bigint(20) unsigned NOT NULL,
  `clock` int(11) NOT NULL DEFAULT '0',
  `value` bigint(20) unsigned NOT NULL DEFAULT '0',
  `ns` int(11) NOT NULL DEFAULT '0',
  KEY `history_uint_1` (`itemid`,`clock`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `history_uint_sync`
--

CREATE TABLE IF NOT EXISTS `history_uint_sync` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nodeid` int(11) NOT NULL,
  `itemid` bigint(20) unsigned NOT NULL,
  `clock` int(11) NOT NULL DEFAULT '0',
  `value` bigint(20) unsigned NOT NULL DEFAULT '0',
  `ns` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `history_uint_sync_1` (`nodeid`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `host_discovery`
--

CREATE TABLE IF NOT EXISTS `host_discovery` (
  `hostid` bigint(20) unsigned NOT NULL,
  `parent_hostid` bigint(20) unsigned DEFAULT NULL,
  `parent_itemid` bigint(20) unsigned DEFAULT NULL,
  `host` varchar(64) NOT NULL DEFAULT '',
  `lastcheck` int(11) NOT NULL DEFAULT '0',
  `ts_delete` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`hostid`),
  KEY `c_host_discovery_2` (`parent_hostid`),
  KEY `c_host_discovery_3` (`parent_itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `host_inventory`
--

CREATE TABLE IF NOT EXISTS `host_inventory` (
  `hostid` bigint(20) unsigned NOT NULL,
  `inventory_mode` int(11) NOT NULL DEFAULT '0',
  `type` varchar(64) NOT NULL DEFAULT '',
  `type_full` varchar(64) NOT NULL DEFAULT '',
  `name` varchar(64) NOT NULL DEFAULT '',
  `alias` varchar(64) NOT NULL DEFAULT '',
  `os` varchar(64) NOT NULL DEFAULT '',
  `os_full` varchar(255) NOT NULL DEFAULT '',
  `os_short` varchar(64) NOT NULL DEFAULT '',
  `serialno_a` varchar(64) NOT NULL DEFAULT '',
  `serialno_b` varchar(64) NOT NULL DEFAULT '',
  `tag` varchar(64) NOT NULL DEFAULT '',
  `asset_tag` varchar(64) NOT NULL DEFAULT '',
  `macaddress_a` varchar(64) NOT NULL DEFAULT '',
  `macaddress_b` varchar(64) NOT NULL DEFAULT '',
  `hardware` varchar(255) NOT NULL DEFAULT '',
  `hardware_full` text NOT NULL,
  `software` varchar(255) NOT NULL DEFAULT '',
  `software_full` text NOT NULL,
  `software_app_a` varchar(64) NOT NULL DEFAULT '',
  `software_app_b` varchar(64) NOT NULL DEFAULT '',
  `software_app_c` varchar(64) NOT NULL DEFAULT '',
  `software_app_d` varchar(64) NOT NULL DEFAULT '',
  `software_app_e` varchar(64) NOT NULL DEFAULT '',
  `contact` text NOT NULL,
  `location` text NOT NULL,
  `location_lat` varchar(16) NOT NULL DEFAULT '',
  `location_lon` varchar(16) NOT NULL DEFAULT '',
  `notes` text NOT NULL,
  `chassis` varchar(64) NOT NULL DEFAULT '',
  `model` varchar(64) NOT NULL DEFAULT '',
  `hw_arch` varchar(32) NOT NULL DEFAULT '',
  `vendor` varchar(64) NOT NULL DEFAULT '',
  `contract_number` varchar(64) NOT NULL DEFAULT '',
  `installer_name` varchar(64) NOT NULL DEFAULT '',
  `deployment_status` varchar(64) NOT NULL DEFAULT '',
  `url_a` varchar(255) NOT NULL DEFAULT '',
  `url_b` varchar(255) NOT NULL DEFAULT '',
  `url_c` varchar(255) NOT NULL DEFAULT '',
  `host_networks` text NOT NULL,
  `host_netmask` varchar(39) NOT NULL DEFAULT '',
  `host_router` varchar(39) NOT NULL DEFAULT '',
  `oob_ip` varchar(39) NOT NULL DEFAULT '',
  `oob_netmask` varchar(39) NOT NULL DEFAULT '',
  `oob_router` varchar(39) NOT NULL DEFAULT '',
  `date_hw_purchase` varchar(64) NOT NULL DEFAULT '',
  `date_hw_install` varchar(64) NOT NULL DEFAULT '',
  `date_hw_expiry` varchar(64) NOT NULL DEFAULT '',
  `date_hw_decomm` varchar(64) NOT NULL DEFAULT '',
  `site_address_a` varchar(128) NOT NULL DEFAULT '',
  `site_address_b` varchar(128) NOT NULL DEFAULT '',
  `site_address_c` varchar(128) NOT NULL DEFAULT '',
  `site_city` varchar(128) NOT NULL DEFAULT '',
  `site_state` varchar(64) NOT NULL DEFAULT '',
  `site_country` varchar(64) NOT NULL DEFAULT '',
  `site_zip` varchar(64) NOT NULL DEFAULT '',
  `site_rack` varchar(128) NOT NULL DEFAULT '',
  `site_notes` text NOT NULL,
  `poc_1_name` varchar(128) NOT NULL DEFAULT '',
  `poc_1_email` varchar(128) NOT NULL DEFAULT '',
  `poc_1_phone_a` varchar(64) NOT NULL DEFAULT '',
  `poc_1_phone_b` varchar(64) NOT NULL DEFAULT '',
  `poc_1_cell` varchar(64) NOT NULL DEFAULT '',
  `poc_1_screen` varchar(64) NOT NULL DEFAULT '',
  `poc_1_notes` text NOT NULL,
  `poc_2_name` varchar(128) NOT NULL DEFAULT '',
  `poc_2_email` varchar(128) NOT NULL DEFAULT '',
  `poc_2_phone_a` varchar(64) NOT NULL DEFAULT '',
  `poc_2_phone_b` varchar(64) NOT NULL DEFAULT '',
  `poc_2_cell` varchar(64) NOT NULL DEFAULT '',
  `poc_2_screen` varchar(64) NOT NULL DEFAULT '',
  `poc_2_notes` text NOT NULL,
  PRIMARY KEY (`hostid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `hostmacro`
--

CREATE TABLE IF NOT EXISTS `hostmacro` (
  `hostmacroid` bigint(20) unsigned NOT NULL,
  `hostid` bigint(20) unsigned NOT NULL,
  `macro` varchar(64) NOT NULL DEFAULT '',
  `value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`hostmacroid`),
  UNIQUE KEY `hostmacro_1` (`hostid`,`macro`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `hosts`
--

CREATE TABLE IF NOT EXISTS `hosts` (
  `hostid` bigint(20) unsigned NOT NULL,
  `proxy_hostid` bigint(20) unsigned DEFAULT NULL,
  `host` varchar(64) NOT NULL DEFAULT '',
  `status` int(11) NOT NULL DEFAULT '0',
  `disable_until` int(11) NOT NULL DEFAULT '0',
  `error` varchar(128) NOT NULL DEFAULT '',
  `available` int(11) NOT NULL DEFAULT '0',
  `errors_from` int(11) NOT NULL DEFAULT '0',
  `lastaccess` int(11) NOT NULL DEFAULT '0',
  `ipmi_authtype` int(11) NOT NULL DEFAULT '0',
  `ipmi_privilege` int(11) NOT NULL DEFAULT '2',
  `ipmi_username` varchar(16) NOT NULL DEFAULT '',
  `ipmi_password` varchar(20) NOT NULL DEFAULT '',
  `ipmi_disable_until` int(11) NOT NULL DEFAULT '0',
  `ipmi_available` int(11) NOT NULL DEFAULT '0',
  `snmp_disable_until` int(11) NOT NULL DEFAULT '0',
  `snmp_available` int(11) NOT NULL DEFAULT '0',
  `maintenanceid` bigint(20) unsigned DEFAULT NULL,
  `maintenance_status` int(11) NOT NULL DEFAULT '0',
  `maintenance_type` int(11) NOT NULL DEFAULT '0',
  `maintenance_from` int(11) NOT NULL DEFAULT '0',
  `ipmi_errors_from` int(11) NOT NULL DEFAULT '0',
  `snmp_errors_from` int(11) NOT NULL DEFAULT '0',
  `ipmi_error` varchar(128) NOT NULL DEFAULT '',
  `snmp_error` varchar(128) NOT NULL DEFAULT '',
  `jmx_disable_until` int(11) NOT NULL DEFAULT '0',
  `jmx_available` int(11) NOT NULL DEFAULT '0',
  `jmx_errors_from` int(11) NOT NULL DEFAULT '0',
  `jmx_error` varchar(128) NOT NULL DEFAULT '',
  `name` varchar(64) NOT NULL DEFAULT '',
  `flags` int(11) NOT NULL DEFAULT '0',
  `templateid` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`hostid`),
  KEY `hosts_1` (`host`),
  KEY `hosts_2` (`status`),
  KEY `hosts_3` (`proxy_hostid`),
  KEY `hosts_4` (`name`),
  KEY `hosts_5` (`maintenanceid`),
  KEY `c_hosts_3` (`templateid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `hosts_groups`
--

CREATE TABLE IF NOT EXISTS `hosts_groups` (
  `hostgroupid` bigint(20) unsigned NOT NULL,
  `hostid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`hostgroupid`),
  UNIQUE KEY `hosts_groups_1` (`hostid`,`groupid`),
  KEY `hosts_groups_2` (`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `hosts_templates`
--

CREATE TABLE IF NOT EXISTS `hosts_templates` (
  `hosttemplateid` bigint(20) unsigned NOT NULL,
  `hostid` bigint(20) unsigned NOT NULL,
  `templateid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`hosttemplateid`),
  UNIQUE KEY `hosts_templates_1` (`hostid`,`templateid`),
  KEY `hosts_templates_2` (`templateid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `housekeeper`
--

CREATE TABLE IF NOT EXISTS `housekeeper` (
  `housekeeperid` bigint(20) unsigned NOT NULL,
  `tablename` varchar(64) NOT NULL DEFAULT '',
  `field` varchar(64) NOT NULL DEFAULT '',
  `value` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`housekeeperid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `httpstep`
--

CREATE TABLE IF NOT EXISTS `httpstep` (
  `httpstepid` bigint(20) unsigned NOT NULL,
  `httptestid` bigint(20) unsigned NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  `no` int(11) NOT NULL DEFAULT '0',
  `url` varchar(255) NOT NULL DEFAULT '',
  `timeout` int(11) NOT NULL DEFAULT '30',
  `posts` text NOT NULL,
  `required` varchar(255) NOT NULL DEFAULT '',
  `status_codes` varchar(255) NOT NULL DEFAULT '',
  `variables` text NOT NULL,
  PRIMARY KEY (`httpstepid`),
  KEY `httpstep_1` (`httptestid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `httpstepitem`
--

CREATE TABLE IF NOT EXISTS `httpstepitem` (
  `httpstepitemid` bigint(20) unsigned NOT NULL,
  `httpstepid` bigint(20) unsigned NOT NULL,
  `itemid` bigint(20) unsigned NOT NULL,
  `type` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`httpstepitemid`),
  UNIQUE KEY `httpstepitem_1` (`httpstepid`,`itemid`),
  KEY `httpstepitem_2` (`itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `httptest`
--

CREATE TABLE IF NOT EXISTS `httptest` (
  `httptestid` bigint(20) unsigned NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  `applicationid` bigint(20) unsigned DEFAULT NULL,
  `nextcheck` int(11) NOT NULL DEFAULT '0',
  `delay` int(11) NOT NULL DEFAULT '60',
  `status` int(11) NOT NULL DEFAULT '0',
  `variables` text NOT NULL,
  `agent` varchar(255) NOT NULL DEFAULT '',
  `authentication` int(11) NOT NULL DEFAULT '0',
  `http_user` varchar(64) NOT NULL DEFAULT '',
  `http_password` varchar(64) NOT NULL DEFAULT '',
  `hostid` bigint(20) unsigned NOT NULL,
  `templateid` bigint(20) unsigned DEFAULT NULL,
  `http_proxy` varchar(255) NOT NULL DEFAULT '',
  `retries` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`httptestid`),
  UNIQUE KEY `httptest_2` (`hostid`,`name`),
  KEY `httptest_1` (`applicationid`),
  KEY `httptest_3` (`status`),
  KEY `httptest_4` (`templateid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `httptestitem`
--

CREATE TABLE IF NOT EXISTS `httptestitem` (
  `httptestitemid` bigint(20) unsigned NOT NULL,
  `httptestid` bigint(20) unsigned NOT NULL,
  `itemid` bigint(20) unsigned NOT NULL,
  `type` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`httptestitemid`),
  UNIQUE KEY `httptestitem_1` (`httptestid`,`itemid`),
  KEY `httptestitem_2` (`itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `icon_map`
--

CREATE TABLE IF NOT EXISTS `icon_map` (
  `iconmapid` bigint(20) unsigned NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  `default_iconid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`iconmapid`),
  KEY `icon_map_1` (`name`),
  KEY `icon_map_2` (`default_iconid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `icon_mapping`
--

CREATE TABLE IF NOT EXISTS `icon_mapping` (
  `iconmappingid` bigint(20) unsigned NOT NULL,
  `iconmapid` bigint(20) unsigned NOT NULL,
  `iconid` bigint(20) unsigned NOT NULL,
  `inventory_link` int(11) NOT NULL DEFAULT '0',
  `expression` varchar(64) NOT NULL DEFAULT '',
  `sortorder` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`iconmappingid`),
  KEY `icon_mapping_1` (`iconmapid`),
  KEY `icon_mapping_2` (`iconid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ids`
--

CREATE TABLE IF NOT EXISTS `ids` (
  `nodeid` int(11) NOT NULL,
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `field_name` varchar(64) NOT NULL DEFAULT '',
  `nextid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`nodeid`,`table_name`,`field_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `images`
--

CREATE TABLE IF NOT EXISTS `images` (
  `imageid` bigint(20) unsigned NOT NULL,
  `imagetype` int(11) NOT NULL DEFAULT '0',
  `name` varchar(64) NOT NULL DEFAULT '0',
  `image` longblob NOT NULL,
  PRIMARY KEY (`imageid`),
  KEY `images_1` (`imagetype`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `interface`
--

CREATE TABLE IF NOT EXISTS `interface` (
  `interfaceid` bigint(20) unsigned NOT NULL,
  `hostid` bigint(20) unsigned NOT NULL,
  `main` int(11) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '0',
  `useip` int(11) NOT NULL DEFAULT '1',
  `ip` varchar(64) NOT NULL DEFAULT '127.0.0.1',
  `dns` varchar(64) NOT NULL DEFAULT '',
  `port` varchar(64) NOT NULL DEFAULT '10050',
  PRIMARY KEY (`interfaceid`),
  KEY `interface_1` (`hostid`,`type`),
  KEY `interface_2` (`ip`,`dns`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `interface_discovery`
--

CREATE TABLE IF NOT EXISTS `interface_discovery` (
  `interfaceid` bigint(20) unsigned NOT NULL,
  `parent_interfaceid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`interfaceid`),
  KEY `c_interface_discovery_2` (`parent_interfaceid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `item_discovery`
--

CREATE TABLE IF NOT EXISTS `item_discovery` (
  `itemdiscoveryid` bigint(20) unsigned NOT NULL,
  `itemid` bigint(20) unsigned NOT NULL,
  `parent_itemid` bigint(20) unsigned NOT NULL,
  `key_` varchar(255) NOT NULL DEFAULT '',
  `lastcheck` int(11) NOT NULL DEFAULT '0',
  `ts_delete` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`itemdiscoveryid`),
  UNIQUE KEY `item_discovery_1` (`itemid`,`parent_itemid`),
  KEY `item_discovery_2` (`parent_itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE IF NOT EXISTS `items` (
  `itemid` bigint(20) unsigned NOT NULL,
  `type` int(11) NOT NULL DEFAULT '0',
  `snmp_community` varchar(64) NOT NULL DEFAULT '',
  `snmp_oid` varchar(255) NOT NULL DEFAULT '',
  `hostid` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `key_` varchar(255) NOT NULL DEFAULT '',
  `delay` int(11) NOT NULL DEFAULT '0',
  `history` int(11) NOT NULL DEFAULT '90',
  `trends` int(11) NOT NULL DEFAULT '365',
  `status` int(11) NOT NULL DEFAULT '0',
  `value_type` int(11) NOT NULL DEFAULT '0',
  `trapper_hosts` varchar(255) NOT NULL DEFAULT '',
  `units` varchar(255) NOT NULL DEFAULT '',
  `multiplier` int(11) NOT NULL DEFAULT '0',
  `delta` int(11) NOT NULL DEFAULT '0',
  `snmpv3_securityname` varchar(64) NOT NULL DEFAULT '',
  `snmpv3_securitylevel` int(11) NOT NULL DEFAULT '0',
  `snmpv3_authpassphrase` varchar(64) NOT NULL DEFAULT '',
  `snmpv3_privpassphrase` varchar(64) NOT NULL DEFAULT '',
  `formula` varchar(255) NOT NULL DEFAULT '1',
  `error` varchar(128) NOT NULL DEFAULT '',
  `lastlogsize` bigint(20) unsigned NOT NULL DEFAULT '0',
  `logtimefmt` varchar(64) NOT NULL DEFAULT '',
  `templateid` bigint(20) unsigned DEFAULT NULL,
  `valuemapid` bigint(20) unsigned DEFAULT NULL,
  `delay_flex` varchar(255) NOT NULL DEFAULT '',
  `params` text NOT NULL,
  `ipmi_sensor` varchar(128) NOT NULL DEFAULT '',
  `data_type` int(11) NOT NULL DEFAULT '0',
  `authtype` int(11) NOT NULL DEFAULT '0',
  `username` varchar(64) NOT NULL DEFAULT '',
  `password` varchar(64) NOT NULL DEFAULT '',
  `publickey` varchar(64) NOT NULL DEFAULT '',
  `privatekey` varchar(64) NOT NULL DEFAULT '',
  `mtime` int(11) NOT NULL DEFAULT '0',
  `flags` int(11) NOT NULL DEFAULT '0',
  `filter` varchar(255) NOT NULL DEFAULT '',
  `interfaceid` bigint(20) unsigned DEFAULT NULL,
  `port` varchar(64) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `inventory_link` int(11) NOT NULL DEFAULT '0',
  `lifetime` varchar(64) NOT NULL DEFAULT '30',
  `snmpv3_authprotocol` int(11) NOT NULL DEFAULT '0',
  `snmpv3_privprotocol` int(11) NOT NULL DEFAULT '0',
  `state` int(11) NOT NULL DEFAULT '0',
  `snmpv3_contextname` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`itemid`),
  UNIQUE KEY `items_1` (`hostid`,`key_`),
  KEY `items_3` (`status`),
  KEY `items_4` (`templateid`),
  KEY `items_5` (`valuemapid`),
  KEY `items_6` (`interfaceid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `items_applications`
--

CREATE TABLE IF NOT EXISTS `items_applications` (
  `itemappid` bigint(20) unsigned NOT NULL,
  `applicationid` bigint(20) unsigned NOT NULL,
  `itemid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`itemappid`),
  UNIQUE KEY `items_applications_1` (`applicationid`,`itemid`),
  KEY `items_applications_2` (`itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `maintenances`
--

CREATE TABLE IF NOT EXISTS `maintenances` (
  `maintenanceid` bigint(20) unsigned NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  `maintenance_type` int(11) NOT NULL DEFAULT '0',
  `description` text NOT NULL,
  `active_since` int(11) NOT NULL DEFAULT '0',
  `active_till` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`maintenanceid`),
  KEY `maintenances_1` (`active_since`,`active_till`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `maintenances_groups`
--

CREATE TABLE IF NOT EXISTS `maintenances_groups` (
  `maintenance_groupid` bigint(20) unsigned NOT NULL,
  `maintenanceid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`maintenance_groupid`),
  UNIQUE KEY `maintenances_groups_1` (`maintenanceid`,`groupid`),
  KEY `maintenances_groups_2` (`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `maintenances_hosts`
--

CREATE TABLE IF NOT EXISTS `maintenances_hosts` (
  `maintenance_hostid` bigint(20) unsigned NOT NULL,
  `maintenanceid` bigint(20) unsigned NOT NULL,
  `hostid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`maintenance_hostid`),
  UNIQUE KEY `maintenances_hosts_1` (`maintenanceid`,`hostid`),
  KEY `maintenances_hosts_2` (`hostid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `maintenances_windows`
--

CREATE TABLE IF NOT EXISTS `maintenances_windows` (
  `maintenance_timeperiodid` bigint(20) unsigned NOT NULL,
  `maintenanceid` bigint(20) unsigned NOT NULL,
  `timeperiodid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`maintenance_timeperiodid`),
  UNIQUE KEY `maintenances_windows_1` (`maintenanceid`,`timeperiodid`),
  KEY `maintenances_windows_2` (`timeperiodid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `mappings`
--

CREATE TABLE IF NOT EXISTS `mappings` (
  `mappingid` bigint(20) unsigned NOT NULL,
  `valuemapid` bigint(20) unsigned NOT NULL,
  `value` varchar(64) NOT NULL DEFAULT '',
  `newvalue` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`mappingid`),
  KEY `mappings_1` (`valuemapid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE IF NOT EXISTS `media` (
  `mediaid` bigint(20) unsigned NOT NULL,
  `userid` bigint(20) unsigned NOT NULL,
  `mediatypeid` bigint(20) unsigned NOT NULL,
  `sendto` varchar(100) NOT NULL DEFAULT '',
  `active` int(11) NOT NULL DEFAULT '0',
  `severity` int(11) NOT NULL DEFAULT '63',
  `period` varchar(100) NOT NULL DEFAULT '1-7,00:00-24:00',
  PRIMARY KEY (`mediaid`),
  KEY `media_1` (`userid`),
  KEY `media_2` (`mediatypeid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `media_type`
--

CREATE TABLE IF NOT EXISTS `media_type` (
  `mediatypeid` bigint(20) unsigned NOT NULL,
  `type` int(11) NOT NULL DEFAULT '0',
  `description` varchar(100) NOT NULL DEFAULT '',
  `smtp_server` varchar(255) NOT NULL DEFAULT '',
  `smtp_helo` varchar(255) NOT NULL DEFAULT '',
  `smtp_email` varchar(255) NOT NULL DEFAULT '',
  `exec_path` varchar(255) NOT NULL DEFAULT '',
  `gsm_modem` varchar(255) NOT NULL DEFAULT '',
  `username` varchar(255) NOT NULL DEFAULT '',
  `passwd` varchar(255) NOT NULL DEFAULT '',
  `status` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`mediatypeid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `node_cksum`
--

CREATE TABLE IF NOT EXISTS `node_cksum` (
  `nodeid` int(11) NOT NULL,
  `tablename` varchar(64) NOT NULL DEFAULT '',
  `recordid` bigint(20) unsigned NOT NULL,
  `cksumtype` int(11) NOT NULL DEFAULT '0',
  `cksum` text NOT NULL,
  `sync` char(128) NOT NULL DEFAULT '',
  KEY `node_cksum_1` (`nodeid`,`cksumtype`,`tablename`,`recordid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `nodes`
--

CREATE TABLE IF NOT EXISTS `nodes` (
  `nodeid` int(11) NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '0',
  `ip` varchar(39) NOT NULL DEFAULT '',
  `port` int(11) NOT NULL DEFAULT '10051',
  `nodetype` int(11) NOT NULL DEFAULT '0',
  `masterid` int(11) DEFAULT NULL,
  PRIMARY KEY (`nodeid`),
  KEY `nodes_1` (`masterid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `opcommand`
--

CREATE TABLE IF NOT EXISTS `opcommand` (
  `operationid` bigint(20) unsigned NOT NULL,
  `type` int(11) NOT NULL DEFAULT '0',
  `scriptid` bigint(20) unsigned DEFAULT NULL,
  `execute_on` int(11) NOT NULL DEFAULT '0',
  `port` varchar(64) NOT NULL DEFAULT '',
  `authtype` int(11) NOT NULL DEFAULT '0',
  `username` varchar(64) NOT NULL DEFAULT '',
  `password` varchar(64) NOT NULL DEFAULT '',
  `publickey` varchar(64) NOT NULL DEFAULT '',
  `privatekey` varchar(64) NOT NULL DEFAULT '',
  `command` text NOT NULL,
  PRIMARY KEY (`operationid`),
  KEY `opcommand_1` (`scriptid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `opcommand_grp`
--

CREATE TABLE IF NOT EXISTS `opcommand_grp` (
  `opcommand_grpid` bigint(20) unsigned NOT NULL,
  `operationid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`opcommand_grpid`),
  KEY `opcommand_grp_1` (`operationid`),
  KEY `opcommand_grp_2` (`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `opcommand_hst`
--

CREATE TABLE IF NOT EXISTS `opcommand_hst` (
  `opcommand_hstid` bigint(20) unsigned NOT NULL,
  `operationid` bigint(20) unsigned NOT NULL,
  `hostid` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`opcommand_hstid`),
  KEY `opcommand_hst_1` (`operationid`),
  KEY `opcommand_hst_2` (`hostid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `opconditions`
--

CREATE TABLE IF NOT EXISTS `opconditions` (
  `opconditionid` bigint(20) unsigned NOT NULL,
  `operationid` bigint(20) unsigned NOT NULL,
  `conditiontype` int(11) NOT NULL DEFAULT '0',
  `operator` int(11) NOT NULL DEFAULT '0',
  `value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`opconditionid`),
  KEY `opconditions_1` (`operationid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `operations`
--

CREATE TABLE IF NOT EXISTS `operations` (
  `operationid` bigint(20) unsigned NOT NULL,
  `actionid` bigint(20) unsigned NOT NULL,
  `operationtype` int(11) NOT NULL DEFAULT '0',
  `esc_period` int(11) NOT NULL DEFAULT '0',
  `esc_step_from` int(11) NOT NULL DEFAULT '1',
  `esc_step_to` int(11) NOT NULL DEFAULT '1',
  `evaltype` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`operationid`),
  KEY `operations_1` (`actionid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `opgroup`
--

CREATE TABLE IF NOT EXISTS `opgroup` (
  `opgroupid` bigint(20) unsigned NOT NULL,
  `operationid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`opgroupid`),
  UNIQUE KEY `opgroup_1` (`operationid`,`groupid`),
  KEY `opgroup_2` (`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `opmessage`
--

CREATE TABLE IF NOT EXISTS `opmessage` (
  `operationid` bigint(20) unsigned NOT NULL,
  `default_msg` int(11) NOT NULL DEFAULT '0',
  `subject` varchar(255) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `mediatypeid` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`operationid`),
  KEY `opmessage_1` (`mediatypeid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `opmessage_grp`
--

CREATE TABLE IF NOT EXISTS `opmessage_grp` (
  `opmessage_grpid` bigint(20) unsigned NOT NULL,
  `operationid` bigint(20) unsigned NOT NULL,
  `usrgrpid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`opmessage_grpid`),
  UNIQUE KEY `opmessage_grp_1` (`operationid`,`usrgrpid`),
  KEY `opmessage_grp_2` (`usrgrpid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `opmessage_usr`
--

CREATE TABLE IF NOT EXISTS `opmessage_usr` (
  `opmessage_usrid` bigint(20) unsigned NOT NULL,
  `operationid` bigint(20) unsigned NOT NULL,
  `userid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`opmessage_usrid`),
  UNIQUE KEY `opmessage_usr_1` (`operationid`,`userid`),
  KEY `opmessage_usr_2` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `optemplate`
--

CREATE TABLE IF NOT EXISTS `optemplate` (
  `optemplateid` bigint(20) unsigned NOT NULL,
  `operationid` bigint(20) unsigned NOT NULL,
  `templateid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`optemplateid`),
  UNIQUE KEY `optemplate_1` (`operationid`,`templateid`),
  KEY `optemplate_2` (`templateid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE IF NOT EXISTS `profiles` (
  `profileid` bigint(20) unsigned NOT NULL,
  `userid` bigint(20) unsigned NOT NULL,
  `idx` varchar(96) NOT NULL DEFAULT '',
  `idx2` bigint(20) unsigned NOT NULL DEFAULT '0',
  `value_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `value_int` int(11) NOT NULL DEFAULT '0',
  `value_str` varchar(255) NOT NULL DEFAULT '',
  `source` varchar(96) NOT NULL DEFAULT '',
  `type` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`profileid`),
  KEY `profiles_1` (`userid`,`idx`,`idx2`),
  KEY `profiles_2` (`userid`,`profileid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `proxy_autoreg_host`
--

CREATE TABLE IF NOT EXISTS `proxy_autoreg_host` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `clock` int(11) NOT NULL DEFAULT '0',
  `host` varchar(64) NOT NULL DEFAULT '',
  `listen_ip` varchar(39) NOT NULL DEFAULT '',
  `listen_port` int(11) NOT NULL DEFAULT '0',
  `listen_dns` varchar(64) NOT NULL DEFAULT '',
  `host_metadata` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `proxy_autoreg_host_1` (`clock`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `proxy_dhistory`
--

CREATE TABLE IF NOT EXISTS `proxy_dhistory` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `clock` int(11) NOT NULL DEFAULT '0',
  `druleid` bigint(20) unsigned NOT NULL,
  `type` int(11) NOT NULL DEFAULT '0',
  `ip` varchar(39) NOT NULL DEFAULT '',
  `port` int(11) NOT NULL DEFAULT '0',
  `key_` varchar(255) NOT NULL DEFAULT '',
  `value` varchar(255) NOT NULL DEFAULT '',
  `status` int(11) NOT NULL DEFAULT '0',
  `dcheckid` bigint(20) unsigned DEFAULT NULL,
  `dns` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `proxy_dhistory_1` (`clock`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `proxy_history`
--

CREATE TABLE IF NOT EXISTS `proxy_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `itemid` bigint(20) unsigned NOT NULL,
  `clock` int(11) NOT NULL DEFAULT '0',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `source` varchar(64) NOT NULL DEFAULT '',
  `severity` int(11) NOT NULL DEFAULT '0',
  `value` longtext NOT NULL,
  `logeventid` int(11) NOT NULL DEFAULT '0',
  `ns` int(11) NOT NULL DEFAULT '0',
  `state` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `proxy_history_1` (`clock`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `regexps`
--

CREATE TABLE IF NOT EXISTS `regexps` (
  `regexpid` bigint(20) unsigned NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  `test_string` text NOT NULL,
  PRIMARY KEY (`regexpid`),
  KEY `regexps_1` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rights`
--

CREATE TABLE IF NOT EXISTS `rights` (
  `rightid` bigint(20) unsigned NOT NULL,
  `groupid` bigint(20) unsigned NOT NULL,
  `permission` int(11) NOT NULL DEFAULT '0',
  `id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`rightid`),
  KEY `rights_1` (`groupid`),
  KEY `rights_2` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `screens`
--

CREATE TABLE IF NOT EXISTS `screens` (
  `screenid` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `hsize` int(11) NOT NULL DEFAULT '1',
  `vsize` int(11) NOT NULL DEFAULT '1',
  `templateid` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`screenid`),
  KEY `screens_1` (`templateid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `screens_items`
--

CREATE TABLE IF NOT EXISTS `screens_items` (
  `screenitemid` bigint(20) unsigned NOT NULL,
  `screenid` bigint(20) unsigned NOT NULL,
  `resourcetype` int(11) NOT NULL DEFAULT '0',
  `resourceid` bigint(20) unsigned NOT NULL DEFAULT '0',
  `width` int(11) NOT NULL DEFAULT '320',
  `height` int(11) NOT NULL DEFAULT '200',
  `x` int(11) NOT NULL DEFAULT '0',
  `y` int(11) NOT NULL DEFAULT '0',
  `colspan` int(11) NOT NULL DEFAULT '0',
  `rowspan` int(11) NOT NULL DEFAULT '0',
  `elements` int(11) NOT NULL DEFAULT '25',
  `valign` int(11) NOT NULL DEFAULT '0',
  `halign` int(11) NOT NULL DEFAULT '0',
  `style` int(11) NOT NULL DEFAULT '0',
  `url` varchar(255) NOT NULL DEFAULT '',
  `dynamic` int(11) NOT NULL DEFAULT '0',
  `sort_triggers` int(11) NOT NULL DEFAULT '0',
  `application` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`screenitemid`),
  KEY `screens_items_1` (`screenid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `scripts`
--

CREATE TABLE IF NOT EXISTS `scripts` (
  `scriptid` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `command` varchar(255) NOT NULL DEFAULT '',
  `host_access` int(11) NOT NULL DEFAULT '2',
  `usrgrpid` bigint(20) unsigned DEFAULT NULL,
  `groupid` bigint(20) unsigned DEFAULT NULL,
  `description` text NOT NULL,
  `confirmation` varchar(255) NOT NULL DEFAULT '',
  `type` int(11) NOT NULL DEFAULT '0',
  `execute_on` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`scriptid`),
  KEY `scripts_1` (`usrgrpid`),
  KEY `scripts_2` (`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `service_alarms`
--

CREATE TABLE IF NOT EXISTS `service_alarms` (
  `servicealarmid` bigint(20) unsigned NOT NULL,
  `serviceid` bigint(20) unsigned NOT NULL,
  `clock` int(11) NOT NULL DEFAULT '0',
  `value` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`servicealarmid`),
  KEY `service_alarms_1` (`serviceid`,`clock`),
  KEY `service_alarms_2` (`clock`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE IF NOT EXISTS `services` (
  `serviceid` bigint(20) unsigned NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  `status` int(11) NOT NULL DEFAULT '0',
  `algorithm` int(11) NOT NULL DEFAULT '0',
  `triggerid` bigint(20) unsigned DEFAULT NULL,
  `showsla` int(11) NOT NULL DEFAULT '0',
  `goodsla` double(16,4) NOT NULL DEFAULT '99.9000',
  `sortorder` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`serviceid`),
  KEY `services_1` (`triggerid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `services_links`
--

CREATE TABLE IF NOT EXISTS `services_links` (
  `linkid` bigint(20) unsigned NOT NULL,
  `serviceupid` bigint(20) unsigned NOT NULL,
  `servicedownid` bigint(20) unsigned NOT NULL,
  `soft` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`linkid`),
  UNIQUE KEY `services_links_2` (`serviceupid`,`servicedownid`),
  KEY `services_links_1` (`servicedownid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `services_times`
--

CREATE TABLE IF NOT EXISTS `services_times` (
  `timeid` bigint(20) unsigned NOT NULL,
  `serviceid` bigint(20) unsigned NOT NULL,
  `type` int(11) NOT NULL DEFAULT '0',
  `ts_from` int(11) NOT NULL DEFAULT '0',
  `ts_to` int(11) NOT NULL DEFAULT '0',
  `note` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`timeid`),
  KEY `services_times_1` (`serviceid`,`type`,`ts_from`,`ts_to`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE IF NOT EXISTS `sessions` (
  `sessionid` varchar(32) NOT NULL DEFAULT '',
  `userid` bigint(20) unsigned NOT NULL,
  `lastaccess` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sessionid`),
  KEY `sessions_1` (`userid`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `slides`
--

CREATE TABLE IF NOT EXISTS `slides` (
  `slideid` bigint(20) unsigned NOT NULL,
  `slideshowid` bigint(20) unsigned NOT NULL,
  `screenid` bigint(20) unsigned NOT NULL,
  `step` int(11) NOT NULL DEFAULT '0',
  `delay` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`slideid`),
  KEY `slides_1` (`slideshowid`),
  KEY `slides_2` (`screenid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `slideshows`
--

CREATE TABLE IF NOT EXISTS `slideshows` (
  `slideshowid` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `delay` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`slideshowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sysmap_element_url`
--

CREATE TABLE IF NOT EXISTS `sysmap_element_url` (
  `sysmapelementurlid` bigint(20) unsigned NOT NULL,
  `selementid` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`sysmapelementurlid`),
  UNIQUE KEY `sysmap_element_url_1` (`selementid`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sysmap_url`
--

CREATE TABLE IF NOT EXISTS `sysmap_url` (
  `sysmapurlid` bigint(20) unsigned NOT NULL,
  `sysmapid` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  `elementtype` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sysmapurlid`),
  UNIQUE KEY `sysmap_url_1` (`sysmapid`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sysmaps`
--

CREATE TABLE IF NOT EXISTS `sysmaps` (
  `sysmapid` bigint(20) unsigned NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  `width` int(11) NOT NULL DEFAULT '600',
  `height` int(11) NOT NULL DEFAULT '400',
  `backgroundid` bigint(20) unsigned DEFAULT NULL,
  `label_type` int(11) NOT NULL DEFAULT '2',
  `label_location` int(11) NOT NULL DEFAULT '0',
  `highlight` int(11) NOT NULL DEFAULT '1',
  `expandproblem` int(11) NOT NULL DEFAULT '1',
  `markelements` int(11) NOT NULL DEFAULT '0',
  `show_unack` int(11) NOT NULL DEFAULT '0',
  `grid_size` int(11) NOT NULL DEFAULT '50',
  `grid_show` int(11) NOT NULL DEFAULT '1',
  `grid_align` int(11) NOT NULL DEFAULT '1',
  `label_format` int(11) NOT NULL DEFAULT '0',
  `label_type_host` int(11) NOT NULL DEFAULT '2',
  `label_type_hostgroup` int(11) NOT NULL DEFAULT '2',
  `label_type_trigger` int(11) NOT NULL DEFAULT '2',
  `label_type_map` int(11) NOT NULL DEFAULT '2',
  `label_type_image` int(11) NOT NULL DEFAULT '2',
  `label_string_host` varchar(255) NOT NULL DEFAULT '',
  `label_string_hostgroup` varchar(255) NOT NULL DEFAULT '',
  `label_string_trigger` varchar(255) NOT NULL DEFAULT '',
  `label_string_map` varchar(255) NOT NULL DEFAULT '',
  `label_string_image` varchar(255) NOT NULL DEFAULT '',
  `iconmapid` bigint(20) unsigned DEFAULT NULL,
  `expand_macros` int(11) NOT NULL DEFAULT '0',
  `severity_min` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sysmapid`),
  KEY `sysmaps_1` (`name`),
  KEY `sysmaps_2` (`backgroundid`),
  KEY `sysmaps_3` (`iconmapid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sysmaps_elements`
--

CREATE TABLE IF NOT EXISTS `sysmaps_elements` (
  `selementid` bigint(20) unsigned NOT NULL,
  `sysmapid` bigint(20) unsigned NOT NULL,
  `elementid` bigint(20) unsigned NOT NULL DEFAULT '0',
  `elementtype` int(11) NOT NULL DEFAULT '0',
  `iconid_off` bigint(20) unsigned DEFAULT NULL,
  `iconid_on` bigint(20) unsigned DEFAULT NULL,
  `label` varchar(2048) NOT NULL DEFAULT '',
  `label_location` int(11) NOT NULL DEFAULT '-1',
  `x` int(11) NOT NULL DEFAULT '0',
  `y` int(11) NOT NULL DEFAULT '0',
  `iconid_disabled` bigint(20) unsigned DEFAULT NULL,
  `iconid_maintenance` bigint(20) unsigned DEFAULT NULL,
  `elementsubtype` int(11) NOT NULL DEFAULT '0',
  `areatype` int(11) NOT NULL DEFAULT '0',
  `width` int(11) NOT NULL DEFAULT '200',
  `height` int(11) NOT NULL DEFAULT '200',
  `viewtype` int(11) NOT NULL DEFAULT '0',
  `use_iconmap` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`selementid`),
  KEY `sysmaps_elements_1` (`sysmapid`),
  KEY `sysmaps_elements_2` (`iconid_off`),
  KEY `sysmaps_elements_3` (`iconid_on`),
  KEY `sysmaps_elements_4` (`iconid_disabled`),
  KEY `sysmaps_elements_5` (`iconid_maintenance`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sysmaps_link_triggers`
--

CREATE TABLE IF NOT EXISTS `sysmaps_link_triggers` (
  `linktriggerid` bigint(20) unsigned NOT NULL,
  `linkid` bigint(20) unsigned NOT NULL,
  `triggerid` bigint(20) unsigned NOT NULL,
  `drawtype` int(11) NOT NULL DEFAULT '0',
  `color` varchar(6) NOT NULL DEFAULT '000000',
  PRIMARY KEY (`linktriggerid`),
  UNIQUE KEY `sysmaps_link_triggers_1` (`linkid`,`triggerid`),
  KEY `sysmaps_link_triggers_2` (`triggerid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sysmaps_links`
--

CREATE TABLE IF NOT EXISTS `sysmaps_links` (
  `linkid` bigint(20) unsigned NOT NULL,
  `sysmapid` bigint(20) unsigned NOT NULL,
  `selementid1` bigint(20) unsigned NOT NULL,
  `selementid2` bigint(20) unsigned NOT NULL,
  `drawtype` int(11) NOT NULL DEFAULT '0',
  `color` varchar(6) NOT NULL DEFAULT '000000',
  `label` varchar(2048) NOT NULL DEFAULT '',
  PRIMARY KEY (`linkid`),
  KEY `sysmaps_links_1` (`sysmapid`),
  KEY `sysmaps_links_2` (`selementid1`),
  KEY `sysmaps_links_3` (`selementid2`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `timeperiods`
--

CREATE TABLE IF NOT EXISTS `timeperiods` (
  `timeperiodid` bigint(20) unsigned NOT NULL,
  `timeperiod_type` int(11) NOT NULL DEFAULT '0',
  `every` int(11) NOT NULL DEFAULT '0',
  `month` int(11) NOT NULL DEFAULT '0',
  `dayofweek` int(11) NOT NULL DEFAULT '0',
  `day` int(11) NOT NULL DEFAULT '0',
  `start_time` int(11) NOT NULL DEFAULT '0',
  `period` int(11) NOT NULL DEFAULT '0',
  `start_date` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`timeperiodid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `trends`
--

CREATE TABLE IF NOT EXISTS `trends` (
  `itemid` bigint(20) unsigned NOT NULL,
  `clock` int(11) NOT NULL DEFAULT '0',
  `num` int(11) NOT NULL DEFAULT '0',
  `value_min` double(16,4) NOT NULL DEFAULT '0.0000',
  `value_avg` double(16,4) NOT NULL DEFAULT '0.0000',
  `value_max` double(16,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`itemid`,`clock`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `trends_uint`
--

CREATE TABLE IF NOT EXISTS `trends_uint` (
  `itemid` bigint(20) unsigned NOT NULL,
  `clock` int(11) NOT NULL DEFAULT '0',
  `num` int(11) NOT NULL DEFAULT '0',
  `value_min` bigint(20) unsigned NOT NULL DEFAULT '0',
  `value_avg` bigint(20) unsigned NOT NULL DEFAULT '0',
  `value_max` bigint(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`itemid`,`clock`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `trigger_depends`
--

CREATE TABLE IF NOT EXISTS `trigger_depends` (
  `triggerdepid` bigint(20) unsigned NOT NULL,
  `triggerid_down` bigint(20) unsigned NOT NULL,
  `triggerid_up` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`triggerdepid`),
  UNIQUE KEY `trigger_depends_1` (`triggerid_down`,`triggerid_up`),
  KEY `trigger_depends_2` (`triggerid_up`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `trigger_discovery`
--

CREATE TABLE IF NOT EXISTS `trigger_discovery` (
  `triggerdiscoveryid` bigint(20) unsigned NOT NULL,
  `triggerid` bigint(20) unsigned NOT NULL,
  `parent_triggerid` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`triggerdiscoveryid`),
  UNIQUE KEY `trigger_discovery_1` (`triggerid`,`parent_triggerid`),
  KEY `trigger_discovery_2` (`parent_triggerid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `triggers`
--

CREATE TABLE IF NOT EXISTS `triggers` (
  `triggerid` bigint(20) unsigned NOT NULL,
  `expression` varchar(2048) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `status` int(11) NOT NULL DEFAULT '0',
  `value` int(11) NOT NULL DEFAULT '0',
  `priority` int(11) NOT NULL DEFAULT '0',
  `lastchange` int(11) NOT NULL DEFAULT '0',
  `comments` text NOT NULL,
  `error` varchar(128) NOT NULL DEFAULT '',
  `templateid` bigint(20) unsigned DEFAULT NULL,
  `type` int(11) NOT NULL DEFAULT '0',
  `state` int(11) NOT NULL DEFAULT '0',
  `flags` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`triggerid`),
  KEY `triggers_1` (`status`),
  KEY `triggers_2` (`value`),
  KEY `triggers_3` (`templateid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_history`
--

CREATE TABLE IF NOT EXISTS `user_history` (
  `userhistoryid` bigint(20) unsigned NOT NULL,
  `userid` bigint(20) unsigned NOT NULL,
  `title1` varchar(255) NOT NULL DEFAULT '',
  `url1` varchar(255) NOT NULL DEFAULT '',
  `title2` varchar(255) NOT NULL DEFAULT '',
  `url2` varchar(255) NOT NULL DEFAULT '',
  `title3` varchar(255) NOT NULL DEFAULT '',
  `url3` varchar(255) NOT NULL DEFAULT '',
  `title4` varchar(255) NOT NULL DEFAULT '',
  `url4` varchar(255) NOT NULL DEFAULT '',
  `title5` varchar(255) NOT NULL DEFAULT '',
  `url5` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`userhistoryid`),
  UNIQUE KEY `user_history_1` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `userid` bigint(20) unsigned NOT NULL,
  `alias` varchar(100) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL DEFAULT '',
  `surname` varchar(100) NOT NULL DEFAULT '',
  `passwd` char(32) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `autologin` int(11) NOT NULL DEFAULT '0',
  `autologout` int(11) NOT NULL DEFAULT '900',
  `lang` varchar(5) NOT NULL DEFAULT 'en_GB',
  `refresh` int(11) NOT NULL DEFAULT '30',
  `type` int(11) NOT NULL DEFAULT '1',
  `theme` varchar(128) NOT NULL DEFAULT 'default',
  `attempt_failed` int(11) NOT NULL DEFAULT '0',
  `attempt_ip` varchar(39) NOT NULL DEFAULT '',
  `attempt_clock` int(11) NOT NULL DEFAULT '0',
  `rows_per_page` int(11) NOT NULL DEFAULT '50',
  PRIMARY KEY (`userid`),
  KEY `users_1` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users_groups`
--

CREATE TABLE IF NOT EXISTS `users_groups` (
  `id` bigint(20) unsigned NOT NULL,
  `usrgrpid` bigint(20) unsigned NOT NULL,
  `userid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_groups_1` (`usrgrpid`,`userid`),
  KEY `users_groups_2` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `usrgrp`
--

CREATE TABLE IF NOT EXISTS `usrgrp` (
  `usrgrpid` bigint(20) unsigned NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  `gui_access` int(11) NOT NULL DEFAULT '0',
  `users_status` int(11) NOT NULL DEFAULT '0',
  `debug_mode` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`usrgrpid`),
  KEY `usrgrp_1` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `valuemaps`
--

CREATE TABLE IF NOT EXISTS `valuemaps` (
  `valuemapid` bigint(20) unsigned NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`valuemapid`),
  KEY `valuemaps_1` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `acknowledges`
--
ALTER TABLE `acknowledges`
  ADD CONSTRAINT `c_acknowledges_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_acknowledges_2` FOREIGN KEY (`eventid`) REFERENCES `events` (`eventid`) ON DELETE CASCADE;

--
-- Constraints for table `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `c_alerts_1` FOREIGN KEY (`actionid`) REFERENCES `actions` (`actionid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_alerts_2` FOREIGN KEY (`eventid`) REFERENCES `events` (`eventid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_alerts_3` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_alerts_4` FOREIGN KEY (`mediatypeid`) REFERENCES `media_type` (`mediatypeid`) ON DELETE CASCADE;

--
-- Constraints for table `application_template`
--
ALTER TABLE `application_template`
  ADD CONSTRAINT `c_application_template_1` FOREIGN KEY (`applicationid`) REFERENCES `applications` (`applicationid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_application_template_2` FOREIGN KEY (`templateid`) REFERENCES `applications` (`applicationid`) ON DELETE CASCADE;

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `c_applications_1` FOREIGN KEY (`hostid`) REFERENCES `hosts` (`hostid`) ON DELETE CASCADE;

--
-- Constraints for table `auditlog`
--
ALTER TABLE `auditlog`
  ADD CONSTRAINT `c_auditlog_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE;

--
-- Constraints for table `auditlog_details`
--
ALTER TABLE `auditlog_details`
  ADD CONSTRAINT `c_auditlog_details_1` FOREIGN KEY (`auditid`) REFERENCES `auditlog` (`auditid`) ON DELETE CASCADE;

--
-- Constraints for table `autoreg_host`
--
ALTER TABLE `autoreg_host`
  ADD CONSTRAINT `c_autoreg_host_1` FOREIGN KEY (`proxy_hostid`) REFERENCES `hosts` (`hostid`) ON DELETE CASCADE;

--
-- Constraints for table `conditions`
--
ALTER TABLE `conditions`
  ADD CONSTRAINT `c_conditions_1` FOREIGN KEY (`actionid`) REFERENCES `actions` (`actionid`) ON DELETE CASCADE;

--
-- Constraints for table `config`
--
ALTER TABLE `config`
  ADD CONSTRAINT `c_config_1` FOREIGN KEY (`alert_usrgrpid`) REFERENCES `usrgrp` (`usrgrpid`),
  ADD CONSTRAINT `c_config_2` FOREIGN KEY (`discovery_groupid`) REFERENCES `groups` (`groupid`);

--
-- Constraints for table `dchecks`
--
ALTER TABLE `dchecks`
  ADD CONSTRAINT `c_dchecks_1` FOREIGN KEY (`druleid`) REFERENCES `drules` (`druleid`) ON DELETE CASCADE;

--
-- Constraints for table `dhosts`
--
ALTER TABLE `dhosts`
  ADD CONSTRAINT `c_dhosts_1` FOREIGN KEY (`druleid`) REFERENCES `drules` (`druleid`) ON DELETE CASCADE;

--
-- Constraints for table `drules`
--
ALTER TABLE `drules`
  ADD CONSTRAINT `c_drules_1` FOREIGN KEY (`proxy_hostid`) REFERENCES `hosts` (`hostid`);

--
-- Constraints for table `dservices`
--
ALTER TABLE `dservices`
  ADD CONSTRAINT `c_dservices_1` FOREIGN KEY (`dhostid`) REFERENCES `dhosts` (`dhostid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_dservices_2` FOREIGN KEY (`dcheckid`) REFERENCES `dchecks` (`dcheckid`) ON DELETE CASCADE;

--
-- Constraints for table `expressions`
--
ALTER TABLE `expressions`
  ADD CONSTRAINT `c_expressions_1` FOREIGN KEY (`regexpid`) REFERENCES `regexps` (`regexpid`) ON DELETE CASCADE;

--
-- Constraints for table `functions`
--
ALTER TABLE `functions`
  ADD CONSTRAINT `c_functions_1` FOREIGN KEY (`itemid`) REFERENCES `items` (`itemid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_functions_2` FOREIGN KEY (`triggerid`) REFERENCES `triggers` (`triggerid`) ON DELETE CASCADE;

--
-- Constraints for table `graph_discovery`
--
ALTER TABLE `graph_discovery`
  ADD CONSTRAINT `c_graph_discovery_1` FOREIGN KEY (`graphid`) REFERENCES `graphs` (`graphid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_graph_discovery_2` FOREIGN KEY (`parent_graphid`) REFERENCES `graphs` (`graphid`) ON DELETE CASCADE;

--
-- Constraints for table `graphs`
--
ALTER TABLE `graphs`
  ADD CONSTRAINT `c_graphs_1` FOREIGN KEY (`templateid`) REFERENCES `graphs` (`graphid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_graphs_2` FOREIGN KEY (`ymin_itemid`) REFERENCES `items` (`itemid`),
  ADD CONSTRAINT `c_graphs_3` FOREIGN KEY (`ymax_itemid`) REFERENCES `items` (`itemid`);

--
-- Constraints for table `graphs_items`
--
ALTER TABLE `graphs_items`
  ADD CONSTRAINT `c_graphs_items_1` FOREIGN KEY (`graphid`) REFERENCES `graphs` (`graphid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_graphs_items_2` FOREIGN KEY (`itemid`) REFERENCES `items` (`itemid`) ON DELETE CASCADE;

--
-- Constraints for table `group_discovery`
--
ALTER TABLE `group_discovery`
  ADD CONSTRAINT `c_group_discovery_1` FOREIGN KEY (`groupid`) REFERENCES `groups` (`groupid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_group_discovery_2` FOREIGN KEY (`parent_group_prototypeid`) REFERENCES `group_prototype` (`group_prototypeid`);

--
-- Constraints for table `group_prototype`
--
ALTER TABLE `group_prototype`
  ADD CONSTRAINT `c_group_prototype_1` FOREIGN KEY (`hostid`) REFERENCES `hosts` (`hostid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_group_prototype_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`groupid`),
  ADD CONSTRAINT `c_group_prototype_3` FOREIGN KEY (`templateid`) REFERENCES `group_prototype` (`group_prototypeid`) ON DELETE CASCADE;

--
-- Constraints for table `host_discovery`
--
ALTER TABLE `host_discovery`
  ADD CONSTRAINT `c_host_discovery_1` FOREIGN KEY (`hostid`) REFERENCES `hosts` (`hostid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_host_discovery_2` FOREIGN KEY (`parent_hostid`) REFERENCES `hosts` (`hostid`),
  ADD CONSTRAINT `c_host_discovery_3` FOREIGN KEY (`parent_itemid`) REFERENCES `items` (`itemid`);

--
-- Constraints for table `host_inventory`
--
ALTER TABLE `host_inventory`
  ADD CONSTRAINT `c_host_inventory_1` FOREIGN KEY (`hostid`) REFERENCES `hosts` (`hostid`) ON DELETE CASCADE;

--
-- Constraints for table `hostmacro`
--
ALTER TABLE `hostmacro`
  ADD CONSTRAINT `c_hostmacro_1` FOREIGN KEY (`hostid`) REFERENCES `hosts` (`hostid`) ON DELETE CASCADE;

--
-- Constraints for table `hosts`
--
ALTER TABLE `hosts`
  ADD CONSTRAINT `c_hosts_1` FOREIGN KEY (`proxy_hostid`) REFERENCES `hosts` (`hostid`),
  ADD CONSTRAINT `c_hosts_2` FOREIGN KEY (`maintenanceid`) REFERENCES `maintenances` (`maintenanceid`),
  ADD CONSTRAINT `c_hosts_3` FOREIGN KEY (`templateid`) REFERENCES `hosts` (`hostid`) ON DELETE CASCADE;

--
-- Constraints for table `hosts_groups`
--
ALTER TABLE `hosts_groups`
  ADD CONSTRAINT `c_hosts_groups_1` FOREIGN KEY (`hostid`) REFERENCES `hosts` (`hostid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_hosts_groups_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`groupid`) ON DELETE CASCADE;

--
-- Constraints for table `hosts_templates`
--
ALTER TABLE `hosts_templates`
  ADD CONSTRAINT `c_hosts_templates_1` FOREIGN KEY (`hostid`) REFERENCES `hosts` (`hostid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_hosts_templates_2` FOREIGN KEY (`templateid`) REFERENCES `hosts` (`hostid`) ON DELETE CASCADE;

--
-- Constraints for table `httpstep`
--
ALTER TABLE `httpstep`
  ADD CONSTRAINT `c_httpstep_1` FOREIGN KEY (`httptestid`) REFERENCES `httptest` (`httptestid`) ON DELETE CASCADE;

--
-- Constraints for table `httpstepitem`
--
ALTER TABLE `httpstepitem`
  ADD CONSTRAINT `c_httpstepitem_1` FOREIGN KEY (`httpstepid`) REFERENCES `httpstep` (`httpstepid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_httpstepitem_2` FOREIGN KEY (`itemid`) REFERENCES `items` (`itemid`) ON DELETE CASCADE;

--
-- Constraints for table `httptest`
--
ALTER TABLE `httptest`
  ADD CONSTRAINT `c_httptest_1` FOREIGN KEY (`applicationid`) REFERENCES `applications` (`applicationid`),
  ADD CONSTRAINT `c_httptest_2` FOREIGN KEY (`hostid`) REFERENCES `hosts` (`hostid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_httptest_3` FOREIGN KEY (`templateid`) REFERENCES `httptest` (`httptestid`) ON DELETE CASCADE;

--
-- Constraints for table `httptestitem`
--
ALTER TABLE `httptestitem`
  ADD CONSTRAINT `c_httptestitem_1` FOREIGN KEY (`httptestid`) REFERENCES `httptest` (`httptestid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_httptestitem_2` FOREIGN KEY (`itemid`) REFERENCES `items` (`itemid`) ON DELETE CASCADE;

--
-- Constraints for table `icon_map`
--
ALTER TABLE `icon_map`
  ADD CONSTRAINT `c_icon_map_1` FOREIGN KEY (`default_iconid`) REFERENCES `images` (`imageid`);

--
-- Constraints for table `icon_mapping`
--
ALTER TABLE `icon_mapping`
  ADD CONSTRAINT `c_icon_mapping_1` FOREIGN KEY (`iconmapid`) REFERENCES `icon_map` (`iconmapid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_icon_mapping_2` FOREIGN KEY (`iconid`) REFERENCES `images` (`imageid`);

--
-- Constraints for table `interface`
--
ALTER TABLE `interface`
  ADD CONSTRAINT `c_interface_1` FOREIGN KEY (`hostid`) REFERENCES `hosts` (`hostid`) ON DELETE CASCADE;

--
-- Constraints for table `interface_discovery`
--
ALTER TABLE `interface_discovery`
  ADD CONSTRAINT `c_interface_discovery_1` FOREIGN KEY (`interfaceid`) REFERENCES `interface` (`interfaceid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_interface_discovery_2` FOREIGN KEY (`parent_interfaceid`) REFERENCES `interface` (`interfaceid`) ON DELETE CASCADE;

--
-- Constraints for table `item_discovery`
--
ALTER TABLE `item_discovery`
  ADD CONSTRAINT `c_item_discovery_1` FOREIGN KEY (`itemid`) REFERENCES `items` (`itemid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_item_discovery_2` FOREIGN KEY (`parent_itemid`) REFERENCES `items` (`itemid`) ON DELETE CASCADE;

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `c_items_1` FOREIGN KEY (`hostid`) REFERENCES `hosts` (`hostid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_items_2` FOREIGN KEY (`templateid`) REFERENCES `items` (`itemid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_items_3` FOREIGN KEY (`valuemapid`) REFERENCES `valuemaps` (`valuemapid`),
  ADD CONSTRAINT `c_items_4` FOREIGN KEY (`interfaceid`) REFERENCES `interface` (`interfaceid`);

--
-- Constraints for table `items_applications`
--
ALTER TABLE `items_applications`
  ADD CONSTRAINT `c_items_applications_1` FOREIGN KEY (`applicationid`) REFERENCES `applications` (`applicationid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_items_applications_2` FOREIGN KEY (`itemid`) REFERENCES `items` (`itemid`) ON DELETE CASCADE;

--
-- Constraints for table `maintenances_groups`
--
ALTER TABLE `maintenances_groups`
  ADD CONSTRAINT `c_maintenances_groups_1` FOREIGN KEY (`maintenanceid`) REFERENCES `maintenances` (`maintenanceid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_maintenances_groups_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`groupid`) ON DELETE CASCADE;

--
-- Constraints for table `maintenances_hosts`
--
ALTER TABLE `maintenances_hosts`
  ADD CONSTRAINT `c_maintenances_hosts_1` FOREIGN KEY (`maintenanceid`) REFERENCES `maintenances` (`maintenanceid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_maintenances_hosts_2` FOREIGN KEY (`hostid`) REFERENCES `hosts` (`hostid`) ON DELETE CASCADE;

--
-- Constraints for table `maintenances_windows`
--
ALTER TABLE `maintenances_windows`
  ADD CONSTRAINT `c_maintenances_windows_1` FOREIGN KEY (`maintenanceid`) REFERENCES `maintenances` (`maintenanceid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_maintenances_windows_2` FOREIGN KEY (`timeperiodid`) REFERENCES `timeperiods` (`timeperiodid`) ON DELETE CASCADE;

--
-- Constraints for table `mappings`
--
ALTER TABLE `mappings`
  ADD CONSTRAINT `c_mappings_1` FOREIGN KEY (`valuemapid`) REFERENCES `valuemaps` (`valuemapid`) ON DELETE CASCADE;

--
-- Constraints for table `media`
--
ALTER TABLE `media`
  ADD CONSTRAINT `c_media_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_media_2` FOREIGN KEY (`mediatypeid`) REFERENCES `media_type` (`mediatypeid`) ON DELETE CASCADE;

--
-- Constraints for table `node_cksum`
--
ALTER TABLE `node_cksum`
  ADD CONSTRAINT `c_node_cksum_1` FOREIGN KEY (`nodeid`) REFERENCES `nodes` (`nodeid`) ON DELETE CASCADE;

--
-- Constraints for table `nodes`
--
ALTER TABLE `nodes`
  ADD CONSTRAINT `c_nodes_1` FOREIGN KEY (`masterid`) REFERENCES `nodes` (`nodeid`);

--
-- Constraints for table `opcommand`
--
ALTER TABLE `opcommand`
  ADD CONSTRAINT `c_opcommand_1` FOREIGN KEY (`operationid`) REFERENCES `operations` (`operationid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_opcommand_2` FOREIGN KEY (`scriptid`) REFERENCES `scripts` (`scriptid`);

--
-- Constraints for table `opcommand_grp`
--
ALTER TABLE `opcommand_grp`
  ADD CONSTRAINT `c_opcommand_grp_1` FOREIGN KEY (`operationid`) REFERENCES `operations` (`operationid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_opcommand_grp_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`groupid`);

--
-- Constraints for table `opcommand_hst`
--
ALTER TABLE `opcommand_hst`
  ADD CONSTRAINT `c_opcommand_hst_1` FOREIGN KEY (`operationid`) REFERENCES `operations` (`operationid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_opcommand_hst_2` FOREIGN KEY (`hostid`) REFERENCES `hosts` (`hostid`);

--
-- Constraints for table `opconditions`
--
ALTER TABLE `opconditions`
  ADD CONSTRAINT `c_opconditions_1` FOREIGN KEY (`operationid`) REFERENCES `operations` (`operationid`) ON DELETE CASCADE;

--
-- Constraints for table `operations`
--
ALTER TABLE `operations`
  ADD CONSTRAINT `c_operations_1` FOREIGN KEY (`actionid`) REFERENCES `actions` (`actionid`) ON DELETE CASCADE;

--
-- Constraints for table `opgroup`
--
ALTER TABLE `opgroup`
  ADD CONSTRAINT `c_opgroup_1` FOREIGN KEY (`operationid`) REFERENCES `operations` (`operationid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_opgroup_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`groupid`);

--
-- Constraints for table `opmessage`
--
ALTER TABLE `opmessage`
  ADD CONSTRAINT `c_opmessage_1` FOREIGN KEY (`operationid`) REFERENCES `operations` (`operationid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_opmessage_2` FOREIGN KEY (`mediatypeid`) REFERENCES `media_type` (`mediatypeid`);

--
-- Constraints for table `opmessage_grp`
--
ALTER TABLE `opmessage_grp`
  ADD CONSTRAINT `c_opmessage_grp_1` FOREIGN KEY (`operationid`) REFERENCES `operations` (`operationid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_opmessage_grp_2` FOREIGN KEY (`usrgrpid`) REFERENCES `usrgrp` (`usrgrpid`);

--
-- Constraints for table `opmessage_usr`
--
ALTER TABLE `opmessage_usr`
  ADD CONSTRAINT `c_opmessage_usr_1` FOREIGN KEY (`operationid`) REFERENCES `operations` (`operationid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_opmessage_usr_2` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`);

--
-- Constraints for table `optemplate`
--
ALTER TABLE `optemplate`
  ADD CONSTRAINT `c_optemplate_1` FOREIGN KEY (`operationid`) REFERENCES `operations` (`operationid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_optemplate_2` FOREIGN KEY (`templateid`) REFERENCES `hosts` (`hostid`);

--
-- Constraints for table `profiles`
--
ALTER TABLE `profiles`
  ADD CONSTRAINT `c_profiles_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE;

--
-- Constraints for table `rights`
--
ALTER TABLE `rights`
  ADD CONSTRAINT `c_rights_1` FOREIGN KEY (`groupid`) REFERENCES `usrgrp` (`usrgrpid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_rights_2` FOREIGN KEY (`id`) REFERENCES `groups` (`groupid`) ON DELETE CASCADE;

--
-- Constraints for table `screens`
--
ALTER TABLE `screens`
  ADD CONSTRAINT `c_screens_1` FOREIGN KEY (`templateid`) REFERENCES `hosts` (`hostid`) ON DELETE CASCADE;

--
-- Constraints for table `screens_items`
--
ALTER TABLE `screens_items`
  ADD CONSTRAINT `c_screens_items_1` FOREIGN KEY (`screenid`) REFERENCES `screens` (`screenid`) ON DELETE CASCADE;

--
-- Constraints for table `scripts`
--
ALTER TABLE `scripts`
  ADD CONSTRAINT `c_scripts_1` FOREIGN KEY (`usrgrpid`) REFERENCES `usrgrp` (`usrgrpid`),
  ADD CONSTRAINT `c_scripts_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`groupid`);

--
-- Constraints for table `service_alarms`
--
ALTER TABLE `service_alarms`
  ADD CONSTRAINT `c_service_alarms_1` FOREIGN KEY (`serviceid`) REFERENCES `services` (`serviceid`) ON DELETE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `c_services_1` FOREIGN KEY (`triggerid`) REFERENCES `triggers` (`triggerid`) ON DELETE CASCADE;

--
-- Constraints for table `services_links`
--
ALTER TABLE `services_links`
  ADD CONSTRAINT `c_services_links_1` FOREIGN KEY (`serviceupid`) REFERENCES `services` (`serviceid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_services_links_2` FOREIGN KEY (`servicedownid`) REFERENCES `services` (`serviceid`) ON DELETE CASCADE;

--
-- Constraints for table `services_times`
--
ALTER TABLE `services_times`
  ADD CONSTRAINT `c_services_times_1` FOREIGN KEY (`serviceid`) REFERENCES `services` (`serviceid`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `c_sessions_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE;

--
-- Constraints for table `slides`
--
ALTER TABLE `slides`
  ADD CONSTRAINT `c_slides_1` FOREIGN KEY (`slideshowid`) REFERENCES `slideshows` (`slideshowid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_slides_2` FOREIGN KEY (`screenid`) REFERENCES `screens` (`screenid`) ON DELETE CASCADE;

--
-- Constraints for table `sysmap_element_url`
--
ALTER TABLE `sysmap_element_url`
  ADD CONSTRAINT `c_sysmap_element_url_1` FOREIGN KEY (`selementid`) REFERENCES `sysmaps_elements` (`selementid`) ON DELETE CASCADE;

--
-- Constraints for table `sysmap_url`
--
ALTER TABLE `sysmap_url`
  ADD CONSTRAINT `c_sysmap_url_1` FOREIGN KEY (`sysmapid`) REFERENCES `sysmaps` (`sysmapid`) ON DELETE CASCADE;

--
-- Constraints for table `sysmaps`
--
ALTER TABLE `sysmaps`
  ADD CONSTRAINT `c_sysmaps_1` FOREIGN KEY (`backgroundid`) REFERENCES `images` (`imageid`),
  ADD CONSTRAINT `c_sysmaps_2` FOREIGN KEY (`iconmapid`) REFERENCES `icon_map` (`iconmapid`);

--
-- Constraints for table `sysmaps_elements`
--
ALTER TABLE `sysmaps_elements`
  ADD CONSTRAINT `c_sysmaps_elements_1` FOREIGN KEY (`sysmapid`) REFERENCES `sysmaps` (`sysmapid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_sysmaps_elements_2` FOREIGN KEY (`iconid_off`) REFERENCES `images` (`imageid`),
  ADD CONSTRAINT `c_sysmaps_elements_3` FOREIGN KEY (`iconid_on`) REFERENCES `images` (`imageid`),
  ADD CONSTRAINT `c_sysmaps_elements_4` FOREIGN KEY (`iconid_disabled`) REFERENCES `images` (`imageid`),
  ADD CONSTRAINT `c_sysmaps_elements_5` FOREIGN KEY (`iconid_maintenance`) REFERENCES `images` (`imageid`);

--
-- Constraints for table `sysmaps_link_triggers`
--
ALTER TABLE `sysmaps_link_triggers`
  ADD CONSTRAINT `c_sysmaps_link_triggers_1` FOREIGN KEY (`linkid`) REFERENCES `sysmaps_links` (`linkid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_sysmaps_link_triggers_2` FOREIGN KEY (`triggerid`) REFERENCES `triggers` (`triggerid`) ON DELETE CASCADE;

--
-- Constraints for table `sysmaps_links`
--
ALTER TABLE `sysmaps_links`
  ADD CONSTRAINT `c_sysmaps_links_1` FOREIGN KEY (`sysmapid`) REFERENCES `sysmaps` (`sysmapid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_sysmaps_links_2` FOREIGN KEY (`selementid1`) REFERENCES `sysmaps_elements` (`selementid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_sysmaps_links_3` FOREIGN KEY (`selementid2`) REFERENCES `sysmaps_elements` (`selementid`) ON DELETE CASCADE;

--
-- Constraints for table `trigger_depends`
--
ALTER TABLE `trigger_depends`
  ADD CONSTRAINT `c_trigger_depends_1` FOREIGN KEY (`triggerid_down`) REFERENCES `triggers` (`triggerid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_trigger_depends_2` FOREIGN KEY (`triggerid_up`) REFERENCES `triggers` (`triggerid`) ON DELETE CASCADE;

--
-- Constraints for table `trigger_discovery`
--
ALTER TABLE `trigger_discovery`
  ADD CONSTRAINT `c_trigger_discovery_1` FOREIGN KEY (`triggerid`) REFERENCES `triggers` (`triggerid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_trigger_discovery_2` FOREIGN KEY (`parent_triggerid`) REFERENCES `triggers` (`triggerid`) ON DELETE CASCADE;

--
-- Constraints for table `triggers`
--
ALTER TABLE `triggers`
  ADD CONSTRAINT `c_triggers_1` FOREIGN KEY (`templateid`) REFERENCES `triggers` (`triggerid`) ON DELETE CASCADE;

--
-- Constraints for table `user_history`
--
ALTER TABLE `user_history`
  ADD CONSTRAINT `c_user_history_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE;

--
-- Constraints for table `users_groups`
--
ALTER TABLE `users_groups`
  ADD CONSTRAINT `c_users_groups_1` FOREIGN KEY (`usrgrpid`) REFERENCES `usrgrp` (`usrgrpid`) ON DELETE CASCADE,
  ADD CONSTRAINT `c_users_groups_2` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
