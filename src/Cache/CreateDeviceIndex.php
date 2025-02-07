<?php

namespace AKlump\AudioSwitch\Cache;

use AKlump\AudioSwitch\ConfigManager;

class CreateDeviceIndex {

  public function __invoke(string $device_index_path): void {
    $aliases = [];
    $names = [];
    $config = (new ConfigManager(new CacheManager()))->get();
    foreach ($config['options'] as $device) {
      $json = json_encode($device);
      $names[$device['label']] = $this->getDeviceArrayKey($device['label'], $json);
      $names[strtolower($device['label'])] = $this->getDeviceArrayKey(strtolower($device['label']), $json);
      foreach (($device['aliases'] ?? []) as $item) {
        $aliases[$item] = $this->getDeviceArrayKey($item, $json);
        $aliases[strtolower($item)] = $this->getDeviceArrayKey(strtolower($item), $json);
      }
    }

    $array_values = $aliases + $names;
    $array_values = array_values($array_values);
    $bash = sprintf('device_index=(%s)', implode(' ', $array_values)) . PHP_EOL;

    file_put_contents($device_index_path, $bash);

  }

  private function getDeviceArrayKey(string $user_input, string $device_json): string {
    return '"' . $user_input . '|' . str_replace('"', '\"', $device_json) . '"';
  }
}
