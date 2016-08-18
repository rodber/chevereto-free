DROP TABLE IF EXISTS `%table_prefix%deletions`;
CREATE TABLE `%table_prefix%deletions` (
  `deleted_id` bigint(32) NOT NULL AUTO_INCREMENT,
  `deleted_date_gmt` datetime NOT NULL,
  `deleted_content_id` bigint(32) NOT NULL,
  `deleted_content_date_gmt` datetime NOT NULL,
  `deleted_content_user_id` bigint(32) DEFAULT NULL,
  `deleted_content_ip` varchar(255) NOT NULL,
  `deleted_content_md5` varchar(32) DEFAULT NULL,
  `deleted_content_original_filename` varchar(255) DEFAULT NULL,
  `deleted_content_views` bigint(32) NOT NULL DEFAULT '0',
  `deleted_content_likes` bigint(32) NOT NULL DEFAULT '0',
  PRIMARY KEY (`deleted_id`),
  KEY `deleted_content_id` (`deleted_content_id`),
  KEY `deleted_content_user_id` (`deleted_content_user_id`),
  KEY `deleted_content_ip` (`deleted_content_ip`),
  KEY `deleted_content_md5` (`deleted_content_md5`),
  KEY `deleted_content_views` (`deleted_content_views`),
  KEY `deleted_content_likes` (`deleted_content_likes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
