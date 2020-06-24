DROP TABLE IF EXISTS `%table_prefix%importing`;
CREATE TABLE `%table_prefix%importing` (
  `importing_id` bigint(32) NOT NULL AUTO_INCREMENT,
  `importing_import_id` bigint(32) NOT NULL,
  `importing_path` varchar(4096) NOT NULL,
  `importing_content_type` enum('user','album','image') NOT NULL,
  `importing_content_id` bigint(32) NOT NULL DEFAULT '0',
  PRIMARY KEY (`importing_id`),
  UNIQUE KEY `importing_path` (`importing_path`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
