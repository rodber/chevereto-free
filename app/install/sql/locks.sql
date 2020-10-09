DROP TABLE IF EXISTS `%table_prefix%locks`;
CREATE TABLE `%table_prefix%locks` (
  `lock_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `lock_name` varchar(255) NOT NULL,
  `lock_date_gmt` datetime NOT NULL,
  `lock_expires_gmt` datetime DEFAULT NULL,
  PRIMARY KEY (`lock_id`),
  KEY `lock_date_gmt` (`lock_date_gmt`),
  KEY `lock_expires_gmt` (`lock_expires_gmt`),
  UNIQUE KEY `lock_name` (`lock_name`(191)) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;