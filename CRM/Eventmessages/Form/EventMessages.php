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

        // TODO: add message definition lines


        parent::buildQuickForm();
    }

    public function validate()
    {
        parent::validate();
        // TODO
        return (0 == count($this->_errors));
    }

    public function postProcess()
    {
        $values = $this->exportValues();

        // store the settings
        Civi::settings()->set('eventmessages_disable_default', CRM_Utils_Array::value('eventmessages_disable_default', $values, false));

        // copy the data
        // TODO:

        parent::endPostProcess();
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
