<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\AudioSwitch;

use AKlump\AudioSwitch\Engine\EngineInterface;
use AKlump\AudioSwitch\Engine\MacOSAudioDevicesEngine;
use AKlump\AudioSwitch\Engine\SwitchAudioCommandEngine;
use AKlump\AudioSwitch\Engine\SwitchAudioOSXEngine;

class GetAudioEngine {

  public function __invoke(): ?EngineInterface {
    /** @var MacOSAudioDevicesEngine|SwitchAudioCommandEngine|SwitchAudioOSXEngine[] $engines_by_priority The first that applies() === TRUE will be used. */
    $engines_by_priority = [
      new MacOSAudioDevicesEngine(new CacheManager()),
      new SwitchAudioOSXEngine(),
      new SwitchAudioCommandEngine(),
    ];
    foreach ($engines_by_priority as $engine) {
      if ($engine->applies()) {
        return $engine;
      }
    }

    return NULL;
  }
}
