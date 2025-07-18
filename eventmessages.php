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

// phpcs:disable PSR1.Files.SideEffects
require_once 'eventmessages.civix.php';
// phpcs:enable

use CRM_Eventmessages_ExtensionUtil as E;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Resource\GlobResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function eventmessages_civicrm_config(&$config) {
  _eventmessages_civix_civicrm_config($config);

  // uncomment the next line to get stack traces of core mails still sent
  // define('EVENTMESSAGES_LOG_SYSTEM_EMAILS', 1);

  // REMOTEEVENT.GET filters
  Civi::dispatcher()->addListener(
        'civi.remoteevent.get.result',
        ['CRM_Eventmessages_Logic', 'stripEventMessageData']
    );

  if (interface_exists('\Civi\Mailattachment\AttachmentType\AttachmentTypeInterface')
         && class_exists('\Civi\EventMessages\AttachmentProvider')) {
    \Civi::dispatcher()->addSubscriber(new \Civi\EventMessages\AttachmentProvider());
  }
}

/**
 * Implements hook_civicrm_container().
 */
function eventmessages_civicrm_container(ContainerBuilder $container): void {
  $globResource = new GlobResource(__DIR__ . '/services', '/*.php', FALSE);
  // Container will be rebuilt if a *.php file is added to services
  $container->addResource($globResource);
  foreach ($globResource->getIterator() as $path => $info) {
    // Container will be rebuilt if file changes
    $container->addResource(new FileResource($path));
    require $path;
  }

  if (function_exists('_eventmessages_test_civicrm_container')) {
    // Allow to use different services in tests.
    _eventmessages_test_civicrm_container($container);
  }
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function eventmessages_civicrm_install() {
  _eventmessages_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function eventmessages_civicrm_enable() {
  _eventmessages_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_tabset().
 */
function eventmessages_civicrm_tabset($tabsetName, &$tabs, $context) {
  // Add event configuration tabs
  if ($tabsetName == 'civicrm/event/manage') {
    if (!empty($context['event_id'])) {
      CRM_Eventmessages_Form_EventMessages::addToTabs($context['event_id'], $tabs);
    }
    else {
      CRM_Eventmessages_Form_EventMessages::addToTabs(NULL, $tabs);
    }
  }
}

/**
 * Implements hook_civicrm_pre().
 */
function eventmessages_civicrm_pre(string $op, string $objectName, $id, array &$params): void {
  // Monitor Participant edits
  if ($op === 'edit' && $objectName === 'Participant') {
    CRM_Eventmessages_Logic::recordPreEdit((int) $id, $params);
  }
}

/**
 * Implements hook_civicrm_post().
 */
function eventmessages_civicrm_post(string $op, string $objectName, int $objectId, &$objectRef): void {
  // Monitor Participant creations
  if ($op === 'create' && $objectName === 'Participant') {
    CRM_Eventmessages_Logic::recordPostCreate($objectId);
  }
}

/**
 * Implements hook_civicrm_postCommit().
 */
function eventmessages_civicrm_postCommit(string $op, string $objectName, int $objectId, &$objectRef): void {
  // Monitor Participant post commit
  if (($op === 'edit' || $op === 'create') && $objectName === 'Participant') {
    CRM_Eventmessages_Logic::recordPostCommit($objectId, $objectRef);
  }
}

/**
 * Implements hook_civicrm_alterMailer().
 */
function eventmessages_civicrm_alterMailer(&$mailer, $driver, $params) {
  // Replace the normal mailer with our custom mailer
  CRM_Eventmessages_SendMail::suppressSystemMails($mailer, $driver, $params);
}

/**
 * Implements hook_civicrm_buildForm().
 */
function eventmessages_civicrm_buildForm($formName, &$form) {
  // Inject some UI modifications into selected forms
  if ($form instanceof CRM_Event_Form_Participant) {
    $disabled_field = CRM_Eventmessages_CustomData::getCustomFieldKey(
        'event_messages_settings',
        'event_messages_disable_default'
    );
    CRM_Core_Resources::singleton()->addVars('eventmessages', ['suppression_field' => $disabled_field]);
    CRM_Core_Resources::singleton()->addScriptUrl(E::url('js/event_form_new_participant_mods.js'));
  }
}

/**
 * Implements hook_civicrm_searchTasks().
 */
function eventmessages_civicrm_searchTasks($objectType, &$tasks) {
  // add "Send E-Mail" task to participant list
  if ($objectType == 'event') {
    $tasks[] = [
      'title' => E::ts('Send Emails (via EventMessages)'),
      'class' => 'CRM_Eventmessages_Form_Task_ParticipantEmail',
      'result' => FALSE,
    ];

    $tasks[] = [
      'title' => E::ts('Generate Letters (via EventMessages)'),
      'class' => 'CRM_Eventmessages_Form_Task_ParticipantLetter',
      'result' => FALSE,
    ];
  }
}

/**
 * Implements hook_civicrm_copy().
 */
function eventmessages_civicrm_copy($objectName, &$object) {
  // Inject some UI modifications into selected forms
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
          CRM_Eventmessages_Logic::copySettings($original_event_id, $new_event_id);
        }
      }
    }
  }
}
