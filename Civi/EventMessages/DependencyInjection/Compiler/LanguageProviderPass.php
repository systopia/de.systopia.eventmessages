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

namespace Civi\EventMessages\DependencyInjection\Compiler;

use Civi\EventMessages\Language\LanguageProviderContainer;
use Civi\EventMessages\Language\LanguageProviderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class LanguageProviderPass implements CompilerPassInterface {

  /**
   * @inheritDoc
   */
  public function process(ContainerBuilder $container): void {
    $metadata = [];
    $services = [];

    foreach ($container->findTaggedServiceIds(LanguageProviderInterface::SERVICE_TAG) as $id => $tags) {
      $class = $this->getServiceClass($container, $id);
      if (!is_a($class, LanguageProviderInterface::class, TRUE)) {
        throw new \RuntimeException(sprintf(
          'Class "%s" is not an instance of "%s"', $class, LanguageProviderInterface::class)
        );
      }

      $name = $class::getName();

      if (isset($metadata[$name])) {
        throw new \RuntimeException(sprintf('Duplicate language provider name "%s"', $name));
      }

      $metadata[$name] = [
        'name' => $name,
        'label' => $class::getLabel(),
        'description' => $class::getDescription(),
      ];

      $services[$name] = new Reference($id);
    }

    $container->register(LanguageProviderContainer::class, LanguageProviderContainer::class)
      ->addArgument(ServiceLocatorTagPass::register($container, $services))
      ->addArgument($metadata)
      ->setPublic(TRUE);
  }

  /**
   * @phpstan-return class-string
   */
  private function getServiceClass(ContainerBuilder $container, string $id): string {
    $definition = $container->getDefinition($id);

    /** @phpstan-var class-string $class */
    $class = $definition->getClass() ?? $id;

    return $class;
  }

}
