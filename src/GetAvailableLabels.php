<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\AudioSwitch;

class GetAvailableLabels {

  private array $config;

  public function __construct(array $config) {
    $this->config = $config;
  }

  public function __invoke(): array {
    return array_column($this->config['options'] ?? [], 'label');
  }
}
