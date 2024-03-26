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

declare(strict_types=1);

namespace Civi\EventMessages\Language\Provider;

use Civi\Api4\Participant;
use CRM_Eventmessages_ExtensionUtil as E;

final class ContactLanguageProvider extends AbstractCustomFieldLanguageProvider
{
    public static function getDescription(): string
    {
        return E::ts('Custom fields for contacts using "Event Message Languages" option group and contact\'s preferred language.');
    }

    public static function getLabel(): string
    {
        return E::ts('Contact');
    }

    public static function getName(): string
    {
        return 'contact';
    }

    public function __construct()
    {
        parent::__construct('Contact');
    }

    protected function getLanguageFieldNames(): array
    {
        return array_merge(parent::getLanguageFieldNames(), ['preferred_language']);
    }

    protected function getWhere(int $eventId, int $participantId): array
    {
        $contactId = Participant::get(false)
            ->addSelect('contact_id')
            ->addWhere('id', '=', $participantId)
            ->execute()
            ->single()['contact_id'];

        return [['id', '=', $contactId]];
    }
}
