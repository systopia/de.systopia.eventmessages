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
{if $no_address_count}
  <div id="help">{ts 1=$no_address_count}<b>Warning:</b> %1 participant(s) do not have a viable postal address. You might want to exclude them from being generated a letter.{/ts}</div>
{/if}
  <div class="crm-section">
    {capture assign=label_help}{ts}Template Help{/ts}{/capture}
    <div class="label">{$form.template_id.label} {help id="id-token-help" title=$label_help}</div>
    <div class="content">{$form.template_id.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    {capture assign=help_title}{ts}Help{/ts}{/capture}
    <div class="label">{$form.address_only.label} {help id="id-address-only" title=$help_title}</div>
    <div class="content">{$form.address_only.html}</div>
    <div class="clear"></div>
  </div>

  <br>
  <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
{/crmScope}
