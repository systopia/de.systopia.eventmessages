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
use Civi\EventMessages\Language\LanguageMatcher;
use CRM_Eventmessages_ExtensionUtil as E;
use Civi\RemoteEvent\Event\GetResultEvent as GetResultEvent;
use Civi\EventMessages\MessageTokens as MessageTokens;

/**
 * Basic Logic for the event messages
 */
class CRM_Eventmessages_Logic {

  /**
   * @phpstan-var array<int, array{status_id: int, force_execution: bool}>
   *     Every new and edited participant is added to this array before post
   *     commit with its participant ID as key.
   */
  protected static array $records = [];

  /**
   * Record a participant status before a change
   */
  public static function recordPreEdit(int $participant_id, array $participant_data): void {
    $force_execution = !empty($participant_data['force_trigger_eventmessage']);
    if (!isset(self::$records[$participant_id])) {
      // First participant change in transaction.
      $status_id = (int) CRM_Core_DAO::singleValueQuery(
            "SELECT status_id FROM civicrm_participant WHERE id = {$participant_id}"
      );
      self::$records[$participant_id] = [
        'status_id' => $status_id,
        'force_execution' => $force_execution,
      ];
    }
    elseif ($force_execution) {
      // Further participant change during a transaction.
      self::$records[$participant_id]['force_execution'] = TRUE;
    }
  }

  /**
   * Add new participant to records which are handled after commit
   */
  public static function recordPostCreate(int $participant_id): void {
    // New participant is inserted and has got an ID.
    self::$records[$participant_id] = [
      'status_id' => 0,
      'force_execution' => FALSE,
    ];
  }

  /**
   * Trigger any matching rules after commit
   */
  public static function recordPostCommit(int $participant_id, CRM_Event_BAO_Participant $participant_object): void {
    $record = self::$records[$participant_id] ?? NULL;
    if (NULL !== $record) {
      unset(self::$records[$participant_id]);
      $old_status_id = $record['status_id'];
      if (isset($participant_object->status_id)) {
        $new_status_id = (int) $participant_object->status_id;
      }
      else {
        $new_status_id = (int) CRM_Core_DAO::singleValueQuery(
        "SELECT status_id FROM civicrm_participant WHERE id = {$participant_id}"
          );
      }

      // check if there is a change or execution is enforced
      if (($old_status_id != $new_status_id) || $record['force_execution']) {
        CRM_Eventmessages_Logic::processStatusChange(
        (int) $participant_object->event_id,
        $old_status_id,
        $new_status_id,
        $participant_id
        );
      }
    }
    else {
      Civi::log()->debug('EventMessages: inconsistent pre/post hooks.');
    }
  }

  /**
   * Get all active rules currently stored for the given event
   * Caution: this result is cached
   *
   * @param integer $event_id
   *   the event this refers to
   *
   * @return array
   *   rules in the format [
   *    'id'        => rule id (custom_value ID)
   *    'is_active' => true
   *    'from'      => array of status_ids
   *    'to'        => array of status_ids
   *    'languages' => array of languages
   *    'roles'     => array of roles
   *    'template'  => Message Template ID
   *    'attachments' => array of attachments
   *   ]
   */
  public static function getActiveRules($event_id) {
    $event_id = (int) $event_id;
    static $rules_cache = [];
    if (!isset($rules_cache[$event_id])) {
      $rules = [];
      $query = CRM_Core_DAO::executeQuery(
        <<<SQL
        SELECT
          id          AS rule_id,
          from_status AS from_status,
          to_status   AS to_status,
          languages   AS languages,
          roles       AS roles,
          template_id AS template_id,
          attachments AS attachments
        FROM civicrm_event_message_rules
        WHERE event_id = {$event_id}
          AND is_active = 1
        ORDER BY weight ASC;
        SQL
      );
      while ($query->fetch()) {
        $rules[] = [
          'id' => $query->rule_id,
          'is_active' => TRUE,
          'from' => empty($query->from_status) ? [] : explode(',', $query->from_status),
          'to' => empty($query->to_status) ? [] : explode(',', $query->to_status),
          'languages' => empty($query->languages) ? [] : explode(',', $query->languages),
          'roles' => empty($query->roles) ? [] : explode(',', $query->roles),
          'template' => $query->template_id,
          'attachments' => empty($query->attachments) ? [] : json_decode($query->attachments, TRUE),
        ];
      }
      $rules_cache[$event_id] = $rules;
    }
    return $rules_cache[$event_id];
  }

