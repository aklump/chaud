<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\AudioSwitch;

class GetAudioConfigByAlias {

  private array $config;

  public function __construct(array $config) {
    $this->config = $config;
  }

  public function __invoke(string $alias): array {
    $alias = strtolower($alias);
    $matched_devices = array_filter($this->config['options'] ?? [], function ($value) use ($alias) {
      return in_array($alias, array_map('strtolower', $value['aliases']));
    });
    if (count($matched_devices) !== 1) {
      return [];
    }

    return reset($matched_devices);
  }
}
