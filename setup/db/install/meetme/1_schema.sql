-- Create database
CREATE DATABASE IF NOT EXISTS meetme;

USE meetme;
-- Database: `meetme`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `booking`
-- 

CREATE TABLE `booking` (
  `bookId` int(10) unsigned NOT NULL auto_increment,
  `clientId` int(10) unsigned default '0',
  `roomNo` varchar(30) default '0',
  `roomPass` varchar(30) NOT NULL default '0',
  `silPass` varchar(30) NOT NULL default '0',
  `startTime` datetime NOT NULL default '0000-00-00 00:00:00',
  `endTime` datetime NOT NULL default '0000-00-00 00:00:00',
  `dateReq` datetime NOT NULL default '0000-00-00 00:00:00',
  `dateMod` datetime NOT NULL default '0000-00-00 00:00:00',
  `maxUser` varchar(30) NOT NULL default '10',
  `status` varchar(30) NOT NULL default 'A',
  `confOwner` varchar(30) NOT NULL default '',
  `confDesc` varchar(100) NOT NULL default '',
  `aFlags` varchar(10) NOT NULL default '',
  `uFlags` varchar(10) NOT NULL default '',
  `sequenceNo` int(10) unsigned default '0',
  `recurInterval` int(10) unsigned default '0',
  PRIMARY KEY  (`bookId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table `cdr`
-- 

CREATE TABLE `cdr` (
  `bookId` int(11) default NULL,
  `duration` varchar(12) default NULL,
  `CIDnum` varchar(32) default NULL,
  `CIDname` varchar(32) default NULL,
  `jointime` datetime default NULL,
  `leavetime` timestamp NULL default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `notifications`
-- 

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL default '0',
  `book_id` int(11) NOT NULL default '0',
  `ntype` char(10) default NULL,
  `ndate` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table `participants`
-- 

CREATE TABLE `participants` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL default '0',
  `book_id` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table `user`
-- 

CREATE TABLE `user` (
  `id` int(11) NOT NULL auto_increment,
  `email` varchar(100) NOT NULL default '',
  `password` varchar(25) default NULL,
  `first_name` varchar(50) default NULL,
  `last_name` varchar(50) default NULL,
  `telephone` varchar(15) default NULL,
  `admin` varchar(5) NOT NULL default 'User',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;

-- Create user db
GRANT SELECT, UPDATE, INSERT, DELETE ON `meetme`.* to asteriskuser@localhost;
