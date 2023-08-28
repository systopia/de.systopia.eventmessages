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

use CRM_Eventmessages_ExtensionUtil as E;


/**
 * Form controller for event online registration settings
 */
class CRM_Eventmessages_Form_EventMessages extends CRM_Event_Form_ManageEvent
{
    const MAX_RULE_COUNT = 50;
    const SETTINGS_FIELDS = [
        'event_messages_disable_default',
        'event_messages_sender',
        'event_messages_reply_to',
        'event_messages_cc',
        'event_messages_bcc',
        'event_messages_execute_all_rules',
        'event_messages_custom_data_workaround'
    ];

    /**
     * Set variables up before form is built.
     */
    public function preProcess()
    {
        parent::preProcess();
        $this->setSelectedChild('eventmessages');
    }

    public function buildQuickForm()
    {
        // add settings fields
        $this->add(
            'checkbox',
            'event_messages_disable_default',
            E::ts("Disable CiviCRM Default Messages")
        );
        $this->add(
            'select',
            "event_messages_sender",
            E::ts("Send From"),
            $this->getSenderOptions(),
            true,
            ['class' => 'huge crm-select2', 'placeholder' => E::ts("-select-")]
        );
        $this->add(
            'text',
            "event_messages_reply_to",
            E::ts("Reply To"),
            ['class' => 'huge'],
            false
        );
        $this->add(
            'text',
            "event_messages_cc",
            E::ts("CC"),
            ['class' => 'huge'],
            false
        );
        $this->add(
            'text',
            "event_messages_bcc",
            E::ts("BCC"),
            ['class' => 'huge'],
            false
        );
        $this->add(
            'checkbox',
            'event_messages_execute_all_rules',
            E::ts("Execute All Matching Rules?")
        );
        $this->add(
            'checkbox',
            'event_messages_custom_data_workaround',
            E::ts("Custom Data Workaround")
        );

        // set defaults for these fields
        $return_fields = [];
        foreach (self::SETTINGS_FIELDS as $field_name) {
            $return_fields[] = CRM_Eventmessages_CustomData::getCustomFieldKey('event_messages_settings', $field_name);
        }
        $event_defaults = civicrm_api3('Event', 'getsingle', [
            'id'     => $this->_id,
            'return' => implode(',', $return_fields)
        ]);
        foreach (self::SETTINGS_FIELDS as $field_name) {
            $field_key = CRM_Eventmessages_CustomData::getCustomFieldKey('event_messages_settings', $field_name);
            $this->setDefaults([
                $field_name => CRM_Utils_Array::value($field_key, $event_defaults)
            ]);
        }

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
                E::ts("Active?")
            );
            $this->add(
                'select',
                "from_{$index}",
                E::ts("From Status"),
                $status_list,
                false,
                ['class' => 'huge crm-select2', 'multiple' => 'multiple', 'placeholder' => 'any']
            );
            $this->add(
                'select',
                "to_{$index}",
                E::ts("To Status"),
                $status_list,
                false,
                ['class' => 'huge crm-select2', 'multiple' => 'multiple', 'placeholder' => 'any']
            );
            $this->add(
                'select',
                "languages_{$index}",
                E::ts("Preferred Language"),
                $languages_list,
                false,
                ['class' => 'huge crm-select2', 'multiple' => 'multiple', 'placeholder' => 'any']
            );
            $this->add(
                'select',
                "roles_{$index}",
                E::ts("Roles"),
                $roles_list,
                false,
                ['class' => 'huge crm-select2', 'multiple' => 'multiple', 'placeholder' => 'any']
            );
            $this->add(
                'select',
                "template_{$index}",
                E::ts("Message Template"),
                $template_list,
                false,
                ['class' => 'huge crm-select2']
            );

            // Add attachment elements.
            if (class_exists('\Civi\Mailattachment\Form\Attachments')) {
                \Civi\Mailattachment\Form\Attachments::addAttachmentElements(
                    $this,
                    [
                        'entity_type' => 'participant',
                        'prefix' => $index . '--',
                        'defaults' => $rule['attachments'] ?? []
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
                    'isDefault' => true,
                ],
            ]
        );

        Civi::resources()->addStyleUrl(E::url('css/eventmessages_form.css'));
        parent::buildQuickForm();
    }

    public function validate()
    {
        parent::validate();
        // TODO: validation rules?
        return (0 == count($this->_errors));
    }

    public function postProcess()
    {
        $values = $this->exportValues();

        // store the settings
        $event_update = [
            'id'          => $this->_id,
            'is_template' => 0
        ];

        // set template flag, since it will otherwise reset it
        try {
            $event_update['is_template'] = (int) civicrm_api3('Event', 'getvalue', [
                'return' => 'is_template',
                'id'     => $this->_id
            ]);
        } catch (CiviCRM_API3_Exception $ex) {
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
                'id'        => CRM_Utils_Array::value("id_{$i}", $values, null),
                'is_active' => (int) CRM_Utils_Array::value("is_active_{$i}", $values, false),
                'from'      => CRM_Utils_Array::value("from_{$i}", $values, []),
                'to'        => CRM_Utils_Array::value("to_{$i}", $values, []),
                'languages' => CRM_Utils_Array::value("languages_{$i}", $values, []),
                'roles'     => CRM_Utils_Array::value("roles_{$i}", $values, []),
                'template'  => CRM_Utils_Array::value("template_{$i}", $values, null),
                'weight'    => (10 + count($rules) * 10),
            ];
            if (class_exists('\Civi\Mailattachment\Form\Attachments')) {
                $rule['attachments'] = \Civi\Mailattachment\Form\Attachments::processAttachments($this, ['prefix' => $i . '--']);
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
        $from_email_addresses = CRM_Core_OptionGroup::values('from_email_address');
        foreach ($from_email_addresses as $key => $from_email_address) {
            $dropdown_list[$key] = htmlentities($from_email_address);
        }
        return $dropdown_list;
    }

    /**
     * Get a list of the available participant statuses
     */
    protected function getMessageTemplateList() {
        $list = ['' => E::ts("-mandatory-")];
        $query = civicrm_api3(
            'MessageTemplate',
            'get',
            [
                'option.limit' => 0,
                'is_default'   => 1, // otherwise it won't be sent by MessageTemplate.send
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
    protected function getLanguagesList() {
        $list = [];
        $query = civicrm_api3(
            'OptionValue',
            'get',
            [
                'option_group_id' => 'languages',
                'option.limit'    => 0,
                'return'          => 'name,label',
            ]
        );
        foreach ($query['values'] as $language) {
            $list[$language['name']] = $language['label'];
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
    public static function addToTabs($event_id, &$tabs)
    {
        // then add new registration tab
        if ($event_id) {
            $tabs['eventmessages'] = [
                'title'   => E::ts("Communication"),
                'link'    => CRM_Utils_System::url(
                    'civicrm/event/manage/eventmessages',
                    "action=update&reset=1&id={$event_id}"
                ),
                'valid'   => 1,
                'active'  => 1,
                'current' => false,
            ];
        } else {
            $tabs['eventmessages'] = [
                'title' => E::ts("Communication"),
                'url'   => 'civicrm/event/manage/eventmessages',
                'field' => 'id',
            ];
        }
    }
}
