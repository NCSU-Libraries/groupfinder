-- phpMyAdmin SQL Dump
-- version 3.2.5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 15, 2010 at 03:48 PM
-- Server version: 5.1.44
-- PHP Version: 5.3.2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `groupfinder`
--
CREATE DATABASE `groupfinder` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `groupfinder`;

-- --------------------------------------------------------

--
-- Table structure for table `activity`
--

CREATE TABLE `activity` (
  `id` int(7) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `location_id` int(4) unsigned zerofill NOT NULL,
  `description` varchar(140) NOT NULL,
  `user_id` int(7) unsigned zerofill NOT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `enabled` (`enabled`),
  KEY `location_id` (`location_id`),
  KEY `start_time` (`start_time`,`end_time`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=839 ;

--
-- Dumping data for table `activity`
--


-- --------------------------------------------------------

--
-- Table structure for table `location`
--

CREATE TABLE `location` (
  `id` int(4) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `directions` varchar(200) NOT NULL,
  `code` varchar(8) NOT NULL,
  `building` varchar(4) NOT NULL,
  `floor` int(2) NOT NULL,
  `display_order` int(3) unsigned NOT NULL DEFAULT '999',
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`,`building`),
  UNIQUE KEY `display_order` (`display_order`),
  KEY `enabled` (`enabled`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=28 ;

--
-- Dumping data for table `location`
--

INSERT INTO `location` VALUES(0001, 'Coffee Shop', 'Directions from a common starting point', 'SMPL_COF', 'SMPL', 0, 990, 1);
INSERT INTO `location` VALUES(0002, 'Study Room', 'Directions from a common starting point', 'SMPL_SR', 'SMPL', 1, 980, 1);
INSERT INTO `location` VALUES(0003, 'Service Desk', 'Directions from a common starting point', 'SMPL_SRV', 'SMPL', 2, 970, 1);
INSERT INTO `location` VALUES(0004, 'Technical Support', 'Directions from a common starting point', 'SMPL_TCH', 'SMPL', 3, 960, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(7) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `username` varchar(8) NOT NULL,
  `lastname` varchar(30) DEFAULT NULL,
  `firstname` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '0',
  `admin` tinyint(4) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unity` (`username`),
  KEY `enabled` (`enabled`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=22 ;

--
-- Dumping data for table `user`
--

INSERT INTO `user` VALUES(0000021, 'test', 'User', 'Test', NULL, 1, 1, '2010-09-13 10:37:03');
