-- =============================================================================
-- Ledgerly - personal budget tracker
-- MySQL schema and sample data
-- Import with:  mysql -u root -p < database.sql
--          or:  phpMyAdmin > Import > choose this file
-- =============================================================================

DROP DATABASE IF EXISTS ledgerly;
CREATE DATABASE ledgerly CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ledgerly;

-- -----------------------------------------------------------------------------
-- Table: users
-- -----------------------------------------------------------------------------
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    first_name  VARCHAR(50)  NOT NULL,
    middle_name VARCHAR(50)      NULL,
    last_name   VARCHAR(50)  NOT NULL,
    address     VARCHAR(255) NOT NULL,
    mobile      VARCHAR(20)  NOT NULL,
    username    VARCHAR(30)  NOT NULL UNIQUE,
    email       VARCHAR(120) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,          -- stored with password_hash()
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Table: expenses  (holds both expense and income entries)
-- -----------------------------------------------------------------------------
CREATE TABLE expenses (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    type        ENUM('expense','income') NOT NULL DEFAULT 'expense',
    category    VARCHAR(50)    NOT NULL,
    amount      DECIMAL(12,2)  NOT NULL,
    txn_date    DATE           NOT NULL,
    description VARCHAR(255)       NULL,
    created_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_expenses_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, txn_date),
    INDEX idx_user_category (user_id, category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Table: messages  (contact form submissions)
-- -----------------------------------------------------------------------------
CREATE TABLE messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(120) NOT NULL,
    message    TEXT         NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- Sample data
-- =============================================================================

INSERT INTO messages (name, email, message) VALUES
('Ruwan Jayasuriya', 'ruwan@example.com',
 'Could you add a category for school fees? I track them separately every term.');

-- -----------------------------------------------------------------------------
-- Demo account and sample transactions
-- -----------------------------------------------------------------------------
-- A bcrypt hash cannot be produced by MySQL, so the demo user is created by a
-- small PHP script instead. After importing this file, open once in a browser:
--
--     http://localhost/project/seed-demo.php
--
-- That creates the account  username: demo   password: demo1234
-- together with three months of sample income and expense entries.
-- Delete seed-demo.php before you submit or deploy the project.
