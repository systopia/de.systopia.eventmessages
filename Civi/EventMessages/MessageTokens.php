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
use \Symfony\Contracts\EventDispatcher\Event;

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

    /** @var array list of tokens in the current template */
    protected $template_tokens;

    /** @var string raw template data */
    protected $template_data;

    public function __construct()
    {
        $this->tokens = [];
        $this->template_data = null;
        $this->template_tokens = null;
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
     * Specify the template's data, so the contained
     *   tokens can be extracted
     *
     * This will also reset the known tokens
     *
     * @param string $template_data
     */
    public function setTemplateData($template_data)
    {
        $this->template_data = $template_data;
        $this->template_tokens = null;
    }

    /**
     * Specify the template's data by providing a template ID
     *
     * @param integer $template_id
     *   ID of CiviCRM message template
     *
     * @param boolean $cache
     *   cache the template content?
     */
    public function setTemplateId($template_id, $cache = true)
    {
        static $template_cache = [];
        if ($cache && isset($template_cache[$template_id])) {
            $this->setTemplateData($template_cache[$template_id]);
            return;
        }

        // load message template
        $message_template = \civicrm_api3('MessageTemplate', 'getsingle', ['id' => $template_id]);
        $template_data = $message_template['msg_subject'] . $message_template['msg_text'] . $message_template['msg_html'];
        if ($cache) {
            $template_cache[$template_id] = $template_data;
        }
        $this->setTemplateData($template_data);
    }

    /**
     * Check if the given token is required
     *
     * @param string $name
     *   token name
     *
     * @return boolean
     *   true if the token is required by the token (or no template was set)
     */
    public function requiresToken($name)
    {
        if (!isset($this->template_data)) {
            // no template set -> we don't know
            return true;
        }

        // extract template tokens on the fly
        if (!isset($this->template_tokens)) {
            // we have template_data but have not derived the tokens - let's do that
            $this->template_tokens = [];
            if (preg_match_all('/[$](\w+)/', $this->template_data, $matches)) {
                foreach ($matches[1] as $token) {
                    $this->template_tokens[$token] = true;
                }
            }
        }

        // finally, return if the token is used
        return isset($this->template_tokens[$name]);
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
