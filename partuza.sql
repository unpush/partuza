-- MySQL dump 10.11
--
-- Host: localhost    Database: partuza
-- ------------------------------------------------------
-- Server version	5.0.51a

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `activities` (
  `id` int(11) NOT NULL auto_increment,
  `person_id` int(11) NOT NULL,
  `app_id` int(11) NOT NULL,
  `title` mediumtext NOT NULL,
  `body` mediumtext NOT NULL,
  `created` int(11) NOT NULL,
  KEY `id` (`id`),
  KEY `activity_stream_id` (`person_id`),
  KEY `created` (`created`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `activity_media_items`
--

DROP TABLE IF EXISTS `activity_media_items`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `activity_media_items` (
  `id` int(11) NOT NULL auto_increment,
  `activity_id` int(11) NOT NULL,
  `mime_type` char(64) NOT NULL,
  `media_type` enum('AUDIO','IMAGE','VIDEO') NOT NULL,
  `url` char(128) NOT NULL,
  KEY `id` (`id`),
  KEY `activity_id` (`activity_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `addresses` (
  `id` int(11) NOT NULL auto_increment,
  `country` char(128) default NULL,
  `extended_address` char(128) default NULL,
  `latitude` int(11) default NULL,
  `locality` varchar(128) default NULL,
  `longitude` int(11) default NULL,
  `po_box` char(32) default NULL,
  `postal_code` char(32) default NULL,
  `region` char(64) default NULL,
  `street_address` char(128) default NULL,
  `address_type` char(128) default NULL,
  `unstructured_address` char(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `application_settings`
--

DROP TABLE IF EXISTS `application_settings`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `application_settings` (
  `application_id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `name` char(128) NOT NULL,
  `value` char(255) NOT NULL,
  UNIQUE KEY `application_id` (`application_id`,`person_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `applications`
--

DROP TABLE IF EXISTS `applications`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `applications` (
  `id` int(11) NOT NULL auto_increment,
  `url` char(128) NOT NULL,
  `title` char(128) default NULL,
  `directory_title` varchar(128) default NULL,
  `screenshot` char(128) default NULL,
  `thumbnail` char(128) default NULL,
  `author` char(128) default NULL,
  `author_email` char(128) default NULL,
  `description` mediumtext,
  `settings` mediumtext,
  `views` mediumtext,
  `version` varchar(64) NOT NULL,
  `height` int(11) NOT NULL default '0',
  `scrolling` int(11) NOT NULL default '0',
  `modified` int(11) NOT NULL,
  UNIQUE KEY `url` (`url`),
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `authenticated`
--

DROP TABLE IF EXISTS `authenticated`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `authenticated` (
  `person_id` int(11) NOT NULL,
  `hash` varchar(41) NOT NULL,
  PRIMARY KEY  (`hash`),
  UNIQUE KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `friend_requests`
--

DROP TABLE IF EXISTS `friend_requests`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `friend_requests` (
  `person_id` int(11) NOT NULL,
  `friend_id` int(11) NOT NULL,
  UNIQUE KEY `person_id` (`person_id`,`friend_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `friends`
--

DROP TABLE IF EXISTS `friends`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `friends` (
  `person_id` int(11) NOT NULL,
  `friend_id` int(11) NOT NULL,
  UNIQUE KEY `person_id` (`person_id`,`friend_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `languages`
--

DROP TABLE IF EXISTS `languages`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `languages` (
  `id` int(11) NOT NULL auto_increment,
  `code` char(4) default NULL,
  `name` char(32) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `oauth_consumer`
--

DROP TABLE IF EXISTS `oauth_consumer`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `oauth_consumer` (
  `user_id` bigint(20) NOT NULL,
  `consumer_key` char(64) NOT NULL,
  `consumer_secret` char(64) NOT NULL,
  PRIMARY KEY  (`user_id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `consumer_key` (`consumer_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `oauth_nonce`
--

DROP TABLE IF EXISTS `oauth_nonce`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `oauth_nonce` (
  `nonce` char(64) NOT NULL,
  `nonce_timestamp` int(11) NOT NULL,
  PRIMARY KEY  (`nonce`),
  KEY `nonce_timestamp` (`nonce_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `oauth_token`
--

DROP TABLE IF EXISTS `oauth_token`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `oauth_token` (
  `user_id` bigint(20) NOT NULL default '0',
  `consumer_key` char(64) NOT NULL,
  `type` char(7) NOT NULL,
  `token_key` char(64) NOT NULL,
  `token_secret` char(64) NOT NULL,
  `authorized` int(11) NOT NULL default '0',
  PRIMARY KEY  (`token_key`),
  UNIQUE KEY `token_key` (`token_key`),
  KEY `token_key_2` (`token_key`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `organizations`
--

DROP TABLE IF EXISTS `organizations`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `organizations` (
  `id` int(11) NOT NULL auto_increment,
  `address_id` int(11) default NULL,
  `description` mediumtext,
  `end_date` int(11) default NULL,
  `field` char(128) default NULL,
  `name` char(128) default NULL,
  `salary` char(64) default NULL,
  `start_date` int(11) default NULL,
  `sub_field` char(64) default NULL,
  `title` char(64) default NULL,
  `webpage` char(128) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_activities`
--

DROP TABLE IF EXISTS `person_activities`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_activities` (
  `person_id` int(11) NOT NULL,
  `activity` char(128) NOT NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_addresses`
--

DROP TABLE IF EXISTS `person_addresses`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_addresses` (
  `person_id` int(11) NOT NULL,
  `address_id` int(11) NOT NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_applications`
--

DROP TABLE IF EXISTS `person_applications`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_applications` (
  `id` int(11) NOT NULL auto_increment,
  `person_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `person_id` (`person_id`),
  KEY `application_id` (`application_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_body_type`
--

DROP TABLE IF EXISTS `person_body_type`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_body_type` (
  `person_id` int(11) NOT NULL,
  `build` char(64) default NULL,
  `eye_color` char(64) default NULL,
  `hair_color` char(64) default NULL,
  `height` int(11) default NULL,
  `weight` int(11) default NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_books`
--

DROP TABLE IF EXISTS `person_books`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_books` (
  `person_id` int(11) NOT NULL,
  `book` char(128) NOT NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_cars`
--

DROP TABLE IF EXISTS `person_cars`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_cars` (
  `person_id` int(11) NOT NULL,
  `car` char(128) NOT NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_current_location`
--

DROP TABLE IF EXISTS `person_current_location`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_current_location` (
  `person_id` int(11) NOT NULL,
  `address_id` int(11) NOT NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_emails`
--

DROP TABLE IF EXISTS `person_emails`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_emails` (
  `person_id` int(11) NOT NULL,
  `address` char(128) NOT NULL,
  `email_type` char(128) default NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_food`
--

DROP TABLE IF EXISTS `person_food`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_food` (
  `person_id` int(11) NOT NULL,
  `food` char(128) NOT NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_heroes`
--

DROP TABLE IF EXISTS `person_heroes`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_heroes` (
  `person_id` int(11) NOT NULL,
  `hero` char(128) NOT NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_interests`
--

DROP TABLE IF EXISTS `person_interests`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_interests` (
  `person_id` int(11) NOT NULL,
  `intrest` char(128) NOT NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_jobs`
--

DROP TABLE IF EXISTS `person_jobs`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_jobs` (
  `person_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_languages_spoken`
--

DROP TABLE IF EXISTS `person_languages_spoken`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_languages_spoken` (
  `person_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_movies`
--

DROP TABLE IF EXISTS `person_movies`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_movies` (
  `person_id` int(11) NOT NULL,
  `movie` char(128) default NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_music`
--

DROP TABLE IF EXISTS `person_music`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_music` (
  `person_id` int(11) NOT NULL,
  `music` char(128) default NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_phone_numbers`
--

DROP TABLE IF EXISTS `person_phone_numbers`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_phone_numbers` (
  `person_id` int(11) NOT NULL,
  `number` char(64) default NULL,
  `number_type` char(128) default NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_quotes`
--

DROP TABLE IF EXISTS `person_quotes`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_quotes` (
  `person_id` int(11) NOT NULL,
  `quote` mediumtext,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_schools`
--

DROP TABLE IF EXISTS `person_schools`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_schools` (
  `person_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_sports`
--

DROP TABLE IF EXISTS `person_sports`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_sports` (
  `person_id` int(11) NOT NULL,
  `sport` char(128) default NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_tags`
--

DROP TABLE IF EXISTS `person_tags`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_tags` (
  `person_id` int(11) NOT NULL,
  `tag` char(128) default NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_turn_offs`
--

DROP TABLE IF EXISTS `person_turn_offs`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_turn_offs` (
  `person_id` int(11) NOT NULL,
  `turn_off` char(128) default NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_turn_ons`
--

DROP TABLE IF EXISTS `person_turn_ons`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_turn_ons` (
  `person_id` int(11) NOT NULL,
  `turn_on` char(128) default NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_tv_shows`
--

DROP TABLE IF EXISTS `person_tv_shows`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_tv_shows` (
  `person_id` int(11) NOT NULL,
  `tv_show` char(128) default NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `person_urls`
--

DROP TABLE IF EXISTS `person_urls`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `person_urls` (
  `person_id` int(11) NOT NULL,
  `url` char(128) default NULL,
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `persons`
--

DROP TABLE IF EXISTS `persons`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `persons` (
  `id` int(11) NOT NULL auto_increment,
  `email` varchar(128) NOT NULL,
  `password` char(128) NOT NULL,
  `about_me` mediumtext,
  `age` int(11) default NULL,
  `children` mediumtext,
  `date_of_birth` int(11) default NULL,
  `drinker` enum('HEAVILY','NO','OCCASIONALLY','QUIT','QUITTING','REGULARLY','SOCIALLY','YES') default NULL,
  `ethnicity` char(128) default NULL,
  `fashion` mediumtext,
  `gender` enum('MALE','FEMALE') default NULL,
  `happiest_when` mediumtext,
  `humor` mediumtext,
  `job_interests` mediumtext,
  `living_arrangement` mediumtext,
  `looking_for` mediumtext,
  `nickname` char(128) default NULL,
  `pets` mediumtext,
  `political_views` mediumtext,
  `profile_song` char(128) default NULL,
  `profile_url` char(128) default NULL,
  `profile_video` char(128) default NULL,
  `relationship_status` char(128) default NULL,
  `religion` char(128) default NULL,
  `romance` char(128) default NULL,
  `scared_of` mediumtext,
  `sexual_orientation` char(128) default NULL,
  `smoker` enum('HEAVILY','NO','OCCASIONALLY','QUIT','QUITTING','REGULARLY','SOCIALLY','YES') default NULL,
  `status` char(128) default NULL,
  `thumbnail_url` char(128) default NULL,
  `time_zone` int(11) default NULL,
  `first_name` varchar(64) NOT NULL,
  `last_name` varchar(64) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `nickname` (`nickname`),
  KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2008-11-08 17:33:09
