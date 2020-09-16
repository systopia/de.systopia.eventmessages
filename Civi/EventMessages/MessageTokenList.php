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


namespace Civi\EventMessages;
use Symfony\Component\EventDispatcher\Event;
use CRM_Eventmessages_ExtensionUtil as E;

/**
 * Class MessageTokenList
 *
 * @package Civi\EventMessages
 *
 * This event will generate a list of all *potential* tokens the MessageTokens could have.
 *  This is purely for documentation purposes
 */
class MessageTokenList extends Event
{
    /** @var array holds the final tokens (smarty variables) */
    protected $token_list;

    public function __construct()
    {
        $this->token_list = [];
        $this->addDefaultTokens();
    }

    /**
     * Get all tokens
     *
     * @return array
     *   token key => token description (html)
     */
    public function getTokens()
    {
        return $this->token_list;
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
        $this->token_list[$key] = $description;
    }

    /**
     * Add the default tokens, i.e. the complete contact, event and participant API fields
     * @todo add custom fields
     */
    protected function addDefaultTokens()
    {
        $entities = [
            'Contact'     => 'contact',
            'Event'       => 'event',
            'Participant' => 'participant'
        ];
        foreach ($entities as $entity => $prefix) {
            $fields = \civicrm_api3($entity, 'getfields');

            foreach ($fields['values'] as $field) {
                // get the description
                $description = $field['title'] ?? $field['description'] ?? E::ts("no description available");

                // handle custom fields
                if (preg_match('/^custom_([0-9]+)$/', $field['name'], $match)) {
                    // this is a custom field
                    $field_specs = \CRM_Eventmessages_CustomData::getFieldSpecs($match[1]);
                    $group_name = \CRM_Eventmessages_CustomData::getGroupName($field_specs['custom_group_id']);
                    $field['name'] = "{$group_name}.{$field_specs['name']}";
                }

                $this->addToken('$'."{$prefix}.{$field['name']}", $description);
                // add the enhanced tokens
                if (!empty($field['serialize'])) {
                    $this->addToken('$'."{$prefix}.{$field['name']}_string", "{$description} (String)");
                }
            }
        }
    }
}
