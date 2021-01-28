<?php
/*-------------------------------------------------------+
| SYSTOPIA Event Messages                                |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
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
use \Civi\EventMessages\MessageTokens as MessageTokens;

/**
 * Basic Logic for generating the actual letter
 */
class CRM_Eventmessages_GenerateLetter
{

    /**
     * Generates the actual letter.
     *
     * @param array $context
     *   Some context information, see processStatusChange().
     *
     * @return string
     *   The gnerated PDF file contents.
     */
    public static function generateLetterFor($context)
    {
        try {
            $event = self::getEventData($context['event_id']);
            $data_query = self::buildDataQuery($context);
            $data = CRM_Core_DAO::executeQuery($data_query);
            if ($data->fetch()) {
                $message_tokens = CRM_Eventmessages_Logic::generateTokenEvent($data->participant_id, $data->contact_id, $event);
                Civi::dispatcher()->dispatch('civi.eventmessages.tokens', $message_tokens);

                // and generate the letter from the template
                $letter_data = [
                    'id' => empty($context['template_id']) ? $context['rule']['template'] : $context['template_id'],
                    'toName' => $data->contact_name,
                    'contactId' => $data->contact_id,
                    'tplParams' => $message_tokens->getTokens(),
                ];

                // generate the letter
                Civi::log()->debug("EventMessages: Generating eventmessages letter for '{$data->contact_name}'");
                // Load message template.
                $msg_tpl = civicrm_api3(
                    'MessageTemplate',
                    'getsingle',
                    ['id' => $letter_data['id']]
                );
                $html = $msg_tpl['msg_html'];
                $pdf_format_id = $msg_tpl['pdf_format_id'];

                // Prepare message template.
                CRM_Contact_Form_Task_PDFLetterCommon::formatMessage($html);

                // Replace contact tokens.
                $html = CRM_Utils_Token::replaceContactTokens(
                    $html,
                    $contact,
                    true,
                    $message_tokens->getTokens()
                );

                // Pass tokens as Smarty variables.
                /* @var CRM_Core_Smarty $smarty */
                $smarty = CRM_Core_Smarty::singleton();
                foreach ($message_tokens->getTokens() as $key => $value) {
                    $smarty->assign($key, $value);
                }
                $html = $smarty->fetch("string:$html");

                // Convert to PDF and output the result.
                $pdf = CRM_Utils_PDF_Utils::html2pdf(
                    [$html],
                    'letter.pdf',
                    true,
                    $pdf_format_id
                );
                return $pdf;
            } else {
                Civi::log()->warning(
                    "Couldn't generate letter for participant [{$context['participant_id']}], something is wrong with the data set."
                );
            }
        } catch (Exception $ex) {
            Civi::log()->warning(
                "Couldn't generate letter for participant [{$context['participant_id']}], error was: " . $ex->getMessage(
                )
            );
        }
    }

    /**
     * Get all the necessary event data from the event.
     *  Will be cached
     *
     * @param int $event_id
     *   Event ID
     *
     * @return array
     *   Event data
     */
    protected static function getEventData($event_id)
    {
        static $event_cache = [];
        if (!isset($event_cache[$event_id])) {
            $event = civicrm_api3(
                'Event',
                'getsingle',
                ['id' => $event_id]
            );
            CRM_Eventmessages_CustomData::labelCustomFields($event, 1, '__');
            $event_cache[$event_id] = $event;
        }
        return $event_cache[$event_id];
    }

    /**
     * Build an SQL query to fetch the right data set,
     *  including contact_name, contact_id
     *
     * @param array $context
     *      some context information, see processStatusChange
     *
     * @return string
     *      sql query to gather the data required for generating a letter
     */
    protected static function buildDataQuery($context)
    {
        $participant_id = (int)$context['participant_id'];
        return "
            SELECT 
              address.street_address AS contact_street_address,
              address.postal_code AS contact_postal_code,
              address.city AS contact_city,
              address.supplemental_address_1 AS contact_supplemental_address_1,
              address.supplemental_address_2 AS contact_supplemental_address_2,
              address.supplemental_address_3 AS contact_supplemental_address_3,
              contact.display_name AS contact_name,
              contact.id AS contact_id,
              participant.id AS participant_id
            FROM
              civicrm_participant participant
            INNER JOIN
              civicrm_contact contact  
              ON
                contact.id = participant.contact_id
            INNER JOIN
              civicrm_address   address
              ON
                address.contact_id = contact.id  
            INNER JOIN
              civicrm_event   event
              ON
                event.id = participant.event_id
            WHERE
              participant.id = {$participant_id}
              AND (
                contact.is_deleted IS NULL
                OR contact.is_deleted = 0
              )
            ORDER BY
              address.is_primary DESC
            ";
    }
}
