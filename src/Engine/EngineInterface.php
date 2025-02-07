<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\AudioSwitch\Engine;

interface EngineInterface {

  public function applies(): bool;

  public function getCommandChangeInput(string $device_name): string;

  public function getCommandChangeOutput(string $device_name): string;

  public function getHomepage(): string;
}
