<?php
/*-------------------------------------------------------+
| SYSTOPIA Event Messages                                |
| Copyright (C) 2021 SYSTOPIA                            |
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


namespace Civi\EventMessages;
use Symfony\Component\EventDispatcher\Event;
use CRM_Eventmessages_ExtensionUtil as E;

/**
 * Class MessageAttachments
 *
 * @package Civi\EventMessages
 *
 * This event allows you to add custom attachments to the CiviCRM Event messages
 */
class MessageAttachments extends Event
{
    const PARTICIPANT_CACHED_ATTRIBUTES = ['id', 'event_id', 'status_id', 'contact_id', 'role_id'];

    /** @var integer ID of the participant to render attachments for */
    protected $participant_id;

    /** @var array list of attachment IDs (strings) */
    protected $requested_attachment_ids;

    /** @var array list of the attachments gathered */
    protected $attachments = [];

    /** @var array list of participant IDs as a context. This should be used for caching if provided */
    protected $participant_ids;

    /** @var array cached participants, internal use only */
    private $participant_cache = null;

    /** @var array cached events, internal use only */
    private $event_cache = [];

    /**
     * @param $participant_id integer
     *      ID of the participant to render attachments for
     *
     * @param $attachment_ids array
     *       list of the attachments gathered
     *
     * @param array $participant_ids
     *       list of participant IDs as a context. This should be used for caching if provided
     */
    public function __construct($participant_id, $attachment_ids, $participant_ids = null)
    {
        $this->participant_id = $participant_id;
        $this->requested_attachment_ids = $attachment_ids;
        $this->participant_ids = $participant_ids;
        $this->attachments = [];
    }

    /**
     * Check whether the given attachment is requested
     *
     * @param string $attachment_id
     *     the attachment ID
     *
     * @return boolean
     *     true if this attachment id is requested
     */
    public function isAttachementRequested($attachment_id)
    {
        return in_array($attachment_id, $this->requested_attachment_ids);
    }

    /**
     * Add a new attachment
     *
     * @param $id string
     *   attachment ID
     * @param $title string
     *   attachment title, visible to user
     * @param $mime_type string
     *   attachment mime type
     * @param $file_path string
     *   file containing the data
     * @param boolean $override
     *   should a previously registered attachment of the same ID be overwritten?
     */
    public function addAttachment($id, $title, $mime_type, $file_path, $override = false)
    {
        if (isset($this->attachments[$id]) && !$override) {
            \Civi::log()->warning("EventMessages: MessageAttachmentList - attachment [{$id}] is presented twice (no override).");
            return;
        }
        $this->attachments[$id] = [
            'id'         => $id,
            'title'      => $title,
            'mime_type'  => $mime_type,
            'file_path'  => $file_path,
        ];
    }

    /**
     * Get the given participant ID
     *
     * @return integer participant ID
     */
    public function getParticipantID()
    {
        return $this->participant_id;
    }

    /**
     * Get a list of upcoming participant IDs (if available)
     *
     * @return array|null participant IDs
     */
    public function getParticipantIDs()
    {
        return $this->participant_ids;
    }

    /**
     * Get the given event ID
     *
     * @return integer event ID
     */
    public function getEventID()
    {
        $participant = $this->getParticipant();
        return $participant['event_id'];
    }

    /**
     * Are the event and participant data pre-cached (using the $participant_ids field)?
     *
     * @return boolean
     */
    public function isPrecached()
    {
        return is_array($this->participant_ids);
    }

    /**
     * Get the given participant data
     *
     * @return array|null participant data or null if not found
     */
    public function getParticipant()
    {
        $participant_id = $this->getParticipantID();
        if ($this->isPrecached()) {
            // we have a pre-caching going on: just warm up the cache if that hasn't been done yet
            if ($this->participant_cache === null) {
                $this->participant_cache = [];
                $participant_query = \civicrm_api3('Participant', 'get', [
                    'id' => ['IN' => $this->participant_ids],
                    'option.limit' => 0,
                    'return' => implode(',', self::PARTICIPANT_CACHED_ATTRIBUTES),
                ]);
                foreach ($participant_query['values'] as $participant) {
                    $this->participant_cache[$participant['id']] = $participant;
                }
            }
        } else {
            // this means, no pre-caching
            if (!isset($this->participant_cache[$participant_id])) {
                try {
                    $this->participant_cache[$participant_id] = \civicrm_api3('Participant', 'getsingle', [
                        'id' => $participant_id,
                        'return' => implode(',', self::PARTICIPANT_CACHED_ATTRIBUTES),
                    ]);
                } catch (\CiviCRM_API3_Exception $ex) {
                    \Civi::log()->debug("EventMessages:Attachments: cannot find participant [{$participant_id}]");
                }
            }
        }
        return $this->participant_cache[$participant_id] ?? null;
    }

    /**
     * Get the given event data by ID
     * @param $event_id integer
     * @return array|null event data or null if not found
     */
    public function getEvent($event_id)
    {
        $event_id = $this->getEventID();
        if (!isset($this->event_cache[$event_id])) {
            try {
                $this->event_cache[$event_id] = \civicrm_api3('Event', 'getsingle', [
                    'id' => $event_id,
                ]);
            } catch (\CiviCRM_API3_Exception $ex) {
                \Civi::log()->debug("EventMessages:Attachments: cannot find event [{$event_id}]");
            }
        }
        return $this->event_cache[$event_id] ?? null;
    }

    /**
     * Get the list of currently added attachments
     *
     * @return array of attachments in the form:
     * 'id'         => unique ID for the given attachment
     * 'title'      => title of the file
     * 'mime_type'  => mime type of the content
     * 'file_path'  => path to a file containing the data
     */
    public function getAttachments()
    {
        return $this->attachments;
    }



