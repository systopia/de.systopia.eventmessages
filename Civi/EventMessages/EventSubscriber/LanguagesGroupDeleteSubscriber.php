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

namespace Civi\EventMessages\EventSubscriber;

use Civi\Core\Event\PostEvent;
use Civi\EventMessages\Install\LanguagesOptionsGroupCreator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Civi deletes the event messages languages option group if the last custom
 * field using the option group is deleted. This subscriber re-creates that
 * group immediately after deletion.
 */
final class LanguagesGroupDeleteSubscriber implements EventSubscriberInterface {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    return ['hook_civicrm_post::OptionGroup' => 'postOptionGroup'];
  }

  public function postOptionGroup(PostEvent $event): void {
    // Civi deletes the option group if the last custom field using the
    // option group is deleted, so we recreate it here.
    if ($this->isLanguagesOptionGroupDeleted($event)) {
      (new LanguagesOptionsGroupCreator())->createLanguagesOptionGroup();
    }
  }

  private function isLanguagesOptionGroupDeleted(PostEvent $event): bool {
    return 'delete' === $event->action && 'event_messages_languages' === $event->object->name;
  }

}
