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

/** @var ContainerBuilder $container */

use Civi\EventMessages\DependencyInjection\Compiler\LanguageProviderPass;
use Civi\EventMessages\DependencyInjection\Util\ServiceRegistrator;
use Civi\EventMessages\Language\EventMessagesLanguageProvider;
use Civi\EventMessages\Language\LanguageMatcher;
use Civi\EventMessages\Language\LanguageProviderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

$container->addCompilerPass(new LanguageProviderPass());

$container->autowire(LanguageMatcher::class)
    ->setPublic(true);
$container->autowire(EventMessagesLanguageProvider::class);

ServiceRegistrator::autowireAllImplementing(
    $container,
    __DIR__ . '/../Civi/EventMessages/Language/Provider',
    'Civi\\EventMessages\\Language\\Provider',
    LanguageProviderInterface::class,
    [LanguageProviderInterface::SERVICE_TAG => []]
);
