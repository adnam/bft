
CREATE TABLE IF NOT EXISTS `entities` (
  `added_id` int(11) NOT NULL AUTO_INCREMENT,
  `id` binary(16) NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `body` mediumblob,
  PRIMARY KEY (`added_id`),
  UNIQUE KEY `id` (`id`),
  KEY `updated` (`updated`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `entities_collections` (
  `collection_id` binary(16) NOT NULL,
  `name` varchar(255) NOT NULL,
  `entity_id` binary(16) NOT NULL,
  PRIMARY KEY (`collection_id`,`name`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

