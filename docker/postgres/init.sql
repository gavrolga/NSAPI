-- Runs once when the container is first created.

-- Create test database (used by PHPUnit)
CREATE DATABASE notifications_test
    WITH OWNER = ns_user
    ENCODING = 'UTF8'
    LC_COLLATE = 'en_US.utf8'
    LC_CTYPE = 'en_US.utf8';

-- Grant all privileges to app user
GRANT ALL PRIVILEGES ON DATABASE notifications TO ns_user;
GRANT ALL PRIVILEGES ON DATABASE notifications_test TO ns_user;
