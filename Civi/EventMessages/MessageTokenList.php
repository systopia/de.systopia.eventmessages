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
     * @param boolean $ordered
     *   if true, will sort the list before returning
     *
     * @return array
     *   token key => token description (html)
     */
    public function getTokens($ordered = false)
    {
        if ($ordered) {
            ksort($this->token_list);
        }
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
        $disabled_tokens_data = file_get_contents(E::path('resources/disabled_tokens.list'));
        $disabled_tokens = explode("\n", $disabled_tokens_data);

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

                $token_name = "{$prefix}.{$field['name']}";
                if (!in_array($token_name, $disabled_tokens)) {
                    $this->addToken('$'.$token_name, $description);
                    // add the enhanced tokens
                    if (!empty($field['serialize'])) {
                        $this->addToken('$'."{$token_name}_string", "{$description} (String)");
                    }
                }
            }
        }

        // add additional stuff that the API returns, but doesn't list in getfields
        $this->addToken('$participant.participant_status',         E::ts("Participant Status"));
        $this->addToken('$participant.participant_role',           E::ts("Participant Role"));
        $this->addToken('$participant.participant_register_date',  E::ts('Registration Time/Date. You can format this value using smarty modifiers, e.g. <code>{$participant.participant_register_date|crmDate}</code> or <code>{$participant.participant_register_date|date_format:"%d.%m.%Y"}</code>.'));
        $this->addToken('$participant.participant_source',         E::ts("Participant Source"));
        $this->addToken('$participant.participant_note',           E::ts("Participant Note"));
        $this->addToken('$participant.participant_fee_level',      E::ts("Participant Fee Level"));
        $this->addToken('$participant.participant_fee_amount',     E::ts('Participant Fee Amount. You can format this value using smarty modifiers, e.g. <code>{$participant.participant_fee_amount|crmMoney:$participant.participant_fee_currency}</code>.'));
        $this->addToken('$participant.participant_fee_currency',   E::ts("Participant Fee Currency"));
    }
}
