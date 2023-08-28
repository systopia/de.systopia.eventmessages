<?php
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

use CRM_Eventmessages_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Eventmessages_Upgrader extends CRM_Eventmessages_Upgrader_Base {

    /**
     * Create the required custom data
     */
    public function install()
    {
        // create table
        $this->executeSqlFile('sql/civicrm_value_event_messages.sql');
        $customData = new CRM_Eventmessages_CustomData(E::LONG_NAME);
        $customData->syncCustomGroup(E::path('resources/custom_group_event_messages_settings.json'));
    }

    /**
     * Adding settings
     *
     * @return TRUE on success
     * @throws Exception
     */
    public function upgrade_0001()
    {
        $this->ctx->log->info('Adding settings');
        $customData = new CRM_Eventmessages_CustomData(E::LONG_NAME);
        $customData->syncCustomGroup(E::path('resources/custom_group_event_messages_settings.json'));
        return true;
    }

    /**
     * Refactored table
     *
     * @return TRUE on success
     * @throws Exception
     */
    public function upgrade_0002()
    {
        $this->ctx->log->info('Create table');
        $this->executeSqlFile('sql/civicrm_value_event_messages.sql');
        return true;
    }

    /**
     * Add column "attachments" to "civicrm_event_message_rules" table.
     *
     * @return TRUE on success
     * @throws Exception
     */
    public function upgrade_0003()
    {
        $this->ctx->log->info('Add column "attachments" to "civicrm_event_message_rules" table.');
        $column_exists = CRM_Core_DAO::singleValueQuery(
            "SHOW COLUMNS FROM `civicrm_event_message_rules` LIKE 'attachments';"
        );
        if (!$column_exists) {
            CRM_Core_DAO::executeQuery(
                "ALTER TABLE `civicrm_event_message_rules` ADD COLUMN `attachments` varchar(255) COMMENT 'list of attachments';"
            );
        }
        return true;
    }

    /**
     * Change data type of column "attachments" to TEXT in "civicrm_event_message_rules" table.
     *
     * @return true on success
     */
    public function upgrade_0004() {
        $this->ctx->log->info('Change data type of column "attachments" to TEXT in "civicrm_event_message_rules" table.');
        CRM_Core_DAO::executeQuery(
            "ALTER TABLE `civicrm_event_message_rules` MODIFY `attachments` text DEFAULT NULL COMMENT 'list of attachments';"
        );
        return true;
    }

  /**
   * Adding settings
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0005()
  {
    $this->ctx->log->info('Adding custom data workaround option');
    $customData = new CRM_Eventmessages_CustomData(E::LONG_NAME);
    $customData->syncCustomGroup(E::path('resources/custom_group_event_messages_settings.json'));
    return true;
  }

}