    /**
     * Get the attachments for a given participant using the symfony event to gather them
     *
     * @param $participant_id integer
     *   ID of the participant object
     *
     * @param array $attachment_ids
     *   list of attachment IDs
     *
     * @param $participant_ids array (optional)
     *   list of participant IDs to come, should be used for caching
     *
     * @return array attachments in the form:
     * 'id'         => unique ID for the given attachment
     * 'title'      => title of the file
     * 'mime_type'  => mime type of the content
     * 'file_path'  => path to a file containing the data
     *
     * @note this function could return more or less attachments than requested,
     *   since the consumers of the hook can decide that the attachment is
     *   not there or generate one that wasn't requested
     */
    public static function renderAttachments($participant_id, $attachment_ids, $participant_ids = null)
    {
        $render_event = new MessageAttachments($participant_id, $attachment_ids, $participant_ids);
        \Civi::dispatcher()->dispatch('civi.eventmessages.renderAttachments', $render_event);
        return $render_event->getAttachments();
    }

    /*
     * Convert the data structure received from ::renderAttachments()
     *   to the one understood by MessageTemplate.send
     *
     * @param array $attachments
     */
    public static function convertToTemplateSend($attachments)
    {
        // todo: convert
        return $attachments;
    }

    /**
     * Get the attachments for a given participant using the symfony event to gather them,
     *   and then convert to the atta
     *
     * @param $participant_id integer
     *   ID of the participant object
     *
     * @param array $attachment_ids
     *   list of attachment IDs
     *
     * @param $participant_ids array (optional)
     *   list of participant IDs to come, should be used for caching
     *
     * @return array attachments in the form:
     * 'id'         => unique ID for the given attachment
     * 'title'      => title of the file
     * 'mime_type'  => mime type of the content
     * 'file_path'  => path to a file containing the data
     *
     * @note this function could return more or less attachments than requested,
     *   since the consumers of the hook can decide that the attachment is
     *   not there or generate one that wasn't requested
     */
    public static function renderTemplateSendAttachments($participant_id, $attachment_ids, $participant_ids = null)
    {
        $template_send_attachments = [];
        $attachments = self::renderAttachments($participant_id, $attachment_ids, $participant_ids);
        foreach ($attachments as $attachment) {
            $template_send_attachments[] = [
                'fullPath'  => $attachment['file_path'],
                'mime_type' => $attachment['mime_type'],
                'cleanName' => $attachment['title'],
            ];
        }

        return $template_send_attachments;
    }





    /**
     * Render the default attachments
     *
     * @see MessageAttachmentList:registerDefaultAttachments
     */
    public static function renderDefaultAttachments(MessageAttachments $renderAttachmentEvent)
    {
        /** @var $ical_cache array internal cache for ical data, indexed by event_id */
        static $ical_cache = [];
        static $pdf_message_template_cache = [];
        $event_id = $renderAttachmentEvent->getEventID();
        $participant = $renderAttachmentEvent->getParticipant();

        if ($renderAttachmentEvent->isAttachementRequested('ical')) {
            // simply use the CiviCRM ical function, copied from CRM_Utils_ICalendar
            // let's see if we have it
            if (!isset($ical_cache[$event_id])) {
                // no? render ical data
                $template = \CRM_Core_Smarty::singleton();
                $event_data = \CRM_Event_BAO_Event::getCompleteInfo('19800101', null, $event_id, null, false);
                foreach (['title', 'description', 'event_type', 'location', 'contact_email'] as $field) {
                    if (isset($event_data[0][$field])) {
                        $event_data[0][$field] = html_entity_decode($event_data[0][$field], ENT_QUOTES | ENT_HTML401, 'UTF-8');
                    }
                }
                $template->assign('events', $event_data);
                $template->assign('timezone', @date_default_timezone_get());
                $ical_data = $template->fetch('CRM/Core/Calendar/ICal.tpl');
                $ical_data = preg_replace('/(?<!\r)\n/', "\r\n", $ical_data);
                // write to tmp file
                $tmp_file = \System::mktemp("event_{$event_id}.ical");
                file_put_contents($tmp_file, $ical_data);
                $ical_cache[$event_id] = $tmp_file;
            }

            // add file
            $renderAttachmentEvent->addAttachment(
                'ical',
                E::ts("event.ical"),
                'text/calendar',
                $ical_cache[$event_id]
            );
        }
        foreach (\CRM_Core_BAO_MessageTemplate::getMessageTemplates() as $message_template_id => $message_template_title) {
            if ($renderAttachmentEvent->isAttachementRequested('pdf_message_template_' . $message_template_id)) {
                if (!isset($pdf_message_template_cache[$event_id][$message_template_id])) {
                    $pdf = \CRM_Eventmessages_GenerateLetter::generateLetterFor(
                        [
                            'participant_id' => $participant['id'],
                            'event_id' => $event_id,
                            'from' => $participant['status_id'],
                            'to' => $participant['status_id'],
                            'rule' => 0,
                            'template_id' => $message_template_id,
                        ]
                    );
                    $filename = \System::mktemp('eventmessages_attachment_template_' . $message_template_id . '_participant_' . $participant['id'] . '.pdf');
                    file_put_contents($filename, $pdf);
                    $pdf_message_template_cache[$event_id][$message_template_id] = $filename;
                }
                $renderAttachmentEvent->addAttachment(
                    'pdf_message_template_' . $message_template_id,
                    $message_template_title . '.pdf',
                    'application/pdf',
                    $pdf_message_template_cache[$event_id][$message_template_id]
                );
            }
        }
    }
}
