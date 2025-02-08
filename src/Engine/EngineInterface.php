<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Engine;

interface EngineInterface {

  public function applies(): bool;

  /**
   * @param string $device Can be a device name or numeric identifier.
   * @param float $limit A value from 0 to 1.
   *
   * @return string
   *
   * @throws \AKlump\ChangeAudio\Exception\EngineFeatureException;
   */
  public function getCommandSetOutputLevel(string $device, float $limit): string;

  /**
   * @param string $device Can be a device name or numeric identifier.
   * @param float $limit A value from 0 to 1.
   *
   * @throws \AKlump\ChangeAudio\Exception\EngineFeatureException;
   */
  public function getCommandSetInputLevel(string $device, float $limit): string;

  /**
   * @param string $device Can be a device name or numeric identifier.
   *
   * @throws \AKlump\ChangeAudio\Exception\EngineFeatureException;
   */
  public function getCommandChangeInput(string $device): string;

  /**
   * @param string $device Can be a device name or numeric identifier.
   *
   * @throws \AKlump\ChangeAudio\Exception\EngineFeatureException;
   */
  public function getCommandChangeOutput(string $device): string;

  /**
   * @return string The URL where this engine can be downloaded.
   */
  public function getHomepage(): string;

  /**
   * @return \AKlump\ChangeAudio\Device[]
   */
  public function getAllDevices(): array;
}
