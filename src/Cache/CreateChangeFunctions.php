<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Cache;

use AKlump\ChangeAudio\ConfigManager;
use AKlump\ChangeAudio\Device;
use AKlump\ChangeAudio\DeviceTypes;
use AKlump\ChangeAudio\Engine\EngineInterface;
use AKlump\ChangeAudio\Exception\EngineFeatureException;
use AKlump\ChangeAudio\Exception\MissingDeviceException;
use AKlump\ChangeAudio\GetDeviceLevel;

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
      try {
        $names[$func_name] = $this->getFunctionCode($func_name, $device);
        $names[strtolower($func_name)] = $this->getFunctionCode($func_name, $device);
        foreach (($device['aliases'] ?? []) as $item) {
          $func_name = $this->getFunctionName($item);
          $aliases[$func_name] = $this->getFunctionCode($func_name, $device);
          $aliases[strtolower($func_name)] = $this->getFunctionCode($func_name, $device);
        }
      }
      catch (MissingDeviceException $exception) {
        // Probably the device was BT and was disconnected.
      }
    }

    $functions = $aliases + $names;
    $functions = array_values($functions);
    $bash = '#!/usr/bin/env bash' . PHP_EOL . PHP_EOL;
    $bash .= implode(PHP_EOL, $functions) . PHP_EOL;

    file_put_contents($device_index_path, $bash);
  }

  private function getFunctionCode(string $func_name, array $device): string {
    $get_level = new GetDeviceLevel();

    $code = sprintf('%s(){', $func_name) . PHP_EOL;
    $code .= 'local state' . PHP_EOL;

    if (!empty($device['input'])) {
      $code .= sprintf('  %s', $this->engine->getCommandChangeInput($device['input']['device'])) . PHP_EOL;
      $code .= '  [ $? -ne 0 ] && echo "❌ Failed to change input device." && state=false' . PHP_EOL;
    }

    if (!empty($device['output'])) {
      $code .= sprintf('  %s', $this->engine->getCommandChangeOutput($device['output']['device'])) . PHP_EOL;
      $code .= '  [ $? -ne 0 ] && echo "❌ Failed to change output device." && state=false' . PHP_EOL;
    }

    $level = $get_level($device, DeviceTypes::INPUT);
    if (isset($level)) {
      try {
        $code .= sprintf('  %s',
            $this->engine->getCommandSetInputLevel($device['input']['device'], $level)) . PHP_EOL;
      }
      catch (EngineFeatureException $exception) {
        // Level feature not supported by engine.
      }
    }

    $level = $get_level($device, DeviceTypes::OUTPUT);
    if (isset($level)) {
      try {
        $code .= sprintf('  %s',
            $this->engine->getCommandSetOutputLevel($device['output']['device'], $level)) . PHP_EOL;
      }
      catch (EngineFeatureException $exception) {
        // Level feature not supported by engine.
      }
    }

    if (isset($device['scripts'])) {
      foreach ($device['scripts'] as $script) {
        $code .= sprintf('  %s', $script) . PHP_EOL;
        $code .= sprintf('  [ $? -ne 0 ] && echo "❌ Script failed: %s" && state=false', $script) . PHP_EOL;
      }
    }

    $code .= '  [[ "$state" == "false" ]] && echo "⚠️ Audio remains unchanged." && return 1' . PHP_EOL;
    $code .= '  ' . sprintf('echo "%s"', $this->getUserMessage($device)) . PHP_EOL;
    $code .= '}' . PHP_EOL;

    return $code;
  }

  private function getFunctionName(string $user_input): string {
    return 'change_to_' . str_replace(' ', '_', strtolower($user_input));
  }

  private function getUserMessage(array $audio_config): string {
    $devices = $this->engine->getAllDevices();
    $input = $this->normalizeDevicePointer($audio_config['input']['device'] ?? '', $devices);
    $output = $this->normalizeDevicePointer($audio_config['output']['device'] ?? '', $devices);

    $details = [];
    if ($input) {
      $details[] = '🎤 ' . $input;
    }
    if ($output) {
      $details[] = '🔈 ' . $output;
    }
    $details = implode('  ', $details);

    return sprintf('%s is active (%s)', $audio_config['label'], $details);
  }

  private function normalizeDevicePointer($pointer, array $devices): string {
    if (!is_numeric($pointer)) {
      return $pointer;
    }

    return array_reduce($devices, function (string $carry, Device $device) use ($pointer) {
      if ($device->getId() == $pointer) {
        return $device->getName();
      }

      return $carry;
    }, '');
  }

}
