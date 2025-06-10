<?php
/*
 * Copyright (C) 2023 SYSTOPIA GmbH
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

namespace Civi\EventMessages\Language\Provider;

use CRM_Eventmessages_ExtensionUtil as E;

final class EventLanguageProvider extends AbstractCustomFieldLanguageProvider {

  public static function getDescription(): string {
    return E::ts('Custom fields for events using "Event Message Languages" option group.');
  }

  public static function getLabel(): string {
    return E::ts('Event');
  }

  public static function getName(): string {
    return 'event';
  }

  public function __construct() {
    parent::__construct('Event');
  }

  protected function getWhere(int $eventId, int $participantId): array {
    return [['id', '=', $eventId]];
  }

}
