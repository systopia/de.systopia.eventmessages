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

<div id="help">
  <div class="crm-clear-link">
    {$form.event_messages_disable_default.html}
    <label for="event_messages_disable_default">{$form.event_messages_disable_default.label}</label>
    <a onclick='CRM.help("{ts domain="de.systopia.eventmessages"}Disable Default Messages{/ts}", {literal}{"id":"id-disable-default","file":"CRM\/Eventmessages\/Form\/EventMessages"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.eventmessages"}Help{/ts}" class="helpicon">&nbsp;</a></div>
  </div>
</div>

<div>
  <div class="crm-section">
    <div class="label">{$form.event_messages_sender.label}</div>
    <div class="content">{$form.event_messages_sender.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.event_messages_reply_to.label}</div>
    <div class="content">{$form.event_messages_reply_to.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.event_messages_cc.label}</div>
    <div class="content">{$form.event_messages_cc.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.event_messages_bcc.label}</div>
    <div class="content">{$form.event_messages_bcc.html}</div>
    <div class="clear"></div>
  </div>
</div>

<h3>{ts domain="de.systopia.eventmessages"}Message Rules{/ts}</h3>
<div id="help">
  <div class="crm-clear-link">
    {$form.event_messages_execute_all_rules.html}
    <label for="event_messages_execute_all_rules">{$form.event_messages_execute_all_rules.label}</label>
    <a onclick='CRM.help("{ts domain="de.systopia.eventmessages"}Execute All Matching Rules{/ts}", {literal}{"id":"id-execute-all","file":"CRM\/Eventmessages\/Form\/EventMessages"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.eventmessages"}Help{/ts}" class="helpicon">&nbsp;</a></div>
</div>
<table>
  <thead>
    <tr>
      <th>{$form.is_active_1.label}</th>
      <th>{$form.from_1.label}</th>
      <th>{$form.to_1.label}</th>
      <th>{$form.roles_1.label}</th>
      <th>{$form.languages_1.label}</th>
      <th>{$form.template_1.label}</th>
    </tr>
  </thead>

  <tbody>
  {foreach from=$rules_list item=rule_index}
    <tr>
      {capture assign=field_name}is_active_{$rule_index}{/capture}
      <th>{$form.$field_name.html}</th>
      {capture assign=field_name}from_{$rule_index}{/capture}
      <th>{$form.$field_name.html}</th>
      {capture assign=field_name}to_{$rule_index}{/capture}
      <th>{$form.$field_name.html}</th>
      {capture assign=field_name}roles_{$rule_index}{/capture}
      <th>{$form.$field_name.html}</th>
      {capture assign=field_name}languages_{$rule_index}{/capture}
      <th>{$form.$field_name.html}</th>
      {capture assign=field_name}template_{$rule_index}{/capture}
      <th>{$form.$field_name.html}</th>
    </tr>
  {/foreach}
  </tbody>
</table>

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{literal}
<script>
cj(document).ready(function() {

  /**
   * Update the form so it never shows more than one 'empty' row
   */
  function hide_all_but_one_empty_rows() {
    // first: show all:
    cj("[name^=template_]")
            .parent()
            .parent()
            .show();

    // the hide all but one empty one
    cj("[name^=template_]")
            .filter(function() {return !cj(this).val();})
            .slice(1)
            .parent()
            .parent()
            .hide();
  }

  // attach the function to dropdown
  cj("[name^=template_]").change(hide_all_but_one_empty_rows);

  // and trigger once with buildup
  hide_all_but_one_empty_rows();
});
</script>
{/literal}