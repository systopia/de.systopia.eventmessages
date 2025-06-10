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

use CRM_Eventmessages_ExtensionUtil as E;

/**
 * Generate letters for participants task.
 */
class CRM_Eventmessages_Form_Task_ParticipantLetter extends CRM_Event_Form_Task {
  /**
   * Number of participants being processed per queue item.
   */
  protected const RUNNER_BATCH_SIZE = 2;

  public function buildQuickForm() {
    $participant_count = count($this->_participantIds);

    // Calculate and add the number of contacts with no valid address.
    $no_address_count = $this->getNoAddressCount();
    $this->assign('no_address_count', $no_address_count);

    CRM_Utils_System::setTitle(
        E::ts(
            'Generate Letter for %1 Participants (%2 with primary postal address)',
            [
              1 => $participant_count,
              2 => $participant_count - $no_address_count,
            ]
        )
    );

    $this->add(
        'select',
        'template_id',
        E::ts('Message Template'),
        $this->getMessageTemplates(),
        TRUE,
        ['class' => 'crm-select2 huge']
    );

    $this->add(
        'checkbox',
        'address_only',
        E::ts('Exclude contacts without primary postal address')
    );

    $this->setDefaults(
        [
          'template_id' => Civi::settings()->get(
                'eventmessages_participant_send_template_id'
          ),
        ]
    );

    CRM_Core_Form::addDefaultButtons(
        E::ts(
            'Generate %1 (%2) Letters',
            [
              1 => $participant_count,
              2 => $participant_count - $no_address_count,
            ]
        )
    );
  }

  public function postProcess() {
    // TODO: Depend on "address_only" checkbox value.
    $values = $this->exportValues();
    $participant_count = count($this->_participantIds);
    if (!empty($values['address_only'])) {
      $participant_count -= $this->getNoAddressCount();
    }

    // Store default value for select field.
    Civi::settings()->set('eventmessages_participant_send_template_id', $values['template_id']);

    // Initialize a queue.
    $queue = CRM_Queue_Service::singleton()->create(
        [
          'type' => 'Sql',
          'name' => 'eventmessages_letter_task_'
          . CRM_Core_Session::singleton()->getLoggedInContactID(),
          'reset' => TRUE,
        ]
    );

    // Create a temporary folder to store the PDFs in.
    $temp_folder = tempnam(sys_get_temp_dir(), 'eventmessages_pdf_generator_');
    // Remove the file with that name creaty by tempnam().
    unlink($temp_folder);

    // Add an initialisation queue item.
    $queue->createItem(
        new CRM_Eventmessages_GenerateLetterJob(
            'init',
            [],
            (int) $values['template_id'],
            $temp_folder,
            E::ts('Initialized')
        )
    );

    // Retrieve all participants.
    $participant_id_list = implode(',', $this->_participantIds);
    $participant_query = '
            SELECT participant.id AS participant_id
                FROM civicrm_participant participant
            ';
    if (!empty($values['address_only'])) {
      $participant_query .= '
            LEFT JOIN civicrm_address address
                   ON address.contact_id = participant.contact_id
                   AND address.is_primary = 1
            ';
    }
    $participant_query .= "
            WHERE participant.id IN ({$participant_id_list})
            ";
    if (!empty($values['address_only'])) {
      $participant_query .= '
                AND address.id IS NOT NULL
            ';
    }
    $participant_query .= '
            ORDER BY participant.event_id ASC
            ';

    $participant_query = CRM_Core_DAO::executeQuery($participant_query);

    // Add queue items with sets of participants.
    $current_batch = [];
    $next_offset = self::RUNNER_BATCH_SIZE;
    while ($participant_query->fetch()) {
      $current_batch[] = $participant_query->participant_id;
      $queue->createItem(
        new CRM_Eventmessages_GenerateLetterJob(
            'run',
            $current_batch,
            (int) $values['template_id'],
            $temp_folder,
            E::ts(
                'Generated letters %1 of %2',
                [
                  1 => min($participant_count, $next_offset),
                  2 => $participant_count,
                ]
            )
        )
      );
      $next_offset += self::RUNNER_BATCH_SIZE;
      $current_batch = [];
    }

    // Add a final queue item for generating a zip archive.
    $queue->createItem(
        new CRM_Eventmessages_GenerateLetterJob(
            'finish',
            [],
            (int) $values['template_id'],
            $temp_folder,
            E::ts('Finished')
        )
    );

    // Start a runner on the queue.
    $return_link = base64_encode(CRM_Core_Session::singleton()->readUserContext());
    $download_link = CRM_Utils_System::url(
        'civicrm/eventmessages/download',
        "tmp_folder={$temp_folder}&return_url={$return_link}"
    );
    $runner = new CRM_Queue_Runner(
        [
          'title' => E::ts(
                'Generating %1 Event Letters',
                [1 => $participant_count]
          ),
          'queue' => $queue,
          'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
          'onEndUrl' => $download_link,
        ]
    );
    $runner->runAllViaWeb();
  }

  /**
   * Retrieves a list of eligible templates.
   *
   * @return array
   *   An array with message template IDs as keys and their names as values.
   */
  private function getMessageTemplates(): array {
    $list = [];
    $query = civicrm_api3(
        'MessageTemplate',
        'get',
        [
          'is_active' => 1,
          'workflow_id' => ['IS NULL' => 1],
          'option.limit' => 0,
          'return' => 'id,msg_title',
        ]
    );

    foreach ($query['values'] as $status) {
      $list[$status['id']] = $status['msg_title'];
    }
    asort($list);

    return $list;
  }

  /**
   * Retrieves the number of participants that do not have a primary postal
   * address.
   *
   * @return int
   *   The number of participants without a primary postal address.
   */
  private function getNoAddressCount() {
    $participant_id_list = implode(',', $this->_participantIds);
    return CRM_Core_DAO::singleValueQuery(
        "
            SELECT COUNT(DISTINCT(participant.id))
            FROM civicrm_participant participant
            LEFT JOIN civicrm_address address
                   ON address.contact_id = participant.contact_id
                   AND address.is_primary = 1
            WHERE participant.id IN ({$participant_id_list})
              AND address.id IS NULL"
    );
  }

}
