<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\AudioSwitch;

class GetAudioConfigByDevices {

  private array $config;

  public function __construct(array $config) {
    $this->config = $config;
  }

  public function __invoke(string $input, string $output): array {
    $matched_devices = array_filter($this->config['options'] ?? [], function ($value) use ($input, $output) {
      return $input === ($value["input"]["deviceId"] ?? '') && $output === ($value["output"]["deviceId"] ?? '');
    });
    if (count($matched_devices) !== 1) {
      return [];
    }

    return reset($matched_devices);
  }
}
