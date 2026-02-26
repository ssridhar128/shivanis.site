-- Part 4: Collector ingestion table (MySQL 5.7+)
-- Run this once to create the table, e.g.:
--   mysql -u your_user -p your_database < schema.sql

CREATE TABLE IF NOT EXISTS collector_log (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    received_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    type        VARCHAR(32) NOT NULL COMMENT 'static, performance, activity',
    session_id  VARCHAR(64) NOT NULL,
    payload     JSON NOT NULL,
    INDEX idx_type (type),
    INDEX idx_session (session_id),
    INDEX idx_received (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
