<?php
/*
 * Copyright (C) 2022 SYSTOPIA GmbH
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation in version 3.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

namespace Civi\EventMessages\Fixtures;

use Civi\Api4\Event;

final class EventFixture {

  /**
   * @param array<string, scalar|array<scalar>> $values
   *
   * @return array
   * @phpstan-return array<string, scalar|null|array<scalar>>&array{id: int}
   *
   * @throws \CRM_Core_Exception
   */
  public static function addFixture(array $values = []): array {
    static $i = 0;
    ++$i;

    return Event::create(FALSE)
      ->setValues($values + [
        'title' => 'EventMessagesTest' . $i,
        'event_type_id:name' => 'Conference',
        'start_date' => '2023-08-24',
        'is_online_registration' => FALSE,
        'is_monetary' => FALSE,
        'is_map' => FALSE,
        'is_email_confirm' => FALSE,
        'is_pay_later' => FALSE,
        'is_partial_payment' => FALSE,
        'is_multiple_registrations' => FALSE,
        'allow_same_participant_emails' => FALSE,
        'has_waitlist' => FALSE,
        'requires_approval' => FALSE,
        'allow_selfcancelxfer' => FALSE,
        'is_template' => FALSE,
        'is_billing_required' => FALSE,
      ])->execute()->first();
  }

}
