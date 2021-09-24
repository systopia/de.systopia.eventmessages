-- copies the event messages rules of the event with 
-- the id @TEMPLATE_EVENT_ID to all other events that:
--  * are not templates (is_template=0)
--  * do not currently have any event message rules

SET @TEMPLATE_EVENT_ID := "INSERT ID HERE";

INSERT INTO civicrm_event_message_rules (event_id, is_active, template_id, from_status, to_status, languages, roles, weight, attachments)
SELECT 
 new_event.id          AS event_id,
 template.is_active    AS is_active,
 template.template_id  AS template_id,
 template.from_status  AS from_status,
 template.to_status    AS to_status,
 template.languages    AS languages,
 template.roles        AS roles,
 template.weight       AS weight,
 template.attachments  AS attachments
FROM civicrm_event_message_rules template
LEFT JOIN civicrm_event new_event
       ON new_event.is_template = 0  -- exclude templates
WHERE template.event_id = @TEMPLATE_EVENT_ID
  AND new_event.id <> template.event_id
  AND new_event.id NOT IN ( -- doesn't have any rules yet
    SELECT DISTINCT(event_id) FROM civicrm_event_message_rules)
;