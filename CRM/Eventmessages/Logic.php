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
        // TODO: implement
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
    }
}
