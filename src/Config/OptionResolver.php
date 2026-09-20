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
   * @var array[] Options keyed by normalized alias.
   */
  private array $byNormalizedAlias = [];

  /**
   * @var array[] Options keyed by normalized label.
   */
  private array $byNormalizedLabel = [];

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
      $this->byNormalizedLabel[$this->normalize($label)] = $option;
      $this->names[] = $label;
      foreach (($option['aliases'] ?? []) as $alias) {
        $alias = (string) $alias;
        $this->byAlias[mb_strtolower($alias)] = $option;
        $this->byNormalizedAlias[$this->normalize($alias)] = $option;
        $this->names[] = $alias;
      }
    }
  }

  /**
   * @param string $label A label or alias, in any letter case.
   *
   * @return array|null The option, or NULL if nothing matches.  An alias wins
   *   over a label when both match, and a case-insensitive exact match wins
   *   over the normalized fallback (so "desk-setup" or "desk_setup" finds
   *   "Desk setup").
   */
  public function resolve(string $label): ?array {
    $key = mb_strtolower($label);
    $normalized = $this->normalize($label);

    return $this->byAlias[$key]
      ?? $this->byLabel[$key]
      ?? $this->byNormalizedAlias[$normalized]
      ?? $this->byNormalizedLabel[$normalized]
      ?? NULL;
  }

  /**
   * @return string[] Every label and alias, in the case the user wrote them.
   */
  public function getNames(): array {
    return $this->names;
  }

  /**
   * Lowercase and turn every character outside [a-z0-9_] into an underscore.
   *
   * This is the transform the old Bash function names were built with, so
   * input like "desk-setup" keeps finding "Desk setup".
   */
  private function normalize(string $value): string {
    return preg_replace('/[^a-z0-9_]/', '_', mb_strtolower($value));
  }

}
