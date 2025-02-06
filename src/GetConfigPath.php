<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\AudioSwitch;

class GetConfigPath {

  public function __invoke(): string {
    return $_SERVER['HOME'] . '/.com.aklump.chaud.json';
  }
}
