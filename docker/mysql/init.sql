-- Runs once, on the first boot of the MySQL container.
CREATE DATABASE IF NOT EXISTS `pokemon_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON `pokemon_test`.* TO 'pokemon'@'%';
FLUSH PRIVILEGES;
