DROP TABLE IF EXISTS `%table_prefix%follows`;
CREATE TABLE `%table_prefix%follows` (
  `follow_id` bigint(32) NOT NULL AUTO_INCREMENT,
  `follow_date` datetime NOT NULL,
  `follow_date_gmt` datetime NOT NULL,
  `follow_user_id` bigint(32) NOT NULL,
  `follow_followed_user_id` bigint(32) NOT NULL,
  `follow_ip` varchar(255) NOT NULL,
  PRIMARY KEY (`follow_id`),
  KEY `follow_user_id` (`follow_user_id`),
  KEY `follow_followed_user_id` (`follow_followed_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;