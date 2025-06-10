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
use CRM_Mailattachment_ExtensionUtil as E;
use Civi\Mailattachment\AttachmentType\AttachmentTypeInterface;

class MessageTemplatePDF implements AttachmentTypeInterface {

  /**
   * {@inheritDoc}
   */
  public static function buildAttachmentForm(&$form, $attachment_id, $prefix = '', $defaults = []) {
    $form->add(
        'select',
        $prefix . 'attachments--' . $attachment_id . '--template_id',
        E::ts('Message Template'),
        self::getMessageTemplates(),
        TRUE,
        ['class' => 'crm-select2 huge']
    );

    $form->setDefaults(
        [
          $prefix . 'attachments--' . $attachment_id . '--template_id' => $defaults['template_id'],
        ]
    );

    return [
      $prefix . 'attachments--' . $attachment_id . '--template_id' => 'attachment-message_template_pdf-template_id',
    ];
  }

  public static function getAttachmentFormTemplate($type = 'tpl') {
    return $type == 'hlp' ? 'Civi/EventMessages/AttachmentProvider/MessageTemplatePDF.' . $type : NULL;
  }

  /**
   * {@inheritDoc}
   */
  public static function processAttachmentForm(&$form, $attachment_id, $prefix = '') {
    $values = $form->exportValues();
    return [
      'template_id' => $values[$prefix . 'attachments--' . $attachment_id . '--template_id'],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function buildAttachment($context, $attachment_values) {
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

    $pdf = \CRM_Eventmessages_GenerateLetter::generateLetterFor(
        [
          'participant_id' => $context['entity_id'],
          'event_id' => $event_id,
          'template_id' => $attachment_values['template_id'],
        ]
    );
    $filename = \System::mktemp('eventmessages_attachment_template_' . $attachment_values['template_id'] . '_participant_' . $context['entity_id'] . '.pdf');
    file_put_contents($filename, $pdf);
    $attachment = [
      'fullPath' => $filename,
      'mime_type' => 'application/pdf',
      'cleanName' => self::getMessageTemplates()[$attachment_values['template_id']] . '.pdf',
    ];

    return $attachment ?? NULL;
  }

  /**
   * Retrieves a list of message templates.
   *
   * @return array
   *   list if id -> template name
   */
  protected static function getMessageTemplates(): array {
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

}
