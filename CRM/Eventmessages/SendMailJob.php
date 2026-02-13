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

use Civi\Api4\Participant;
use CRM_Eventmessages_ExtensionUtil as E;

/**
 * Queue item for sending emails to participants
 */
class CRM_Eventmessages_SendMailJob {
  /**
   * @var string job title */
  public string $title;

  /**
   * @var array list of (int) participant IDs */
  protected array $participant_ids;

  /**
   * @var integer template to send to */
  protected int $template_id;

  /**
   * @var array
   *   A list of attachment IDs to add to the e-mail.
   */
  protected array $attachments;

  public function __construct(array $participant_ids, int $template_id, string $title, array $attachments = []) {
    $this->participant_ids = $participant_ids;
    $this->template_id = $template_id;
    $this->title = $title;
    $this->attachments = $attachments;
  }

  /**
   *
   *
   * @return true
   */
  public function run(): bool {
    if (!empty($this->participant_ids)) {
      // load the participants
      $participants = Participant::get(FALSE)
        ->addSelect('id', 'contact_id', 'event_id', 'status_id')
        ->addWhere('id', 'IN', $this->participant_ids)
        ->execute();
      // trigger sendMessageTo for each one of them
      foreach ($participants as $participant) {
        try {
          CRM_Eventmessages_SendMail::sendMessageTo(
          [
            'participant_id' => $participant['id'],
            'event_id' => $participant['event_id'],
            'from' => $participant['status_id'],
            'to' => $participant['status_id'],
            'rule' => 0,
            'template_id' => $this->template_id,
            'participant_ids' => $this->participant_ids,
            'attachments' => $this->attachments,
          ],
        FALSE
          );
        }
        catch (Exception $exception) {
          $formatted_message = E::ts(
                'Could not send e-mail to participant %1, error was: %2',
                [
                  1 => '<a href="' . CRM_Utils_System::url(
                    'civicrm/contact/view/participant',
                    'id=' . $participant['id']
                    . '&cid=' . $participant['contact_id']
                    . '&action=view'
                  ) . '">#' . $participant['id'] . '</a>',
                  2 => '<pre>' . $exception->getMessage() . '</pre>',
                ]
            );
          throw new \RuntimeException($formatted_message, $exception->getCode(), $exception);
        }
      }
    }
    return TRUE;
  }

}
