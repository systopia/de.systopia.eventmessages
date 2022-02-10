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
 * Queue item for sending emails to participants
 */
class CRM_Eventmessages_SendMailJob
{
    /** @var string job title */
    public $title = '';

    /** @var array list of (int) participant IDs */
    protected $participant_ids;

    /** @var integer template to send to */
    protected $template_id;

    /**
     * @var array
     *   A list of attachment IDs to add to the e-mail.
     */
    protected $attachments;

    public function __construct($participant_ids, $template_id, $title, $attachments = [])
    {
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
    public function run(): bool
    {
        if (!empty($this->participant_ids)) {
            // load the participants
            $participants = civicrm_api3('Participant', 'get', [
                'id'           => ['IN' => $this->participant_ids],
                'return'       => 'id,contact_id,event_id,status_id',
                'option.limit' => 0,
            ]);
            // trigger sendMessageTo for each one of them
            foreach ($participants['values'] as $participant) {
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
                        false
                    );
                } catch (Exception $exception) {
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
                    throw new Exception($formatted_message);
                }
            }
        }
        return true;
    }
}