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

namespace Civi\EventMessages;

use Symfony\Component\DependencyInjection\ContainerInterface;

final class CiviTestContainer implements ContainerInterface {
  private ContainerInterface $container;

  /**
   * @var array<string, ?object>
   */
  private array $services = [];

  public function __construct(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * @inheritDoc
   */
  public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE): ?object {
    return $this->services[$id] ?? $this->container->get($id, $invalidBehavior);
  }

  /**
   * @inheritDoc
   */
  public function has($id): bool {
    return isset($this->services[$id]) || $this->container->has($id);
  }

  /**
   * @inheritDoc
   */
  public function set($id, $service): void {
    $this->services[$id] = $service;
  }

  /**
   * @inheritDoc
   */
  public function initialized($id): bool {
    return $this->container->initialized($id);
  }

  /**
   * @inheritDoc
   */
  public function getParameter($name): array|bool|string|int|float|\UnitEnum|null {
    return $this->container->getParameter($name);
  }

  /**
   * @inheritDoc
   */
  public function hasParameter($name): bool {
    return $this->container->hasParameter($name);
  }

  /**
   * @inheritDoc
   */
  public function setParameter($name, $value): void {
    $this->container->setParameter($name, $value);
  }

}
