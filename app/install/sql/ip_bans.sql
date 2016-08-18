DROP TABLE IF EXISTS `%table_prefix%ip_bans`;
CREATE TABLE `%table_prefix%ip_bans` (
  `ip_ban_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ip_ban_date` datetime NOT NULL,
  `ip_ban_date_gmt` datetime NOT NULL,
  `ip_ban_expires` datetime DEFAULT NULL,
  `ip_ban_expires_gmt` datetime DEFAULT NULL,
  `ip_ban_ip` varchar(255) NOT NULL,
  `ip_ban_message` longtext,
  PRIMARY KEY (`ip_ban_id`),
  KEY `ip_ban_date_gmt` (`ip_ban_date_gmt`),
  UNIQUE KEY `ip_ban_ip` (`ip_ban_ip`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;