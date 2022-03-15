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

namespace Civi\EventMessages;

use CRM_Eventmessages_ExtensionUtil as E;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AttachmentProvider implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'civi.mailattachment.attachmentTypes' => 'getAttachmentTypes',
        ];
    }

    public static function getAttachmentTypes($event)
    {
        // Add attachment provider for iCal files.
        $event->attachment_types['ical'] = [
            'label' => E::ts('iCalendar file'),
            'controller' => '\Civi\EventMessages\AttachmentProvider\ICal',
            'context' => [
                'entity_types' => ['event', 'participant'],
            ],
        ];

        // TODO: Add attachment provider for rendering message templates as PDF
        //   Instead of registering a separate provider for each template, the template should be selected in the
        //   AttachmentProvider form.
//        foreach (\CRM_Core_BAO_MessageTemplate::getMessageTemplates() as $message_template_id => $message_template_title) {
//            $event->attachment_types['pdf_message_template_' . $message_template_id] = [
//                'label' => E::ts("Message Template as PDF - %1", [1 => $message_template_title]),
//                'controller' => self::class,
//            ];
//        }
    }
}
