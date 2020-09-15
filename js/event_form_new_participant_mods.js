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

/** page-wide variable to define whether CiviCRM event communications should be hidden */
let event_communications_hidden = 0;

cj(document).ready(function () {

  /**
   * Changes to the event ID will trigger a check to
   *  whether event messages are suppressed for this event,
   *  If so, the 'send message' panel will be hidden
   */
  function eventmessages_trigger_update_mail_panel() {
    let event_id = cj("input[name=event_id]").val();
    if (event_id) {
      let suppression_field_name = CRM.vars.eventmessages.suppression_field;
      CRM.api3('Event', 'getsingle', {
        id: event_id,
        return: suppression_field_name
      }).done(function (result) {
          if (result.is_error) {
            event_communications_hidden = 0;
          }
          else {
            event_communications_hidden = result[suppression_field_name];
          }
          eventmessages_hide_message_panel();
        });
    }
  }
  // add trigger and run once
  cj("input[name=event_id]").change(eventmessages_trigger_update_mail_panel);
  cj("select[name=status_id]").change(eventmessages_hide_message_panel);
  eventmessages_trigger_update_mail_panel();

  /**
   * Will hide the various CiviCRM's default communications
   *  if the event_communications_hidden is set
   */
  function eventmessages_hide_message_panel() {
    if (event_communications_hidden) {
      // hide the whole fieldset und unset the checkboxes
      console.log("hide stuff");
      cj("fieldset#send_confirmation_receipt,fieldset#email-receipt,div#notify")
        .hide()
        .find(".crm-form-checkbox")
        .prop('checked', '')
        .change();
    } else {
      // show the fieldset
      console.log("show stuff");
      cj("fieldset#send_confirmation_receipt,fieldset#email-receipt,div#notify")
        .show()
        .change();
    }
  }

  // trigger the event_id change once
  eventmessages_hide_message_panel();

  // but also call, when loading (of some subsection) is completed
  cj(document).on('ajaxComplete', eventmessages_hide_message_panel);

  // make sure it will be unassigned
  cj(document).on('crmPopupClose', function () {
    cj(document).off('ajaxComplete', eventmessages_hide_message_panel);
  });
});
