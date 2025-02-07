<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\AudioSwitch\Engine;

interface EngineInterface {

  public function applies(): bool;

  public function getInput(): string;

  public function getOutput(): string;

  public function setInput(string $device_name);

  public function setOutput(string $device_name);

  public function getHomepage(): string;
}
