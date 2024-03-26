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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\EventMessages\Language\EventMessagesLanguageProvider
 */
final class EventMessagesLanguageProviderTest extends TestCase
{

    /**
     * @var LanguageProviderContainer&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject
     */
    private MockObject $providerContainerMock;

    private EventMessagesLanguageProvider $eventMessagesLanguageProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->providerContainerMock = $this->createMock(LanguageProviderContainer::class);
        $this->eventMessagesLanguageProvider = new EventMessagesLanguageProvider($this->providerContainerMock);
    }

    public function test(): void
    {
        $providerMock = $this->createMock(LanguageProviderInterface::class);

        $this->providerContainerMock->expects(static::once())->method('has')
            ->with('name')
            ->willReturn(true);
        $this->providerContainerMock->expects(static::once())->method('get')
            ->with('name')
            ->willReturn($providerMock);

        $providerMock->expects(static::once())->method('getLanguages')
            ->with(2, 3)
            ->willReturn(['en_US', 'fr_FR', 'en_US']);

        static::assertSame(
            ['en_US', 'fr_FR'],
            [...$this->eventMessagesLanguageProvider->getLanguages(['name'], 2, 3)]
        );
        // Test languages are cached. (Mocked methods are expected to only run once.)
        static::assertSame(
            ['en_US', 'fr_FR'],
            [...$this->eventMessagesLanguageProvider->getLanguages(['name'], 2, 3)]
        );
    }

    public function testProviderNotAvailable(): void {
        $this->providerContainerMock->expects(static::once())->method('has')
                ->with('name')
                ->willReturn(false);
        $this->providerContainerMock->expects(static::never())->method('get');

        static::assertSame([], [...$this->eventMessagesLanguageProvider->getLanguages(['name'], 2, 3)]);
    }
}
