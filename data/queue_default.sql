CREATE TABLE IF NOT EXISTS `queue_default` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `queue` varchar(64) NOT NULL,
  `data` text NOT NULL,
  `status` smallint(1) NOT NULL,
  `created` datetime(6) NOT NULL DEFAULT '1000-01-01 00:00:00.000000',
  `scheduled` datetime(6) NOT NULL DEFAULT '1000-01-01 00:00:00.000000',
  `executed` datetime(6) DEFAULT NULL,
  `finished` datetime(6) DEFAULT NULL,
  `message` text,
  `trace` text,
  PRIMARY KEY (`id`),
  KEY `pop` (`status`,`queue`,`scheduled`),
  KEY `prune` (`status`,`queue`,`finished`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
