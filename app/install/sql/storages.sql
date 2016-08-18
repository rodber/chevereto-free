DROP TABLE IF EXISTS `%table_prefix%storages`;
CREATE TABLE `%table_prefix%storages` (
  `storage_id` bigint(32) NOT NULL AUTO_INCREMENT,
  `storage_api_id` bigint(32) NOT NULL,
  `storage_name` varchar(255) NOT NULL,
  `storage_service` varchar(255) DEFAULT NULL,
  `storage_url` varchar(255) NOT NULL,
  `storage_bucket` varchar(255) DEFAULT NULL,
  `storage_region` varchar(255) DEFAULT NULL,
  `storage_server` varchar(255) DEFAULT NULL,
  `storage_account_id` varchar(255) DEFAULT NULL,
  `storage_account_name` varchar(255) DEFAULT NULL,
  `storage_key` text,
  `storage_secret` text,
  `storage_is_https` tinyint(1) NOT NULL DEFAULT '0',
  `storage_is_active` tinyint(1) NOT NULL DEFAULT '0',
  `storage_capacity` bigint(32) DEFAULT NULL,
  `storage_space_used` bigint(32) DEFAULT '0',
  PRIMARY KEY (`storage_id`),
  KEY `storage_api_id` (`storage_api_id`),
  KEY `storage_is_active` (`storage_is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;