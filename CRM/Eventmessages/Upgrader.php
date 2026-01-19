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

declare(strict_types = 1);

use Civi\Api4\CustomGroup;
use Civi\Api4\Event;
use Civi\Api4\OptionGroup;
use Civi\EventMessages\Install\LanguagesOptionsGroupCreator;
use Civi\EventMessages\Language\Provider\ContactLanguageProvider;
use CRM_Eventmessages_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Eventmessages_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Create the required custom data
   */
  public function install(): void {
    (new LanguagesOptionsGroupCreator())->createLanguagesOptionGroup();
  }

  public function uninstall(): void {
    CustomGroup::delete(FALSE)
      ->addWhere('name', '=', 'event_messages_settings')
      ->execute();
    OptionGroup::delete(FALSE)
      ->addWhere('name', '=', 'event_messages_languages')
      ->execute();
  }

  /**
   * Adding settings
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0001() {
    // Removed unnecessary synchronisation of custom group, now done via managed entities.
    return TRUE;
  }

  /**
   * Refactored table
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0002() {
    $this->ctx->log->info('Create table');
    $this->executeSqlFile('sql/civicrm_value_event_messages.sql');
    return TRUE;
  }

  /**
   * Add column "attachments" to "civicrm_event_message_rules" table.
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0003() {
    $this->ctx->log->info('Add column "attachments" to "civicrm_event_message_rules" table.');
    $column_exists = CRM_Core_DAO::singleValueQuery(
      "SHOW COLUMNS FROM `civicrm_event_message_rules` LIKE 'attachments';"
    );
    if (!$column_exists) {
      CRM_Core_DAO::executeQuery(
        "ALTER TABLE `civicrm_event_message_rules` ADD COLUMN `attachments` varchar(255) COMMENT 'list of attachments';"
      );
    }
    return TRUE;
  }

  /**
   * Change data type of column "attachments" to TEXT in "civicrm_event_message_rules" table.
   *
   * @return true on success
   */
  public function upgrade_0004() {
    $this->ctx->log->info('Change data type of column "attachments" to TEXT in "civicrm_event_message_rules" table.');
    CRM_Core_DAO::executeQuery(
      <<<SQL
      ALTER TABLE `civicrm_event_message_rules`
        MODIFY `attachments` text DEFAULT NULL COMMENT 'list of attachments';
      SQL
    );
    return TRUE;
  }

  /**
   * Adding settings
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0005() {
    // Removed unnecessary synchronisation of custom group, now done via managed entities.
    return TRUE;
  }

  public function upgrade_0006(): bool {
    $this->ctx->log->info('Add custom field');
    // Set language_provider_names on existing events to keep previous behavior.
    Event::update(FALSE)
      ->addValue('event_messages_settings.language_provider_names', [ContactLanguageProvider::getName()])
      // Where is required for update action.
      ->addWhere('id', 'IS NOT NULL')
      ->execute();

    $this->ctx->log->info('Add languages option group');
    (new LanguagesOptionsGroupCreator())->createLanguagesOptionGroup();

    return TRUE;
  }

  /**
   * Add custom activity type "Event Message sent".
   */
  public function upgrade_0007(): bool {
    $this->ctx->log->info('EventMessages: ensure activity type "event_message_sent" exists');

    $name = 'event_message_sent';
    $label = 'Event Message sent';

    $existing = \Civi\Api4\OptionValue::get(FALSE)
      ->addSelect('id')
      ->addWhere('option_group_id:name', '=', 'activity_type')
      ->addWhere('name', '=', $name)
      ->setLimit(1)
      ->execute()
      ->first();

    if (!empty($existing['id'])) {
      return TRUE;
    }

    \Civi\Api4\OptionValue::create(FALSE)
      ->addValue('option_group_id:name', 'activity_type')
      ->addValue('name', $name)
      ->addValue('label', $label)
      ->addValue('is_active', TRUE)
      ->addValue('is_reserved', FALSE)
      ->execute();

    return TRUE;
  }

}
