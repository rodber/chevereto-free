DROP TABLE IF EXISTS `%table_prefix%queues`;
CREATE TABLE `%table_prefix%queues` (
  `queue_id` bigint(32) NOT NULL AUTO_INCREMENT,
  `queue_type` enum('storage-delete') NOT NULL,
  `queue_date_gmt` datetime NOT NULL,
  `queue_args` longtext NOT NULL,
  `queue_join` bigint(32) NOT NULL,
  `queue_attempts` varchar(255) DEFAULT '0',
  `queue_status` enum('pending','failed') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`queue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;