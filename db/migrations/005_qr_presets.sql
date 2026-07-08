-- Fase 3 (priority 2): saved color/style presets per user.

CREATE TABLE IF NOT EXISTS `qr_presets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(25) NOT NULL,
  `name` varchar(50) NOT NULL,
  `foreground` varchar(10) NOT NULL,
  `background` varchar(10) NOT NULL,
  `level` varchar(1) NOT NULL DEFAULT 'L',
  `size` int(10) unsigned NOT NULL DEFAULT 200,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
