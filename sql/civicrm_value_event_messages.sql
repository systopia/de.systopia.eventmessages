-- Event Message Rules table
CREATE TABLE `civicrm_event_message_rules` (
  `id`          int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'primary key',
  `event_id`    int(10) unsigned NOT NULL                COMMENT 'civicrm_event this rule belongs to',
  `is_active`   tinyint(4)   DEFAULT NULL                COMMENT 'is this rule active',
  `template_id` int(10) unsigned NOT NULL                COMMENT 'civicrm_message_template to be used',
  `from_status` varchar(255) DEFAULT NULL                COMMENT 'list of (previous) participant status IDs',
  `to_status`   varchar(255) DEFAULT NULL                COMMENT 'list of (future) participant status IDs',
  `languages`   varchar(255) DEFAULT NULL                COMMENT 'list of languages',
  `roles`       varchar(255) DEFAULT NULL                COMMENT 'list of roles',
  `weight`      int(10)      DEFAULT NULL                COMMENT 'list of weights defining the order',
  `attachments` varchar(255) DEFAULT NULL                COMMENT 'list of attachments',
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_event_message_rules_event_id` (`event_id`),
  KEY `FK_civicrm_event_message_rules_template_id` (`template_id`),
  KEY `INDEX_is_active` (`is_active`),
  CONSTRAINT `FK_civicrm_event_messages_event_id` FOREIGN KEY (`event_id`) REFERENCES `civicrm_event` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_civicrm_msg_template_id` FOREIGN KEY (`template_id`) REFERENCES `civicrm_msg_template` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
;