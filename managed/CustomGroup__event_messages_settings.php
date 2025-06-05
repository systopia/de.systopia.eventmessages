<?php

use CRM_Eventmessages_ExtensionUtil as E;

return [
  [
    'name' => 'CustomGroup_event_messages_settings',
    'entity' => 'CustomGroup',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'event_messages_settings',
        'title' => E::ts('Event Messages Settings'),
        'extends' => 'Event',
        'style' => 'Tab',
        'collapse_display' => TRUE,
        'weight' => 38,
        'collapse_adv_display' => TRUE,
        'is_reserved' => TRUE,
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'CustomGroup_event_messages_settings_CustomField_language_provider_names',
    'entity' => 'CustomField',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'custom_group_id.name' => 'event_messages_settings',
        'name' => 'language_provider_names',
        'label' => E::ts('Language Providers'),
        'html_type' => 'Text',
        'is_required' => TRUE,
        'column_name' => 'language_provider_names',
        'serialize' => 1,
      ],
      'match' => [
        'custom_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'CustomGroup_event_messages_settings_CustomField_event_messages_bcc',
    'entity' => 'CustomField',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'custom_group_id.name' => 'event_messages_settings',
        'name' => 'event_messages_bcc',
        'label' => E::ts('BCC'),
        'html_type' => 'Text',
        'is_searchable' => TRUE,
        'column_name' => 'bcc',
        'in_selector' => TRUE,
      ],
      'match' => [
        'custom_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'CustomGroup_event_messages_settings_CustomField_event_messages_custom_data_workaround',
    'entity' => 'CustomField',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'custom_group_id.name' => 'event_messages_settings',
        'name' => 'event_messages_custom_data_workaround',
        'label' => E::ts('Custom Data Workaround'),
        'data_type' => 'Boolean',
        'html_type' => 'Radio',
        'is_searchable' => TRUE,
        'is_view' => TRUE,
        'column_name' => 'custom_data_workaround',
        'in_selector' => TRUE,
      ],
      'match' => [
        'custom_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'CustomGroup_event_messages_settings_CustomField_event_messages_cc',
    'entity' => 'CustomField',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'custom_group_id.name' => 'event_messages_settings',
        'name' => 'event_messages_cc',
        'label' => E::ts('CC'),
        'html_type' => 'Text',
        'is_searchable' => TRUE,
        'column_name' => 'cc',
        'in_selector' => TRUE,
      ],
      'match' => [
        'custom_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'CustomGroup_event_messages_settings_CustomField_event_messages_reply_to',
    'entity' => 'CustomField',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'custom_group_id.name' => 'event_messages_settings',
        'name' => 'event_messages_reply_to',
        'label' => E::ts('Reply-To'),
        'html_type' => 'Text',
        'is_searchable' => TRUE,
        'column_name' => 'reply_to',
        'in_selector' => TRUE,
      ],
      'match' => [
        'custom_group_id',
        'name',
      ],
    ],
  ],
  // TODO: Remove check when minimum core version requirement is >= 6.0.0.
  (class_exists('\Civi\Api4\SiteEmailAddress')
    ? [
      'name' => 'CustomGroup_event_messages_settings_CustomField_event_messages_sender',
      'entity' => 'CustomField',
      'cleanup' => 'always',
      'update' => 'unmodified',
      'params' => [
        'version' => 4,
        'values' => [
          'custom_group_id.name' => 'event_messages_settings',
          'name' => 'event_messages_sender',
          'label' => E::ts('Sender'),
          'data_type' => 'EntityReference',
          'html_type' => 'Autocomplete-Select',
          'fk_entity' => 'SiteEmailAddress',
          'filter' => 'is_active=1',
          'fk_entity_on_delete' => 'set_null',
          'is_searchable' => TRUE,
          'column_name' => 'sender',
          'in_selector' => TRUE,
        ],
        'match' => [
          'custom_group_id',
          'name',
        ],
      ],
    ]
    : [
      'name' => 'CustomGroup_event_messages_settings_CustomField_event_messages_sender',
      'entity' => 'CustomField',
      'cleanup' => 'always',
      'update' => 'unmodified',
      'params' => [
        'version' => 4,
        'values' => [
          'custom_group_id.name' => 'event_messages_settings',
          'name' => 'event_messages_sender',
          'label' => E::ts('Sender'),
          'html_type' => 'Select',
          'is_searchable' => TRUE,
          'column_name' => 'sender',
          'option_group_id.name' => 'from_email_address',
          'in_selector' => TRUE,
        ],
        'match' => [
          'custom_group_id',
          'name',
        ],
      ],
    ]),
  [
    'name' => 'CustomGroup_event_messages_settings_CustomField_event_messages_execute_all_rules',
    'entity' => 'CustomField',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'custom_group_id.name' => 'event_messages_settings',
        'name' => 'event_messages_execute_all_rules',
        'label' => E::ts('Execute All Rules'),
        'data_type' => 'Boolean',
        'html_type' => 'Radio',
        'is_searchable' => TRUE,
        'is_view' => TRUE,
        'column_name' => 'execute_all_rules',
        'in_selector' => TRUE,
      ],
      'match' => [
        'custom_group_id',
        'name',
      ],
    ],
  ],
  [
    'name' => 'CustomGroup_event_messages_settings_CustomField_event_messages_disable_default',
    'entity' => 'CustomField',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'custom_group_id.name' => 'event_messages_settings',
        'name' => 'event_messages_disable_default',
        'label' => E::ts('Disable Default Messages'),
        'data_type' => 'Boolean',
        'html_type' => 'Radio',
        'is_searchable' => TRUE,
        'is_view' => TRUE,
        'column_name' => 'disable_default',
        'in_selector' => TRUE,
      ],
      'match' => [
        'custom_group_id',
        'name',
      ],
    ],
  ],
];
