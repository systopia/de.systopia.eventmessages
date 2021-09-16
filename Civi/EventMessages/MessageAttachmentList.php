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
 * Class MessageAttachmentList
 *
 * @package Civi\EventMessages
 *
 * This event will generate a list of all *potential* attachments the MessageAttachments could have.
 *  This is purely for usability / documentation purposes
 */
class MessageAttachmentList extends Event
{
    /**
     * @var array holds the list of known (potential) attachments
     *
     * An entry to the attachment list is an array with the following fields:
     * 'id'         => unique ID for the given attachment
     * 'title'      => title of the file
     * 'mime_type'  => mime type of the content
     */
    protected $attachment_list;

    public function __construct()
    {
        $this->attachment_list = [];
    }

    /**
     * Register a new attachment type
     *
     * @param $id string
     *   attachment ID
     * @param $title string
     *   attachment title, visible to user
     * @param $mime_type string
     *   attachment mime type
     * @param false $override
     */
    public function registerAttachment($id, $title, $mime_type, $override = false)
    {
        if (isset($this->attachment_list[$id]) && !$override) {
            \Civi::log()->warning("EventMessages: MessageAttachmentList - attachment [{$id}] is registered twice.");
        }
        $this->attachment_list[$id] = [
            'id'         => $id,
            'title'      => $title,
            'mime_type'  => $mime_type,
        ];
    }

    /**
     * Get a list of all registered attachments
     *
     * @return array of arrays with the following fields
     * 'id'         => unique ID for the given attachment
     * 'title'      => title of the attachment
     * 'mime_type'  => mime type of the content
     */
    public function getRegisteredAttachments()
    {
        return $this->attachment_list;
    }

    /**
     * Get all tokens
     *
     * @param boolean $ordered
     *   if true, will sort the list before returning
     *
     * @return array
     *   token key => token description (html)
     */
    public function getTokens($ordered = false)
    {
        if ($ordered) {
            ksort($this->attachment_list);
        }
        return $this->attachment_list;
    }

    /**
     * Add a token along with a description
     *
     * @param string $key
     *   token key as addressed by smarty, including the $
     *
     * @param string $description
     *   html description of the token
     */
    public function addToken($key, $description)
    {
        $this->attachment_list[$key] = $description;
    }

    /**
     * Register the default attachments
     *
     * @see MessageAttachments:renderDefaultAttachments
     */
    public static function registerDefaultAttachments(MessageAttachmentList $event)
    {
        // register event .ical file
        $event->registerAttachment(
            'ical',
            E::ts("Event (iCalendar)"),
            'text/calendar'
        );
    }


    /**
     * Get a list of available attachments
     *
     * @return array
     *   'key' => 'label' list of generally available attachments
     */
    public static function getAttachmentList()
    {
        $attachment_collector = new MessageAttachmentList();
        \Civi::dispatcher()->dispatch('civi.eventmessages.registerAttachments', $attachment_collector);
        return $attachment_collector->getRegisteredAttachments();
    }
}
