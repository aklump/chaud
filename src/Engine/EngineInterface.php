<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Engine;

interface EngineInterface {

  public function applies(): bool;

  /**
   * @param string $device_name
   * @param float $volume A value from 0 to 1.
   *
   * @return string
   *
   * @throws \AKlump\ChangeAudio\Exception\EngineFeatureException;
   */
  public function getCommandSetOutputLevel(string $device_name, float $volume): string;

  /**
   * @throws \AKlump\ChangeAudio\Exception\EngineFeatureException;
   */
  public function getCommandSetInputLevel(string $device_name, float $volume): string;

  /**
   * @throws \AKlump\ChangeAudio\Exception\EngineFeatureException;
   */
  public function getCommandChangeInput(string $device_name): string;

  /**
   * @throws \AKlump\ChangeAudio\Exception\EngineFeatureException;
   */
  public function getCommandChangeOutput(string $device_name): string;

  public function getHomepage(): string;
}
