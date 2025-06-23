<?php
/*-------------------------------------------------------+
| SYSTOPIA Event Messages                                |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

declare(strict_types = 1);

use Civi\Api4\Event;
use Civi\Api4\OptionValue;
use Civi\EventMessages\Language\LanguageProviderContainer;
use Civi\Api4\SiteEmailAddress;
use CRM_Eventmessages_ExtensionUtil as E;

/**
 * Form controller for event online registration settings
 */
class CRM_Eventmessages_Form_EventMessages extends CRM_Event_Form_ManageEvent {
  protected const MAX_RULE_COUNT = 50;
  public const SETTINGS_FIELDS = [
    'event_messages_disable_default',
    'event_messages_sender',
    'event_messages_reply_to',
    'event_messages_cc',
    'event_messages_bcc',
    'event_messages_execute_all_rules',
    'event_messages_custom_data_workaround',
    'language_provider_names',
  ];

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    parent::preProcess();
    Civi::resources()->addScriptFile(E::LONG_NAME, 'js/language_provider_names_keep_order.js');
    $this->setSelectedChild('eventmessages');
  }

  public function buildQuickForm() {
    $event_settings = $this->loadEventSettings();

    // add settings fields
    $this->add(
        'checkbox',
        'event_messages_disable_default',
        E::ts('Disable CiviCRM Default Messages')
    );
    $this->add(
        'select',
        'event_messages_sender',
        E::ts('Send From'),
        $this->getSenderOptions(),
        TRUE,
        ['class' => 'huge crm-select2', 'placeholder' => E::ts('-select-')]
    );
    $this->add(
        'text',
        'event_messages_reply_to',
        E::ts('Reply To'),
        ['class' => 'huge'],
        FALSE
    );
    $this->add(
        'text',
        'event_messages_cc',
        E::ts('CC'),
        ['class' => 'huge'],
        FALSE
    );
    $this->add(
        'text',
        'event_messages_bcc',
        E::ts('BCC'),
        ['class' => 'huge'],
        FALSE
    );
    $this->add(
        'checkbox',
        'event_messages_execute_all_rules',
        E::ts('Execute All Matching Rules?')
    );

    // Actually it would be better to have the metadata array available as
    // variable in the {htxt} block, though it seems that it's only possible
    // to pass strings...
    $this->assign('language_provider_options_help', $this->buildLanguageProviderOptionsHelp());

    $this->add(
        'select',
        'language_provider_names',
        E::ts('Language Providers'),
            $this->getLanguageProviderOptions($event_settings),
        FALSE,
        ['class' => 'huge crm-select2', 'multiple' => 'multiple'],
    );

    $this->add(
        'checkbox',
        'event_messages_custom_data_workaround',
        E::ts('Custom Data Workaround')
    );

    // set defaults for these fields
    $this->setDefaults($event_settings);

    // add message rules block
    $this->assign('rules_list', range(1, self::MAX_RULE_COUNT));
    $status_list = $this->getParticipantStatusList();
    $template_list = $this->getMessageTemplateList();
    $languages_list = $this->getLanguagesList();
    $roles_list = $this->getRolesList();
    $rules = CRM_Eventmessages_Logic::getAllRules($this->_id);
    $rules = array_pad($rules, self::MAX_RULE_COUNT, []);
    foreach ($rules as $index => $rule) {
      // 1-based.
      $index++;

      $this->add(
        'hidden',
        "id_{$index}"
      );
      $this->add(
        'checkbox',
        "is_active_{$index}",
        E::ts('Active?')
      );
      $this->add(
        'select',
        "from_{$index}",
        E::ts('From Status'),
        $status_list,
        FALSE,
        ['class' => 'huge crm-select2', 'multiple' => 'multiple', 'placeholder' => 'any']
      );
      $this->add(
        'select',
        "to_{$index}",
        E::ts('To Status'),
        $status_list,
        FALSE,
        ['class' => 'huge crm-select2', 'multiple' => 'multiple', 'placeholder' => 'any']
      );
      $this->add(
        'select',
        "languages_{$index}",
        E::ts('Preferred Language'),
        $languages_list,
        FALSE,
        ['class' => 'huge crm-select2', 'multiple' => 'multiple', 'placeholder' => 'any']
      );
      $this->add(
        'select',
        "roles_{$index}",
        E::ts('Roles'),
        $roles_list,
        FALSE,
        ['class' => 'huge crm-select2', 'multiple' => 'multiple', 'placeholder' => 'any']
      );
      $this->add(
        'select',
        "template_{$index}",
        E::ts('Message Template'),
        $template_list,
        FALSE,
        ['class' => 'huge crm-select2']
      );

      // Add attachment elements.
      if (class_exists('\Civi\Mailattachment\Form\Attachments')) {
        \Civi\Mailattachment\Form\Attachments::addAttachmentElements(
        $this,
        [
          'entity_type' => 'participant',
          'prefix' => $index . '--',
          'defaults' => $rule['attachments'] ?? [],
        ]
        );
      }
    }

    // set current rule data
    foreach ($rules as $index => $rule) {
      if (!empty($rule)) {
        $i = $index + 1;
        $this->setDefaults(
        [
          "id_{$i}" => $rule['id'],
          "is_active_{$i}" => $rule['is_active'],
          "from_{$i}" => $rule['from'],
          "to_{$i}" => $rule['to'],
          "roles_{$i}" => $rule['roles'],
          "languages_{$i}" => $rule['languages'],
          "template_{$i}" => $rule['template'],
        ]
        );
      }
    }

    $this->addButtons(
        [
            [
              'type'      => 'submit',
              'name'      => E::ts('Save'),
              'isDefault' => TRUE,
            ],
        ]
    );

    Civi::resources()->addStyleUrl(E::url('css/eventmessages_form.css'));
    parent::buildQuickForm();
  }

  public function validate() {
    parent::validate();
    // TODO: validation rules?
    return (0 == count($this->_errors));
  }

  public function postProcess() {
    $values = $this->exportValues();

    // store the settings
    $event_update = [
      'id'          => $this->_id,
      'is_template' => 0,
    ];

    // set template flag, since it will otherwise reset it
    try {
      $event_update['is_template'] = (int) civicrm_api3('Event', 'getvalue', [
        'return' => 'is_template',
        'id'     => $this->_id,
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
      // that's weird...
      Civi::log()->warning("Event.get [{$this->_id}]: retreiving is_template failed: " . $ex->getMessage());
    }

    // set all the settings fields
    foreach (self::SETTINGS_FIELDS as $field_name) {
      $field_key = CRM_Eventmessages_CustomData::getCustomFieldKey(
        'event_messages_settings',
        $field_name
      );
      $event_update[$field_key] = CRM_Utils_Array::value($field_name, $values, '');
    }
    civicrm_api3('Event', 'create', $event_update);

    // extract rules
    $rules = [];
    foreach (range(1, self::MAX_RULE_COUNT) as $i) {
      $rule = [
        'id'        => CRM_Utils_Array::value("id_{$i}", $values, NULL),
        'is_active' => (int) CRM_Utils_Array::value("is_active_{$i}", $values, FALSE),
        'from'      => CRM_Utils_Array::value("from_{$i}", $values, []),
        'to'        => CRM_Utils_Array::value("to_{$i}", $values, []),
        'languages' => CRM_Utils_Array::value("languages_{$i}", $values, []),
        'roles'     => CRM_Utils_Array::value("roles_{$i}", $values, []),
        'template'  => CRM_Utils_Array::value("template_{$i}", $values, NULL),
        'weight'    => (10 + count($rules) * 10),
      ];
      if (class_exists('\Civi\Mailattachment\Form\Attachments')) {
        $rule['attachments'] = \Civi\Mailattachment\Form\Attachments::processAttachments(
          $this,
          ['prefix' => $i . '--']
        );
      }
      if (!empty($rule['template'])) {
        $rules[] = $rule;
      }
    }
    CRM_Eventmessages_Logic::syncRules($this->_id, $rules);

    $this->_action = CRM_Core_Action::UPDATE;

    parent::endPostProcess();
  }

  /**
   * Get a list of the available participant statuses
   */
  protected function getParticipantStatusList() {
    $list = [];
    $query = civicrm_api3(
        'ParticipantStatusType',
        'get',
        [
          'option.limit' => 0,
          'return'       => 'id,label',
        ]
    );
    foreach ($query['values'] as $status) {
      $list[$status['id']] = $status['label'];
    }
    return $list;
  }

  /**
   * Get a list of the available/allowed sender email addresses
   */
  protected function getSenderOptions() {
    $dropdown_list = [];
    // TODO: Remove check when minimum core version requirement is >= 6.0.0.
    if (class_exists(SiteEmailAddress::class)) {
      $from_email_addresses = SiteEmailAddress::get(FALSE)
        ->addSelect('display_name', 'id')
        ->addWhere('domain_id', '=', 'current_domain')
        ->addWhere('is_active', '=', TRUE)
        ->execute()
        ->indexBy('id')
        ->getArrayCopy();
      // Include "email" column as the option value label did.
      $from_email_addresses = array_map(
        fn($address) => sprintf('"%s" <%s>', $address['display_name'], $address['email']),
        $from_email_addresses
      );
    }
    else {
      $from_email_addresses = OptionValue::get(FALSE)
        ->addSelect('value', 'label')
        ->addWhere('option_group_id:name', '=', 'from_email_address')
        ->addWhere('is_active', '=', TRUE)
        ->execute()
        ->indexBy('value')
        ->column('label');
    }
    foreach ($from_email_addresses as $key => $from_email_address) {
      $dropdown_list[$key] = htmlentities($from_email_address);
    }
    return $dropdown_list;
  }

  /**
   * Get a list of the available participant statuses
   */
  protected function getMessageTemplateList() {
    $list = ['' => E::ts('-mandatory-')];
    $query = civicrm_api3(
        'MessageTemplate',
        'get',
        [
          'option.limit' => 0,
    // otherwise it won't be sent by MessageTemplate.send
          'is_default'   => 1,
          'return'       => 'id,msg_title',
        ]
    );
    foreach ($query['values'] as $status) {
      $list[$status['id']] = $status['msg_title'];
    }
    return $list;
  }

  /**
   * Get a list of the available participant roles
   */
  protected function getRolesList() {
    $list = [];
    $query = civicrm_api3(
        'OptionValue',
        'get',
        [
          'option_group_id' => 'participant_role',
          'option.limit'    => 0,
          'return'          => 'value,label',
        ]
    );
    foreach ($query['values'] as $role) {
      $list[$role['value']] = $role['label'];
    }
    return $list;
  }

  /**
   * Get a list of the available participant languages
   */
  protected function getLanguagesList(): array {
    $list = [];
    $languages = OptionValue::get(FALSE)
      ->addSelect('value', 'label')
      ->addWhere('option_group_id:name', '=', 'event_messages_languages')
      ->addWhere('is_active', '=', TRUE)
      ->addOrderBy('weight')
      ->execute();
    /** @phpstan-var array{value: string, label: string} $language */
    foreach ($languages as $language) {
      $list[$language['value']] = $language['label'];
    }

    return $list;
  }

  /**
   * Manipulates the Implements hook_civicrm_tabset
   *
   * @param integer $event_id
   *      the event in question
   *
   * @param array $tabs
   *      tab structure to be displayed
   */
  public static function addToTabs($event_id, &$tabs) {
    // then add new registration tab
    if ($event_id) {
      $tabs['eventmessages'] = [
        'title'   => E::ts('Communication'),
        'link'    => CRM_Utils_System::url(
            'civicrm/event/manage/eventmessages',
            "action=update&reset=1&id={$event_id}"
        ),
        'valid'   => 1,
        'active'  => 1,
        'current' => FALSE,
      ];
    }
    else {
      $tabs['eventmessages'] = [
        'title' => E::ts('Communication'),
        'url'   => 'civicrm/event/manage/eventmessages',
        'field' => 'id',
      ];
    }
  }

  /**
   * Sort language provider options in the order in settings.
   *
   * @phpstan-param array<string, string> $language_provider_options
   * @phpstan-param array<string, mixed> $event_settings
   */
  private function sortLanguageProviderOptions(array &$language_provider_options, array $event_settings): void {
    /** @phpstan-var array<string> $language_provider_names */
    $language_provider_names = $event_settings['language_provider_names'] ?? [];
    uksort(
        $language_provider_options,
        fn(string $a, string $b) =>
            array_search($a, $language_provider_names) - array_search($b, $language_provider_names)
    );
  }

  /**
   * @phpstan-return array<string, mixed>
   *     Settings keyed by their option name.
   *
   * @throws CRM_Core_Exception
   */
  private function loadEventSettings(): array {
    $values = Event::get()
      ->addSelect('event_messages_settings.*')
      ->addWhere('id', '=', $this->_id)
      ->execute()
      ->single();

    $settings = [];
    foreach ($values as $key => $value) {
      [, $optionName] = explode('.', $key, 2);
      $settings[$optionName] = $value;
    }

    return $settings;
  }

  /**
   * @phpstan-param array<string, mixed> $event_settings
   *
   * @phpstan-return array<string, string>
   *     Mapping of provider name to label ordered by their order in given
   *     event settings.
   */
  private function getLanguageProviderOptions(array $event_settings): array {
    /** @var \Civi\EventMessages\Language\LanguageProviderContainer $language_provider_container */
    $language_provider_container = Civi::service(LanguageProviderContainer::class);
    $language_provider_options = [];
    foreach ($language_provider_container->getMetadata() as $metadata) {
      $language_provider_options[$metadata['name']] = $metadata['label'];
    }
    // Display options in order of their selection.
    $this->sortLanguageProviderOptions($language_provider_options, $event_settings);

    return $language_provider_options;
  }

  private function buildLanguageProviderOptionsHelp(): string {
    // HTML tags are stripped when used as argument in {help}.
    /** @var \Civi\EventMessages\Language\LanguageProviderContainer $language_provider_container */
    $language_provider_container = \civi::service(LanguageProviderContainer::class);
    $help = [];
    foreach ($language_provider_container->getMetadata() as $metadata) {
      $help[] = sprintf('• %s: %s', $metadata['label'], $metadata['description']);
    }

    return implode("\n", $help);
  }

}
