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
 * Basic Logic for the event messages
 */
class CRM_Eventmessages_Logic {

    /** @var array stack of [participant_id, status_id] tuples */
    protected static $record_stack = [];

    /**
     * Record a participant status before a change
     *
     * @param integer $participant_id
     * @param array $participant_data
     */
    public static function recordPre($participant_id, $participant_data)
    {
        if (empty($participant_id)) {
            // this is a new contact
            array_push(self::$record_stack, [0, 0]);
        } else {
            $participant_id = (int) $participant_id;
            $status_id = CRM_Core_DAO::singleValueQuery("SELECT status_id FROM civicrm_participant WHERE id = {$participant_id}");
            array_push(self::$record_stack, [$participant_id, $status_id]);
        }
    }

    /**
     * Record a participant status after a change, and trigger any matching rules
     *
     * @param integer $participant_id
     * @param CRM_Event_BAO_Participant $participant_object
     */
    public static function recordPost($participant_id, $participant_object)
    {
        $record = array_pop(self::$record_stack);
        if (empty($record[0]) || $record[0] == $participant_id) {
            $old_status_id = $record[1];
            if (isset($participant_object->status_id)) {
                $new_status_id = $participant_object->status_id;
            } else {
                $new_status_id = CRM_Core_DAO::singleValueQuery("SELECT status_id FROM civicrm_participant WHERE id = {$participant_id}");
            }

            // check if there is a change
            if ($old_status_id <> $new_status_id) {
                CRM_Eventmessages_Logic::processStatusChange($participant_object->event_id, $old_status_id, $new_status_id, $participant_id);
            }
        } else {
            Civi::log()->debug("EventMessages: inconsistent pre/post hooks.");
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
     *    'template'  => Message Template ID
     * ]
     */
    public static function getActiveRules($event_id) {
        $event_id = (int) $event_id;
        static $rules_cache = [];
        if (!isset($rules_cache[$event_id])) {
            $rules = [];
            $query = CRM_Core_DAO::executeQuery("
                SELECT
                  id          AS rule_id,
                  from_status AS from_status,
                  to_status   AS to_status,
                  template_id AS template_id
                FROM civicrm_value_event_messages
                WHERE entity_id = {$event_id}
                  AND is_active = 1");
            while ($query->fetch()) {
                $rules[] = [
                    'id'        => $query->rule_id,
                    'is_active' => true,
                    'from'      => empty($query->from_status) ? [] : explode(',', $query->from_status),
                    'to'        => empty($query->to_status)   ? [] : explode(',', $query->to_status),
                    'template'  => $query->template_id,
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
     * ]
     */
    public static function getAllRules($event_id) {
        $rules = [];
        $query = CRM_Core_DAO::executeQuery("
                SELECT
                  id          AS rule_id,
                  from_status AS from_status,
                  to_status   AS to_status,
                  is_active   AS is_active,
                  template_id AS template_id
                FROM civicrm_value_event_messages
                WHERE entity_id = {$event_id}");
        while ($query->fetch()) {
            $rules[] = [
                'id'        => $query->rule_id,
                'is_active' => $query->is_active,
                'from'      => empty($query->from_status) ? [] : explode(',', $query->from_status),
                'to'        => empty($query->to_status)   ? [] : explode(',', $query->to_status),
                'template'  => $query->template_id,
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
        foreach ($rules as $new_rule) {
            if (empty($new_rule['id'])) {
                // this is a new rule -> insert
                CRM_Core_DAO::executeQuery("
                INSERT INTO civicrm_value_event_messages(entity_id,from_status,to_status,is_active,template_id)
                VALUES (%1, %2, %3, %4, %5);
                ", [
                    1 => [$event_id, 'Integer'],
                    2 => [implode(',', $new_rule['from']), 'String'],
                    3 => [implode(',', $new_rule['to']), 'String'],
                    4 => [empty($new_rule['active']) ? 0 : 1, 'Integer'],
                    5 => [$new_rule['template'], 'Integer'],
                ]);
            } else {
                // this is an update
                CRM_Core_DAO::executeQuery("
                    UPDATE civicrm_value_event_messages
                    SET from_status = %2, to_status = %3, is_active = %4, template_id = %5
                    WHERE id = %1;", [
                    1 => [$new_rule['id'], 'Integer'],
                    2 => [implode(',', $new_rule['from']), 'String'],
                    3 => [implode(',', $new_rule['to']), 'String'],
                    4 => [empty($new_rule['is_active']) ? 0 : 1, 'Integer'],
                    5 => [$new_rule['template'], 'Integer'],
                ]);
                // remove from list
                unset($current_rules[$new_rule['id']]);
            }
        }

        // finally: delete all remaining rules
        foreach ($current_rules as $rule_to_delete) {
            CRM_Core_DAO::executeQuery("DELETE FROM civicrm_value_event_messages WHERE id = %1",
                                       [1 => [$rule_to_delete['id'], 'String']]);
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
        $rules = self::getActiveRules($event_id);
        $multi_match = self::isMultiMatch($event_id);
        foreach ($rules as $rule) {
            if (in_array($from_status_id, $rule['from']) || empty($rule['from'])) {
                // 'from' status matches!
                if (in_array($to_status_id, $rule['to']) || empty($rule['to'])) {
                    // 'to' status matches, too: send message
                    CRM_Eventmessages_SendMail::sendMessageTo([
                        'participant_id' => $participant_id,
                        'event_id'       => $event_id,
                        'from'           => $from_status_id,
                        'to'             => $to_status_id,
                        'rule'           => $rule,
                    ]);
                    if (!$multi_match) {
                        break;
                    }
                }
            }
        }
    }

    /**
     * Check if the given event ID allows multiple rule matches (and multiple emails to be sent)
     *
     * @param integer $event_id
     *   event ID
     *
     * @return boolean
     *   is multimatch allowed in this event?
     */
    public static function isMultiMatch($event_id)
    {
        $event_id = (int) $event_id;
        $value = CRM_Core_DAO::singleValueQuery("
            SELECT execute_all_rules FROM civicrm_value_event_messages_settings WHERE entity_id = {$event_id}");
        return (boolean) $value;
    }
}
