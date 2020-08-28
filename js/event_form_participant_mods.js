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

cj(document).ready(function() {
    /**
     * Will hide the 'send confirmation receipt' section
     *  of the participant form, if the event has
     *  disabled CiviCRM's default communications
     */
    function eventmessages_hide_message_panel()
    {
        // hide the whole fieldset und unset the checkboxes
        cj("fieldset#send_confirmation_receipt")
            .hide()
            .find(".crm-form-checkbox")
            .prop('checked', '')
            .change();
    }

    // call once
    eventmessages_hide_message_panel();

    // but also call, when loading (of some subsection) is completed
    cj(document).on('ajaxComplete', eventmessages_hide_message_panel);

    // make sure it will be unassigned
    cj(document).on('crmPopupClose', function () {
        cj(document).off('ajaxComplete', eventmessages_hide_message_panel);
    });
});
