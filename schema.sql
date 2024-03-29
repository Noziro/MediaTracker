-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 18, 2021 at 03:26 AM
-- Server version: 10.3.16-MariaDB
-- PHP Version: 7.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `collections`
--
CREATE DATABASE IF NOT EXISTS `collections` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `collections`;

-- --------------------------------------------------------

--
-- Table structure for table `activity`
--

CREATE TABLE `activity` (
  `user_id` int(11) NOT NULL,
  `type` tinyint(2) NOT NULL DEFAULT 0,
  `media_id` int(15) DEFAULT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `boards`
--

CREATE TABLE `boards` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` tinytext NOT NULL,
  `display_order` int(2) NOT NULL,
  `permission_level` int(2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `collections`
--

CREATE TABLE `collections` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `type` varchar(50) NOT NULL,
  `display_image` tinyint(1) NOT NULL DEFAULT 1,
  `display_score` tinyint(1) NOT NULL DEFAULT 1,
  `display_progress` tinyint(1) NOT NULL DEFAULT 1,
  `display_user_started` tinyint(1) NOT NULL DEFAULT 1,
  `display_user_finished` tinyint(1) NOT NULL DEFAULT 1,
  `display_days` tinyint(1) NOT NULL DEFAULT 1,
  `rating_system` tinyint(3) NOT NULL DEFAULT 10,
  `private` tinyint(1) NOT NULL,
  `deleted` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `id` int(15) NOT NULL,
  `user_id` int(11) NOT NULL,
  `collection_id` int(11) NOT NULL,
  `status` tinytext NOT NULL,
  `name` tinytext NOT NULL,
  `image` tinytext NOT NULL,
  `score` int(3) DEFAULT 0,
  `episodes` smallint(6) DEFAULT 0,
  `progress` smallint(6) NOT NULL DEFAULT 0,
  `rewatched` smallint(6) NOT NULL DEFAULT 0,
  `user_started_at` date DEFAULT NULL,
  `user_finished_at` date DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `started_at` date DEFAULT NULL,
  `finished_at` date DEFAULT NULL,
  `description` text NOT NULL,
  `comments` text NOT NULL,
  `credits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `links` longtext CHARACTER SET armscii8 COLLATE armscii8_bin NOT NULL,
  `adult` tinyint(1) NOT NULL DEFAULT 0,
  `favourite` tinyint(1) NOT NULL,
  `private` tinyint(1) NOT NULL,
  `deleted` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `media`
--
DELIMITER $$
CREATE TRIGGER `new_media` BEFORE INSERT ON `media` FOR EACH ROW SET NEW.`status` = CASE WHEN NEW.status = '' THEN 'planned' ELSE NEW.status END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `media_name`
--

CREATE TABLE `media_name` (
  `media_id` int(15) NOT NULL,
  `type` tinytext NOT NULL,
  `name` tinytext NOT NULL,
  `is_default` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `permission_levels`
--

CREATE TABLE `permission_levels` (
  `permission_level` int(2) NOT NULL,
  `title` varchar(50) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `replies`
--

CREATE TABLE `replies` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(32) NOT NULL,
  `started` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL,
  `user_ip` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `threads`
--

CREATE TABLE `threads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `board_id` int(11) NOT NULL,
  `title` tinytext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `anonymous` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `nickname` varchar(50) DEFAULT '',
  `email` varchar(255) DEFAULT '',
  `password` varchar(255) NOT NULL,
  `permission_level` int(2) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_image` tinytext NOT NULL,
  `banner_image` tinytext NOT NULL,
  `about` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `user_id` int(11) NOT NULL,
  `timezone` tinytext NOT NULL,
  `profile_colour` varchar(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `user_preferences`
--
DELIMITER $$
CREATE TRIGGER `new_user_preferences` BEFORE INSERT ON `user_preferences` FOR EACH ROW SET NEW.`timezone` = CASE WHEN NEW.timezone = '' THEN 'UTC' ELSE NEW.timezone END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity`
--
ALTER TABLE `activity`
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `boards`
--
ALTER TABLE `boards`
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `display_order` (`display_order`);

--
-- Indexes for table `collections`
--
ALTER TABLE `collections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `collection_id` (`collection_id`);

--
-- Indexes for table `permission_levels`
--
ALTER TABLE `permission_levels`
  ADD UNIQUE KEY `permission_level` (`permission_level`);

--
-- Indexes for table `replies`
--
ALTER TABLE `replies`
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `deleted` (`deleted`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `threads`
--
ALTER TABLE `threads`
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `deleted` (`deleted`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `boards`
--
ALTER TABLE `boards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `collections`
--
ALTER TABLE `collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` int(15) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `replies`
--
ALTER TABLE `replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `threads`
--
ALTER TABLE `threads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
