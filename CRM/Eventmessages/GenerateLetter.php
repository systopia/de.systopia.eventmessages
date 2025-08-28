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

declare(strict_types = 1);

use Civi\Token\TokenProcessor;

/**
 * Basic Logic for generating the actual letter
 */
class CRM_Eventmessages_GenerateLetter {

  /**
   * Generates the actual letter.
   *
   * @param array<string, mixed> $context
   *   Some context information, see processStatusChange().
   *
   * @return string|null
   *   The generated PDF file contents, or NULL on error.
   */
  public static function generateLetterFor(array $context): ?string {
    try {
      // @phpstan-ignore argument.type
      $event = self::getEventData($context['event_id']);
      $data_query = self::buildDataQuery($context);
      /** @var \CRM_Core_DAO $data */
      $data = CRM_Core_DAO::executeQuery($data_query);
      // @phpstan-ignore offsetAccess.nonOffsetAccessible
      $template_id = empty($context['template_id']) ? $context['rule']['template'] : $context['template_id'];
      if ($data->fetch()) {
        $message_tokens = CRM_Eventmessages_Logic::generateTokenEvent($data->participant_id, $data->contact_id, $event);
        $message_tokens->setTemplateId($template_id);
        Civi::dispatcher()->dispatch('civi.eventmessages.tokens', $message_tokens);

        // generate the letter
        Civi::log()->debug("EventMessages: Generating eventmessages letter for '{$data->contact_name}'");
        // Load message template.
        /** @var array<string, string> $msg_tpl */
        $msg_tpl = civicrm_api3(
          'MessageTemplate',
          'getsingle',
          ['id' => $template_id]
        );
        $html = $msg_tpl['msg_html'];
        $pdf_format_id = isset($msg_tpl['pdf_format_id']) ? (int) $msg_tpl['pdf_format_id'] : NULL;

        // Prepare message template.
        self::formatMessage($html);

        $smartyTokenFlattener = new CRM_Eventmessages_Util_SmartyTokenFlattener($html);
        $smartyTokenFlattener->flattenTokens($message_tokens->getSmartyTokens(), 'smarty');

        // Replace contact tokens and convert Smarty variables to tokens using
        // 'smartyTokenAlias' (see \Civi\Token\TokenCompatSubscriber).
        $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
          'smarty' => TRUE,
          'class' => __CLASS__,
          'schema' => ['contactId', 'participantId', 'eventId'],
          'smartyTokenAlias' => $smartyTokenFlattener->getAliases(),
        ]);
        $row = $tokenProcessor->addRow([
          'contactId' => $data->contact_id,
          'participantId' => $data->participant_id,
          'eventId' => $context['event_id'],
        ]);
        // Register values for Smarty variables.
        $row->tokens($smartyTokenFlattener->getTokens());
        $tokenProcessor->addMessage('templateContent', $smartyTokenFlattener->getTemplate(), 'text/html');
        $tokenProcessor->evaluate();
        $row = $tokenProcessor->getRow(0);
        $html = $row->render('templateContent');

        // Convert to PDF and output the result.
        return CRM_Utils_PDF_Utils::html2pdf(
          [$html],
          'letter.pdf',
          TRUE,
          $pdf_format_id
        );
      }
      else {
        Civi::log()->warning(
          "Couldn't generate letter for participant [" . $context['participant_id']
          . '], something is wrong with the data set.'
        );
      }
    }
    catch (Exception $ex) {
      Civi::log()->warning(
        "Couldn't generate letter for participant [{$context['participant_id']}], error was: " . $ex->getMessage()
      );
    }

    return NULL;
  }

  /**
   * Get all the necessary event data from the event.
   *  Will be cached
   *
   * @param int $event_id
   *   Event ID
   *
   * @return array<string, mixed>
   *   Event data
   *
   * @throws \CRM_Core_Exception
   */
  protected static function getEventData(int $event_id): array {
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
   * @param array<string, mixed> $context
   *      some context information, see processStatusChange
   *
   * @return string
   *   sql query to gather the data required for generating a letter
   */
  protected static function buildDataQuery(array $context): string {
    $participant_id = (int) $context['participant_id'];
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
            LEFT JOIN
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

  /**
   * Copied from \CRM_Contact_Form_Task_PDFTrait::formatMessage() as the original
   * \CRM_Core_Form_Task_PDFLetterCommon::formatMessage() is deprecated.
   *
   * @see \CRM_Contact_Form_Task_PDFTrait::formatMessage()
   * @see \CRM_Core_Form_Task_PDFLetterCommon::formatMessage()
   *
   * TODO: Make use of the trait instead of copying code.
   */
  public static function formatMessage(string &$message): void {
    $newLineOperators = [
      'p' => [
        'oper' => '<p>',
        'pattern' => '/<(\s+)?p(\s+)?>/m',
      ],
      'br' => [
        'oper' => '<br />',
        'pattern' => '/<(\s+)?br(\s+)?\/>/m',
      ],
    ];

    $htmlMsg = preg_split($newLineOperators['p']['pattern'], $message);
    assert(FALSE !== $htmlMsg);
    foreach ($htmlMsg as &$m) {
      $messages = preg_split($newLineOperators['br']['pattern'], $m);
      assert(FALSE !== $messages);
      foreach ($messages as &$msg) {
        $msg = trim($msg);
        $matches = [];
        if (preg_match('/^(&nbsp;)+/', $msg, $matches) === 1) {
          $spaceLen = strlen($matches[0]) / 6;
          $trimMsg = ltrim($msg, '&nbsp; ');
          $charLen = strlen($trimMsg);
          $totalLen = $charLen + $spaceLen;
          if ($totalLen > 100) {
            $spacesCount = 10;
            if ($spaceLen > 50) {
              $spacesCount = 20;
            }
            if ($charLen > 100) {
              $spacesCount = 1;
            }
            $msg = str_repeat('&nbsp;', $spacesCount) . $trimMsg;
          }
        }
      }
      $m = implode($newLineOperators['br']['oper'], $messages);
    }
    $message = implode($newLineOperators['p']['oper'], $htmlMsg);
  }

}
