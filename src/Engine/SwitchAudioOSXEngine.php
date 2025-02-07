<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\AudioSwitch\Engine;

class SwitchAudioOSXEngine implements EngineInterface {

  /**
   * @var false|string
   */
  private $script;

  public function applies(): bool {
    $this->script = exec('which SwitchAudioSource');

    return is_executable($this->script);
  }

  public function getCommandChangeInput(string $device_name): string {
    return sprintf('%s -s "%s" -t input', $this->script, $device_name);
  }

  public function getCommandChangeOutput(string $device_name): string {
    return sprintf('%s -s "%s" -t output', $this->script, $device_name);
  }

  public function getHomepage(): string {
    return 'https://github.com/deweller/switchaudio-osx';
  }
}
