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

use CRM_Eventmessages_ExtensionUtil as E;
use Civi\EventMessages\MessageTokenList as MessageTokenList;

class CRM_Eventmessages_Page_TokenList extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle(E::ts('EventMessages: List of (potential) E-Mail Tokens'));

    // collect all tokens
    $message_tokens = new MessageTokenList();
    Civi::dispatcher()->dispatch('civi.eventmessages.tokenlist', $message_tokens);
    $this->assign('token_list', $message_tokens->getTokens(TRUE));

    parent::run();
  }

}
