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
 * Send E-Mail to participants task
 */
class CRM_Eventmessages_Form_Task_ParticipantEmail extends CRM_Event_Form_Task
{
    const RUNNER_BATCH_SIZE = 2;

    /**
     * Compile task form
     */
    function buildQuickForm()
    {
        $participant_count = count($this->_participantIds);
        $no_email_count = $this->getNoEmailCount();

        // now build the form
        CRM_Utils_System::setTitle(E::ts('Send Email to %1 Participants', [1 => $participant_count]));

        // calculate and add the number of contacts with no valid E-Mail
        $this->assign('no_email_count', $no_email_count);

//        $this->add(
//            'select',
//            'sender_email',
//            E::ts('E-mail sender address'),
//            $this->getSenderOptions(),
//            true,
//            ['class' => 'crm-select2 huge']
//        );

        $this->add(
            'select',
            'template_id',
            E::ts('Message Template'),
            $this->getMessageTemplates(),
            true,
            ['class' => 'crm-select2 huge']
        );

        if (class_exists('Civi\Mailattachment\Form\Attachments')) {
            \Civi\Mailattachment\Form\Attachments::addAttachmentElements($this, ['entity_type' => 'participant']);
        }

        // Set default values.
        $defaults = [
            'template_id'  => Civi::settings()->get('eventmessages_participant_send_template_id'),
//            'sender_email' => Civi::settings()->get('eventmessages_participant_send_sender_email'),
        ];
        if (class_exists('Civi\Mailattachment\Form\Attachments')) {
            // TODO: Set default values for attachments?
        }
        $this->setDefaults($defaults);

        CRM_Core_Form::addDefaultButtons(E::ts("Send %1 Emails", [1 => $participant_count - $no_email_count]));
    }


    function postProcess()
    {
        $values = $this->exportValues();
        $participant_count = count($this->_participantIds) - $this->getNoEmailCount();

        // store default values
        Civi::settings()->set('eventmessages_participant_send_template_id', $values['template_id']);
        Civi::settings()->set('eventmessages_participant_send_attachments', $values['attachments']);
//        Civi::settings()->set('eventmessages_participant_send_sender_email', $values['sender_email']);

        if (class_exists('Civi\Mailattachment\Form\Attachments')) {
            $values['attachments'] = \Civi\Mailattachment\Form\Attachments::processAttachments($this);
        }

        // init a queue
        $queue = CRM_Queue_Service::singleton()->create([
            'type' => 'Sql',
            'name' => 'eventmessages_email_task_' . CRM_Core_Session::singleton()->getLoggedInContactID(),
            'reset' => true,
        ]);
        // add a dummy item to display the 'upcoming' message
        $queue->createItem(new CRM_Eventmessages_SendMailJob(
            [],
            $values['template_id'],
            E::ts("Sending Emails %1 - %2", [
                1 => 1, // keep in mind that this is showing when the _next_ task is running
                2 => min(self::RUNNER_BATCH_SIZE, $participant_count)])
        ));

        // run query to get all participants
        $participant_id_list = implode(',', $this->_participantIds);
        $participant_query = CRM_Core_DAO::executeQuery("
            SELECT participant.id AS participant_id
            FROM civicrm_participant participant
            LEFT JOIN civicrm_email email
                   ON email.contact_id = participant.contact_id
                   AND email.is_primary = 1
                   AND email.on_hold = 0
            WHERE participant.id IN ({$participant_id_list})
              AND email.id IS NOT NULL
            ORDER BY participant.event_id ASC");

        // batch the participants into bite-sized jobs
        $current_batch = [];
        $next_offset = self::RUNNER_BATCH_SIZE;
        while ($participant_query->fetch()) {
            $current_batch[] = $participant_query->participant_id;
            if (count($current_batch) >= self::RUNNER_BATCH_SIZE) {
                $queue->createItem(
                    new CRM_Eventmessages_SendMailJob(
                        $current_batch,
                        $values['template_id'],
                        E::ts("Sending Emails %1 - %2", [
                            1 => $next_offset, // keep in mind that this is showing when the _next_ task is running
                            2 => $next_offset + self::RUNNER_BATCH_SIZE]),
                        $values['attachments']
                    )
                );
                $next_offset += self::RUNNER_BATCH_SIZE;
                $current_batch = [];
            }
        }

        // add final runner
        $queue->createItem(
            new CRM_Eventmessages_SendMailJob(
                $current_batch,
                $values['template_id'],
                E::ts("Finishing"),
                $values['attachments']
            )
        );

        // start a runner on the queue
        $runner = new CRM_Queue_Runner([
                'title'     => E::ts("Sending %1 Event Emails", [1 => $participant_count]),
                'queue'     => $queue,
                'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
                'onEndUrl'  => CRM_Core_Session::singleton()->readUserContext()
        ]);
        $runner->runAllViaWeb();
    }

    /**
     * Get a list of eligible templates
     * @return array
     *   list if id -> template name
     */
    private function getMessageTemplates(): array
    {
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

        return $list;
    }

    /**
     * Get a list of the available/allowed sender email addresses
     *
     * @return array
     *   list of sender options
     */
    private function getSenderOptions(): array
    {
        $list = [];
        $query = civicrm_api3(
            'OptionValue',
            'get',
            [
                'option_group_id' => 'from_email_address',
                'option.limit' => 0,
                'return' => 'value,label',
            ]
        );

        foreach ($query['values'] as $sender) {
            $list[$sender['value']] = $sender['label'];
        }

        return $list;
    }

    /**
     * Get the number of participants that
     *   do not have a viable email address
     */
    private function getNoEmailCount()
    {
        $participant_id_list = implode(',', $this->_participantIds);
        return CRM_Core_DAO::singleValueQuery("
            SELECT COUNT(DISTINCT(participant.id))
            FROM civicrm_participant participant
            LEFT JOIN civicrm_email email
                   ON email.contact_id = participant.contact_id
                   AND email.is_primary = 1
                   AND email.on_hold = 0
            WHERE participant.id IN ({$participant_id_list})
              AND email.id IS NULL");
    }
}

