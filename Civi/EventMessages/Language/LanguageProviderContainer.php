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

use Psr\Container\ContainerInterface;

/**
 * @phpstan-type metadataT array<string, array{
 *   name: string,
 *   label: string,
 *   description: string,
 * }>
 *
 * @codeCoverageIgnore
 */
class LanguageProviderContainer
{
    /**
     * @var ContainerInterface
     *    Contains language providers with their names as key.
     */
    private ContainerInterface $container;

    /**
     * @phpstan-var metadataT
     *    Language provider names mapped to their metadata.
     */
    private array $metadata;

    /**
     * @phpstan-param metadataT $metadata
     *    Language provider names mapped to their metadata.
     */
    public function __construct(ContainerInterface $container, array $metadata)
    {
        $this->container = $container;
        $this->metadata = $metadata;
    }

    public function get(string $providerName): LanguageProviderInterface {
        // @phpstan-ignore-next-line
        return $this->container->get($providerName);
    }

    public function has(string $providerName): bool {
        return $this->container->has($providerName);
    }

    /**
     * @phpstan-return metadataT
     *    Language provider names mapped to their metadata.
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
