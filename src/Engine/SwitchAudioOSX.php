<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\AudioSwitch\Engine;

/**
 * @url https://github.com/deweller/switchaudio-osx
 */
class SwitchAudioOSX implements EngineInterface {

  /**
   * @var false|string
   */
  private $script;

  public function applies(): bool {
    $this->script = exec('which SwitchAudioSource');

    return is_executable($this->script);
  }

  public function getInput(): string {
    return exec(sprintf('%s -c -t input', $this->script));
  }

  public function getOutput(): string {
    return exec(sprintf('%s -c -t output', $this->script));
  }

  public function setInput(string $device_name) {
    exec(sprintf('%s -s "%s" -t input', $this->script, $device_name));
  }

  public function setOutput(string $device_name) {
    exec(sprintf('%s -s "%s" -t output', $this->script, $device_name));
  }
}
