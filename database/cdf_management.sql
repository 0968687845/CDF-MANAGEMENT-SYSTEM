-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 15, 2025 at 08:15 AM
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
-- Database: `cdf_management`
--

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 2, 'project_creation', 'Created new project: Chicken laying', '::1', '2025-10-07 00:36:38'),
(2, 4, 'assignment_created', 'Assigned officer to project ID: 1', '::1', '2025-10-10 11:18:03'),
(3, 4, 'assignment_created', 'Assigned officer to project ID: 1', '::1', '2025-10-10 11:18:52'),
(4, 4, 'assignment_created', 'Assigned officer to project ID: 1', '::1', '2025-10-10 11:50:43'),
(5, 4, 'system_settings_update', 'Updated system settings', '::1', '2025-10-10 13:32:42'),
(6, 2, 'expense_added', 'Added expense: Bought 100 chicks - ZMW 500', '::1', '2025-10-15 12:32:34'),
(7, 2, 'expense_deleted', 'Deleted expense: Bought 100 chicks - ZMW 500.00', '::1', '2025-10-15 12:35:08'),
(8, 2, 'expense_added', 'Added expense: Bought 100 chicks - ZMW 500', '::1', '2025-10-15 12:36:12'),
(9, 2, 'expense_added', 'Added expense: paid for transport - ZMW 100', '::1', '2025-10-15 12:38:07'),
(10, 2, 'profile_picture_update', 'Updated profile picture', '::1', '2025-10-15 13:00:41'),
(11, 2, 'profile_picture_update', 'Updated profile picture', '::1', '2025-10-15 13:02:14'),
(12, 2, 'profile_picture_update', 'Updated profile picture', '::1', '2025-10-15 13:02:25'),
(13, 2, 'profile_picture_update', 'Updated profile picture', '::1', '2025-10-15 13:07:16'),
(14, 2, 'profile_picture_update', 'Updated profile picture', '::1', '2025-10-15 13:09:01'),
(15, 2, 'profile_picture_update', 'Updated profile picture', '::1', '2025-10-15 13:19:15'),
(16, 2, 'profile_picture_update', 'Updated profile picture', '::1', '2025-10-15 13:19:39'),
(17, 2, 'profile_picture_update', 'Updated profile picture', '::1', '2025-10-15 13:19:57'),
(18, 2, 'profile_picture_update', 'Updated profile picture', '::1', '2025-10-15 13:23:14'),
(19, 2, 'profile_picture_update', 'Updated profile picture', '::1', '2025-10-15 13:25:57'),
(20, 2, 'privacy_update', 'Updated privacy settings', '::1', '2025-10-15 13:38:25'),
(21, 2, 'settings_update', 'Updated notification preferences', '::1', '2025-10-15 13:38:38'),
(22, 2, 'settings_update', 'Updated notification preferences', '::1', '2025-10-15 13:40:01'),
(23, 2, 'profile_picture_update', 'Updated profile picture', '::1', '2025-10-15 13:49:32'),
(24, 2, 'profile_picture_update', 'Updated profile picture', '::1', '2025-10-15 13:59:37'),
(25, 2, 'profile_picture_update', 'Updated profile picture', '::1', '2025-10-15 13:59:54'),
(26, 2, 'privacy_update', 'Updated privacy settings', '::1', '2025-10-16 11:41:22'),
(27, 2, 'expense_added', 'Added expense: feed - ZMW 1500', '::1', '2025-10-22 06:33:33'),
(28, 2, 'expense_added', 'Added expense: paid the worker - ZMW 800', '::1', '2025-10-22 06:34:25'),
(29, 2, 'expense_added', 'Added expense: vaccination - ZMW 208', '::1', '2025-10-22 06:35:55'),
(30, 2, 'expense_added', 'Added expense: Bought 150 chicks - ZMW 750', '::1', '2025-10-22 06:41:27'),
(31, 2, 'expense_added', 'Added expense: paid for transport - ZMW 100', '::1', '2025-10-22 06:42:03'),
(32, 4, 'system_settings_update', 'Updated system settings', '::1', '2025-10-22 07:31:23'),
(33, 4, 'manual_backup', 'Initiated manual system backup', '::1', '2025-10-22 07:34:13'),
(34, 4, 'user_creation', 'Created new user: John', '::1', '2025-10-22 08:00:16'),
(35, 4, 'project_update', 'Updated project: Chicken laying', '::1', '2025-10-22 08:25:53'),
(36, 4, 'project_update', 'Updated project: Chicken laying', '::1', '2025-10-22 09:20:09'),
(37, 2, 'expense_deleted', 'Deleted expense: paid for transport - ZMW 100.00', '::1', '2025-10-23 05:55:34'),
(38, 2, 'expense_added', 'Added expense: paid for transport - ZMW 120', '::1', '2025-10-23 05:56:41'),
(39, 2, 'progress_update', 'Updated progress for project ID: 1 to 92%', '::1', '2025-10-25 19:55:01'),
(40, 2, 'expense_added', 'Added expense: feed - ZMW 3000', '::1', '2025-10-25 20:50:16'),
(41, 2, 'expense_deleted', 'Deleted expense: feed - ZMW 3000.00', '::1', '2025-10-25 21:24:45'),
(42, 2, 'expense_added', 'Added expense: feed - ZMW 3600', '::1', '2025-10-25 21:25:51'),
(43, 2, 'project_creation', 'Created new project: sss', '::1', '2025-10-30 09:21:02'),
(44, 2, 'project_creation', 'Created new project: Winter Maize Plantation', '::1', '2025-11-02 16:07:22'),
(45, 4, 'officer_assignment', 'Assigned officer to project ID: 3', '::1', '2025-11-02 16:37:55'),
(46, 4, 'project_update', 'Updated project: Winter Maize Plantation', '::1', '2025-11-02 16:41:09'),
(47, 3, 'site_visit_created', 'Scheduled site visit for project: Chicken laying', '::1', '2025-11-07 19:13:58'),
(48, 3, 'site_visit_created', 'Scheduled site visit for project: Chicken laying', '::1', '2025-11-07 19:40:33'),
(49, 3, 'progress_review', 'Submitted progress review for project ID: 1', '::1', '2025-11-09 07:43:32'),
(50, 3, 'compliance_check', 'Submitted compliance check for project ID: 1', '::1', '2025-11-10 17:07:24'),
(51, 3, 'compliance_check', 'Submitted compliance check for project ID: 1', '::1', '2025-11-10 17:23:05'),
(52, 3, 'quality_assessment', 'Submitted quality assessment for project ID: 1', '::1', '2025-11-11 19:27:05'),
(53, 3, 'impact_assessment', 'Submitted impact assessment for project ID: 1', '::1', '2025-11-11 19:52:35'),
(54, 3, 'impact_assessment', 'Submitted impact assessment for project ID: 1', '::1', '2025-11-11 19:55:11'),
(55, 3, 'evaluation_created', 'Created evaluation for project ID: 1', '::1', '2025-11-12 17:47:06'),
(56, 3, 'evaluation_created', 'Created evaluation for project ID: 3', '::1', '2025-11-12 18:42:30'),
(57, 3, 'evaluation_created', 'Created evaluation for project ID: 1', '::1', '2025-11-12 18:44:01'),
(58, 3, 'site_visit_created', 'Scheduled site visit for project: Chicken laying', '::1', '2025-11-13 05:47:14'),
(59, 4, 'user_update', 'Updated user ID: 2', '::1', '2025-11-15 17:02:55'),
(60, 4, 'user_update', 'Updated user ID: 2', '::1', '2025-11-15 17:06:07'),
(61, 2, 'profile_picture_update', 'Updated profile picture', '::1', '2025-11-21 06:29:59'),
(62, 2, 'user_update', 'Updated user ID: 2', '::1', '2025-11-21 06:38:00'),
(63, 2, 'progress_update', 'Updated progress for project ID: 3 to 100%', '::1', '2025-11-21 06:50:29'),
(65, 4, 'project_update', 'Updated project: Chicken laying', '::1', '2025-11-21 08:42:26'),
(66, 4, 'project_update', 'Updated project: Chicken laying', '::1', '2025-11-21 09:01:29'),
(67, 6, 'project_creation', 'Created new project: Solar hummer-meal', '::1', '2025-11-21 13:09:13'),
(68, 3, 'compliance_check', 'Submitted compliance check for project ID: 1', '::1', '2025-11-23 09:44:17'),
(69, 3, 'compliance_check', 'Submitted compliance check for project ID: 1', '::1', '2025-11-23 09:45:49'),
(70, 3, 'impact_assessment', 'Submitted impact assessment for project ID: 4', '::1', '2025-11-23 11:09:19'),
(71, 3, 'impact_assessment', 'Submitted impact assessment for project ID: 4', '::1', '2025-11-23 11:30:41'),
(72, 4, 'user_update', 'Updated user ID: 5', '::1', '2025-11-24 11:18:25'),
(73, 4, 'user_creation', 'Created new user: hmukumbi', '::1', '2025-11-24 12:26:36'),
(74, 2, 'password_reset_requested', 'Password reset email prepared (dev mode): samuelsitmba23@gmail.com', '::1', '2025-11-24 14:24:24'),
(75, 3, 'compliance_check', 'Updated compliance check ID: 4', '::1', '2025-11-24 20:04:35'),
(76, 2, 'project_creation', 'Created new project: daily cattle latching', '::1', '2025-11-26 09:22:07'),
(77, 2, 'progress_update', 'Updated progress for project ID: 5 to 0%', '::1', '2025-11-27 21:02:36'),
(78, 2, 'expense_added', 'Added expense: Bought 6 cows - ZMW 18000', '::1', '2025-11-27 21:11:06'),
(79, 2, 'progress_update', 'Updated progress for project ID: 5 to 8%', '::1', '2025-11-27 21:49:09'),
(80, 2, 'progress_update', 'Updated progress for project ID: 5 to 12%', '::1', '2025-11-27 22:03:04'),
(81, 2, 'progress_update', 'Updated progress for project ID: 5 to 12%', '::1', '2025-11-27 22:16:13'),
(82, 2, 'progress_update', 'Updated progress for project ID: 5 to 8%', '::1', '2025-11-27 22:29:34'),
(83, 2, 'progress_update', 'Updated progress for project ID: 5 to 30%', '::1', '2025-11-28 06:02:46'),
(84, 2, 'progress_update', 'Updated progress for project ID: 1 to 44.91%', '::1', '2025-11-28 06:42:30'),
(85, 3, 'impact_assessment', 'Submitted impact assessment for project ID: 5', '::1', '2025-11-28 08:40:27'),
(86, 2, 'expense_added', 'Added expense: feed - ZMW 422', '::1', '2025-11-28 09:09:10'),
(87, 2, 'progress_update', 'Updated progress for project ID: 1 to 56.67%', '::1', '2025-11-28 09:13:51'),
(88, 2, 'progress_update', 'Updated progress for project ID: 1 to 56.67%', '::1', '2025-11-28 09:18:25'),
(89, 2, 'progress_update', 'Updated progress for project ID: 5 to 30%', '::1', '2025-11-28 11:28:45'),
(90, 2, 'profile_picture_update', 'Updated profile picture', '::1', '2025-12-05 19:02:43');

