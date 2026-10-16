-- ============================================================
--  AI Portfolio Optimizer – MySQL Database Schema
--  Run this once to initialise the database.
--  Compatible with MySQL 8.0+
-- ============================================================

CREATE DATABASE IF NOT EXISTS `ai_portfolio_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ai_portfolio_db`;

-- ────────────────────────────────────────────────────────────
--  Table: users
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `username`     VARCHAR(50)      NOT NULL,
  `email`        VARCHAR(150)     NOT NULL,
  `password`     VARCHAR(255)     NOT NULL COMMENT 'bcrypt hash',
  `role`         ENUM('user','admin') NOT NULL DEFAULT 'user',
  `avatar`       VARCHAR(255)     NULL      DEFAULT NULL,
  `is_active`    TINYINT(1)       NOT NULL  DEFAULT 1,
  `created_at`   DATETIME         NOT NULL  DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME         NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email`    (`email`),
  UNIQUE KEY `uq_users_username` (`username`),
  INDEX `idx_users_role`         (`role`),
  INDEX `idx_users_created_at`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
--  Table: code_reviews
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `code_reviews` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`         INT UNSIGNED  NOT NULL,
  `language`        VARCHAR(20)   NOT NULL DEFAULT 'javascript'
                      COMMENT 'html|css|javascript|php|typescript',
  `original_code`   MEDIUMTEXT    NOT NULL,
  `ai_feedback`     MEDIUMTEXT    NOT NULL COMMENT 'Markdown formatted AI response',
  `tokens_used`     INT UNSIGNED  NULL     DEFAULT NULL,
  `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_code_reviews_user` (`user_id`),
  INDEX `idx_code_reviews_created` (`created_at`),
  CONSTRAINT `fk_code_reviews_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
--  Table: interview_sessions
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `interview_sessions` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED  NOT NULL,
  `role`           VARCHAR(80)   NOT NULL
                     COMMENT 'e.g. Frontend Developer, Full-Stack Developer',
  `transcript_json` JSON         NOT NULL COMMENT 'Array of {role,content} message objects',
  `final_score`    DECIMAL(4,2)  NULL     DEFAULT NULL
                     COMMENT 'Aggregate score out of 10',
  `status`         ENUM('active','completed','abandoned')
                     NOT NULL DEFAULT 'active',
  `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_interview_user` (`user_id`),
  INDEX `idx_interview_status`   (`status`),
  INDEX `idx_interview_created`  (`created_at`),
  CONSTRAINT `fk_interview_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
--  Table: rate_limits  (simple in-DB rate limiter)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED  NOT NULL,
  `endpoint`    VARCHAR(80)   NOT NULL COMMENT 'e.g. review|chat',
  `requests`    SMALLINT      NOT NULL DEFAULT 1,
  `window_start` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rate_user_endpoint` (`user_id`, `endpoint`),
  CONSTRAINT `fk_rate_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
--  Seed: default admin user
--  Password: Admin@1234  (bcrypt hash – CHANGE IN PRODUCTION)
-- ────────────────────────────────────────────────────────────
INSERT INTO `users` (`username`, `email`, `password`, `role`) VALUES
(
  'admin',
  'admin@aiportfolio.dev',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'admin'
);
