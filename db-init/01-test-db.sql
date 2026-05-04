-- Auto-run by the MySQL container on first init (mounted in /docker-entrypoint-initdb.d).
-- Creates a separate DB used by PHPUnit so tests don't pollute the dev DB.
CREATE DATABASE IF NOT EXISTS adhesion_test DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON adhesion_test.* TO 'adhesion'@'%';
FLUSH PRIVILEGES;