--
-- Dumping data for table `compliance_checks`
--

INSERT INTO `compliance_checks` (`id`, `project_id`, `budget_compliance`, `timeline_compliance`, `documentation_compliance`, `quality_standards`, `community_engagement`, `environmental_compliance`, `procurement_compliance`, `safety_standards`, `overall_compliance`, `findings`, `recommendations`, `next_audit_date`, `officer_id`, `created_at`, `updated_at`) VALUES
(5, 4, 1, 1, 1, 1, 1, 1, 1, 1, 20, 'the project is delayed to start', 'start the project', '2025-11-30', 3, '2025-11-23 12:28:45', '2025-11-23 12:28:45'),
(6, 5, 5, 5, 5, 5, 1, 5, 5, 5, 90, 'The project shows a positive indicator for successful implementation', 'focus on raising the number of cows for mass milk production and high profits', '2025-12-20', 3, '2025-11-28 08:29:48', '2025-11-28 08:29:48'),
(10, 5, 5, 5, 5, 5, 5, 5, 5, 5, 100, 'In-complance', 'none', '2026-01-22', 3, '2025-11-30 17:55:37', '2025-11-30 17:55:37'),
(12, 5, 5, 5, 5, 5, 5, 5, 5, 5, 100, 'In-compliant', 'none', '2026-01-01', 3, '2025-11-30 18:22:05', '2025-11-30 18:22:05'),
(15, 5, 90, 83, 100, 89, 94, 92, 92, 98, 92, 'Very good', 'none', '2025-12-02', 3, '2025-11-30 19:40:21', '2025-11-30 19:40:21'),
(16, 5, 50, 50, 50, 50, 50, 50, 50, 50, 50, '', '', '2026-01-11', 3, '2025-11-30 20:11:20', '2025-11-30 20:11:20');

