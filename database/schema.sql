-- CDF Management System - Complete Database Schema
-- Generated from setup_database.php, functions.php, migrations, and module files

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Core tables
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(15),
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `nrc` VARCHAR(20),
    `dob` DATE,
    `gender` VARCHAR(20),
    `role` ENUM('admin','officer','beneficiary') NOT NULL,
    `department` VARCHAR(100),
    `employee_id` VARCHAR(50),
    `position` VARCHAR(100),
    `constituency` VARCHAR(100),
    `ward` VARCHAR(100),
    `village` VARCHAR(100),
    `profile_picture` VARCHAR(500),
    `street` VARCHAR(255),
    `marital_status` VARCHAR(50),
    `project_type` VARCHAR(100),
    `project_description` TEXT,
    `status` ENUM('active','inactive','pending') DEFAULT 'active',
    `last_login` TIMESTAMP NULL,
    `meta` JSON DEFAULT NULL,
    `preferences` JSON DEFAULT NULL,
    `login_attempts` INT DEFAULT 0,
    `account_locked_until` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255),
    `name` VARCHAR(255),
    `description` TEXT,
    `beneficiary_id` INT(11),
    `beneficiary_name` VARCHAR(255),
    `assigned_officer_id` INT(11),
    `officer_id` INT(11),
    `constituency` VARCHAR(100),
    `category` VARCHAR(100),
    `funding_source` VARCHAR(100),
    `budget_breakdown` TEXT,
    `milestones` TEXT,
    `required_materials` TEXT,
    `human_resources` TEXT,
    `stakeholders` TEXT,
    `community_approval` TINYINT(1) DEFAULT 0,
    `environmental_compliance` TINYINT(1) DEFAULT 0,
    `land_ownership` TINYINT(1) DEFAULT 0,
    `technical_feasibility` TINYINT(1) DEFAULT 0,
    `budget_approval` TINYINT(1) DEFAULT 0,
    `additional_notes` TEXT,
    `budget` DECIMAL(15,2) DEFAULT 0,
    `status` VARCHAR(50) DEFAULT 'planning',
    `approval_status` VARCHAR(50) DEFAULT 'pending',
    `overall_compliance` DECIMAL(5,2) DEFAULT 0,
    `financial_compliance` DECIMAL(5,2) DEFAULT 0,
    `timeline_compliance` DECIMAL(5,2) DEFAULT 0,
    `quality_compliance` DECIMAL(5,2) DEFAULT 0,
    `progress` INT(3) DEFAULT 0,
    `start_date` DATE,
    `end_date` DATE,
    `location` VARCHAR(255),
    `created_by` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL,
    `estimated_duration_days` INT,
    `estimated_completion_date` DATE,
    `actual_start_date` DATE,
    `actual_end_date` DATE,
    `total_expenses` DECIMAL(15,2) DEFAULT 0,
    `budget_utilization` DECIMAL(5,2) DEFAULT 0,
    `last_automated_update` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `beneficiary_groups` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `group_name` VARCHAR(255) NOT NULL,
    `owner_user_id` INT(11) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`owner_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `group_members` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `group_id` INT(11) NOT NULL,
    `member_name` VARCHAR(255) NOT NULL,
    `member_phone` VARCHAR(20),
    `member_nrc` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`group_id`) REFERENCES `beneficiary_groups`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Activity & notifications
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `activity_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `ip_address` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_is_read` (`is_read`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT NOT NULL,
    `recipient_id` INT NOT NULL,
    `subject` VARCHAR(255),
    `message` TEXT NOT NULL,
    `is_urgent` TINYINT(1) DEFAULT 0,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`recipient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_sender` (`sender_id`),
    INDEX `idx_recipient` (`recipient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Projects & financials
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `project_expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `category` VARCHAR(100),
    `description` TEXT,
    `expense_date` DATE,
    `receipt_number` VARCHAR(100),
    `vendor` VARCHAR(255),
    `payment_method` VARCHAR(100),
    `notes` TEXT,
    `created_by` INT NOT NULL,
    `receipt_path` VARCHAR(500),
    `resource_photos` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_progress` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `progress_percentage` INT DEFAULT 0,
    `description` TEXT,
    `challenges` TEXT,
    `next_steps` TEXT,
    `photos` TEXT,
    `receipt_path` VARCHAR(500),
    `achievements` TEXT,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Evaluations & assessments
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `evaluations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `officer_id` INT NOT NULL,
    `evaluation_type` VARCHAR(100),
    `evaluation_date` DATE,
    `status` VARCHAR(50) DEFAULT 'pending',
    `compliance_score` INT,
    `budget_compliance` INT,
    `timeline_compliance` INT,
    `quality_score` INT,
    `documentation_score` INT,
    `community_impact_score` INT,
    `overall_score` INT,
    `findings` TEXT,
    `recommendations` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`officer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `compliance_checks` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `budget_compliance` INT NOT NULL,
    `timeline_compliance` INT NOT NULL,
    `documentation_compliance` INT NOT NULL,
    `quality_standards` INT NOT NULL,
    `community_engagement` INT NOT NULL,
    `environmental_compliance` INT NOT NULL,
    `procurement_compliance` INT NOT NULL,
    `safety_standards` INT NOT NULL,
    `overall_compliance` INT NOT NULL,
    `findings` TEXT,
    `recommendations` TEXT,
    `next_audit_date` DATE NOT NULL,
    `officer_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quality_assessments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `officer_id` INT NOT NULL,
    `workmanship_score` INT,
    `material_quality` INT,
    `safety_standards` INT,
    `completion_quality` INT,
    `overall_quality` INT,
    `strengths` TEXT,
    `improvement_areas` TEXT,
    `recommendations` TEXT,
    `assessment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`officer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quality_evaluations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `officer_id` INT NOT NULL,
    `quality_score` INT,
    `workmanship_score` INT,
    `materials_score` INT,
    `safety_score` INT,
    `compliance_score` INT,
    `overall_score` INT,
    `comments` TEXT,
    `recommendations` TEXT,
    `status` VARCHAR(50) DEFAULT 'pending',
    `evaluation_date` DATE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`officer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `impact_assessments` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `officer_id` INT NOT NULL,
    `community_beneficiaries` INT NOT NULL,
    `employment_generated` INT NOT NULL,
    `economic_impact` INT NOT NULL,
    `social_impact` INT NOT NULL,
    `environmental_impact` INT NOT NULL,
    `sustainability_score` INT NOT NULL,
    `overall_impact` INT NOT NULL,
    `success_stories` TEXT,
    `challenges` TEXT,
    `recommendations` TEXT,
    `assessment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`officer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `progress_reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `officer_id` INT NOT NULL,
    `progress_score` INT,
    `timeline_adherence` INT,
    `quality_rating` INT,
    `resource_utilization` INT,
    `challenges` TEXT,
    `recommendations` TEXT,
    `review_date` DATE,
    `next_review_date` DATE,
    `status` VARCHAR(50) DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`officer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Site visits
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `site_visits` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT(11) NOT NULL,
    `officer_id` INT(11) NOT NULL,
    `visit_date` DATE NOT NULL,
    `visit_time` TIME NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `latitude` DECIMAL(10,8) NULL,
    `longitude` DECIMAL(11,8) NULL,
    `purpose` TEXT NOT NULL,
    `status` VARCHAR(20) DEFAULT 'scheduled',
    `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`officer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_project_id` (`project_id`),
    INDEX `idx_officer_id` (`officer_id`),
    INDEX `idx_visit_date` (`visit_date`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Settings & preferences
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(255) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `setting_type` VARCHAR(50) DEFAULT 'string',
    `setting_group` VARCHAR(100) DEFAULT 'general',
    `description` TEXT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `email_notifications` TINYINT(1) DEFAULT 1,
    `sms_notifications` TINYINT(1) DEFAULT 0,
    `push_notifications` TINYINT(1) DEFAULT 1,
    `project_updates` TINYINT(1) DEFAULT 1,
    `message_alerts` TINYINT(1) DEFAULT 1,
    `deadline_reminders` TINYINT(1) DEFAULT 1,
    `profile_visibility` VARCHAR(20) DEFAULT 'public',
    `location_sharing` TINYINT(1) DEFAULT 0,
    `data_collection` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Password resets
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `used_at` TIMESTAMP NULL,
    `is_used` BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_token` (`token`),
    INDEX `idx_email` (`email`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
