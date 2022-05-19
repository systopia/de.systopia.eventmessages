<?php
/*-------------------------------------------------------+
| SYSTOPIA Event Messages                                |
| Copyright (C) 2022 SYSTOPIA                            |
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

class CRM_Eventmessages_ApiWrapper implements API_Wrapper
{
    /**
     * If 'template_id' is set (and 'id' isn't), clone the event specified by the template_id,
     *  and then continue with the create/update process
     *
     * @param array $apiRequest
     *
     * @return array $apiRequest
     */
    public function fromApiInput($apiRequest)
    {
        if (strtolower($apiRequest['entity']) == 'event' && strtolower($apiRequest['action']) == 'create') {
            // this is an Event.create call
            $params = $apiRequest['params'];
            if (empty($params['id']) && !empty($params['template_id'])) {
                // this means:
                // 1. the event doesn't exist yet (no 'id' given)
                // 2. a template_id was submitted
                // => we need to clone the event
                $cloned_event = CRM_Event_BAO_Event::copy($params['template_id']);
                $params['id'] = $cloned_event->id;
                unset($params['template_id']);
                $params['is_template'] = 0;
                $params['template_title'] = '';
            }
        }
        return $apiRequest;
    }

    /**
     * Simply add the 'template_id' field to the field list
     *
     * @param array $apiRequest
     *
     * @return array $apiRequest
     */
    public function toApiOutput($apiRequest, $result) {
        if (strtolower($apiRequest['entity']) == 'event' && strtolower($apiRequest['action']) == 'getfields') {
            $result['values']['template_id'] = [
                'name' => 'template_id',
                'type' => CRM_Utils_Type::T_INT,
                'title' => E::ts("Event Template ID"),
                'description' => E::ts("Pass an event template ID to copy values from there."),
            ];
        }
        return $result;
    }
}
