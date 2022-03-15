<?php
/*-------------------------------------------------------+
| SYSTOPIA Event Messages                                |
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

use CRM_Eventmessages_ExtensionUtil as E;
use Civi\Mailattachment\Form\Task\AttachmentsTrait;

/**
 * Send e-mail to contacts based on participants task, supporting attachments.
 */
class CRM_Eventmessages_Form_Task_ParticipantEmailAttachments extends CRM_Eventmessages_Form_Task_ParticipantEmail
{
    use AttachmentsTrait;

    public function getTemplateFileName()
    {
        return 'CRM/Eventmessages/Form/Task/ParticipantEmail.tpl';
    }
}
