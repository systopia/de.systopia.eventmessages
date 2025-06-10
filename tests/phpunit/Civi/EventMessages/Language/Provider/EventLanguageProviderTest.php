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

use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\EventMessages\AbstractEventmessagesHeadlessTestCase;
use Civi\EventMessages\Fixtures\ContactFixture;
use Civi\EventMessages\Fixtures\EventFixture;
use Civi\EventMessages\Fixtures\ParticipantFixture;

/**
 * @covers \Civi\EventMessages\Language\Provider\EventLanguageProvider
 * @covers \Civi\EventMessages\Language\Provider\AbstractCustomFieldLanguageProvider
 *
 * @group headless
 */
final class EventLanguageProviderTest extends AbstractEventmessagesHeadlessTestCase {

  private EventLanguageProvider $provider;

  protected function setUp(): void {
    parent::setUp();
    $this->provider = new EventLanguageProvider();

    $customGroup = CustomGroup::create(FALSE)
      ->setValues([
        'title' => 'Event Custom Test',
        'name' => 'group',
        'extends' => 'Event',
      ])->execute()->first();

    CustomField::create(FALSE)
      ->setValues([
        'custom_group_id' => $customGroup['id'],
        'name' => 'language',
        'option_group_id:name' => 'event_messages_languages',
        'label' => 'Language',
        'data_type' => 'String',
        'html_type' => 'Select',
        'is_required' => FALSE,
        'is_searchable' => FALSE,
        'is_search_range' => FALSE,
        'is_view' => FALSE,
        'serialize' => 0,
        'in_selector' => FALSE,
        'weight' => 2,
      ])->execute();

    CustomField::create(FALSE)
      ->setValues([
        'custom_group_id' => $customGroup['id'],
        'name' => 'languages',
        'option_group_id:name' => 'event_messages_languages',
        'label' => 'Languages',
        'data_type' => 'String',
        'html_type' => 'Select',
        'is_required' => FALSE,
        'is_searchable' => FALSE,
        'is_search_range' => FALSE,
        'is_view' => FALSE,
        'serialize' => 1,
        'in_selector' => FALSE,
        'weight' => 1,
      ])->execute();
  }

  protected function tearDown(): void {
    CustomGroup::delete(FALSE)
      ->addWhere('name', '=', 'group')
      ->execute();
    parent::tearDown();
  }

  public function test(): void {
    $event = EventFixture::addFixture([
      'group.language' => 'en_US',
      'group.languages' => ['de_DE', 'fr'],
    ]);

    $contact = ContactFixture::addIndividual(['preferred_language' => 'de_DE']);
    $participant = ParticipantFixture::addFixture($contact['id'], $event['id']);

    static::assertSame(
        ['de_DE', 'fr', 'en_US'],
        [...$this->provider->getLanguages($event['id'], $participant['id'])]
    );
  }

}
