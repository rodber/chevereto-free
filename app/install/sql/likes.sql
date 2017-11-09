DROP TABLE IF EXISTS `%table_prefix%likes`;
CREATE TABLE `%table_prefix%likes` (
  `like_id` bigint(32) NOT NULL AUTO_INCREMENT,
  `like_date` datetime NOT NULL,
  `like_date_gmt` datetime NOT NULL,
  `like_user_id` bigint(32) DEFAULT NULL,
  `like_content_type` enum('image','album') DEFAULT NULL,
  `like_content_id` bigint(32) NOT NULL,
  `like_content_user_id` bigint(32) DEFAULT NULL,
  `like_ip` varchar(255) NOT NULL,
  PRIMARY KEY (`like_id`),
  KEY `like_date_gmt` (`like_date_gmt`),
  KEY `like_user_id` (`like_user_id`),
  KEY `like_content_type` (`like_content_type`),
  KEY `like_content_id` (`like_content_id`),
  KEY `like_content_user_id` (`like_content_user_id`),
  KEY `like_ip` (`like_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
