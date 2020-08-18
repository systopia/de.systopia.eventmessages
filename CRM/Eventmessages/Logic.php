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
                    'from'      => explode(',', $query->from_status),
                    'to'        => explode(',', $query->to_status),
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
                'from'      => explode(',', $query->from_status),
                'to'        => explode(',', $query->to_status),
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
        if (in_array($from_status_id, $rules['from']) || empty($rules['from'])) {
            // from status matches!
            if (in_array($to_status_id, $rules['to']) || empty($rules['to'])) {
                // to status matches, too:
                self::sendMessageTo($participant_id, [
                    'event_id' => $event_id,
                    'from'     => $from_status_id,
                    'to'       => $to_status_id,
                ]);
            }
        }
    }

    /**
     * Triggers the actual sending of a message (or at least it's scheduling)
     *
     * @param integer $participant_id
     *      participant ID
     *
     * @param array $context
     *      some context information
     */
    public static function sendMessageTo($participant_id, $context) {
        // TODO: implement. maybe just schedule...?
        Civi::log()->debug("Send mail to {$participant_id}!");
    }
}
