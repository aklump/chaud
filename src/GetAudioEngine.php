<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\AudioSwitch;

use AKlump\AudioSwitch\Engine\EngineInterface;
use AKlump\AudioSwitch\Engine\SwitchAudioCommand;
use AKlump\AudioSwitch\Engine\SwitchAudioOSX;

class GetAudioEngine {

  public function __invoke(): ?EngineInterface {
    /** @var array $engines_by_priority The first that applies() === TRUE will be used. */
    $engines_by_priority = [
      new SwitchAudioOSX(),
      new SwitchAudioCommand(),
    ];
    foreach ($engines_by_priority as $engine) {
      if ($engine->applies()) {
        return $engine;
      }
    }

    return NULL;
  }
}
