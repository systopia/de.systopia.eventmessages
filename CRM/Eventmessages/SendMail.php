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
 * Basic Logic for sending the actual email
 */
class CRM_Eventmessages_SendMail {

    /**
     * Triggers the actual sending of a message (or at least it's scheduling)
     *
     * @param array $context
     *      some context information, see processStatusChange
     */
    public static function sendMessageTo($context) {
        try {
            // load some stuff via SQL
            $event = self::getEventData($context['event_id']);
            $data  = CRM_Core_DAO::executeQuery(self::buildDataQuery($context));
            if ($data->fetch()) {
                // todo: do some checks?

                // template params
                $params = $event;
                $params['contact_id'] = $data->contact_id;
                $params['event_id'] = $data->event_id;
                $params['participant_id'] = $data->participant_id;


                // and send the template via email
                $email_data = [
                    'id'        => $context['rule']['template'],
                    'toName'    => $data->contact_name,
                    'toEmail'   => $data->contact_email,
                    'from'      => $event['event_messages_settings.event_messages_sender'],
                    'replyTo'   => $event['event_messages_settings.event_messages_reply_to'],
                    'cc'        => $event['event_messages_settings.event_messages_cc'],
                    'bcc'       => $event['event_messages_settings.event_messages_bcc'],
                    'contactId' => $data->contact_id,
                    'tplParams' => ['event' => $event],
                ];
                civicrm_api3('MessageTemplate', 'send', $email_data);

            } else {
                Civi::log()->warning("Couldn't send message to participant [{$context['participant_id']}], something is wrong with the data set.");
            }
        } catch (Exception $ex) {
            Civi::log()->warning("Couldn't send email to participant [{$participant_id}], error was: " . $ex->getMessage());
        }
    }

    /**
     * Get all the necessary event data from the event.
     *  Will be cached
     *
     * @param integer $event_id
     *   Event ID
     *
     * @return array
     *   Event data
     */
    protected static function getEventData($event_id)
    {
        static $event_cache = [];
        if (!isset($event_cache[$event_id])) {
            $event = civicrm_api3('Event', 'getsingle', ['id' => $event_id]);
            CRM_Eventmessages_CustomData::labelCustomFields($event);
            $event_cache[$event_id] = $event;
        }
        return $event_cache[$event_id];
    }

    /**
     * Build an SQL query to fetch the right data set,
     *  including contact_name, contact_id, contact_email
     *
     * @param array $context
     *      some context information, see processStatusChange
     */
    protected static function buildDataQuery($context)
    {
        $participant_id = (int) $context['participant_id'];
        $query = "
                SELECT 
                  email.email          AS contact_email,
                  contact.display_name AS contact_name,
                  contact.id           AS contact_id
                FROM civicrm_participant   participant
                INNER JOIN civicrm_contact contact  
                        ON contact.id = participant.contact_id
                INNER JOIN civicrm_email   email
                        ON email.contact_id = contact.id
                        AND (email.on_hold IS NULL OR email.on_hold = 0)  
                INNER JOIN civicrm_event   event
                        ON event.id = participant.event_id
                WHERE participant.id = {$participant_id}
                ORDER BY email.is_primary DESC, email.is_bulkmail ASC, email.is_billing ASC
            ";

        return $query;
    }
}
