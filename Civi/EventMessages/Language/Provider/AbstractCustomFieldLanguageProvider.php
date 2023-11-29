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

namespace Civi\EventMessages\Language\Provider;

use Civi\Api4\CustomField;
use Civi\Api4\OptionValue;
use Civi\EventMessages\Language\LanguageProviderInterface;

/**
 * @phpstan-type comparisonT array{string, string, 2?: scalar|array<scalar>}
 *  Actually this should be: array{string, array<ComparisonT|CompositeConditionT>}, though that is not possible.
 * @phpstan-type compositeConditionT array{string, array<array<mixed>>}
 */
abstract class AbstractCustomFieldLanguageProvider implements LanguageProviderInterface
{
    private string $entityName;

    /**
     * @phpstan-var array<string>
     */
    private ?array $languageFieldNames = null;

    public function __construct(string $entityName)
    {
        $this->entityName = $entityName;
    }

    /**
     * @inheritDoc
     *
     * @throws \CRM_Core_Exception
     */
    public function getLanguages(int $eventId, int $participantId): iterable
    {
        $languageFieldNames = $this->getLanguageFieldNames();
        if ([] === $languageFieldNames) {
            return [];
        }

        $entity = civicrm_api4($this->entityName, 'get', [
            'select' => $languageFieldNames,
            'where' => $this->getWhere($eventId, $participantId),
            'checkPermissions' => false,
            ])
            ->single();

        foreach ($languageFieldNames as $fieldName) {
            if (is_array($entity[$fieldName])) {
                foreach ($entity[$fieldName] as $language) {
                    if (null !== $language && '' !== $language) {
                        yield $language;
                    }
                }
            } else if (null !== $entity[$fieldName] && '' !== $entity[$fieldName]) {
                yield $entity[$fieldName];
            }
        }
    }

    /**
     * @phpstan-return array<string>
     *
     * @throws \CRM_Core_Exception
     */
    protected function getLanguageFieldNames(): array {
        if (null === $this->languageFieldNames) {
            $this->languageFieldNames = [];
            $fields = CustomField::get(false)
                ->setSelect(['custom_group_id:name', 'name'])
                ->addWhere('custom_group_id.extends', '=', $this->entityName)
                ->addWhere('option_group_id:name', '=', 'event_messages_languages')
                ->addOrderBy('weight', 'ASC')
                ->execute();

            /** @phpstan-var array{'custom_group_id:name': string, name: string} $field */
            foreach ($fields as $field) {
                $this->languageFieldNames[] = $field['custom_group_id:name'] . '.' . $field['name'];
            }

        }

        return $this->languageFieldNames;
    }

    /**
     * @phpstan-return array<comparisonT, compositeConditionT>
     *   Array that can be used as "where" in APIv4 action to select a single
     *   entity of the entity type specified in constructor.
     */
    protected abstract function getWhere(int $eventId, int $participantId): array;
}
