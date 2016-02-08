-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 07, 2016 at 11:58 PM
-- Server version: 5.6.28-76.1-56
-- PHP Version: 5.5.9-1ubuntu4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

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
  KEY `lat` (`lat`,`lng`),
  KEY `lng` (`lng`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='The different groups that we host' AUTO_INCREMENT=156705 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Location data, the bulk derived from OSM' AUTO_INCREMENT=9037486 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Used to map lat/lng to gridid for location searches' AUTO_INCREMENT=170483 ;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE IF NOT EXISTS `logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Machine assumed set to GMT',
  `byuser` bigint(20) unsigned DEFAULT NULL COMMENT 'User responsible for action, if any',
  `type` enum('Group','Message','User','Plugin','Config','StdMsg','Location','BulkOp') DEFAULT NULL,
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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Logs.  Not guaranteed against loss' AUTO_INCREMENT=9346630 ;

-- --------------------------------------------------------

--
-- Table structure for table `logs_api`
--

CREATE TABLE IF NOT EXISTS `logs_api` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `session` varchar(255) NOT NULL,
  `request` longtext NOT NULL,
  `response` longtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `session` (`session`),
  KEY `date` (`date`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Log of all API requests and responses' AUTO_INCREMENT=194707 ;

-- --------------------------------------------------------

--
-- Table structure for table `logs_sql`
--

CREATE TABLE IF NOT EXISTS `logs_sql` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `duration` decimal(15,10) unsigned DEFAULT '0.0000000000' COMMENT 'seconds',
  `userid` bigint(20) unsigned DEFAULT NULL,
  `session` varchar(255) NOT NULL,
  `request` longtext NOT NULL,
  `response` varchar(20) NOT NULL COMMENT 'rc:lastInsertId',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `session` (`session`),
  KEY `date` (`date`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Log of modification SQL operations' AUTO_INCREMENT=47123074 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Which groups users are members of' AUTO_INCREMENT=2385904 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Used to spot multijoiners' AUTO_INCREMENT=2372398 ;

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
  `source` enum('Yahoo Approved','Yahoo Pending','Yahoo System') DEFAULT NULL COMMENT 'Source of incoming message',
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
  `suggestedsubject` varchar(1024) DEFAULT NULL COMMENT 'Any suggested subject improvement',
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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=16 COMMENT='All our messages' AUTO_INCREMENT=1785211 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Attachments parsed out from messages and resized' AUTO_INCREMENT=779872 ;

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
  `approvedby` bigint(20) unsigned DEFAULT NULL COMMENT 'Mod who approved this post (if any)',
  UNIQUE KEY `msgid` (`msgid`,`groupid`),
  KEY `messageid` (`msgid`,`groupid`,`collection`,`arrival`),
  KEY `groupid` (`groupid`,`collection`,`deleted`),
  KEY `collection` (`collection`),
  KEY `groupid_2` (`groupid`,`yahoopendingid`),
  KEY `groupid_3` (`groupid`,`yahooapprovedid`),
  KEY `approvedby` (`approvedby`)
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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Message arrivals, used for spam checking' AUTO_INCREMENT=1993912 ;

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
-- Table structure for table `mod_bulkops`
--

CREATE TABLE IF NOT EXISTS `mod_bulkops` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `configid` bigint(20) unsigned DEFAULT NULL,
  `set` enum('Members','Member Logs','Mail Logs','Web Logs','Moderator Logs','Command Logs') NOT NULL,
  `criterion` enum('Bouncing','BouncingFor','WebOnly','All') DEFAULT NULL,
  `runevery` int(11) NOT NULL DEFAULT '168' COMMENT 'In hours',
  `action` enum('Unbounce','Remove','ToGroup','ToSpecialNotices') DEFAULT NULL,
  `bouncingfor` int(11) NOT NULL DEFAULT '90',
  UNIQUE KEY `uniqueid` (`id`),
  KEY `configid` (`configid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Configurations for use by moderators' AUTO_INCREMENT=24283 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=73228 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Outstanding work required to be performed by the plugin' AUTO_INCREMENT=34804 ;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned DEFAULT NULL,
  `series` bigint(20) unsigned NOT NULL,
  `token` varchar(255) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `userid` (`userid`),
  KEY `date` (`date`),
  KEY `id_3` (`id`,`series`,`token`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2060955 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Users who are spammers or trusted' AUTO_INCREMENT=85 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Whitelisted IP addresses' AUTO_INCREMENT=3043 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Whitelisted subjects' AUTO_INCREMENT=1452 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='People who have supported this site' AUTO_INCREMENT=6705 ;

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
  `licenses` int(11) NOT NULL DEFAULT '0' COMMENT 'Any licenses not added to groups',
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
  KEY `suspectcount_2` (`suspectcount`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3949837 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Comments from mods on members' AUTO_INCREMENT=15475 ;

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
  KEY `userid` (`userid`),
  KEY `validated` (`validated`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=84236299 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5809 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Unique words for searches' AUTO_INCREMENT=1492267 ;

-- --------------------------------------------------------

--
-- Structure for view `vw_manyemails`
--
DROP TABLE IF EXISTS `vw_manyemails`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_manyemails` AS select `users`.`id` AS `id`,`users`.`fullname` AS `fullname`,`users_emails`.`email` AS `email` from (`users` join `users_emails` on((`users`.`id` = `users_emails`.`userid`))) where `users`.`id` in (select `users_emails`.`userid` from `users_emails` group by `users_emails`.`userid` having (count(0) > 4) order by count(0) desc);

-- --------------------------------------------------------

--
-- Structure for view `vw_multiemails`
--
DROP TABLE IF EXISTS `vw_multiemails`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_multiemails` AS select `vw_manyemails`.`id` AS `id`,`vw_manyemails`.`fullname` AS `fullname`,count(0) AS `count`,group_concat(`vw_manyemails`.`email` separator ', ') AS `GROUP_CONCAT(email SEPARATOR ', ')` from `vw_manyemails` group by `vw_manyemails`.`id` order by `count` desc;

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
ADD CONSTRAINT `locations_excluded_ibfk_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `locations_excluded_ibfk_3` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`fromuser`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`locationid`) REFERENCES `locations` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `messages_attachments`
--
ALTER TABLE `messages_attachments`
ADD CONSTRAINT `_messages_attachments_ibfk_1` FOREIGN KEY (`msgid`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages_groups`
--
ALTER TABLE `messages_groups`
ADD CONSTRAINT `_messages_groups_ibfk_1` FOREIGN KEY (`msgid`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `messages_groups_ibfk_2` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `messages_groups_ibfk_3` FOREIGN KEY (`approvedby`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
ADD CONSTRAINT `_messages_index_ibfk_2` FOREIGN KEY (`wordid`) REFERENCES `words` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `_messages_index_ibfk_3` FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `messages_related`
--
ALTER TABLE `messages_related`
ADD CONSTRAINT `messages_related_ibfk_1` FOREIGN KEY (`id1`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `messages_related_ibfk_2` FOREIGN KEY (`id2`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mod_bulkops`
--
ALTER TABLE `mod_bulkops`
ADD CONSTRAINT `mod_bulkops_ibfk_1` FOREIGN KEY (`configid`) REFERENCES `mod_configs` (`id`) ON DELETE CASCADE;

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
CREATE DEFINER=`root`@`localhost` EVENT `Delete Unlicensed Groups` ON SCHEDULE EVERY 1 DAY STARTS '2015-12-23 04:00:00' ON COMPLETION PRESERVE DISABLE ON SLAVE DO DELETE FROM `groups` WHERE licenserequired = 1 AND (licenseduntil IS NULL OR licenseduntil < NOW()) AND (trial IS NULL OR DATEDIFF(NOW(), trial) > 30)$$

CREATE DEFINER=`root`@`localhost` EVENT `Delete Stranded Messages` ON SCHEDULE EVERY 1 DAY STARTS '2015-12-23 04:30:00' ON COMPLETION PRESERVE DISABLE ON SLAVE DO DELETE FROM messages WHERE id NOT IN (SELECT DISTINCT msgid FROM messages_groups)$$

CREATE DEFINER=`root`@`localhost` EVENT `Delete Non-Freegle Old Messages` ON SCHEDULE EVERY 1 DAY STARTS '2016-01-02 04:00:00' ON COMPLETION PRESERVE DISABLE ON SLAVE COMMENT 'Non-Freegle groups don''t have old messages preserved.' DO SELECT * FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid INNER JOIN groups ON messages_groups.groupid = groups.id WHERE  DATEDIFF(NOW(), `date`) > 31 AND groups.type != 'Freegle'$$

CREATE DEFINER=`root`@`localhost` EVENT `Delete Old Sessions` ON SCHEDULE EVERY 1 DAY STARTS '2016-01-29 04:00:00' ON COMPLETION PRESERVE DISABLE ON SLAVE DO DELETE FROM sessions WHERE DATEDIFF(NOW(), `date`) > 31$$

CREATE DEFINER=`root`@`localhost` EVENT `Delete Old API logs` ON SCHEDULE EVERY 1 DAY STARTS '2016-02-06 04:00:00' ON COMPLETION PRESERVE DISABLE ON SLAVE DO DELETE FROM logs_api WHERE DATEDIFF(NOW(), `date`) > 31$$

CREATE DEFINER=`root`@`localhost` EVENT `Delete Old SQL Logs` ON SCHEDULE EVERY 1 DAY STARTS '2016-02-06 04:30:00' ON COMPLETION PRESERVE DISABLE ON SLAVE DO DELETE FROM logs_sql WHERE DATEDIFF(NOW(), `date`) > 2$$

DELIMITER ;
