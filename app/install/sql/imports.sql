DROP TABLE IF EXISTS `%table_prefix%imports`;
CREATE TABLE `%table_prefix%imports` (
  `import_id` bigint(32) NOT NULL AUTO_INCREMENT,
  `import_path` varchar(4096) NOT NULL,
  `import_options` varchar(255) DEFAULT NULL,
  `import_status` enum('queued','working','paused','canceled','completed') NOT NULL,
  `import_users` bigint(32) NOT NULL DEFAULT '0',
  `import_images` bigint(32) NOT NULL DEFAULT '0',
  `import_albums` bigint(32) NOT NULL DEFAULT '0',
  `import_time_created` datetime DEFAULT NULL,
  `import_time_updated` datetime DEFAULT NULL,
  `import_errors` tinyint(1) NOT NULL DEFAULT '0',
  `import_started` tinyint(1) NOT NULL DEFAULT '0',
  `import_continuous` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`import_id`),
  KEY `import_path` (`import_path`(255)) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `%table_prefix%imports` VALUES ('1', '%rootPath%importing/no-parse', 'a:1:{s:4:"root";s:5:"plain";}', 'working', '0', '0', '0', NOW(), NOW(), '0', '1', '1');
INSERT INTO `%table_prefix%imports` VALUES ('2', '%rootPath%importing/parse-users', 'a:1:{s:4:"root";s:5:"users";}', 'working', '0', '0', '0', NOW(), NOW(), '0', '1', '1');
INSERT INTO `%table_prefix%imports` VALUES ('3', '%rootPath%importing/parse-albums', 'a:1:{s:4:"root";s:6:"albums";}', 'working', '0', '0', '0', NOW(), NOW(), '0', '1', '1');