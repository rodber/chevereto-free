DROP TABLE IF EXISTS `%table_prefix%requests`;
CREATE TABLE `%table_prefix%requests` (
  `request_id` bigint(32) NOT NULL AUTO_INCREMENT,
  `request_type` enum('upload','signup','account-edit','account-password-forgot','account-password-reset','account-resend-activation','account-email-needed','account-change-email','account-activate','login', 'content-password') NOT NULL,
  `request_user_id` bigint(32) DEFAULT NULL,
  `request_content_id` bigint(32) DEFAULT NULL,
  `request_ip` varchar(255) NOT NULL,
  `request_date` datetime NOT NULL,
  `request_date_gmt` datetime NOT NULL,
  `request_result` enum('success','fail') NOT NULL,
  PRIMARY KEY (`request_id`),
  KEY `request_type` (`request_type`),
  KEY `request_user_id` (`request_user_id`),
  KEY `request_content_id` (`request_content_id`),
  KEY `request_ip` (`request_ip`),
  KEY `request_date_gmt` (`request_date_gmt`),
  KEY `request_result` (`request_result`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;