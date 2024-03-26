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

use Civi\Api4\Contact;

final class ContactFixture {

  /**
   * @param array<string, scalar> $values
   *
   * @return array
   * @phpstan-return array<string, scalar|null>&array{id: int}
   *
   * @throws \CRM_Core_Exception
   */
  public static function addIndividual(array $values = []): array {
    return Contact::create(false)
      ->setValues($values + [
        'contact_type' => 'Individual',
        'first_name' => 'Some',
        'last_name' => 'Individual',
      ])->execute()->first();
  }

  /**
   * @param array<string, scalar> $values
   *
   * @return array
   * @phpstan-return array<string, scalar|null>&array{id: int}
   *
   * @throws \CRM_Core_Exception
   */
  public static function addOrganization(array $values = []): array {
    return Contact::create(false)
      ->setValues($values + [
        'contact_type' => 'Organization',
        'legal_name' => 'Test organization',
      ])->execute()->first();
  }

}
