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

use Civi\Api4\Event;
use Civi\Core\Transaction\Manager as CiviTransactionManager;
use Civi\EventMessages\AbstractEventmessagesHeadlessTestCase;
use Civi\EventMessages\Fixtures\ContactFixture;
use Civi\EventMessages\Fixtures\EmailFixture;
use Civi\EventMessages\Fixtures\EventFixture;
use Civi\EventMessages\Fixtures\EventMessageRuleFixture;
use Civi\EventMessages\Fixtures\ParticipantFixture;
use Civi\EventMessages\Language\LanguageMatcher;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers CRM_Eventmessages_Logic
 *
 * @group headless
 */
final class CRM_Eventmessages_LogicTest extends AbstractEventmessagesHeadlessTestCase
{
    private MockObject $mailerMock;
    /**
     * @var LanguageMatcher&MockObject
     */
    private MockObject $languageMatcherMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->languageMatcherMock = $this->createMock(LanguageMatcher::class);
        $this->container->set(LanguageMatcher::class, $this->languageMatcherMock);
        $this->mailerMock = $this->getMockBuilder(\stdClass::class)
                ->addMethods(['send'])
                ->getMock();
        $this->container->set('pear_mail', $this->mailerMock);
    }

    public function testRuleMatch(): void {
        $languageProverNames = ['participant', 'event', 'contact'];
        $event = EventFixture::addFixture([
            'event_messages_settings.language_provider_names' => $languageProverNames,
            'event_messages_settings.event_messages_execute_all_rules' => true,
        ]);
        Event::get(FALSE)
            ->addSelect('custom.*')
            ->addWhere('id', '=', $event['id'])
            ->execute()
            ->single();

        EventMessageRuleFixture::addFixture($event['id'], [
            'to_status' => [1], // Registered
            'languages' => ['de_DE', 'en'],
        ]);

        $contact = ContactFixture::addIndividual(['preferred_language' => 'de_DE']);
        EmailFixture::addFixture($contact['id']);

        $this->languageMatcherMock->expects(static::once())->method('match')->with(
            ['de_DE', 'en'],
            static::callback(fn (array $arg) => $arg['id'] === $event['id'] &&
             $arg['event_messages_settings.language_provider_names'] === $languageProverNames),
            static::isType('int')
        )->willReturn(true);

        $this->mailerMock->expects(static::once())->method('send')->with(
            ['test@example.org'],
            static::isType('array'),
            static::isType('string')
        );

        ParticipantFixture::addFixture($contact['id'], $event['id']);
        $this->simulateCommit();
    }

    public function testNoStatusMatch(): void {
        $event = EventFixture::addFixture([
            'event_messages_settings.language_provider_names' => ['contact'],
        ]);
        Event::get(FALSE)
            ->addSelect('custom.*')
            ->addWhere('id', '=', $event['id'])
            ->execute()
            ->single();

        EventMessageRuleFixture::addFixture($event['id'], ['to_status' => [2]]);

        $contact = ContactFixture::addIndividual(['preferred_language' => 'en_US']);
        EmailFixture::addFixture($contact['id']);

        $this->languageMatcherMock->expects(static::never())->method('match');
        $this->mailerMock->expects(static::never())->method('send');

        ParticipantFixture::addFixture($contact['id'], $event['id']);
        $this->simulateCommit();
    }

    /**
     * Because this test case is run in a transaction we have to ensure post
     * commit hooks are called.
     */
    private function simulateCommit(): void {
        $frame = CiviTransactionManager::singleton()->getBaseFrame();
        $frame->invokeCallbacks(CRM_Core_Transaction::PHASE_POST_COMMIT);
    }
}
