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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\EventMessages\Language\LanguageMatcher
 */
final class LanguageMatcherTest extends TestCase {

  /**
   * @var EventMessagesLanguageProvider&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject
   */
  private MockObject $languageProviderMock;
  private LanguageMatcher $languageMatcher;

  protected function setUp(): void {
    parent::setUp();
    $this->languageProviderMock = $this->createMock(EventMessagesLanguageProvider::class);
    $this->languageMatcher = new LanguageMatcher($this->languageProviderMock);
  }

  public function testMatchLanguageCode(): void {
    $providerNames = ['name1', 'name2'];
    $event = [
      'id' => 2,
      'event_messages_settings.language_provider_names' => $providerNames,
    ];
    $this->languageProviderMock->method('getLanguages')
      ->with($providerNames, 2, 3)
      ->willReturn(['en']);

    static::assertFalse($this->languageMatcher->match(['en_US'], $event, 3));
    static::assertTrue($this->languageMatcher->match(['en'], $event, 3));
    static::assertFalse($this->languageMatcher->match(['de_DE'], $event, 3));
    static::assertFalse($this->languageMatcher->match(['de'], $event, 3));
  }

  public function testMatchLocale(): void {
    $providerNames = ['name1', 'name2'];
    $event = [
      'id' => 2,
      'event_messages_settings.language_provider_names' => $providerNames,
    ];
    $this->languageProviderMock->method('getLanguages')
      ->with($providerNames, 2, 3)
      ->willReturn(['en_US']);

    static::assertTrue($this->languageMatcher->match(['en'], $event, 3));
    static::assertTrue($this->languageMatcher->match(['en_US'], $event, 3));
    static::assertFalse($this->languageMatcher->match(['de_DE'], $event, 3));
    static::assertFalse($this->languageMatcher->match(['de'], $event, 3));
  }

}
