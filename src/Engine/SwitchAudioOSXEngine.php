<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Engine;

use AKlump\ChangeAudio\Exception\EngineFeatureException;

class SwitchAudioOSXEngine implements EngineInterface {

  /**
   * @var false|string
   */
  private $script;

  public function applies(): bool {
    $this->script = exec('which SwitchAudioSource');

    return is_executable($this->script);
  }

  public function getCommandChangeInput(string $device): string {
    return sprintf('%s -s "%s" -t input', $this->script, $device);
  }

  public function getCommandChangeOutput(string $device): string {
    return sprintf('%s -s "%s" -t output', $this->script, $device);
  }

  public function getHomepage(): string {
    return 'https://github.com/deweller/switchaudio-osx';
  }

  public function getCommandSetOutputLevel(string $device, float $volume): string {
    throw new EngineFeatureException("SwitchAudioOSXEngine does not support output levels.");
  }

  public function getCommandSetInputLevel(string $device, float $volume): string {
    throw new EngineFeatureException("SwitchAudioOSXEngine does not support input levels.");
  }

  public function getAllDevices(): array {
    // TODO Implement
    return [];
  }
}
