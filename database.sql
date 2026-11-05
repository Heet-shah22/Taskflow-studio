-- ========================================================
-- TaskFlow Studio - Database Schema & Sample Dataset
-- Compatible with MySQL 5.7+ / MariaDB 10.2+
-- ========================================================

CREATE DATABASE IF NOT EXISTS `taskflow_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `taskflow_db`;

-- Drop existing tables to allow clean re-import
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `task_comments`;
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `project_members`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'manager', 'member') NOT NULL DEFAULT 'member',
  `title` VARCHAR(100) DEFAULT 'Team Member',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: projects
-- --------------------------------------------------------
CREATE TABLE `projects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('planning', 'in_progress', 'completed', 'on_hold') NOT NULL DEFAULT 'in_progress',
  `priority` ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
  `color` VARCHAR(20) DEFAULT '#6366f1',
  `start_date` DATE DEFAULT NULL,
  `due_date` DATE DEFAULT NULL,
  `budget` DECIMAL(10,2) DEFAULT 0.00,
  `created_by` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_projects_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: project_members
-- --------------------------------------------------------
CREATE TABLE `project_members` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_project_user` (`project_id`, `user_id`),
  CONSTRAINT `fk_pm_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: tasks
-- --------------------------------------------------------
CREATE TABLE `tasks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT NOT NULL,
  `assigned_to` INT DEFAULT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('todo', 'in_progress', 'review', 'done') NOT NULL DEFAULT 'todo',
  `priority` ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
  `due_date` DATE DEFAULT NULL,
  `estimated_hours` DECIMAL(5,1) DEFAULT 0.0,
  `created_by` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tasks_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tasks_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tasks_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: task_comments
-- --------------------------------------------------------
CREATE TABLE `task_comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `task_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_comments_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: activity_logs
-- --------------------------------------------------------
CREATE TABLE `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT DEFAULT NULL,
  `task_id` INT DEFAULT NULL,
  `user_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_activity_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_activity_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- Sample Demo Data
-- Default password for all seed accounts: password123
-- Hash: $2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm
-- ========================================================

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `title`, `avatar`) VALUES
(1, 'Heet', 'admin@taskflow.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'admin', 'Lead Architect & Director', NULL),
(2, 'Rishikesh', 'rishikesh@taskflow.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'manager', 'Technical Project Manager', NULL),
(3, 'Sujal', 'sujal@taskflow.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'member', 'Full-Stack Engineer', NULL),
(4, 'Harshil', 'harshil@taskflow.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'member', 'UI/UX Product Designer', NULL),
(5, 'Ativeer', 'ativeer@taskflow.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'member', 'DevOps & Cloud Engineer', NULL);

INSERT INTO `projects` (`id`, `title`, `description`, `status`, `priority`, `color`, `start_date`, `due_date`, `budget`, `created_by`) VALUES
(1, 'NextGen Mobile App Redesign', 'Revamping the core iOS and Android application with modern reactive design system, dark mode support, and biometrics authentication.', 'in_progress', 'high', '#6366f1', CURDATE() - INTERVAL 14 DAY, CURDATE() + INTERVAL 25 DAY, 45000.00, 1),
(2, 'Enterprise Cloud Migration', 'Migrating legacy on-premise infrastructure to scalable AWS containerized clusters with Kubernetes, zero-downtime database replication.', 'in_progress', 'urgent', '#ec4899', CURDATE() - INTERVAL 30 DAY, CURDATE() + INTERVAL 12 DAY, 85000.00, 2),
(3, 'Brand Identity & Design System', 'Comprehensive brand overhaul including logo guidelines, component tokens, accessibility compliance, and design tokens for web apps.', 'completed', 'medium', '#10b981', CURDATE() - INTERVAL 45 DAY, CURDATE() - INTERVAL 5 DAY, 22000.00, 1),
(4, 'SOC2 Security Audit & Compliance', 'Implementing granular role-based access control, encryption key rotation, and automated intrusion detection to prepare for annual compliance.', 'planning', 'medium', '#f59e0b', CURDATE() + INTERVAL 7 DAY, CURDATE() + INTERVAL 60 DAY, 30000.00, 2);

INSERT INTO `project_members` (`project_id`, `user_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5),
(2, 1), (2, 2), (2, 3), (2, 5),
(3, 1), (3, 4),
(4, 2), (4, 3), (4, 5);

