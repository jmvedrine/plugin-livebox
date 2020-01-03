CREATE TABLE IF NOT EXISTS `livebox_calls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `callerName` varchar(128) DEFAULT NULL,
  `phone` varchar(128) DEFAULT NULL,
  `startDate` datetime DEFAULT NULL,
  `isFetched` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;