--
-- Dumping data for table `evaluations`
--

INSERT INTO `evaluations` (`id`, `project_id`, `officer_id`, `evaluation_type`, `evaluation_date`, `status`, `compliance_score`, `budget_compliance`, `timeline_compliance`, `quality_score`, `documentation_score`, `community_impact_score`, `overall_score`, `findings`, `recommendations`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'progress', '2025-11-12', 'completed', 100, 100, 100, 100, 100, 100, 100, 'Good progress', 'None', '2025-11-12 17:47:06', '2025-11-12 17:47:06'),
(3, 1, 3, 'progress', '2025-11-12', 'completed', 100, 100, 100, 100, 100, 100, 100, '', '', '2025-11-12 18:44:01', '2025-11-12 18:44:01');

--
-- Dumping data for table `impact_assessments`
--

INSERT INTO `impact_assessments` (`id`, `project_id`, `officer_id`, `community_beneficiaries`, `employment_generated`, `economic_impact`, `social_impact`, `environmental_impact`, `sustainability_score`, `overall_impact`, `success_stories`, `challenges`, `recommendations`, `assessment_date`, `created_at`, `updated_at`) VALUES
(2, 1, 3, 100, 20, 4, 3, 3, 5, 4, '', '', '', '2025-11-11 19:55:11', '2025-11-11 19:55:11', '2025-11-11 19:55:11'),
(3, 4, 3, 0, 0, 3, 3, 3, 3, 3, 'none', 'not started', 'start the project', '2025-11-23 11:09:19', '2025-11-23 11:09:19', '2025-11-23 11:09:19'),
(4, 4, 3, 0, 0, 1, 1, 2, 1, 1, '', '', '', '2025-11-23 11:30:41', '2025-11-23 11:30:41', '2025-11-23 11:30:41'),
(5, 5, 3, 12, 12, 1, 4, 5, 5, 4, 'None', 'None', 'Increase cattle growth for mass milk production', '2025-11-28 08:40:27', '2025-11-28 08:40:27', '2025-11-28 08:40:27');

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `recipient_id`, `subject`, `message`, `is_urgent`, `is_read`, `created_at`) VALUES
(1, 2, 3, 'Advice', 'Hello Sir', 1, 1, '2025-10-15 09:44:42'),
(2, 3, 2, 'Re: Conversation', 'hello, how can i help you', 0, 1, '2025-11-03 18:45:31'),
(3, 3, 2, 'Re: Conversation', 'hello, how can i help you', 0, 0, '2025-11-03 18:47:31'),
(4, 3, 2, 'Re: Conversation', 'hello, how can i help you', 0, 0, '2025-11-03 18:48:02'),
(5, 3, 2, 'Re: Conversation', 'hello, how can i help you', 0, 0, '2025-11-03 18:48:32'),
(6, 3, 2, 'Re: Conversation', 'hello, how can i help you', 0, 0, '2025-11-03 18:49:02'),
(7, 3, 2, 'Re: Conversation', 'hello, how can i help you', 0, 0, '2025-11-03 18:49:32'),
(8, 3, 2, 'Re: Conversation', 'hello, how can i help you', 0, 0, '2025-11-03 18:50:03'),
(9, 3, 2, 'Re: Conversation', 'hello, how can i help you', 0, 0, '2025-11-03 18:50:33'),
(10, 3, 2, 'Re: Conversation', 'hello, how can i help you', 0, 1, '2025-11-03 18:51:03'),
(11, 3, 2, 'Re: Conversation', 'hello, how can i help you', 0, 1, '2025-11-03 18:51:34'),
(12, 3, 2, 'Re: Conversation', 'hello, how can i help you', 0, 1, '2025-11-03 18:52:04'),
(13, 3, 2, 'Re: Conversation', 'hello, how can i help you', 0, 1, '2025-11-03 18:54:35'),
(14, 5, 4, 'Advice', 'Hello, need help on how do i set my knew project', 1, 0, '2025-11-15 17:21:35'),
(15, 5, 4, 'Advice', 'Hello, need help on how do i set my knew project', 1, 0, '2025-11-15 17:22:32'),
(16, 3, 2, 'Progress Check', 'Hello we\'ve observed that your project for maize plantation has been staled for some time...kindly update us with the progress\r\n', 1, 1, '2025-11-20 19:53:40'),
(17, 3, 2, 'Re: Conversation', 'hello', 0, 0, '2025-12-01 14:23:54');

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(43, 2, 'New Message', 'You have received a new message from Samuel.o. Sitemba', 0, '2025-12-01 14:23:54');

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `email`, `token`, `created_at`, `expires_at`, `used_at`, `is_used`) VALUES
(1, 2, 'samuelsitmba23@gmail.com', 'ce063b69a12483988570be60cd1e0663f913ed32cde7fda96c1d283f61ed4ac0', '2025-11-24 14:17:04', '2025-11-25 13:17:04', NULL, 0),
(2, 2, 'samuelsitmba23@gmail.com', 'cd32d13587df2e2cfe5d9adb4c71b21a9e8c19220f6adf3c8975de7650319c31', '2025-11-24 14:24:22', '2025-11-25 13:24:22', NULL, 0);

