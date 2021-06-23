-- copies the event messages settings of the event with 
-- the id @TEMPLATE_EVENT_ID to all other events that:
--  * are not templates (is_template=0)
--  * do not currently have any event message settings

SET @TEMPLATE_EVENT_ID := "INSERT ID HERE";

INSERT INTO civicrm_value_event_messages_settings (entity_id, disable_default, execute_all_rules, sender, reply_to, cc, bcc)
SELECT 
 new_event.id               AS entity_id,
 template.disable_default   AS disable_default,
 template.execute_all_rules AS execute_all_rules,
 template.sender            AS sender,
 template.reply_to          AS reply_to,
 template.cc                AS cc,
 template.bcc               AS bcc
FROM civicrm_value_event_messages_settings template
LEFT JOIN civicrm_event new_event
       ON new_event.is_template = 0  -- exclude templates
WHERE template.entity_id = @TEMPLATE_EVENT_ID
  AND new_event.id <> template.entity_id
  AND new_event.id NOT IN ( -- doesn't have any settings yet
    SELECT DISTINCT(entity_id) FROM civicrm_value_event_messages_settings)
;