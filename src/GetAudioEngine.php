<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio;

use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\Engine\EngineInterface;
use AKlump\ChangeAudio\Engine\MacOSAudioDevicesEngine;
use AKlump\ChangeAudio\Engine\SwitchAudioCommandEngine;
use AKlump\ChangeAudio\Engine\SwitchAudioOSXEngine;

class GetAudioEngine {

  /**
   * Determines and returns the appropriate audio engine based on priority.
   *
   * This method evaluates a series of audio engine implementations in a
   * priority-defined order and selects the first one that applies to the
   * current context. If no engine is applicable, it will return NULL.
   *
   * @return EngineInterface|null The applicable audio engine or NULL if no engine applies.
   */
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
