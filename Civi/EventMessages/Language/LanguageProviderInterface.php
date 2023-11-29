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

namespace Civi\EventMessages\Language;

interface LanguageProviderInterface
{
    public const SERVICE_TAG = 'eventmessages.language.provider';

    public static function getDescription(): string;

    public static function getLabel(): string;

    public static function getName(): string;

    /**
     * @phpstan-return iterable<string>
     *   Language code optionally together with country code, e.g. 'en_US' or
     *   'en'.
     */
    public function getLanguages(int $eventId, int $participantId): iterable;
}
