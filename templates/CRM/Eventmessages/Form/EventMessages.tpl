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

<div>
  <div class="crm-section">
    <div class="label">{$form.eventmessages_disable_default.label}</div>
    <div class="content">{$form.eventmessages_disable_default.html}</div>
    <div class="clear"></div>
  </div>
</div>

<table>
  <thead>
    <tr>
      <th>{$form.is_active_1.label}</th>
      <th>{$form.from_1.label}</th>
      <th>{$form.to_1.label}</th>
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
  // TODO: implement "always hide all empty rows except one";
});
</script>
{/literal}