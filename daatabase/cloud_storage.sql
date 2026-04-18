-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 18, 2026 at 03:18 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cloud_storage`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `file_id` int(11) DEFAULT NULL,
  `folder_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `user_id`, `action`, `file_id`, `folder_id`, `description`, `created_at`) VALUES
(1, 1, 'create_folder', NULL, 1, 'Created folder: Testing', '2026-04-18 14:02:05'),
(2, 1, 'upload_file', 1, 1, 'Uploaded: 4.jpg', '2026-04-18 14:02:42');

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `folder_id` int(11) DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `extension` varchar(10) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `size` bigint(20) NOT NULL,
  `is_starred` tinyint(1) DEFAULT 0,
  `in_trash` tinyint(1) DEFAULT 0,
  `trashed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `files`
--

INSERT INTO `files` (`id`, `user_id`, `folder_id`, `original_name`, `stored_name`, `extension`, `mime_type`, `size`, `is_starred`, `in_trash`, `trashed_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '4.jpg', '6d33de416a3e7a2b42fe8802e6127930.jpg', 'jpg', 'image/jpeg', 3062905, 0, 0, NULL, '2026-04-18 14:02:42', '2026-04-18 14:02:42');

-- --------------------------------------------------------

--
-- Table structure for table `folders`
--

CREATE TABLE `folders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `folders`
--

INSERT INTO `folders` (`id`, `user_id`, `parent_id`, `name`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'Testing', '2026-04-18 14:02:05', '2026-04-18 14:02:05');

-- --------------------------------------------------------

--
-- Table structure for table `shares`
--

CREATE TABLE `shares` (
  `id` int(11) NOT NULL,
  `shared_by` int(11) NOT NULL,
  `file_id` int(11) DEFAULT NULL,
  `folder_id` int(11) DEFAULT NULL,
  `shared_with_email` varchar(150) DEFAULT NULL,
  `token` varchar(64) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `access_level` enum('view','edit') DEFAULT 'view',
  `download_limit` int(11) DEFAULT NULL,
  `downloads_count` int(11) DEFAULT 0,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','free','premium') DEFAULT 'free',
  `avatar` varchar(255) DEFAULT 'default.png',
  `quota_limit_bytes` bigint(20) DEFAULT 1073741824,
  `quota_used_bytes` bigint(20) DEFAULT 0,
  `status` enum('active','suspended') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `avatar`, `quota_limit_bytes`, `quota_used_bytes`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@admin.com', '$2y$10$x8WMVInXw3p0gxsFmXmL0uANg9OugS1hGsBO3E13HRQfNEJl2DLTK', 'admin', 'default.png', 1073741824, 3062905, 'active', '2026-04-18 14:00:36', '2026-04-18 14:02:42'),
(2, 'Jonathan Odoh', 'jonathanodoh3140@gmail.com', '$2y$10$cpziERi/SuX05joyu2hdBe1sYU73ArjPhVMICcF3Z5B0DDW3nTaBS', 'free', 'default.png', 1073741824, 0, 'active', '2026-04-18 14:04:15', '2026-04-18 14:04:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `file_id` (`file_id`),
  ADD KEY `folder_id` (`folder_id`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stored_name` (`stored_name`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `folder_id` (`folder_id`);

--
-- Indexes for table `folders`
--
ALTER TABLE `folders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `shares`
--
ALTER TABLE `shares`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `shared_by` (`shared_by`),
  ADD KEY `file_id` (`file_id`),
  ADD KEY `folder_id` (`folder_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `folders`
--
ALTER TABLE `folders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `shares`
--
ALTER TABLE `shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activities_ibfk_2` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `activities_ibfk_3` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `files_ibfk_2` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `folders`
--
ALTER TABLE `folders`
  ADD CONSTRAINT `folders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `folders_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shares`
--
ALTER TABLE `shares`
  ADD CONSTRAINT `shares_ibfk_1` FOREIGN KEY (`shared_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shares_ibfk_2` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shares_ibfk_3` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
