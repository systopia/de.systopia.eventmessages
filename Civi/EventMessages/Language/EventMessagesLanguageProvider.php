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

class EventMessagesLanguageProvider {
  private LanguageProviderContainer $providerContainer;

  /**
   * @var array<int, array<string, array<string>>>
   *   Maps participant IDs to maps of provider names to languages.
   */
  private array $cachedLanguages = [];

  public function __construct(LanguageProviderContainer $providerContainer) {
    $this->providerContainer = $providerContainer;
  }

  /**
   * @phpstan-return iterable<string>
   *   Language code optionally together with country code, e.g. 'en_US' or
   *   'en'.
   */
  public function getLanguages(array $providerNames, int $eventId, int $participantId): iterable {
    foreach ($providerNames as $providerName) {
      $this->cachedLanguages[$participantId][$providerName] ??=
                $this->getLanguagesByProvider($providerName, $eventId, $participantId);
      foreach ($this->cachedLanguages[$participantId][$providerName] as $language) {
        yield $language;
      }
    }
  }

  /**
   * @phpstan-return array<string>
   */
  private function getLanguagesByProvider(string $providerName, int $eventId, int $participantId): array {
    if (!$this->providerContainer->has($providerName)) {
      return [];
    }

    /** @var LanguageProviderInterface $provider */
    $provider = $this->providerContainer->get($providerName);

    return array_unique([...$provider->getLanguages($eventId, $participantId)]);
  }

}
