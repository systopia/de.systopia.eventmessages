{*-------------------------------------------------------+
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
+-------------------------------------------------------*}

{crmScope extensionKey='de.systopia.eventmessages'}
{if $no_email_count}
  <div id="help">{ts 1=$no_email_count}<b>Warning:</b> %1 participant(s) have no viable email address, an email will not be sent to them{/ts}</div>
{/if}

{*  <div class="crm-section">*}
{*    <div class="label">{$form.sender_email.label}</div>*}
{*    <div class="content">{$form.sender_email.html}</div>*}
{*    <div class="clear"></div>*}
{*  </div>*}
  <div class="crm-section">
    {capture assign=label_help}{ts}Template Help{/ts}{/capture}
    <div class="label">{$form.template_id.label}{help id="id-token-help" title=$label_help}</div>
    <div class="content">{$form.template_id.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.attachments.label}</div>
    <div class="content">{$form.attachments.html}</div>
    <div class="clear"></div>
  </div>

  <div id="help">{ts}The email parameters (sender, cc, bcc, reply-to) will be taken from the EventMessages configuration of the respective event(s).{/ts}</div>

  <br>
  <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
{/crmScope}
