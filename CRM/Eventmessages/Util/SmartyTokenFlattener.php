<?php
/*
 * Copyright (C) 2025 SYSTOPIA GmbH
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

/**
 * Prepares Smarty tokens for use in TokenProcessor. TokenProcessor requires a token entity and doesn't allow arrays
 * as token values.
 */
final class CRM_Eventmessages_Util_SmartyTokenFlattener {

  /**
   * @var array<string, string>
   */
  private array $aliases = [];

  private string $template;

  /**
   * @var array<string, array<string, mixed>>
   */
  private array $tokens = [];

  public function __construct(string $template) {
    $this->template = $template;
  }

  /**
   * @param array<string, mixed> $tokens
   */
  public function flattenTokens(array $tokens, string $tokenEntity, string $prefix = ''): void {
    foreach ($tokens as $tokenName => $tokenValue) {
      if (is_array($tokenValue)) {
        // Arrays are not allowed as token value in TokenProcessor.
        $subPrefix = "$prefix${tokenName}__";
        $this->template = str_replace('{$' . "$tokenName.", '{$' . $subPrefix, $this->template);
        // @phpstan-ignore argument.type
        $this->flattenTokens($tokenValue, $tokenEntity, $subPrefix);
      }
      else {
        $fullTokenName = $prefix . $tokenName;
        $this->tokens[$tokenEntity][$fullTokenName] = $tokenValue;
        $this->aliases[$fullTokenName] = "$tokenEntity.$fullTokenName";
      }
    }
  }

  /**
   * @return array<string, string>
   */
  public function getAliases(): array {
    return $this->aliases;
  }

  public function getTemplate(): string {
    return $this->template;
  }

  /**
   * @return array<string, array<string, mixed>>
   */
  public function getTokens(): array {
    return $this->tokens;
  }

}
