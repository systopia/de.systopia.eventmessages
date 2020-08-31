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

<div class="crm-block crm-form-block crm-event-manage-eventmessages-form-block">

  <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="top"}
  </div>

  <table class="form-layout-compressed">

    <tr class="crm-event-manage-eventmessages-form-block-event_messages_disable_default">
      <td class="label">{$form.event_messages_disable_default.label}  {help id="id-disable-default" title=$form.event_messages_disable_default.label}</td>
      <td>{$form.event_messages_disable_default.html}</td>
    </tr>

    <tr class="crm-event-manage-eventmessages-form-block-event_messages_sender">
      <td class="label">{$form.event_messages_sender.label}</td>
      <td>{$form.event_messages_sender.html}</td>
    </tr>

    <tr class="crm-event-manage-eventmessages-form-block-event_messages_reply_to">
      <td class="label">{$form.event_messages_reply_to.label}</td>
      <td>{$form.event_messages_reply_to.html}</td>
    </tr>

    <tr class="crm-event-manage-eventmessages-form-block-event_messages_cc">
      <td class="label">{$form.event_messages_cc.label}</td>
      <td>{$form.event_messages_cc.html}</td>
    </tr>

    <tr class="crm-event-manage-eventmessages-form-block-event_messages_bcc">
      <td class="label">{$form.event_messages_bcc.label}</td>
      <td>{$form.event_messages_bcc.html}</td>
    </tr>

  </table>

  <div class="crm-block crm-manage-events crm-accordion-wrapper">
    <div class="crm-accordion-header">{ts domain="de.systopia.eventmessages"}Message Rules{/ts}</div>
    <div class="crm-accordion-body">
      <table class="form-layout-compressed">

        <tr class="crm-event-manage-eventmessages-form-block-event_messages_execute_all_rules">
          <td class="label">{$form.event_messages_execute_all_rules.label} {help id="id-execute-all" title=$form.event_messages_execute_all_rules.label}</td>
          <td>{$form.event_messages_execute_all_rules.html}</td>
        </tr>

      </table>

      <div class="eventmessages-rules-list">
          {foreach from=$rules_list item=rule_index}
            <div class="eventmessages-rule eventmessages-rule-{$rule_index}">
              <table class="form-layout-compressed">

                <tr class="crm-event-manage-eventmessages-form-block-event_messages_rule-template">
                    {capture assign=field_name}template_{$rule_index}{/capture}
                  <td class="label">{$form.$field_name.label}</td>
                  <td>{$form.$field_name.html}</td>
                </tr>

                <tr class="crm-event-manage-eventmessages-form-block-event_messages_rule-is_active">
                    {capture assign=field_name}is_active_{$rule_index}{/capture}
                  <td class="label">{$form.$field_name.label}</td>
                  <td>{$form.$field_name.html}</td>
                </tr>

                <tr class="crm-event-manage-eventmessages-form-block-event_messages_rule-from">
                    {capture assign=field_name}from_{$rule_index}{/capture}
                  <td class="label">{$form.$field_name.label}</td>
                  <td>{$form.$field_name.html}</td>
                </tr>

                <tr class="crm-event-manage-eventmessages-form-block-event_messages_rule-to">
                    {capture assign=field_name}to_{$rule_index}{/capture}
                  <td class="label">{$form.$field_name.label}</td>
                  <td>{$form.$field_name.html}</td>
                </tr>

                <tr class="crm-event-manage-eventmessages-form-block-event_messages_rule-roles">
                    {capture assign=field_name}roles_{$rule_index}{/capture}
                  <td class="label">{$form.$field_name.label}</td>
                  <td>{$form.$field_name.html}</td>
                </tr>

                <tr class="crm-event-manage-eventmessages-form-block-event_messages_rule-languages">
                    {capture assign=field_name}languages_{$rule_index}{/capture}
                  <td class="label">{$form.$field_name.label}</td>
                  <td>{$form.$field_name.html}</td>
                </tr>

              </table>
            </div>
          {/foreach}

      </div>
    </div>
  </div>

  <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

</div>

{literal}
<script>
cj(document).ready(function() {

  /**
   * Update the form so it never shows more than one 'empty' row
   */
  function hide_all_but_one_empty_rows() {
    // Show all rule blocks, ...
    cj('.eventmessages-rule')
      .show();

    // ... then hide all but one empty block.
    cj("[name^=template_]")
      .filter(function () {
        return !cj(this).val();
      })
      .closest('.eventmessages-rule')
      .not(':first')
      .hide();
  }

  // attach the function to dropdown
  cj("[name^=template_]").change(hide_all_but_one_empty_rows);

  // and trigger once with buildup
  hide_all_but_one_empty_rows();
});
</script>
{/literal}