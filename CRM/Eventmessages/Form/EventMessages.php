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
    const MAX_RULE_COUNT = 20;

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
        // add basic fields
        $this->add(
            'checkbox',
            'eventmessages_disable_default',
            E::ts("Disable CiviCRM Default Messages")
        );
        $this->setDefaults([
            'eventmessages_disable_default' => Civi::settings()->get('eventmessages_disable_default')
       ]);

        // add message definition lines
        $this->assign('rules_list', range(1, self::MAX_RULE_COUNT));
        $status_list = $this->getParticipantStatusList();
        $template_list = $this->getMessageTemplateList();
        foreach (range(1, self::MAX_RULE_COUNT) as $index) {
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
                "template_{$index}",
                E::ts("Message Template"),
                $template_list,
                false,
                ['class' => 'huge crm-select2']
            );
        }

        // set current rule data
        $rules = CRM_Eventmessages_Logic::getAllRules($this->_id);
        foreach ($rules as $index => $rule) {
            $i = $index + 1;
            $this->setDefaults([
               "id_{$i}"        => $rule['id'],
               "is_active_{$i}" => $rule['is_active'],
               "from_{$i}"      => $rule['from'],
               "to_{$i}"        => $rule['to'],
               "template_{$i}"  => $rule['template'],
           ]);
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
        Civi::settings()->set('eventmessages_disable_default', CRM_Utils_Array::value('eventmessages_disable_default', $values, false));

        // extract rules
        $rules = [];
        foreach (range(1, self::MAX_RULE_COUNT) as $i) {
            $rule = [
                'id'        => CRM_Utils_Array::value("id_{$i}", $values, null),
                'is_active' => CRM_Utils_Array::value("is_active_{$i}", $values, false),
                'from'      => CRM_Utils_Array::value("from_{$i}", $values, []),
                'to'        => CRM_Utils_Array::value("to_{$i}", $values, []),
                'template'  => CRM_Utils_Array::value("template_{$i}", $values, null),
            ];
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
     * Get a list of the available participant statuses
     */
    protected function getMessageTemplateList() {
        $list = ['' => E::ts("-please select-")];
        $query = civicrm_api3(
            'MessageTemplate',
            'get',
            [
                'option.limit' => 0,
                'return'       => 'id,msg_title',
            ]
        );
        foreach ($query['values'] as $status) {
            $list[$status['id']] = $status['msg_title'];
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
                'title'   => E::ts("Event Communication"),
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
                'title' => E::ts("Event Communication"),
                'url'   => 'civicrm/event/manage/eventmessages',
            ];
        }
    }
}
