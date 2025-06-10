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

namespace Civi\EventMessages\Language;

class LanguageMatcher {
  private EventMessagesLanguageProvider $languageProvider;

  public function __construct(EventMessagesLanguageProvider $languageProvider) {
    $this->languageProvider = $languageProvider;
  }

  /**
   * @phpstan-param array<string> $languages
   * @phpstan-param array{
   *   id: int,
   *   "event_messages_settings.language_provider_names": array<string>|null,
   * } $event
   *
   * @return bool
   *   true if the languages linked to the given event and
   *   participant matches any of the given languages, or the languages array
   *   is empty.
   */
  public function match(array $languages, array $event, int $participantId): bool {
    if ([] === $languages) {
      return TRUE;
    }

    $providerNames = $event['event_messages_settings.language_provider_names'] ?? [];
    foreach ($this->languageProvider->getLanguages($providerNames, $event['id'], $participantId) as $language) {
      if (in_array($language, $languages, TRUE)) {
        return TRUE;
      }

      [$langCode] = explode('_', $language, 2);
      if (in_array($langCode, $languages, TRUE)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
