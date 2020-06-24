DROP TABLE IF EXISTS `%table_prefix%redirects`;
CREATE TABLE `%table_prefix%redirects` (
  `redirect_id` bigint(32) NOT NULL AUTO_INCREMENT,
  `redirect_from` varchar(2083) NOT NULL,
  `redirect_content_id` bigint(32) NOT NULL,
  `redirect_content_type` enum('image','user','album') NOT NULL,
  PRIMARY KEY (`redirect_id`),
  UNIQUE KEY `redirect_from` (`redirect_from`(255)),
  KEY `redirect_content_id` (`redirect_content_id`)
) ENGINE=%table_engine% DEFAULT CHARSET=utf8;
