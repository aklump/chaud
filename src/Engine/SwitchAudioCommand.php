<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\AudioSwitch\Engine;

/**
 * @url https://www.macscripter.net/t/switchaudio-a-command-line-tool-to-change-the-audio-input-and-output-device/75630/1
 * @url https://klieme.ch/pub/SwitchAudio.dmg
 *
 * Does not provide current device info.
 * Does not provide volume control.
 */
class SwitchAudioCommand implements EngineInterface {

  private string $script;

  public function applies(): bool {
    $this->script = getenv('HOME') . '/bin/SwitchAudio';

    return is_executable($this->script);
  }

  public function setInput(string $device_name) {
    exec(sprintf("%s -i '%s'", $this->script, $device_name));
  }

  public function setOutput(string $device_name) {
    exec(sprintf("%s -o '%s'", $this->script, $device_name));
  }

  public function getInput(): string {
    return '';
  }

  public function getOutput(): string {
    return '';
  }
}
