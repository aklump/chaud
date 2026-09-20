<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Engine;

use AKlump\ChangeAudio\DeviceReference;
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

  public function getCommandChangeInput(DeviceReference $device): string {
    return $this->getCommandChange($device, 'input');
  }

  public function getCommandChangeOutput(DeviceReference $device): string {
    return $this->getCommandChange($device, 'output');
  }

  private function getCommandChange(DeviceReference $device, string $type): string {
    // SwitchAudioSource matches -u as a substring of the UID, so a short or
    // partial "uid" in config may select a different device than intended.
    return sprintf('%s %s "%s" -t %s', $this->script, $device->isUid() ? '-u' : '-s', $device, $type);
  }

  public function getHomepage(): string {
    return 'https://github.com/deweller/switchaudio-osx';
  }

  public function getCommandSetOutputLevel(DeviceReference $device, float $volume): string {
    throw new EngineFeatureException("SwitchAudioOSXEngine does not support output levels.");
  }

  public function getCommandSetInputLevel(DeviceReference $device, float $volume): string {
    throw new EngineFeatureException("SwitchAudioOSXEngine does not support input levels.");
  }

  public function getAllDevices(): array {
    // TODO Implement
    return [];
  }
}
