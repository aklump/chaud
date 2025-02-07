<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\AudioSwitch\Cache;

use AKlump\AudioSwitch\ConfigManager;
use AKlump\AudioSwitch\Engine\EngineInterface;

class CreateChangeFunctions {

  private EngineInterface $engine;

  public function __construct(EngineInterface $engine) {
    $this->engine = $engine;
  }


  /**
   * This class is responsible for generating bash functions
   * that can be used to change audio devices dynamically.
   *
   * It uses the provided EngineInterface implementation to get the appropriate
   * commands to change input and output devices, as well as a ConfigManager
   * to retrieve device configuration.
   *
   * The generated bash functions are written to the specified file path,
   * enabling users to execute these functions directly from the command line.
   */
  public function __invoke(string $device_index_path): void {
    $aliases = [];
    $names = [];
    $config = (new ConfigManager(new CacheManager()))->get();
    foreach ($config['options'] as $device) {
      $func_name = $this->getFunctionName($device['label']);
      $names[$func_name] = $this->getFunctionCode($func_name, $device);
      $names[strtolower($func_name)] = $this->getFunctionCode($func_name, $device);
      foreach (($device['aliases'] ?? []) as $item) {
        $func_name = $this->getFunctionName($item);
        $aliases[$func_name] = $this->getFunctionCode($func_name, $device);
        $aliases[strtolower($func_name)] = $this->getFunctionCode($func_name, $device);
      }
    }

    $functions = $aliases + $names;
    $functions = array_values($functions);
    $bash = '#!/usr/bin/env bash' . PHP_EOL . PHP_EOL;
    $bash .= implode(PHP_EOL, $functions) . PHP_EOL;

    file_put_contents($device_index_path, $bash);
  }

  private function getFunctionCode(string $func_name, array $device): string {
    $code = sprintf('%s(){', $func_name) . PHP_EOL;
    $code .= sprintf('  %s &> /dev/null', $this->engine->getCommandChangeInput($device['input']['deviceId'])) . PHP_EOL;
    $code .= sprintf('  %s &> /dev/null', $this->engine->getCommandChangeOutput($device['output']['deviceId'])) . PHP_EOL;
    $code .= '  ' . sprintf('echo "%s"', $this->getUserMessage($device)) . PHP_EOL;
    $code .= '}' . PHP_EOL;

    return $code;
  }

  private function getFunctionName(string $user_input): string {
    return 'change_to_' . str_replace(' ', '_', strtolower($user_input));
  }

  private function getUserMessage(array $device): string {
    return sprintf('%s is active (🎤 %s 🔈 %s)',
      $device['label'],
      $device['input']['deviceId'],
      $device['output']['deviceId']);
  }
}