  /**
   * Get all rules currently stored for the given event
   *
   * @param integer $event_id
   *   the event this refers to
   *
   * @return array
   *   rules in the format [
   *    'id'        => rule id (custom_value ID)
   *    'is_active' => true/false
   *    'from'      => array of status_ids
   *    'to'        => array of status_ids
   *    'template'  => Message Template ID
   *   ]
   */
  public static function getAllRules($event_id) {
    $rules = [];
    $query = CRM_Core_DAO::executeQuery(
      <<<SQL
      SELECT
        id          AS rule_id,
        is_active   AS is_active,
        from_status AS from_status,
        to_status   AS to_status,
        languages   AS languages,
        roles       AS roles,
        template_id AS template_id,
        attachments AS attachments
      FROM civicrm_event_message_rules
      WHERE event_id = {$event_id}
      ORDER BY weight ASC;
      SQL
    );
    while ($query->fetch()) {
      $rules[] = [
        'id' => $query->rule_id,
        'is_active' => (int) $query->is_active,
        'from' => empty($query->from_status) ? [] : explode(',', $query->from_status),
        'to' => empty($query->to_status) ? [] : explode(',', $query->to_status),
        'languages' => empty($query->languages) ? [] : explode(',', $query->languages),
        'roles' => empty($query->roles) ? [] : explode(',', $query->roles),
        'template' => $query->template_id,
        'attachments' => empty($query->attachments) ? [] : json_decode($query->attachments, TRUE),
      ];
    }
    return $rules;
  }

  /**
   * Update the current rules stored with the event based
   *   on the set given. That means, that the ones with an
   *   'id' need to be updated, the ones without added,
   *   and the ones missing deleted.
   *
   * @param integer $event_id
   *   event ID
   * @param array $rules
   *   list of rules, see ::getAllRules
   */
  public static function syncRules($event_id, $rules) {
    // first: get all current rules by ID
    $current_rules = [];
    foreach (self::getAllRules($event_id) as $current_rule) {
      $current_rules[$current_rule['id']] = $current_rule;
    }

    // now process the new rules
    $weight = 0;
    foreach ($rules as $new_rule) {
      $weight += 10;
      if (empty($new_rule['id'])) {
        // this is a new rule -> insert
        CRM_Core_DAO::executeQuery(
          <<<SQL
          INSERT INTO civicrm_event_message_rules(
            event_id,
            from_status,
            to_status,
            languages,
            roles,
            is_active,
            template_id,
            weight,
            attachments
          )
          VALUES (%1, %2, %3, %4, %5, %6, %7, %8, %9);
          SQL,
        [
          1 => [$event_id, 'Integer'],
          2 => [implode(',', $new_rule['from']), 'String'],
          3 => [implode(',', $new_rule['to']), 'String'],
          4 => [implode(',', $new_rule['languages']), 'String'],
          5 => [implode(',', $new_rule['roles']), 'String'],
          6 => [empty($new_rule['is_active']) ? 0 : 1, 'Integer'],
          7 => [$new_rule['template'], 'Integer'],
          8 => [$weight, 'Integer'],
          9 => [json_encode($new_rule['attachments']), 'String'],
        ]
        );
      }
      else {
        // this is an update
        CRM_Core_DAO::executeQuery(
          <<<SQL
          UPDATE civicrm_event_message_rules
          SET
            from_status = %2,
            to_status = %3,
            is_active = %4,
            template_id = %5,
            languages = %6,
            roles = %7,
            weight = %8,
            attachments = %9
          WHERE id = %1;
          SQL,
        [
          1 => [$new_rule['id'], 'Integer'],
          2 => [implode(',', $new_rule['from']), 'String'],
          3 => [implode(',', $new_rule['to']), 'String'],
          4 => [empty($new_rule['is_active']) ? 0 : 1, 'Integer'],
          5 => [$new_rule['template'], 'Integer'],
          6 => [implode(',', $new_rule['languages']), 'String'],
          7 => [implode(',', $new_rule['roles']), 'String'],
          8 => [$weight, 'Integer'],
          9 => [json_encode($new_rule['attachments']), 'String'],
        ]
          );
        // remove from list
        unset($current_rules[$new_rule['id']]);
      }
    }

    // finally: delete all remaining rules
    foreach ($current_rules as $rule_to_delete) {
      CRM_Core_DAO::executeQuery(
        'DELETE FROM civicrm_event_message_rules WHERE id = %1',
        [1 => [$rule_to_delete['id'], 'String']]
      );
    }
  }

