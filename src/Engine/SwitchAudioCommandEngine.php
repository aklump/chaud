<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Engine;

use AKlump\ChangeAudio\Exception\EngineFeatureException;

/**
 * Does not provide current device info.
 * Does not provide volume control.
 */
class SwitchAudioCommandEngine implements EngineInterface {

  private string $script;

  public function applies(): bool {
    $this->script = getenv('HOME') . '/bin/SwitchAudio';

    return is_executable($this->script);
  }

  public function getCommandChangeInput(string $device): string {
    return sprintf("%s -i '%s'", $this->script, $device);
  }

  public function getCommandChangeOutput(string $device): string {
    return sprintf("%s -o '%s'", $this->script, $device);
  }

  public function getHomepage(): string {
    return 'https://www.macscripter.net/t/switchaudio-a-command-line-tool-to-change-the-audio-input-and-output-device/75630/1';
  }

  public function getCommandSetOutputLevel(string $device, float $volume): string {
    throw new EngineFeatureException("SwitchAudioCommandEngine does not support output levels.");
  }

  public function getCommandSetInputLevel(string $device, float $volume): string {
    throw new EngineFeatureException("SwitchAudioCommandEngine does not support input levels.");
  }

  public function getAllDevices(): array {
    // TODO Implement
    return [];
  }
}
