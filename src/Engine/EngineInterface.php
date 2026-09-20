<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Engine;

use AKlump\ChangeAudio\DeviceReference;

interface EngineInterface {

  public function applies(): bool;

  /**
   * @param \AKlump\ChangeAudio\DeviceReference $device A device name, numeric
   *   identifier or UID.
   * @param float $limit A value from 0 to 1.
   *
   * @return string
   *
   * @throws \AKlump\ChangeAudio\Exception\EngineFeatureException;
   * @throws \AKlump\ChangeAudio\Exception\MissingDeviceException;
   */
  public function getCommandSetOutputLevel(DeviceReference $device, float $limit): string;

  /**
   * @param \AKlump\ChangeAudio\DeviceReference $device A device name, numeric
   *   identifier or UID.
   * @param float $limit A value from 0 to 1.
   *
   * @throws \AKlump\ChangeAudio\Exception\EngineFeatureException;
   * @throws \AKlump\ChangeAudio\Exception\MissingDeviceException;
   */
  public function getCommandSetInputLevel(DeviceReference $device, float $limit): string;

  /**
   * @param \AKlump\ChangeAudio\DeviceReference $device A device name, numeric
   *   identifier or UID.
   *
   * @throws \AKlump\ChangeAudio\Exception\EngineFeatureException;
   * @throws \AKlump\ChangeAudio\Exception\MissingDeviceException;
   */
  public function getCommandChangeInput(DeviceReference $device): string;

  /**
   * @param \AKlump\ChangeAudio\DeviceReference $device A device name, numeric
   *   identifier or UID.
   *
   * @throws \AKlump\ChangeAudio\Exception\EngineFeatureException;
   * @throws \AKlump\ChangeAudio\Exception\MissingDeviceException;
   */
  public function getCommandChangeOutput(DeviceReference $device): string;

  /**
   * @return string The URL where this engine can be downloaded.
   */
  public function getHomepage(): string;

  /**
   * @return \AKlump\ChangeAudio\Device[]
   */
  public function getAllDevices(): array;
}
