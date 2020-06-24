DROP TABLE IF EXISTS `%table_prefix%albums`;
CREATE TABLE `%table_prefix%albums` (
  `album_id` bigint(32) NOT NULL AUTO_INCREMENT,
  `album_name` varchar(100) NOT NULL,
  `album_user_id` bigint(32) DEFAULT NULL,
  `album_date` datetime NOT NULL,
  `album_date_gmt` datetime NOT NULL,
  `album_creation_ip` varchar(255) NOT NULL,
  `album_privacy` enum('public','password','private','private_but_link','custom') DEFAULT 'public',
  `album_privacy_extra` mediumtext,
  `album_password` mediumtext,
  `album_image_count` bigint(32) NOT NULL DEFAULT '0',
  `album_description` mediumtext,
  `album_likes` bigint(32) NOT NULL DEFAULT '0',
  `album_views` bigint(32) NOT NULL DEFAULT '0',
  PRIMARY KEY (`album_id`),
  KEY `album_name` (`album_name`),
  KEY `album_user_id` (`album_user_id`),
  KEY `album_date_gmt` (`album_date_gmt`),
  KEY `album_privacy` (`album_privacy`),
  KEY `album_image_count` (`album_image_count`),
  KEY `album_creation_ip` (`album_creation_ip`(191)),
  FULLTEXT KEY `searchindex` (`album_name`,`album_description`)
) ENGINE=%table_engine% DEFAULT CHARSET=utf8mb4;