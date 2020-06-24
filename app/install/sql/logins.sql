DROP TABLE IF EXISTS `%table_prefix%logins`;
CREATE TABLE `%table_prefix%logins` (
  `login_id` bigint(32) NOT NULL AUTO_INCREMENT,
  `login_user_id` bigint(32) NOT NULL,
  `login_type` enum('password','session','cookie','facebook','twitter','google','vk','cookie_facebook','cookie_twitter','cookie_google','cookie_vk') NOT NULL,
  `login_ip` varchar(255) DEFAULT NULL,
  `login_hostname` mediumtext,
  `login_date` datetime NOT NULL,
  `login_date_gmt` datetime NOT NULL,
  `login_resource_id` varchar(255) DEFAULT NULL,
  `login_resource_name` mediumtext,
  `login_resource_avatar` mediumtext,
  `login_resource_url` mediumtext,
  `login_secret` mediumtext DEFAULT NULL COMMENT 'The secret part',
  `login_token_hash` mediumtext COMMENT 'Hashed complement to secret if needed',
  PRIMARY KEY (`login_id`),
  KEY `login_user_id` (`login_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;