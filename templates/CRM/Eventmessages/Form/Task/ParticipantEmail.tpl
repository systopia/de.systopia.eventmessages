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
<div class="crm-block crm-form-block">

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

  {if !empty($supports_attachments)}
    <div class="crm-accordion-wrapper">
      <div class="crm-accordion-header">{ts}Attachments{/ts}</div>
      <div class="crm-accordion-body">
          {include file="Civi/Mailattachment/Form/Attachments.tpl"}
      </div>
    </div>
  {else}
    <div class="help">
        {capture assign="mailattachment_link"}<a href="https://github.com/systopia/de.systopia.mailattachment">Mail Attachments</a>{/capture}
      <p>{ts 1=$mailattachment_link}If you would like to add file attachments to e-mails, consider installing the %1 extension which provides a framework for different attachment types.{/ts}</p>
      <p>{ts}This includes e.g. attaching existing files per contact.{/ts}</p>
    </div>
  {/if}

  <div id="help">{ts}The email parameters (sender, cc, bcc, reply-to) will be taken from the EventMessages configuration of the respective event(s).{/ts}</div>

  <br>
  <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

</div>
{/crmScope}