  /**
   * This function processes a detected participant status change
   *
   * @param integer $event_id
   *      the related event
   * @param integer $from_status_id
   *      participant status before the change
   * @param integer $to_status_id
   *      participant status after the change
   * @param integer $participant_id
   *      participant ID
   */
  public static function processStatusChange($event_id, $from_status_id, $to_status_id, $participant_id) {
    $event = Event::get(FALSE)
      ->setSelect(['*', 'event_messages_settings.*'])
      ->addWhere('id', '=', $event_id)
      ->execute()
      ->single();
    $rules = self::getActiveRules($event_id);
    $multi_match = $event['event_messages_settings.event_messages_execute_all_rules'] ?? FALSE;
    foreach ($rules as $rule) {
      if (in_array($from_status_id, $rule['from']) || empty($rule['from'])) {
        // 'from' status matches!
        if (in_array($to_status_id, $rule['to']) || empty($rule['to'])) {
          // 'to' status matches, too!
          if (self::participantMatchesLanguageAndRole($event, $rule, $participant_id)) {
            // everything checks out, go for it!
            CRM_Eventmessages_SendMail::sendMessageTo(
            [
              'participant_id' => $participant_id,
              'event_id' => $event_id,
              'from' => $from_status_id,
              'to' => $to_status_id,
              'rule' => $rule,
            ]
            );
            if (!$multi_match) {
              break;
            }
          }
        }
      }
    }
  }

