<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Engine;

use AKlump\ChangeAudio\DeviceReference;
use AKlump\ChangeAudio\Exception\EngineFeatureException;

/**
 * Does not provide current device info.
 * Does not provide volume control.
 * Does not support identifying devices by UID.
 */
class SwitchAudioCommandEngine implements EngineInterface {

  private string $script;

  public function applies(): bool {
    $this->script = getenv('HOME') . '/bin/SwitchAudio';

    return is_executable($this->script);
  }

  public function getCommandChangeInput(DeviceReference $device): string {
    $this->assertNotUid($device);

    return sprintf("%s -i '%s'", $this->script, $device);
  }

  public function getCommandChangeOutput(DeviceReference $device): string {
    $this->assertNotUid($device);

    return sprintf("%s -o '%s'", $this->script, $device);
  }

  public function getHomepage(): string {
    return 'https://www.macscripter.net/t/switchaudio-a-command-line-tool-to-change-the-audio-input-and-output-device/75630/1';
  }

  public function getCommandSetOutputLevel(DeviceReference $device, float $volume): string {
    throw new EngineFeatureException("SwitchAudioCommandEngine does not support output levels.");
  }

  public function getCommandSetInputLevel(DeviceReference $device, float $volume): string {
    throw new EngineFeatureException("SwitchAudioCommandEngine does not support input levels.");
  }

  public function getAllDevices(): array {
    // TODO Implement
    return [];
  }

  /**
   * The capabilities of ~/bin/SwitchAudio cannot be verified, so a UID is not
   * assumed to be understood.
   *
   * @throws \AKlump\ChangeAudio\Exception\EngineFeatureException
   */
  private function assertNotUid(DeviceReference $device): void {
    if ($device->isUid()) {
      throw new EngineFeatureException('SwitchAudioCommandEngine does not support devices by "uid"; use "device" instead.');
    }
  }
}
