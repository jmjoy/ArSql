CREATE DATABASE IF NOT EXISTS `test` DEFAULT CHARSET utf8mb4;

DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`name` varchar(128),
`email` varchar(128) NOT NULL,
`address` text,
`status` int (11) DEFAULT 0,
PRIMARY KEY (`id`),
);
