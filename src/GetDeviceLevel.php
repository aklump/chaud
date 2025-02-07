<?php

namespace AKlump\AudioSwitch;

class GetDeviceLevel {


  /**
   * Retrieves the level for the specified audio device type from the audio configuration.
   *
   * @param array $audio_config
   *   An associative array containing device configuration, where the key is the device type,
   *   and the value contains further configuration parameters such as 'level'.
   * @param string $device_type
   *   The type of audio device to retrieve the level for.
   *
   * @return float|null
   *   The normalized audio level (between 0 and 1) if available, or NULL if the level
   *   is not defined for the given device type.
   */
  public function __invoke(array $audio_config, string $device_type): ?float {
    $device = $audio_config[$device_type] ?? [];
    if (!array_key_exists('level', $device)) {
      return NULL;
    }
    $level = max(0, $device['level']);

    // Prevent blowing out one's ears.  They probably entered 50 instead of 0.5
    if ($level > 1) {
      $level /= 100;
    }

    return $level;
  }
}
