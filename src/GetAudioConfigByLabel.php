<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\AudioSwitch;

use RuntimeException;

class GetAudioConfigByLabel {

  private array $config;

  public function __construct(array $config) {
    $this->config = $config;
  }

  public function __invoke(string $device_label): array {
    $matched_devices = array_filter($this->config['options'] ?? [], function ($value) use ($device_label) {
      return strcasecmp($device_label, $value['label']) === 0;
    });
    if (count($matched_devices) !== 1) {
      return [];
    }

    return reset($matched_devices);
  }
}
