DROP TABLE IF EXISTS `%table_prefix%notifications`;
CREATE TABLE `%table_prefix%notifications` (
  `notification_id` bigint(32) NOT NULL AUTO_INCREMENT,
  `notification_date_gmt` datetime NOT NULL,
  `notification_user_id` bigint(32) NOT NULL,
  `notification_trigger_user_id` bigint(32) DEFAULT NULL,
  `notification_type` enum('follow','like') NOT NULL,
  `notification_content_type` enum('user','image','album') NOT NULL,
  `notification_type_id` bigint(32) NOT NULL COMMENT 'type_id based on action (type) table',
  `notification_is_read` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`notification_id`),
  KEY `notification_date_gmt` (`notification_date_gmt`),
  KEY `notification_user_id` (`notification_user_id`),
  KEY `notification_trigger_user_id` (`notification_trigger_user_id`),
  KEY `notification_type` (`notification_type`),
  KEY `notification_content_type` (`notification_content_type`),
  KEY `notification_type_id` (`notification_type_id`),
  KEY `notification_is_read` (`notification_is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;