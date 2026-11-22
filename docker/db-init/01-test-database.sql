-- Separate database for the WordPress PHPUnit suite. Runs once, when the MySQL volume is first created.
CREATE DATABASE IF NOT EXISTS wordpress_test;
GRANT ALL PRIVILEGES ON wordpress_test.* TO 'wordpress'@'%';
