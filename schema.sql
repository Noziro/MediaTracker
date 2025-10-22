SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";



CREATE DATABASE IF NOT EXISTS `mediatracker` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `mediatracker`;



CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `nickname` varchar(50) DEFAULT '',
  `email` varchar(255) DEFAULT '',
  `password` varchar(255) NOT NULL,
  `permission_level` int(2) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_image` tinytext NOT NULL DEFAULT '',
  `banner_image` tinytext NOT NULL DEFAULT '',
  `about` text NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
ALTER TABLE `users`
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `username` (`username`);
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


CREATE TABLE `user_preferences` (
  `user_id` int(11) NOT NULL,
  `timezone` tinytext NOT NULL DEFAULT 'UTC',
  `profile_colour` varchar(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`user_id`);


CREATE TABLE `collections` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `type` varchar(50) NOT NULL,
  `display_image` tinyint NOT NULL DEFAULT 1,
  `display_score` tinyint NOT NULL DEFAULT 1,
  `display_progress` tinyint NOT NULL DEFAULT 1,
  `display_user_started` tinyint NOT NULL DEFAULT 1,
  `display_user_finished` tinyint NOT NULL DEFAULT 1,
  `display_days` tinyint NOT NULL DEFAULT 1,
  `rating_system` tinyint NOT NULL DEFAULT 10,
  `private` tinyint NOT NULL DEFAULT 0,
  `deleted` tinyint NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
ALTER TABLE `collections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);
ALTER TABLE `collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


CREATE TABLE `media` (
  `id` int(15) NOT NULL,
  `user_id` int(11) NOT NULL,
  `collection_id` int(11) NOT NULL,
  `status` tinytext NOT NULL DEFAULT 'planned',
  `name` tinytext NOT NULL,
  `image` tinytext NOT NULL DEFAULT '',
  `score` int(3) DEFAULT 0,
  `episodes` smallint(6) DEFAULT 0,
  `progress` smallint(6) NOT NULL DEFAULT 0,
  `rewatched` smallint(6) NOT NULL DEFAULT 0,
  `user_started_at` date DEFAULT NULL,
  `user_finished_at` date DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `started_at` date DEFAULT NULL,
  `finished_at` date DEFAULT NULL,
  `description` text NOT NULL DEFAULT '',
  `comments` text NOT NULL DEFAULT '',
  `credits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `adult` tinyint NOT NULL DEFAULT 0,
  `favourite` tinyint NOT NULL DEFAULT 0,
  `private` tinyint NOT NULL DEFAULT 0,
  `deleted` tinyint NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `collection_id` (`collection_id`);
ALTER TABLE `media`
  MODIFY `id` int(15) NOT NULL AUTO_INCREMENT;


CREATE TABLE `media_name` (
  `media_id` int(15) NOT NULL,
  `type` tinytext NOT NULL,
  `name` tinytext NOT NULL,
  `is_default` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `activity` (
  `user_id` int(11) NOT NULL,
  `type` tinyint NOT NULL DEFAULT 0,
  `media_id` int(15) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
ALTER TABLE `activity`
  ADD KEY `user_id` (`user_id`);


CREATE TABLE `permission_levels` (
  `permission_level` int(2) NOT NULL,
  `title` varchar(50) NOT NULL,
  `description` text NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
ALTER TABLE `permission_levels`
  ADD UNIQUE KEY `permission_level` (`permission_level`);


CREATE TABLE `sessions` (
  `id` varchar(32) NOT NULL,
  `started` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL,
  `user_ip` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
ALTER TABLE `sessions`
  ADD UNIQUE KEY `id` (`id`);