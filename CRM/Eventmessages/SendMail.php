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
            $data_query = self::buildDataQuery($context);
            $data  = CRM_Core_DAO::executeQuery($data_query);
            if ($data->fetch()) {
                // todo: do some more checks?

                // load participant
                $participant = civicrm_api3('Participant', 'getsingle', ['id' => $data->participant_id]);
                CRM_Eventmessages_CustomData::labelCustomFields($participant);

                // load contact
                $contact = civicrm_api3('Contact', 'getsingle', ['id' => $data->contact_id]);
                CRM_Eventmessages_CustomData::labelCustomFields($contact);

                // and send the template via email
                $email_data = [
                    'id'        => $context['rule']['template'],
                    'toName'    => $data->contact_name,
                    'toEmail'   => $data->contact_email,
                    'from'      => CRM_Utils_Array::value('event_messages_settings.event_messages_sender', $event, ''),
                    'replyTo'   => CRM_Utils_Array::value('event_messages_settings.event_messages_reply_to', $event, ''),
                    'cc'        => CRM_Utils_Array::value('event_messages_settings.event_messages_cc', $event, ''),
                    'bcc'       => CRM_Utils_Array::value('event_messages_settings.event_messages_bcc', $event, ''),
                    'contactId' => $data->contact_id,
                    'tplParams' => [
                        'event'       => $event,
                        'participant' => $participant,
                        'contact'     => $contact,
                    ],
                ];
                civicrm_api3('MessageTemplate', 'send', $email_data);

            } else {
                Civi::log()->warning("Couldn't send message to participant [{$context['participant_id']}], something is wrong with the data set.");
            }
        } catch (Exception $ex) {
            Civi::log()->warning("Couldn't send email to participant [{$context['participant_id']}], error was: " . $ex->getMessage());
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
        return "
                SELECT 
                  email.email          AS contact_email,
                  contact.display_name AS contact_name,
                  contact.id           AS contact_id,
                  participant.id       AS participant_id
                FROM civicrm_participant   participant
                INNER JOIN civicrm_contact contact  
                        ON contact.id = participant.contact_id
                INNER JOIN civicrm_email   email
                        ON email.contact_id = contact.id
                        AND (email.on_hold IS NULL OR email.on_hold = 0)  
                INNER JOIN civicrm_event   event
                        ON event.id = participant.event_id
                WHERE participant.id = {$participant_id}
                  AND (contact.is_deleted IS NULL OR contact.is_deleted = 0)
                ORDER BY email.is_primary DESC, email.is_bulkmail ASC, email.is_billing ASC
            ";
    }

    /**
     * Make sure that CiviCRM event mails will be suppressed if the event is configured is this way
     * This is achieved by swapping the mailer object for a dummy
     *
     * @param object $mailer
     *      the currently used mailer, to be manipulated
     */
    public static function suppressSystemEventMails(&$mailer)
    {
        // check, if this is coming through CRM_Event_BAO_Event::sendMail
        $callstack = debug_backtrace();
        foreach ($callstack as $call) {
            if (isset($call['class']) && isset($call['function'])) {
                if ($call['class'] == 'CRM_Event_BAO_Event' && $call['function'] == 'sendMail') {
                    // this is coming from CRM_Event_BAO_Event::sendMail
                    $participant_id = $call['args'][2];
                    if (self::suppressSystemEventMailsForParticipant($participant_id)) {
                        $mailer = self::createDummyMailer($participant_id);
                        return;
                    }
                }
                if ($call['class'] == 'CRM_Event_Form_Participant' && $call['function'] == 'submit') {
                    // this is coming from the CRM_Event_Form_Participant form
                    $participant_id = $call['object']->_id;
                    if (self::suppressSystemEventMailsForParticipant($participant_id)) {
                        $mailer = self::createDummyMailer($participant_id);
                        return;
                    }
                }
            }
        }
        Civi::log()->debug("mailing passed");
    }

    /**
     * Generate a mailer, that would only write a line to the civicrm log
     *   instead of actually sending out an email
     *
     * @param integer $participant_id
     *   ID of the participant, for logging
     *
     * @return object CiviCRM mailer
     */
    protected static function createDummyMailer($participant_id)
    {
        return new class() {
            function send($recipients, $headers, $body) {
                $recipient_list = is_array($recipients) ? implode(';', $recipients) : $recipients;
                Civi::log()->debug("Suppressed CiviCRM Event mail for recipients '{$recipient_list}'");
            }
        };
    }

    /**
     * Check whether CiviCRM's native event notifications should be suppressed
     *  for this participant/event
     *
     * @param integer $participant_id
     *   the participant
     *
     * @return boolean
     *   should the email be suppressed?
     */
    public static function suppressSystemEventMailsForParticipant($participant_id)
    {
        $participant_id = (int) $participant_id;
        return (boolean) CRM_Core_DAO::singleValueQuery("
            SELECT settings.disable_default
            FROM civicrm_participant participant
            LEFT JOIN civicrm_value_event_messages_settings settings
                   ON settings.entity_id = participant.event_id
            WHERE participant.id = {$participant_id}");
    }
}