  /**
   * Check if the participant matches the language and role rules as well
   *
   * @param array $event
   *     the event
   * @param array $rule
   *   rule data
   * @param integer $participant_id
   *    the participant
   *
   * @return bool
   *   true, if the contact matches the rule's language and role filters
   */
  public static function participantMatchesLanguageAndRole(array $event, array $rule, int $participant_id) {
    if (!empty($rule['roles'])) {
      // we'll have to check the roles
      $participant_roles = self::getParticipantRoles($participant_id);
      if (empty(array_intersect($participant_roles, $rule['roles']))) {
        return FALSE;
      }
    }

    if (!empty($rule['languages'])) {
      // we'll have to check the language
      /** @var \Civi\EventMessages\Language\LanguageMatcher $language_matcher */
      $language_matcher = \Civi::service(LanguageMatcher::class);
      if (!$language_matcher->match($rule['languages'], $event, $participant_id)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Get the participant's roles
   * Caution: cached
   *
   * @param integer $participant_id
   *
   * @return array
   *   roles
   */
  protected static function getParticipantRoles($participant_id) {
    $participant_id = (int) $participant_id;
    static $roles_cache = [];
    if (!isset($roles_cache[$participant_id])) {
      $roles = CRM_Core_DAO::singleValueQuery(
        "
             SELECT role_id
             FROM civicrm_participant
             WHERE id = {$participant_id}"
      );
      if (empty($roles)) {
        $roles_cache[$participant_id] = [];
      }
      else {
        $roles_cache[$participant_id] = CRM_Utils_Array::explodePadded($roles);
      }
    }
    return $roles_cache[$participant_id];
  }

  /**
   * Copy all rules from one table
   *
   * @param integer $source_event_id
   *   the source event, from which the rules are copied
   * @param integer $target_event_id
   *   the target event, where the rules are copied to
   */
  public static function copyRules($source_event_id, $target_event_id) {
    $source_event_id = (int) $source_event_id;
    $target_event_id = (int) $target_event_id;
    if ($source_event_id && $target_event_id) {
      CRM_Core_DAO::executeQuery(
        <<<SQL
        INSERT INTO civicrm_event_message_rules(
          event_id,
          from_status,
          to_status,
          languages,
          roles,
          is_active,
          template_id,
          weight,
          attachments
        )
        SELECT * FROM
          (SELECT
            {$target_event_id} AS event_id,
            from_status        AS from_status,
            to_status          AS to_status,
            languages          AS languages,
            roles              AS roles,
            is_active          AS is_active,
            template_id        AS template_id,
            weight             AS weight,
            attachments        AS attachments
          FROM civicrm_event_message_rules
          WHERE event_id = {$source_event_id}) tmp_table
        SQL
      );
    }
  }

  /**
   * Copy all settings from on event to the other
   *
   * @param integer $source_event_id
   *   the source event, from which the rules are copied
   * @param integer $target_event_id
   *   the target event, where the rules are copied to
   */
  public static function copySettings($source_event_id, $target_event_id) {
    $source_event_id = (int) $source_event_id;
    $target_event_id = (int) $target_event_id;
    if ($source_event_id && $target_event_id) {
      // load current event settings
      $return_fields = [];
      foreach (CRM_Eventmessages_Form_EventMessages::SETTINGS_FIELDS as $field_name) {
        $return_fields[] = CRM_Eventmessages_CustomData::getCustomFieldKey('event_messages_settings', $field_name);
      }
      $current_event_settings = civicrm_api3('Event', 'getsingle', [
        'id'     => $source_event_id,
        'return' => implode(',', $return_fields),
      ]);

      // set for the now event
      $new_event_settings = $current_event_settings;
      $new_event_settings['id'] = $target_event_id;
      civicrm_api3('Event', 'create', $new_event_settings);
    }
  }

  /**
   * Strip the event data from RemoteEvent.get calls
   *
   * @param \Civi\RemoteEvent\Event\GetResultEvent $result
   */
  public static function stripEventMessageData(GetResultEvent $result) {
    $events = &$result->getEventData();
    foreach ($events as &$event) {
      foreach (array_keys($event) as $key) {
        if (substr($key, 0, 23) == 'event_messages_settings') {
          unset($event[$key]);
        }
      }
    }
  }

  /**
   * Generate and fill a token event
   *
   * @param integer $participant_id
   *   the participant
   *
   * @param integer $contact_id
   *   the contact id. will be derived if not given
   *
   * @param array $event_data
   *   the event information. will be derived if not given
   */
  public static function generateTokenEvent($participant_id, $contact_id = NULL, $event_data = NULL) {
    $participant = civicrm_api3('Participant', 'getsingle', ['id' => $participant_id]);
    CRM_Eventmessages_SendMail::applyCustonFieldSubmissionWorkaroundForParticipant($participant_id, $participant);
    CRM_Eventmessages_CustomData::labelCustomFields($participant, 1, '__');
    // a small extension for the tokens
    $participant['participant_roles'] = is_array($participant['participant_role']) ?
            $participant['participant_role'] : [$participant['participant_role']];
    $participant['participant_role_ids'] = is_array($participant['participant_role_id']) ?
            $participant['participant_role_id'] : [$participant['participant_role_id']];

    // derive contact ID if not given
    if (empty($contact_id)) {
      $contact_id = $participant['contact_id'];
    }

    // load contact
    $contact = civicrm_api3('Contact', 'getsingle', ['id' => $contact_id]);
    // add custom fields (not included)
    $custom_fields = civicrm_api3('CustomValue', 'get', [
      'entity_id'    => $contact_id,
      'entity_table' => 'civicrm_contact',
      'option.limit' => 0,
    ]);
    foreach ($custom_fields['values'] as $custom_field) {
      $contact["custom_{$custom_field['id']}"] = (string) \CRM_Core_BAO_CustomField::displayValue(
        $custom_field['latest'],
        $custom_field['id'],
        $contact_id
      );
    }
    // Add checksum
    $contact['checksum'] = \CRM_Contact_BAO_Contact_Utils::generateChecksum($contact_id);

    CRM_Eventmessages_CustomData::labelCustomFields($contact, 1, '__');

    // load event
    if (empty($event_data)) {
      $event_data = civicrm_api3('Event', 'getsingle', ['id' => $participant['event_id']]);
      CRM_Eventmessages_CustomData::labelCustomFields($event_data, 1, '__');
    }

    // generate token collection event
    $message_tokens = new MessageTokens();
    $message_tokens->setToken('event', $event_data);
    $message_tokens->setToken('participant', $participant);
    $message_tokens->setToken('contact', $contact);

    return $message_tokens;
  }

}
