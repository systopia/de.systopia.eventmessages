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

/**
 * Class MessageTokens
 *
 * @package Civi\EventMessages
 *
 * This event allows you to add custom tokens to the CiviCRM Event messages
 */
class MessageTokens extends Event
{
    /** @var array holds the final tokens (smarty variables) */
    protected $tokens;

    public function __construct()
    {
        $this->tokens = [];
    }

    /**
     * Get all tokens as a single multi-dimension array
     *
     * @return array
     *   tokens
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * Set a given token
     *
     * @param string $name
     *   the token name
     * @param mixed $data
     *   the token data. Typically a scalar, or an array for multi-level tokens
     * @param boolean $enhance
     *   use true to run the data through the enhance function, which adds convenience tokens for some structures
     */
    public function setToken($name, $data, $enhance = true)
    {
        if ($enhance && is_array($data)) {
            $this->tokens[$name] = $this->enhanceTokens($data);
        } else {
            $this->tokens[$name] = $data;
        }
    }

    /**
     * Some enhancements / beautification of the tokens passed to the
     *   message templates
     *
     * @param array $tokens
     *    current tokens
     *
     * @return array
     *    enhanced tokens
     */
    public function enhanceTokens($tokens)
    {
        // step 1: of all array data, offer a _string version
        foreach (array_keys($tokens) as $token_name) {
            if (is_array($tokens[$token_name])) {
                $tokens["{$token_name}_string"] = implode(', ', $tokens[$token_name]);
            }

            // todo: more stuff?
        }
        return $tokens;
    }

}
