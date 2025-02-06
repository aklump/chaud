<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\AudioSwitch;

/**
 * Class GetDefaultAudioConfig
 *
 * This class is responsible for determining the default audio label based on the current
 * configuration and available audio devices. It uses other classes for managing the
 * audio engine, retrieving device configurations, and obtaining available labels.
 */
class GetDefaultAudioConfig {

  private array $config;

  public function __construct(array $config) {
    $this->config = $config;
  }

  public function __invoke(): string {
    $labels = (new GetAvailableLabels($this->config))();
    if (empty($labels)) {
      return '';
    }
    $first_audio_configuration = reset($labels);
    $current_device = $this->config['current']['label'] ?? $first_audio_configuration;
    $key = array_search($current_device, $labels);

    return $labels[$key + 1] ?? $first_audio_configuration;
  }
}
