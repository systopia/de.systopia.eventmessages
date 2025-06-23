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
  public static function sendMessageTo($context, $silent = TRUE) {
    try {
      // load some stuff via SQL
      $event = self::getEventData($context['event_id']);
      $data_query = self::buildDataQuery($context);
      $data = CRM_Core_DAO::executeQuery($data_query);
      $template_id = empty($context['template_id']) ? $context['rule']['template'] : $context['template_id'];
      if ($data->fetch()) {
        // load participant
        $message_tokens = CRM_Eventmessages_Logic::generateTokenEvent($data->participant_id, $data->contact_id, $event);
        $message_tokens->setTemplateId($template_id);
        Civi::dispatcher()->dispatch('civi.eventmessages.tokens', $message_tokens);

        // and send the template via email
        $email_data = [
          'id' => (int) $template_id,
          'toName' => $data->contact_name,
          'toEmail' => $data->contact_email,
          'from' => CRM_Utils_Array::value('event_messages_settings__event_messages_sender', $event, ''),
          'replyTo' => CRM_Utils_Array::value('event_messages_settings__event_messages_reply_to', $event, ''),
          'cc' => CRM_Utils_Array::value('event_messages_settings__event_messages_cc', $event, ''),
          'bcc' => CRM_Utils_Array::value('event_messages_settings__event_messages_bcc', $event, ''),
          'contactId' => (int) $data->contact_id,
          'tokenContext' => [
            'contactId' => (int) $data->contact_id,
            'participantId' => (int) $data->participant_id,
            'eventId' => $context['event_id'],
          ],
          'tplParams' => $message_tokens->getTokens(),
        ];

        // add attachments
        if (class_exists('Civi\Mailattachment\Form\Attachments')) {
          $email_data['attachments'] = array_merge(
            $email_data['attachments'] ?? [],
            self::buildAttachments($data, $context)
          );
        }

        // resolve/beautify sender (use name instead of value of the option_value)
        $from_addresses = CRM_Core_OptionGroup::values('from_email_address');
        if (isset($from_addresses[$email_data['from']])) {
          $email_data['from'] = $from_addresses[$email_data['from']];
        }
        else {
          $email_data['from'] = reset($from_addresses);
        }

        // send the mail
        if (self::isMailingDisabled()) {
          // phpcs:disable Generic.Files.LineLength.TooLong
          Civi::log()->info(
            "EventMessages: Outbound mailing disabled, NOT sending email to '{$data->contact_email}' from '{$email_data['from']}'"
          );
          // phpcs:enable
        }
        else {
          Civi::log()->debug("EventMessages: Sending email to '{$data->contact_email}' from '{$email_data['from']}'");
          civicrm_api3('MessageTemplate', 'send', $email_data);
        }
      }
      else {
        Civi::log()->warning(
          "Couldn't send message to participant [{$context['participant_id']}], something is wrong with the data set."
        );
      }
    }
    catch (Exception $exception) {
      Civi::log()->warning(
        E::ts(
          'Could not send e-mail to participant %1, error was: %2',
          [
            1 => $context['participant_id'],
            2 => $exception->getMessage(),
          ]
        )
      );
      if (!$silent) {
        throw $exception;
      }
    }
  }

  public static function buildAttachments(\CRM_Core_DAO $data, array $context) {
    $renderedAttachments = [];
    $attachment_types = \Civi\Mailattachment\Form\Attachments::attachmentTypes();
    $attachments = $context['attachments'] ?? $context['rule']['attachments'];
    foreach ($attachments as $attachment_id => $attachment_values) {
      $attachment_type = $attachment_types[$attachment_values['type']];
      /** @var \Civi\Mailattachment\AttachmentType\AttachmentTypeInterface $controller */
      $controller = $attachment_type['controller'];
      if (
        !($attachment = $controller::buildAttachment(
          [
            'entity_type' => 'participant',
            'entity_id' => $data->participant_id,
            'entity_ids' => $context['participant_ids'],
          ],
          $attachment_values
        )
        )
      ) {
        // no attachment -> cannot send
        throw new \RuntimeException(
          E::ts("Attachment '%1' could not be generated or found.", [1 => $attachment_id])
        );
      }
      $renderedAttachments[] = $attachment;
    }
    return $renderedAttachments;
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
  protected static function getEventData($event_id) {
    static $event_cache = [];
    if (!isset($event_cache[$event_id])) {
      $event = civicrm_api3('Event', 'getsingle', ['id' => $event_id]);
      CRM_Eventmessages_CustomData::labelCustomFields($event, 1, '__');
      $event_cache[$event_id] = $event;
    }
    return $event_cache[$event_id];
  }

  /**
   * Make sure that CiviCRM event mails will be suppressed if the event is configured is this way
   * This is achieved by wrapping the mailer object in a filter class
   *
   * @param object $mailer
   *      the currently used mailer, to be manipulated
   */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.NestingLevel.TooHigh
  public static function suppressSystemMails(&$mailer, $driver, $params) {
    $mailer = new class($mailer, $driver, $params) {

      protected $mailer = NULL;
      protected $driver = NULL;
      protected $params = NULL;

      public function __construct($mailer, $driver, $params) {
        $this->mailer = $mailer;
        $this->driver = $driver;
        $this->params = $params;
      }

      /**
       * Implement the mailer's send function, so that
       *   system mails from events with active suppression will be dropped
       */
      function send($recipients, $headers, $body) {
        $callstack = debug_backtrace();

        // scan the call stack for "forbidden" calls
        foreach ($callstack as $stack_idx => $call) {

          if (isset($call['class']) && isset($call['function'])) {
            // 1. check for emails coming through CRM_Event_BAO_Event::sendMessageTo
            if ($call['class'] == 'CRM_Eventmessages_SendMail' && $call['function'] == 'sendMessageTo') {
              // these are ours, continue to send
              break;
            }

            // 2. check for emails coming through CRM_Event_BAO_Event::sendMail
            if ($call['class'] == 'CRM_Event_BAO_Event' && $call['function'] == 'sendMail') {
              $participant_id = $call['args'][2];
              if (CRM_Eventmessages_SendMail::suppressSystemEventMailsForParticipant($participant_id)) {
                Civi::log()->debug('EventMessages: CRM_Event_BAO_Event::sendMail detected!');
                $this->logDroppedMail($recipients, $headers, $body);
                // don't send
                return;
              }
              // no suppression, continue to send
              break;
            }

            // 3. check for mails coming from the CRM_Event_Form_Participant form
            //  note that this also triggers our own messages, but that was already dealt with in 1.
            if ($call['class'] == 'CRM_Event_Form_Participant' && $call['function'] == 'submit') {
              $participant_id = $call['object']->_id;
              if (CRM_Eventmessages_SendMail::suppressSystemEventMailsForParticipant($participant_id)) {
                Civi::log()->debug('EventMessages: CRM_Event_Form_Participant::submit detected!');
                $this->logDroppedMail($recipients, $headers, $body);
                // don't send
                return;
              }
              // no suppression, continue to send
              break;
            }

            // 4. check for emails coming through event self-service
            if ($call['class'] == 'CRM_Event_Form_SelfSvcUpdate'
            && ($call['function'] == 'cancelParticipant' || $call['function'] == 'transferParticipant')) {
              // extract participant_id
              // This is extremely hacky, if anyone finds a better way to extract the participant_id,
              // please let us know!
              $entry_url = $call['args'][0]['entryURL'];
              if (preg_match('/pid=(\d+)\D/', $entry_url, $matches)) {
                $participant_id = $matches[1];
                if (CRM_Eventmessages_SendMail::suppressSystemEventMailsForParticipant($participant_id)) {
                  Civi::log()->debug(
                    'EventMessages: CRM_Event_Form_SelfSvcUpdate::cancelParticipant/transferParticipant detected!'
                  );
                  $this->logDroppedMail($recipients, $headers, $body);
                  // don't send
                  return;
                }
              }
              else {
                // phpcs:disable Generic.Files.LineLength.TooLong
                Civi::log()->debug(
                  "EventMessages: couldn't extract participant ID from CRM_Event_Form_SelfSvcUpdate::cancelParticipant/transferParticipant"
                );
                // phpcs:enable
              }
              // no suppression, continue to send
              break;
            }

            // 5. suppress mails from the participant transition form
            if ($call['class'] == 'CRM_Event_BAO_Participant' && $call['function'] == 'sendTransitionParticipantMail') {
              // this is transition confirmation...hope it's ok to filter out all of them
              $participant_id = $call['args'][0];
              if (CRM_Eventmessages_SendMail::suppressSystemEventMailsForParticipant($participant_id)) {
                // phpcs:disable Generic.Files.LineLength.TooLong
                Civi::log()->debug(
                  "EventMessages: CRM_Event_BAO_Participant::sendTransitionParticipantMail [{$participant_id}] detected!"
                );
                // phpcs:enable
                $this->logDroppedMail($recipients, $headers, $body);
                // don't send
                return;
              }
            }
          }
        }

        // this email WILL be sent - if requested,
        //   the stack trace will be logged if
        if (defined('EVENTMESSAGES_LOG_SYSTEM_EMAILS')) {
          // print a stack trace to the CiviCRM log
          $stack_trace = 'EVENTMESSAGES - MESSAGE SENT STACK TRACE:';
          foreach ($callstack as $call) {
            if (isset($call['class']) && isset($call['function'])) {
              $stack_trace .= "\n{$call['class']}:{$call['line']} - {$call['function']}:"
                . json_encode($call['args'], 0, 1);
            }
          }
          Civi::log()->debug($stack_trace);
        }

        // we're done filtering -> send it already...
        $this->mailer->send($recipients, $headers, $body);
      }

      /**
       * @return Mail|null
       */
      function getDriver() {
        return $this->driver;
      }

      /**
       * If we really drop/suppress a system mail, let's at least
       *   log something...
       */
      function logDroppedMail($recipients, $headers, $body) {
        $recipient_list = is_array($recipients) ? implode(';', $recipients) : $recipients;
        Civi::log()->debug("EventMessages: Suppressed CiviCRM event mail for recipients '{$recipient_list}'");
      }

    };
  }

  // phpcs:enable

  /**
   * Check whether CiviCRM's native event notifications should be suppressed
   *  for this participant/event
   *
   * @param integer $participant_id
   *   the participant ID
   *
   * @param integer $event_id
   *   the event ID
   *
   * @return boolean
   *   should the email be suppressed?
   */
  public static function suppressSystemEventMailsForParticipant($participant_id, $event_id = NULL) {
    // check if something's slipped through, despite our efforts to identify the participant
    if (empty($participant_id)) {
      Civi::log()->debug(
        'EventMessages: empty participant ID encountered! Will NOT suppress, but this should be fixed.'
      );
      return FALSE;
    }

    // get the current setting
    $disable_default = (boolean) self::getEventMailsSettingsForParticipant(
      'disable_default', $participant_id, $event_id
    );

    // TODO: remove logging
    Civi::log()->debug(
      "EventMessages: suppress system messages for event [{$event_id}] / participant [{$participant_id}]: "
      . ($disable_default ? 'yes' : 'no')
    );

    return $disable_default;
  }

  /**
   * Apply the submission data overwrite
   *
   * @param integer $participant_id
   *   the participant ID
   *
   * @param array $participant
   *   participant data to be extended
   *
   * @see https://github.com/systopia/de.systopia.eventmessages/issues/31
   */
  public static function applyCustonFieldSubmissionWorkaroundForParticipant($participant_id, &$participant) {
    $custom_data_workaround = (boolean) self::getEventMailsSettingsForParticipant(
      'custom_data_workaround', $participant_id, $participant['event_id']
    );
    if ($custom_data_workaround) {
      $event_id = $participant['event_id'] ?? 'n/a';
      Civi::log()->debug(
        "EventMessages: adding custom data submission for event [{$event_id}] / participant [{$participant_id}]"
      );

      // get all potential sources of the registration data submission
      $submission_sources = [];
      // add session as a source
      if (isset($_SESSION['CiviCRM']) && is_array($_SESSION['CiviCRM'])) {
        foreach ($_SESSION['CiviCRM'] as $key => $data) {
          if (strpos($key, 'CRM_Event_Controller_Registration_') !== FALSE) {
            if (isset($_SESSION['CiviCRM'][$key]['value'])) {
              $submission_sources[] = $_SESSION['CiviCRM'][$key]['value'];
            }
          }
        }
      }
      // add the current request as a source
      $submission_sources[] = $_REQUEST;

      // copy all custom_xx parameters into the participant
      foreach ($submission_sources as $submission_source) {
        foreach ($submission_source as $key => $value) {
          if (preg_match('/^custom_[0-9]+$/', $key)) {
            $participant[$key] = $value;
          }
        }
      }
    }

    return $custom_data_workaround;
  }

  /**
   * Check whether CiviCRM's native event notifications should be suppressed
   *  for this participant/event
   *
   * @param string $setting_name
   *   name of the setting to look for
   *
   * @param integer $participant_id
   *   the participant ID
   *
   * @param integer $event_id
   *   the event ID
   *
   * @return mixed
   *   get a specific setting based on participant_id or event_id
   */
  public static function getEventMailsSettingsForParticipant($setting_name, $participant_id, $event_id = NULL) {
    static $cached_event_results = [];
    static $cached_participant_results = [];

    // if an event ID is given, use that
    $event_id = (int) $event_id;
    if ($event_id) {
      if (!isset($cached_event_results[$setting_name][$event_id])) {
        $settings = CRM_Core_DAO::executeQuery(
          <<<SQL
          SELECT disable_default, custom_data_workaround
          FROM civicrm_value_event_messages_settings settings
          WHERE settings.entity_id = {$event_id};
          SQL
        );

        $settings->fetch();
        $cached_event_results['disable_default'][$event_id] = $settings->disable_default ?? FALSE;
        $cached_event_results['custom_data_workaround'][$event_id] = $settings->custom_data_workaround ?? FALSE;
      }
      return $cached_event_results[$setting_name][$event_id];
    }

    // otherwise, we have to work with the participant
    $participant_id = (int) $participant_id;
    if ($participant_id) {
      if (!isset($cached_participant_results[$setting_name][$participant_id])) {
        $settings = CRM_Core_DAO::executeQuery(
          <<<SQL
          SELECT disable_default, custom_data_workaround
          FROM civicrm_participant participant
          LEFT JOIN civicrm_value_event_messages_settings settings
            ON settings.entity_id = participant.event_id
          WHERE participant.id = {$participant_id};
          SQL
        );
        $settings->fetch();
        $cached_participant_results['disable_default'][$participant_id] = (boolean) $settings->disable_default ?? FALSE;
        $cached_participant_results['custom_data_workaround'][$participant_id]
          = (boolean) $settings->custom_data_workaround ?? FALSE;
      }
      return $cached_participant_results[$setting_name][$participant_id];
    }

    Civi::log()->debug('EventMessages: suppression of system messages unknown, no IDs submitted');
    return FALSE;
  }

  /**
   * Build an SQL query to fetch the right data set,
   *  including contact_name, contact_id, contact_email
   *
   * @param array $context
   *      some context information, see processStatusChange
   *
   * @return string
   *   sql query to gather the data required for generating an email
   */
  protected static function buildDataQuery($context) {
    $participant_id = (int) $context['participant_id'];
    return <<<SQL
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
      SQL;
  }

  /**
   * Check if the outbound (mailing backend) is disabled
   *
   * @return boolean
   *   true iff mailing is disabled
   */
  public static function isMailingDisabled() {
    $mailing_backend = Civi::settings()->get('mailing_backend') ?? [];
    $outbound = $mailing_backend['outBound_option'] ?? -1;
    return ($outbound == CRM_Mailing_Config::OUTBOUND_OPTION_DISABLED);
  }

}
