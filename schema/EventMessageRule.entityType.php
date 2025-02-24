<?php
use CRM_Eventmessages_ExtensionUtil as E;
return [
  'name' => 'EventMessageRule',
  'table' => 'civicrm_event_message_rules',
  'class' => 'CRM_Eventmessages_DAO_EventMessageRule',
  'getInfo' => fn() => [
    'title' => E::ts('Event Message Rule'),
    'title_plural' => E::ts('Event Message Rules'),
    'description' => E::ts('Event Messages Rule Entity'),
    'log' => TRUE,
  ],
  'getIndices' => fn() => [
    'INDEX_is_active' => [
      'fields' => [
        'is_active' => TRUE,
      ],
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique EventMessageRule ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'event_id' => [
      'title' => E::ts('Event ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('FK to Event'),
      'entity_reference' => [
        'entity' => 'Event',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'is_active' => [
      'title' => E::ts('Enabled'),
      'sql_type' => 'tinyint',
      'input_type' => 'Number',
      'description' => E::ts('is this rule active'),
      'default' => NULL,
    ],
    'template_id' => [
      'title' => E::ts('Template ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('civicrm_message_template to be used'),
      'entity_reference' => [
        'entity' => 'MessageTemplate',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'from_status' => [
      'title' => E::ts('From Status'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => E::ts('list of (previous) participant status IDs'),
      'default' => NULL,
      'serialize' => constant('CRM_Core_DAO::SERIALIZE_COMMA'),
    ],
    'to_status' => [
      'title' => E::ts('To Status'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => E::ts('list of (future) participant status IDs'),
      'default' => NULL,
      'serialize' => constant('CRM_Core_DAO::SERIALIZE_COMMA'),
    ],
    'languages' => [
      'title' => E::ts('Languages'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'description' => E::ts('list of languages'),
      'default' => NULL,
      'serialize' => constant('CRM_Core_DAO::SERIALIZE_COMMA'),
      'pseudoconstant' => [
        'option_group_name' => 'event_messages_languages',
      ],
    ],
    'roles' => [
      'title' => E::ts('Roles'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => E::ts('list of roles'),
      'default' => NULL,
      'serialize' => constant('CRM_Core_DAO::SERIALIZE_COMMA'),
    ],
    'weight' => [
      'title' => E::ts('Weight'),
      'sql_type' => 'int',
      'input_type' => 'Number',
      'description' => E::ts('list of weights defining the order'),
      'default' => NULL,
    ],
    'attachments' => [
      'title' => E::ts('Attachments'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('list of attachments'),
      'default' => NULL,
      'serialize' => constant('CRM_Core_DAO::SERIALIZE_JSON'),
    ],
  ],
];
