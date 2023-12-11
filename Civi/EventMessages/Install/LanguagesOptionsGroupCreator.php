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

namespace Civi\EventMessages\Install;

use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;
use CRM_Eventmessages_ExtensionUtil as E;

/**
 * Creates a languages options group based on CiviCRM's languages option group.
 */
final class LanguagesOptionsGroupCreator
{
    public function createLanguagesOptionGroup(): void {
        $transaction = \CRM_Core_Transaction::create();

        $countryLessLanguageOptions = [];
        $optionGroup = OptionGroup::create(false)
            ->setValues([
                'name' => 'event_messages_languages',
                'title' => E::ts('Event Message Languages'),
                'data_type' => 'String',
                'is_reserved' => false,
            ])
            ->execute()
            ->single();

        $civiLanguageOptions = OptionValue::get(false)
            ->setSelect(['value', 'name', 'label', 'weight', 'is_active'])
            ->addWhere('option_group_id:name', '=', 'languages')
            ->addOrderBy('weight')
            ->execute();

        /** @phpstan-var array{id: int, value: string, name: string, label: string, weight: int, is_active: bool} $languageOption */
        foreach ($civiLanguageOptions as $languageOption) {
            unset($languageOption['id']);
            $languageOption['option_group_id'] = $optionGroup['id'];
            // In CiviCRM's language group 'name' contains the language and locale
            // code, e.g. en_US, 'value' contains only the language code, e.g. 'en'.
            // Normally both values are unique for each group.
            $languageOption['value'] = $languageOption['name'];
            [$langCode, $countryCode] = explode('_', $languageOption['value']) + [NULL, NULL];
            $matches = [];
            if (0 === preg_match('/(.*) \([^)]+\)$/', $languageOption['label'], $matches)) {
                $languageLabel = $languageOption['label'];
                $languageOption['label'] .= ' (' . $countryCode . ')';
            } else {
                $languageLabel = $matches[1];
            }

            $countryLessLanguageOptions[$langCode] ??= [
                'option_group_id' => $optionGroup['id'],
                'value' => $langCode,
                'name' => $langCode,
                'label' => $languageLabel,
                'weight' => $languageOption['weight'],
                'is_active' => $languageOption['is_active'],
            ];

            if ($languageOption['is_active']) {
                $countryLessLanguageOptions[$langCode]['is_active'] = true;
                // Disable the localized language if it is the only instance of the language.
                $languageOption['is_active'] = OptionValue::get(false)
                    ->selectRowCount()
                    ->addWhere('option_group_id:name', '=', 'languages')
                    ->addWhere('value', '=', $langCode)
                    ->execute()
                    ->countMatched() > 1;
            }

            // There might be languages not following the format <language code>_<country code>.
            if (NULL !== $countryCode) {
                OptionValue::create(false)
                    ->setValues($languageOption)
                    ->execute();
            }
        }

        foreach ($countryLessLanguageOptions as $languageOption) {
            OptionValue::create(false)
                ->setValues($languageOption)
                ->execute();
        }

        $transaction->commit();
    }
}
