DROP TABLE IF EXISTS `%table_prefix%settings`;
CREATE TABLE `%table_prefix%settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_name` varchar(191) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `setting_value` mediumtext,
  `setting_default` mediumtext,
  `setting_typeset` enum('string','bool') DEFAULT 'string',
  PRIMARY KEY (`setting_id`),
  KEY `setting_name` (`setting_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;