<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Config;

/**
 * Finds a configured option by label or alias.
 */
class OptionResolver {

  /**
   * @var array[] Options keyed by lowercase alias.
   */
  private array $byAlias = [];

  /**
   * @var array[] Options keyed by lowercase label.
   */
  private array $byLabel = [];

  /**
   * @var string[]
   */
  private array $names = [];

  /**
   * @param array $options The "options" value from the config.
   */
  public function __construct(array $options) {
    foreach ($options as $option) {
      $label = (string) ($option['label'] ?? '');
      if ($label === '') {
        continue;
      }
      $this->byLabel[mb_strtolower($label)] = $option;
      $this->names[] = $label;
      foreach (($option['aliases'] ?? []) as $alias) {
        $this->byAlias[mb_strtolower((string) $alias)] = $option;
        $this->names[] = (string) $alias;
      }
    }
  }

  /**
   * @param string $label A label or alias, in any letter case.
   *
   * @return array|null The option, or NULL if nothing matches.  An alias wins
   *   over a label when both match.
   */
  public function resolve(string $label): ?array {
    $key = mb_strtolower($label);

    return $this->byAlias[$key] ?? $this->byLabel[$key] ?? NULL;
  }

  /**
   * @return string[] Every label and alias, in the case the user wrote them.
   */
  public function getNames(): array {
    return $this->names;
  }

}