--
-- Dumping data for table `progress_reviews`
--

INSERT INTO `progress_reviews` (`id`, `project_id`, `officer_id`, `progress_score`, `timeline_adherence`, `quality_rating`, `resource_utilization`, `challenges`, `recommendations`, `review_date`, `next_review_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 100, 100, 'excellent', 100, 'No', 'No', '2025-11-09', '2025-11-15', 'submitted', '2025-11-09 07:43:32', '2025-11-09 07:43:32');

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `title`, `description`, `beneficiary_id`, `beneficiary_name`, `assigned_officer_id`, `officer_id`, `constituency`, `category`, `funding_source`, `budget_breakdown`, `milestones`, `required_materials`, `human_resources`, `stakeholders`, `community_approval`, `environmental_compliance`, `land_ownership`, `technical_feasibility`, `budget_approval`, `additional_notes`, `budget`, `status`, `approval_status`, `overall_compliance`, `financial_compliance`, `timeline_compliance`, `quality_compliance`, `progress`, `start_date`, `end_date`, `location`, `created_at`, `updated_at`, `completed_at`, `estimated_duration_days`, `estimated_completion_date`, `actual_start_date`, `actual_end_date`, `total_expenses`, `budget_utilization`, `last_automated_update`) VALUES
(1, 'Chicken laying', 'Growing layers chicken for eggs', 2, NULL, NULL, 3, 'Ndola Central', 'women empowerment', 'cdf', '200 chicks at k10 each\r\n90 kg feed at k5000\r\nShelta at k1000\r\nOther expenses k2000', '', 'chicks, water, shelter, feed, electricity\r\n', 'skilled labor', 'government officials', 1, 1, 1, 1, 1, '', 8000.00, 'completed', 'pending', 0.00, 0.00, 0.00, 0.00, 100, '2025-10-07', '2025-11-22', 'Ndola', '2025-10-07 00:36:38', '2025-11-24 11:13:34', NULL, 90, NULL, NULL, NULL, 0.00, 0.00, NULL),
(4, 'Solar hummer-meal', 'Buy a solar powered hummer-meal for JAMES Co-operative', 6, NULL, NULL, NULL, 'Ndola Central', 'youth-empowerment', 'CDF', '1. hummer-meal k30 000\r\n2. Solar panels  & batteries k70 000\r\n3. Building and installations k50 000\r\n4. Other costs k40 000', NULL, '1. Building\r\n2. Hummer-meal\r\n3. Solar panels\r\n4. Batteries', '2 people for operation', '1. Samuel Sitemba\r\n2. James Mapanza\r\n3. John Muleya\r\n4. Luyando Mweetwa\r\n5. Kabungo Katongo\r\n6. Mooga Kache', 1, 1, 1, 1, 1, '', 200000.00, 'planning', 'pending', 0.00, 0.00, 0.00, 0.00, 0, '2025-12-01', '2025-12-30', 'Ndola', '2025-11-21 13:09:13', '2025-11-24 11:14:14', NULL, 90, NULL, NULL, NULL, 0.00, 0.00, NULL),
(5, 'daily cattle latching', 'keep cattle for dairy milk', 2, NULL, NULL, 3, 'Ndola Central', 'youth-empowerment', 'CDF', 'buy 6 cows k5000 each', NULL, 'grazing land, and cows', 'team members', 'government and team members', 1, 1, 1, 1, 1, '', 30000.00, 'in-progress', 'pending', 0.00, 0.00, 0.00, 0.00, 0, '2025-11-27', '2026-01-01', 'ndola', '2025-11-26 09:22:07', '2025-11-27 21:34:31', NULL, 90, NULL, '2025-11-27', NULL, 0.00, 0.00, NULL);

--
-- Dumping data for table `project_expenses`
--

INSERT INTO `project_expenses` (`id`, `project_id`, `amount`, `category`, `description`, `expense_date`, `receipt_number`, `vendor`, `payment_method`, `notes`, `created_by`, `created_at`, `updated_at`, `receipt_path`, `resource_photos`) VALUES
(2, 1, 500.00, 'Utilities', 'Bought 100 chicks', '2025-10-15', '1002', 'M&K Suppliers', 'Cash', NULL, 2, '2025-10-15 12:36:12', '2025-10-15 12:36:12', NULL, NULL),
(4, 1, 1500.00, 'Utilities', 'feed', '2025-10-22', NULL, 'M&K Suppliers', 'Cash', 'The feed will take us for a month', 2, '2025-10-22 06:33:33', '2025-10-22 06:33:33', NULL, NULL),
(5, 1, 800.00, 'Labor', 'paid the worker', '2025-10-22', NULL, NULL, 'Cash', NULL, 2, '2025-10-22 06:34:25', '2025-10-22 06:34:25', NULL, NULL),
(6, 1, 208.00, 'Utilities', 'vaccination', '2025-10-22', NULL, 'M&K Suppliers', 'Cash', NULL, 2, '2025-10-22 06:35:55', '2025-10-22 06:35:55', NULL, NULL),
(7, 1, 750.00, 'Utilities', 'Bought 150 chicks', '2025-10-22', '10021', 'M&K Suppliers', 'Cash', 'We increased the number of chicken to 249', 2, '2025-10-22 06:41:27', '2025-10-22 06:41:27', NULL, NULL),
(8, 1, 100.00, 'Transport', 'paid for transport', '2025-10-22', NULL, NULL, 'Cash', NULL, 2, '2025-10-22 06:42:03', '2025-10-22 06:42:03', NULL, NULL),
(9, 1, 120.00, 'Transport', 'paid for transport', '2025-10-23', NULL, NULL, 'Cash', NULL, 2, '2025-10-23 05:56:41', '2025-10-23 05:56:41', NULL, NULL),
(11, 1, 3600.00, 'Utilities', 'feed', '2025-10-25', '100211', 'M&K Suppliers', 'Cash', NULL, 2, '2025-10-25 21:25:51', '2025-10-25 21:25:51', 'uploads/receipts/1/receipt_1761427551.jpg', NULL),
(12, 5, 18000.00, 'Other', 'Bought 6 cows', '2025-11-27', '1002', 'J7 farms', 'Cash', 'We have managed to secure at least 6 cows', 2, '2025-11-27 21:11:06', '2025-11-27 21:11:06', 'uploads/receipts/5/receipt_1764277866.jpg', '[\"uploads\\/progress\\/5\\/progress_1764277866_0.jpg\",\"uploads\\/progress\\/5\\/progress_1764277866_1.jpg\",\"uploads\\/progress\\/5\\/progress_1764277866_2.jpg\"]'),
(13, 1, 422.00, 'Utilities', 'feed', '2025-11-28', '100212', 'M&K Suppliers', 'Cash', NULL, 2, '2025-11-28 09:09:10', '2025-11-28 09:09:10', 'uploads/receipts/1/receipt_1764320950.jpg', NULL);

--
-- Dumping data for table `project_progress`
--

INSERT INTO `project_progress` (`id`, `project_id`, `progress_percentage`, `description`, `challenges`, `next_steps`, `photos`, `receipt_path`, `created_by`, `created_at`, `achievements`) VALUES
(1, 1, 92.00, '99 chicken has started to lay eggs ', 'No challenges so far', 'Selling eggs to customers', '[\"uploads\\/progress\\/1\\/progress_1761422101_0.jpg\",\"uploads\\/progress\\/1\\/progress_1761422101_1.jpg\",\"uploads\\/progress\\/1\\/progress_1761422101_2.jpg\"]', 'uploads/receipts/1/receipt_1761422101.jpg', 2, '2025-10-25 19:55:01', NULL),
(8, 5, 30.00, 'we managed to buy 6 cows for the start ', 'No challenges', '', '[\"uploads\\/progress\\/5\\/progress_1764309765_0.jpg\"]', 'uploads/receipts/5/receipt_1764309766.jpg', 2, '2025-11-28 06:02:46', '[\"6 cows bought\"]'),
(9, 1, 44.91, 'Raised over 250 chickens producing 250 eggs daily, now we are able to raise over k600 per day', 'Market issues', 'continue selling eggs', '[\"uploads\\/progress\\/1\\/progress_1764312150_0.jpg\",\"uploads\\/progress\\/1\\/progress_1764312150_1.jpg\"]', 'uploads/receipts/1/receipt_1764312150.jpg', 2, '2025-11-28 06:42:30', '[\"Raised over 250 chickens producing 250 eggs daily\"]'),
(10, 1, 56.67, 'Mass production of Eggs 200 per day with annual profit of k500', '', '', '[\"uploads\\/progress\\/1\\/progress_1764321231_0.jpg\",\"uploads\\/progress\\/1\\/progress_1764321231_1.jpg\",\"uploads\\/progress\\/1\\/progress_1764321231_2.jpg\"]', 'uploads/receipts/1/receipt_1764321231.jpg', 2, '2025-11-28 09:13:51', '[\"Raised over 250 chickens producing 250 eggs daily\",\"Mass production of Eggs 200 per day with annual profit of k500\"]'),
(11, 1, 56.67, 'Mass production of Eggs 200 per day with annual profit of k5000', '', '', '[\"uploads\\/progress\\/1\\/progress_1764321505_0.jpg\",\"uploads\\/progress\\/1\\/progress_1764321505_1.jpg\",\"uploads\\/progress\\/1\\/progress_1764321505_2.jpg\"]', NULL, 2, '2025-11-28 09:18:25', '[\"Core hub of eggs suppliers in the community\",\"Monthly profit over k15000\"]'),
(12, 5, 30.00, 'Bought 2 more cows for dairy milk', '', '', '[\"uploads\\/progress\\/5\\/progress_1764329325_0.jpg\"]', NULL, 2, '2025-11-28 11:28:45', '[\"provides milk to local \"]');

--
-- Dumping data for table `quality_assessments`
--

INSERT INTO `quality_assessments` (`id`, `project_id`, `officer_id`, `workmanship_score`, `material_quality`, `safety_standards`, `completion_quality`, `overall_quality`, `strengths`, `improvement_areas`, `recommendations`, `assessment_date`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 5, 5, 5, 5, 5, '', '', '', '2025-11-11 19:27:05', '2025-11-11 19:27:05', '2025-11-11 19:27:05');

--
-- Dumping data for table `quality_evaluations`
--

INSERT INTO `quality_evaluations` (`id`, `project_id`, `officer_id`, `quality_score`, `workmanship_score`, `materials_score`, `safety_score`, `compliance_score`, `overall_score`, `comments`, `recommendations`, `status`, `evaluation_date`, `created_at`, `updated_at`) VALUES
(1, 4, 3, 0, 0, 0, 0, 0, 0.00, 'begin the project', 'The project must start', 'completed', '2025-11-24', '2025-11-24 04:43:28', '2025-11-24 04:43:28'),
(2, 1, 3, 100, 100, 100, 100, 97, 99.40, 'completed', 'completed', 'completed', '2025-11-24', '2025-11-24 05:06:38', '2025-11-24 05:06:38'),
(3, 5, 3, 100, 100, 100, 100, 100, 100.00, 'The quality is highly acceptable ', 'None', 'completed', '2025-11-28', '2025-11-28 08:33:32', '2025-11-28 08:33:32');

--
-- Dumping data for table `site_visits`
--

INSERT INTO `site_visits` (`id`, `project_id`, `officer_id`, `visit_date`, `visit_time`, `location`, `latitude`, `longitude`, `purpose`, `status`, `created`) VALUES
(1, 1, 3, '2025-11-20', '09:00:00', 'Lusaka', 0.00000000, 0.00000000, 'Project progress tracking', 'scheduled', '2025-11-19 09:52:10'),
(2, 1, 3, '2025-11-26', '09:00:00', 'lusaka', 0.00000000, 0.00000000, 'progress track', 'scheduled', '2025-11-19 11:56:11');

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `setting_group`, `description`, `updated_at`, `created_at`) VALUES
(1, 'system_name', 'CDF Management System', 'string', 'general', 'The name that appears throughout the system', '2025-11-24 13:50:33', '2025-11-22 21:39:22'),
(2, 'system_email', 'noreply@cdf.gov.zm', 'string', 'general', 'Email address used for system notifications', '2025-11-24 13:50:33', '2025-11-22 21:39:22'),
(3, 'admin_email', 'samuelsitmba72@gmail.com', 'string', 'general', 'Primary administrator contact email', '2025-11-24 13:50:33', '2025-11-22 21:39:22'),
(4, 'timezone', 'Africa/Lusaka', 'string', 'general', 'System timezone for all date/time displays', '2025-11-24 13:50:33', '2025-11-22 21:39:22'),
(5, 'date_format', 'Y-m-d', 'string', 'general', 'How dates are displayed throughout the system', '2025-11-24 13:50:33', '2025-11-22 21:39:22'),
(6, 'items_per_page', '100', 'integer', 'general', 'Number of items to display per page in lists and tables', '2025-11-24 13:50:33', '2025-11-22 21:39:22'),
(7, 'maintenance_mode', '0', 'boolean', 'general', 'When enabled, only administrators can access the system', '2025-11-24 13:50:33', '2025-11-22 21:39:22'),
(8, 'email_notifications', '1', 'boolean', 'notifications', 'Send email notifications for important system events', '2025-11-22 21:39:22', '2025-11-22 21:39:22'),
(9, 'project_approvals', '1', 'boolean', 'notifications', 'Notify administrators when projects require approval', '2025-11-22 21:39:22', '2025-11-22 21:39:22'),
(10, 'officer_assignments', '1', 'boolean', 'notifications', 'Notify officers when they are assigned to new projects', '2025-11-22 21:39:22', '2025-11-22 21:39:22'),
(11, 'budget_alerts', '1', 'boolean', 'notifications', 'Send alerts when project budgets approach or exceed limits', '2025-11-22 21:39:22', '2025-11-22 21:39:22'),
(12, 'system_updates', '1', 'boolean', 'notifications', 'Notify administrators about system updates and maintenance', '2025-11-22 21:39:22', '2025-11-22 21:39:22'),
(13, 'password_policy', 'medium', 'string', 'security', 'Minimum requirements for user passwords', '2025-11-22 21:48:51', '2025-11-22 21:39:22'),
(14, 'session_timeout', '60', 'integer', 'security', 'Time before inactive users are automatically logged out', '2025-11-22 21:48:51', '2025-11-22 21:39:22'),
(15, 'max_login_attempts', '5', 'integer', 'security', 'Number of failed login attempts before account lockout', '2025-11-22 21:48:51', '2025-11-22 21:39:22'),
(16, 'two_factor_auth', '0', 'boolean', 'security', 'Require two-factor authentication for administrator accounts', '2025-11-22 21:48:51', '2025-11-22 21:39:22'),
(17, 'ip_whitelist', '', 'text', 'security', 'Restrict access to specific IP addresses', '2025-11-22 21:48:51', '2025-11-22 21:39:22'),
(18, 'auto_backup', '1', 'boolean', 'backup', 'Automatically create system backups on a schedule', '2025-11-22 21:39:22', '2025-11-22 21:39:22'),
(19, 'backup_frequency', 'daily', 'string', 'backup', 'How often to create automatic backups', '2025-11-22 21:39:22', '2025-11-22 21:39:22'),
(20, 'backup_retention', '30', 'integer', 'backup', 'How long to keep backup files', '2025-11-22 21:39:22', '2025-11-22 21:39:22'),
(21, 'backup_email', 'backups@cdf.gov.zm', 'string', 'backup', 'Email address to receive backup status notifications', '2025-11-22 21:39:22', '2025-11-22 21:39:22'),
(22, 'last_backup', '2025-11-22 22:48:13 (Simulated)', 'string', 'backup', 'Timestamp of last successful backup', '2025-11-22 21:48:13', '2025-11-22 21:39:22');

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `phone`, `first_name`, `last_name`, `nrc`, `dob`, `gender`, `role`, `department`, `employee_id`, `position`, `constituency`, `ward`, `village`, `profile_picture`, `street`, `marital_status`, `project_type`, `project_description`, `status`, `last_login`, `created_at`, `updated_at`, `preferences`, `login_attempts`, `account_locked_until`) VALUES
(2, 'SAMUEL', '$2y$10$rL0v9f1JXisIsJLXbn/dguvMOY93fkuX2zgK5GomvVOPXZce9.AnK', 'samuelsitmba23@gmail.com', '0968687845', 'Samuel', 'Sitemba', '387742/73/1', '1990-02-02', 'male', 'beneficiary', NULL, NULL, NULL, 'Ndola Central', 'nkwazi 3035', 'ndola', 'profile_2_1764961363.jpg', 'senior', 'single', 'agriculture', 'fish farming', 'active', NULL, '2025-09-22 08:10:54', '2025-12-05 19:02:43', NULL, 0, NULL),
(3, 'samuel2', '$2y$10$wFwyBEu2NiOn58UwU96f4uJeMg3nkZ4HgmHxMN1G6amFAVt1C9TRS', 'samuelsitemba72@gmail.com', '0968687845', 'Samuel.o.', 'Sitemba', '383477/73/1', '1980-01-01', '', 'officer', NULL, NULL, NULL, 'Ndola Central', '', '', NULL, NULL, '', NULL, NULL, 'active', NULL, '2025-09-22 18:50:28', '2025-09-22 18:50:28', NULL, 0, NULL),
(4, 'admin', '$2y$10$exka1MemTpi2TX6XPLSWteh3/sxiHvjTMsDxd.h1FEA6AM.tJpfgy', 'admin@cdf.gov.zm', '0968687845', 'admin', 'admin', '384477/72/1', '2000-02-01', '', 'admin', NULL, NULL, NULL, '', '', '', NULL, NULL, '', NULL, NULL, 'active', NULL, '2025-09-22 19:04:58', '2025-11-22 22:54:11', '{\"email_notifications\":1,\"desktop_notifications\":1,\"theme\":\"dark\",\"language\":\"en\",\"timezone\":\"Africa\\/Lusaka\"}', 0, NULL),
(5, 'neckson', '$2y$10$Dh9u5vu2IBYl.bitynMDYOhhYbj5qpPfJyiTcmKBCMVJjzacHyO/S', 'sikazweneckson@gmail.com', '0774588316', 'Neckson', 'Sikazwe', '161306/93/1', '2003-07-19', 'male', 'officer', NULL, NULL, NULL, 'Ndola Central', 'nkwazi', 'Nkwazi', NULL, NULL, 'single', NULL, NULL, 'active', NULL, '2025-10-05 17:59:45', '2025-11-24 11:18:25', NULL, 0, NULL),
(6, 'John', '$2y$10$pjFFOlBBlCL23NJg12RiIO0lvb5yYuJluRYqrWLWnZvEWMqVNuijO', 'johnmapanza@gmail.com', '0968687845', 'John', 'Mapanza', '384477/74/1', '2002-06-28', 'male', 'beneficiary', NULL, NULL, NULL, 'Kalomo', 'Siachitema', 'Siamabele', NULL, NULL, 'single', NULL, NULL, 'active', NULL, '2025-10-22 08:00:16', '2025-10-22 08:00:16', NULL, 0, NULL),
(7, 'hmukumbi', '$2y$10$CjynWUYQPHDISmpJJNhH5.FLQZJOqHVtx5o9jBsJ2BRr9cLQSS07K', 'henrymukumbi2@gmail.com', '0777779934', 'Henry', 'Mukumbi', NULL, NULL, NULL, 'officer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2025-11-24 12:26:36', '2025-11-24 12:26:36', NULL, 0, NULL),
(8, 'lukando Co-operative', '$2y$10$o/Dc8FAGz/BpVfFUs8TA5ucr85FRVBRBS4nPtRfo1agIQqvocJYSa', 'jermapanza@gmail.com', '0777779939', 'samuel', 'Mapanza', '387749/73/1', NULL, NULL, 'beneficiary', '', NULL, NULL, 'Ndola Central', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2025-11-26 20:31:00', '2025-11-26 20:31:00', NULL, 0, NULL);

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`id`, `user_id`, `email_notifications`, `sms_notifications`, `push_notifications`, `project_updates`, `message_alerts`, `deadline_reminders`, `profile_visibility`, `location_sharing`, `data_collection`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 1, 1, 1, 1, 1, 'public', 1, 1, '2025-10-15 13:38:25', '2025-10-16 11:41:22');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
