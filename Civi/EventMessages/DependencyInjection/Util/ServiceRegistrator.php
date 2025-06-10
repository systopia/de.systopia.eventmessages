<?php

/*
 * Copyright (C) 2023 SYSTOPIA GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 3 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

namespace Civi\EventMessages\DependencyInjection\Util;

use Symfony\Component\Config\Resource\GlobResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @codeCoverageIgnore
 */
final class ServiceRegistrator {

  /**
   * Autowires all implementations of the given class or interface.
   *
   * All PSR conform classes below the given directory (recursively) are
   * considered.
   *
   * @phpstan-param class-string $classOrInterface
   * @phpstan-param array<string, array<string, scalar>> $tags
   *   Tag names mapped to attributes.
   * @phpstan-param array{lazy?: bool, shared?: bool, public?: bool} $options
   *
   * @phpstan-return array<string, \Symfony\Component\DependencyInjection\Definition>
   *   Service ID mapped to definition.
   */
  public static function autowireAllImplementing(
    ContainerBuilder $container,
    string $dir,
    string $namespace,
    string $classOrInterface,
    array $tags = [],
    array $options = []
  ): array {
    return self::doAutowireAll($container, $dir, $namespace, $classOrInterface, $tags, $options);
  }

  /**
   * Autowires all PSR conform classes below the given directory (recursively).
   *
   * If $classOrInterface is given only those classes are autowrired that
   * implement the class/interface.
   *
   * @phpstan-param class-string|null $classOrInterface
   * @phpstan-param array<string, array<string, scalar>> $tags
   *   Tag names mapped to attributes.
   * @phpstan-param array{lazy?: bool, shared?: bool, public?: bool} $options
   *
   * @phpstan-return array<string, \Symfony\Component\DependencyInjection\Definition>
   *   Service ID mapped to definition.
   */
  private static function doAutowireAll(
    ContainerBuilder $container,
    string $dir,
    string $namespace,
    ?string $classOrInterface,
    array $tags,
    array $options
  ): array {
    $container->addResource(new GlobResource($dir, '/*.php', TRUE));

    $definitions = [];
    $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
    while ($it->valid()) {
      if ($it->isFile() && 'php' === $it->getFileInfo()->getExtension()) {
        // @phpstan-ignore-next-line
        $class = static::getClass($namespace, $it->getInnerIterator());
        if (static::isServiceClass($class, $classOrInterface) && !$container->has($class)) {
          /** @phpstan-var class-string $class */
          $definition = $container->autowire($class);
          $definition->setLazy($options['lazy'] ?? FALSE);
          $definition->setShared($options['shared'] ?? FALSE);
          $definition->setPublic($options['public'] ?? FALSE);
          foreach ($tags as $tagName => $tagAttributes) {
            $definition->addTag($tagName, $tagAttributes);
          }

          $definitions[$class] = $definition;
        }
      }

      $it->next();
    }

    return $definitions;
  }

  private static function getClass(string $namespace, \RecursiveDirectoryIterator $it): string {
    $class = $namespace . '\\';
    if ('' !== $it->getSubPath()) {
      $class .= str_replace('/', '\\', $it->getSubPath()) . '\\';
    }

    return $class . $it->getFileInfo()->getBasename('.php');
  }

  /**
   * @phpstan-param class-string|null $classOrInterface
   */
  private static function isServiceClass(string $class, ?string $classOrInterface): bool {
    if (!class_exists($class)) {
      return FALSE;
    }

    $reflClass = new \ReflectionClass($class);

    return (NULL === $classOrInterface || $reflClass->isSubclassOf($classOrInterface))
      && !$reflClass->isAbstract();
  }

}
