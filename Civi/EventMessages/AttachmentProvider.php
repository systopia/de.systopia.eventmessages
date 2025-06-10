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

declare(strict_types = 1);

namespace Civi\EventMessages;

use CRM_Eventmessages_ExtensionUtil as E;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AttachmentProvider implements EventSubscriberInterface {

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      'civi.mailattachment.attachmentTypes' => 'getAttachmentTypes',
    ];
  }

  public static function getAttachmentTypes($event) {
    // Add attachment provider for iCal files.
    $event->attachment_types['ical'] = [
      'label' => E::ts('iCalendar file'),
      'controller' => \Civi\EventMessages\AttachmentProvider\ICal::class,
      'context' => [
        'entity_types' => ['participant'],
      ],
    ];

    // Add attachment provider for rendering message templates as PDF.
    $event->attachment_types['message_template_pdf'] = [
      'label' => E::ts('Message Template as PDF'),
      'controller' => \Civi\EventMessages\AttachmentProvider\MessageTemplatePDF::class,
      'context' => [
        'entity_types' => ['participant'],
      ],
    ];
  }

}
