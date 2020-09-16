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

require_once 'eventmessages.civix.php';

use CRM_Eventmessages_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function eventmessages_civicrm_config(&$config)
{
    _eventmessages_civix_civicrm_config($config);

    // REMOTEEVENT.GET filters
    Civi::dispatcher()->addListener(
        'civi.remoteevent.get.result',
        ['CRM_Eventmessages_Logic', 'stripEventMessageData']
    );
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function eventmessages_civicrm_xmlMenu(&$files)
{
    _eventmessages_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function eventmessages_civicrm_install()
{
    _eventmessages_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function eventmessages_civicrm_postInstall()
{
    _eventmessages_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function eventmessages_civicrm_uninstall()
{
    _eventmessages_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function eventmessages_civicrm_enable()
{
    _eventmessages_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function eventmessages_civicrm_disable()
{
    _eventmessages_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function eventmessages_civicrm_upgrade($op, CRM_Queue_Queue $queue = null)
{
    return _eventmessages_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function eventmessages_civicrm_managed(&$entities)
{
    _eventmessages_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function eventmessages_civicrm_caseTypes(&$caseTypes)
{
    _eventmessages_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function eventmessages_civicrm_angularModules(&$angularModules)
{
    _eventmessages_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function eventmessages_civicrm_alterSettingsFolders(&$metaDataFolders = null)
{
    _eventmessages_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function eventmessages_civicrm_entityTypes(&$entityTypes)
{
    _eventmessages_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_thems().
 */
function eventmessages_civicrm_themes(&$themes)
{
    _eventmessages_civix_civicrm_themes($themes);
}

/**
 * Add event configuration tabs
 */
function eventmessages_civicrm_tabset($tabsetName, &$tabs, $context)
{
    if ($tabsetName == 'civicrm/event/manage') {
        if (!empty($context['event_id'])) {
            CRM_Eventmessages_Form_EventMessages::addToTabs($context['event_id'], $tabs);
        } else {
            CRM_Eventmessages_Form_EventMessages::addToTabs(null, $tabs);
        }
    }
}

/**
 * Monitor Participant objects
 */
function eventmessages_civicrm_pre($op, $objectName, $id, &$params)
{
    if (($op == 'edit' || $op == 'create') && $objectName == 'Participant') {
        CRM_Eventmessages_Logic::recordPre($id, $params);
    }
}

/**
 * Monitor Participant objects
 */
function eventmessages_civicrm_post($op, $objectName, $objectId, &$objectRef)
{
    if (($op == 'edit' || $op == 'create') && $objectName == 'Participant') {
        CRM_Eventmessages_Logic::recordPost($objectId, $objectRef);
    }
}

/**
 * Implementation of hook_civicrm_alterMailer
 *
 * Replace the normal mailer with our custom mailer
 */
function eventmessages_civicrm_alterMailer(&$mailer, $driver, $params)
{
    CRM_Eventmessages_SendMail::suppressSystemMails($mailer);
}

/**
 * Implementation of hook_civicrm_buildForm
 *
 *   inject some UI modifications into selected forms
 */
function eventmessages_civicrm_buildForm($formName, &$form)
{
    if ($formName == 'CRM_Event_Form_Participant') {
        //Civi::log()->debug("EventMessages: injecting 'event_form_new_participant_mods.js'");
        $disabled_field = CRM_Eventmessages_CustomData::getCustomFieldKey(
            'event_messages_settings',
            'event_messages_disable_default'
        );
        CRM_Core_Resources::singleton()->addVars('eventmessages', ['suppression_field' => $disabled_field]);
        CRM_Core_Resources::singleton()->addScriptUrl(E::url('js/event_form_new_participant_mods.js'));
    }
}

/**
 * Implementation of hook_civicrm_copy
 *
 *   inject some UI modifications into selected forms
 */
function eventmessages_civicrm_copy($objectName, &$object)
{
    if ($objectName == 'Event') {
        // we have the new event ID...
        $new_event_id = $object->id;

        // ...unfortunately, we have to dig up the original event ID:
        $callstack = debug_backtrace();
        foreach ($callstack as $call) {
            if (isset($call['class']) && isset($call['function'])) {
                if ($call['class'] == 'CRM_Event_BAO_Event' && $call['function'] == 'copy') {
                    // this should be it:
                    $original_event_id = $call['args'][0];
                    CRM_Eventmessages_Logic::copyRules($original_event_id, $new_event_id);
                }
            }
        }
    }
}
