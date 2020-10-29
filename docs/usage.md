## Usage

After installing and activating the extension a new tab "Event 
Communication" will be available in the event configuration UI. You can 
choose to disable all CiviEvent confirmation mails for the event at 
hand which is recomendended for most use cases.

Set your sender's cc, bcc and reply to addresses as desired and create 
at least one message rule. Message rules will use regular CiviCRM 
message templates so make sure to set up at least one template before 
defining rules.  

If you tick the box "Execute All Matching Rules?" all matching rules 
will be executed, and potentially multiple emails will be sent to the 
same person. If this is disabled, the processing will stop after the 
first matching rule.  

You should be able to use most contact token in the message templates. 
A list of additional token can be found under: 
/civiremote/civicrm/eventmessages/tokenlist
