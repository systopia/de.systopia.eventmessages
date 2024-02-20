<?php
/*-------------------------------------------------------+
| SYSTOPIA Event Communication                           |
| Copyright (C) 2022 SYSTOPIA                            |
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

namespace Civi\EventMessages\AttachmentProvider;

use Civi\Api4\Participant;
use CRM_Eventmessages_ExtensionUtil as E;
use Civi\Mailattachment\AttachmentType\AttachmentTypeInterface;

/**
 * Attachment provider for generating iCal files for a given event.
 */
class ICal implements AttachmentTypeInterface
{
    /**
     * {@inheritDoc}
     */
    public static function buildAttachmentForm(&$form, $attachment_id, $prefix = '', $defaults = [])
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public static function processAttachmentForm(&$form, $attachment_id, $prefix = '')
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public static function buildAttachment($context, $attachment_values)
    {
        if ($context['entity_type'] == 'participant') {
            // Warm up the cache if all entity IDs are given.
            if (empty(\Civi::$statics[__CLASS__]['participants']) && isset($context['entity_ids'])) {
                \Civi::$statics[__CLASS__]['participants'] = Participant::get(FALSE)
                    ->addWhere('id', 'IN', $context['entity_ids'])
                    ->addSelect('event_id')
                    ->execute()
                    ->indexBy('id');
            }
            else {
                \Civi::$statics[__CLASS__]['participants'][$context['entity_id']] = Participant::get(FALSE)
                    ->addWhere('id', '=', $context['entity_id'])
                    ->addSelect('event_id')
                    ->execute()
                    ->indexBy('id')
                    ->single();
            }
            $event_id = \Civi::$statics[__CLASS__]['participants'][$context['entity_id']]['event_id'];
        }

        // simply use the CiviCRM ical function, copied from CRM_Utils_ICalendar
        // let's see if we have it
        if (!isset(\Civi::$statics[__CLASS__]['ical_files'][$event_id])) {
            // no? render ical data
            $template = \CRM_Core_Smarty::singleton();
            $event_data = \CRM_Event_BAO_Event::getCompleteInfo(null, null, $event_id, null, false);
            foreach (['title', 'description', 'event_type', 'location', 'contact_email'] as $field) {
                if (isset($event_data[0][$field])) {
                    $event_data[0][$field] = html_entity_decode($event_data[0][$field], ENT_QUOTES | ENT_HTML401, 'UTF-8');
                }
            }
            $template->assign('events', $event_data);
            $template->assign('timezone', @date_default_timezone_get());
            $ical_data = $template->fetch('CRM/Core/Calendar/ICal.tpl');
            $ical_data = preg_replace('/(?<!\r)\n/', "\r\n", $ical_data);

            // TODO: Utilize new Core method, once it's there, see
            //       https://github.com/civicrm/civicrm-core/pull/26980
            // $ical_data = \CRM_Utils_ICalendar::createCalendarFileForEvent($event_id);

            // write to tmp file
            $tmp_file = \System::mktemp("event_{$event_id}.ics");
            file_put_contents($tmp_file, $ical_data);
            \Civi::$statics[__CLASS__]['ical_files'][$event_id] = $tmp_file;
        }

        return [
            'fullPath' => \Civi::$statics[__CLASS__]['ical_files'][$event_id],
            'mime_type' => 'text/calendar',
            'cleanName' => E::ts("event.ics"),
        ];
    }
}