INSERT INTO `tasks` (`id`, `project_id`, `assigned_to`, `title`, `description`, `status`, `priority`, `due_date`, `estimated_hours`, `created_by`) VALUES
(1, 1, 4, 'Design High-Fidelity Figma Wireframes', 'Complete UI mockups for onboarding, user profile, settings and analytics dashboard with interactive components.', 'done', 'high', CURDATE() - INTERVAL 2 DAY, 24.0, 1),
(2, 1, 3, 'Implement Biometric Auth Module', 'Integrate FaceID / Fingerprint API with fallback to JWT refresh tokens and secure device storage keychain.', 'in_progress', 'urgent', CURDATE() + INTERVAL 3 DAY, 18.5, 2),
(3, 1, 3, 'Setup Push Notification Service', 'Integrate Firebase Cloud Messaging and Apple APNS for instant activity alerts and project updates.', 'todo', 'medium', CURDATE() + INTERVAL 10 DAY, 12.0, 2),
(4, 1, 4, 'Accessibility Audit & Contrast Testing', 'Ensure all screens comply with WCAG 2.1 AA standards and support dynamic text scaling.', 'review', 'medium', CURDATE() + INTERVAL 5 DAY, 8.0, 1),
(5, 2, 3, 'Dockerize Microservices Architecture', 'Create optimized multi-stage Dockerfiles for auth, billing, and notification workers.', 'done', 'high', CURDATE() - INTERVAL 8 DAY, 30.0, 2),
(6, 2, 3, 'Configure Terraform AWS EKS Cluster', 'Provision production and staging VPCs, managed node groups, and autoscaling target groups.', 'in_progress', 'urgent', CURDATE() + INTERVAL 4 DAY, 40.0, 1),
(7, 2, 2, 'Setup Prometheus & Grafana Dashboards', 'Build real-time monitoring alerting rules for cluster memory pressure, latency spikes, and 5xx errors.', 'todo', 'high', CURDATE() + INTERVAL 14 DAY, 16.0, 2),
(8, 3, 4, 'Export SVG Icon Library & Color Tokens', 'Standardize 120+ custom vector icons with 24px grid guidelines and CSS custom variables.', 'done', 'low', CURDATE() - INTERVAL 10 DAY, 15.0, 1),
(9, 4, 3, 'Audit API Endpoints for Rate Limiting', 'Implement Redis token-bucket rate limiters on public login and password recovery endpoints.', 'todo', 'medium', CURDATE() + INTERVAL 20 DAY, 14.0, 2);

INSERT INTO `task_comments` (`task_id`, `user_id`, `comment`, `created_at`) VALUES
(2, 1, 'Make sure we test the biometric fallback on older devices without facial recognition sensor.', NOW() - INTERVAL 2 DAY),
(2, 3, 'Checked and confirmed! It seamlessly presents the 6-digit pin code fallback screen.', NOW() - INTERVAL 1 DAY),
(6, 2, 'EKS staging environment is up. Running smoke tests before deploying production manifests.', NOW() - INTERVAL 5 HOUR);

INSERT INTO `activity_logs` (`project_id`, `task_id`, `user_id`, `action`, `details`, `created_at`) VALUES
(1, 1, 4, 'completed_task', 'Elena marked "Design High-Fidelity Figma Wireframes" as Done', NOW() - INTERVAL 2 DAY),
(1, 2, 3, 'status_change', 'Marcus moved "Implement Biometric Auth Module" to In Progress', NOW() - INTERVAL 1 DAY),
(2, 6, 1, 'task_created', 'Sophia created task "Configure Terraform AWS EKS Cluster"', NOW() - INTERVAL 1 DAY),
(1, 2, 3, 'added_comment', 'Elena commented on "Implement Biometric Auth Module"', NOW() - INTERVAL 1 DAY),
(2, 5, 3, 'completed_task', 'Elena completed "Dockerize Microservices Architecture"', NOW() - INTERVAL 6 HOUR);
