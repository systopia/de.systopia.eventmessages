# Usage

## Message rules

After installing and activating the extension a new tab "Event Communication"
will be available in the event configuration UI. You can choose to disable all
CiviEvent confirmation mails for the event at hand which is recomendended for
most use cases.

Set your sender's *Cc*, *BCc* and *Reply To* addresses as desired and create at
least one message rule. Message rules will use regular CiviCRM message templates
so make sure to set up at least one template before defining rules.

If you tick the box *Execute All Matching Rules?* all matching rules will be
executed, and potentially multiple emails will be sent to the same person. If
this is disabled, the processing will stop after the first matching rule.

!!! tip
    You should be able to use most contact tokens in the message templates. A
    list of additional tokens can be found at
    */civiremote/civicrm/eventmessages/tokenlist*.

## Search result tasks

The extension provides participant search result tasks for:

* Sending e-mails with a selectable template
* Generating Letters (PDF) with a selectable template

Note that contacts without e-mail addresses (or postal addresses for the
*Generate Letters* task respectively) will be filtered out during processing the
task.
