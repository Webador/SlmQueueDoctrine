CREATE TABLE IF NOT EXISTS `queue_defaults` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `queue` varchar(64) NOT NULL,
  `data` mediumtext NOT NULL,
  `status` smallint(1) NOT NULL,
  `created` datetime(6) NOT NULL,
  `scheduled` datetime(6) NOT NULL,
  `executed` datetime(6) DEFAULT NULL,
  `finished` datetime(6) DEFAULT NULL,
  `priority` int DEFAULT 1024 NOT NULL,
  `message` text,
  `trace` text,
  PRIMARY KEY (`id`),
  KEY `pop` (`status`,`queue`,`scheduled`,`priority`),
  KEY `prune` (`status`,`queue`,`finished`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
