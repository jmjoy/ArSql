DROP TABLE IF EXISTS `user`;
DROP TABLE IF EXISTS `customer`;
DROP TABLE IF EXISTS `item` CASCADE;
DROP TABLE IF EXISTS `category` CASCADE;

CREATE TABLE `user` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`name` varchar(128),
`email` varchar(128) NOT NULL,
`address` text,
`status` int (11) DEFAULT 0,
PRIMARY KEY (`id`)
);

CREATE TABLE `customer` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`email` varchar(128) NOT NULL,
`name` varchar(128),
`address` text,
`status` int (11) DEFAULT 0,
`profile_id` int(11),
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `category` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`name` varchar(128) NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `item` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`name` varchar(128) NOT NULL,
`category_id` int(11) NOT NULL,
PRIMARY KEY (`id`),
KEY `FK_item_category_id` (`category_id`),
CONSTRAINT `FK_item_category_id` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO `customer` (email, name, address, status, profile_id) VALUES ('user1@example.com', 'user1', 'address1', 1, 1);
INSERT INTO `customer` (email, name, address, status) VALUES ('user2@example.com', 'user2', 'address2', 1);
INSERT INTO `customer` (email, name, address, status, profile_id) VALUES ('user3@example.com', 'user3', 'address3', 2, 2);


INSERT INTO `category` (name) VALUES ('Books');
INSERT INTO `category` (name) VALUES ('Movies');


INSERT INTO `item` (name, category_id) VALUES ('Agile Web Application Development with Yii1.1 and PHP5', 1);
INSERT INTO `item` (name, category_id) VALUES ('Yii 1.1 Application Development Cookbook', 1);
INSERT INTO `item` (name, category_id) VALUES ('Ice Age', 2);
INSERT INTO `item` (name, category_id) VALUES ('Toy Story', 2);
INSERT INTO `item` (name, category_id) VALUES ('Cars', 2);
