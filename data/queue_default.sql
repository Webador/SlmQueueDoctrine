CREATE TABLE IF NOT EXISTS `queue_default` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `queue` varchar(64) NOT NULL,
  `data` text NOT NULL,
  `status` smallint(1) NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `scheduled` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `executed` datetime NULL DEFAULT NULL,
  `finished` datetime NULL DEFAULT NULL,
  `message` varchar(256) DEFAULT NULL,
  `trace` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

