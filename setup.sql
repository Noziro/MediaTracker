-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 22, 2020 at 05:48 AM
-- Server version: 10.3.16-MariaDB
-- PHP Version: 7.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Dumping data for table `permission_levels`
--

INSERT INTO `permission_levels` (`permission_level`, `title`, `description`) VALUES
(0, 'Guest', 'User with no account.'),
(1, 'Member', 'User with account.'),
(20, 'VIP', 'Users with extra permissions. These permissions have not yet been added or decided.'),
(80, 'Trial Moderator', 'Trial moderator.'),
(90, 'Moderator', 'Site moderator.'),
(95, 'Admin', 'Administrator. Highest level below the owner.'),
(99, 'Owner', 'Site owner.');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
