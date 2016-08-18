DROP TABLE IF EXISTS `%table_prefix%id_reservations`;
CREATE TABLE `%table_prefix%id_reservations` (
  `id_reservation_id` bigint(32) NOT NULL AUTO_INCREMENT,
  `id_reservation_date_gmt` datetime NOT NULL,
  `id_reservation_reserved_id` bigint(32) NOT NULL,
  `id_reservation_next_id` bigint(32) NOT NULL,
  PRIMARY KEY (`id_reservation_id`),
  KEY `id_reservation_date_gmt` (`id_reservation_date_gmt`),
  KEY `id_reservation_reserved_id` (`id_reservation_reserved_id`),
  KEY `id_reservation_next_id` (`id_reservation_next_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;