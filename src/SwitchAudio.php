<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio;

use AKlump\ChangeAudio\Engine\EngineInterface;
use AKlump\ChangeAudio\Exception\EngineFeatureException;
use AKlump\ChangeAudio\Exception\MissingDeviceException;
use AKlump\ChangeAudio\Process\CommandRunner;

/**
 * Makes one configured option active by running the engine's commands and the
 * option's own scripts.
 */
class SwitchAudio {

  private EngineInterface $engine;

  private CommandRunner $runner;

  public function __construct(EngineInterface $engine, CommandRunner $runner) {
    $this->engine = $engine;
    $this->runner = $runner;
  }

  /**
   * @param array $option One entry of the config "options".
   *
   * @return \AKlump\ChangeAudio\SwitchResult
   */
  public function __invoke(array $option): SwitchResult {
    $errors = [];
    $ran = [];

    // Every engine command is built before any is run, so a device that cannot
    // be found, or that the engine cannot address (e.g. by UID), leaves the
    // audio untouched rather than half switched.
    try {
      $commands = $this->getEngineCommands($option);
    }
    catch (MissingDeviceException | EngineFeatureException $exception) {
      return new SwitchResult(FALSE, '', [
        '❌ ' . $exception->getMessage(),
        '⚠️ Audio remains unchanged.',
      ]);
    }

    $run = function (string $command) use (&$ran): int {
      $ran[] = $command;

      return $this->runner->run($command);
    };

    if (isset($commands['input']) && $run($commands['input']) !== 0) {
      $errors[] = '❌ Failed to change input device.';
    }
    if (isset($commands['output']) && $run($commands['output']) !== 0) {
      $errors[] = '❌ Failed to change output device.';
    }
    // A failing level command has never made the switch fail.
    foreach ($commands['levels'] as $command) {
      $run($command);
    }
    foreach (($option['scripts'] ?? []) as $script) {
      if ($run($script) !== 0) {
        $errors[] = sprintf('❌ Script failed: %s', $script);
      }
    }

    if ($errors) {
      $errors[] = '⚠️ Audio remains unchanged.';

      return new SwitchResult(FALSE, '', $errors, $ran);
    }

    return new SwitchResult(TRUE, $this->getUserMessage($option), [], $ran);
  }

  /**
   * @return array With keys "input" and "output" (each present only when the
   *   option configures that device) and "levels", a list of commands.
   *
   * @throws \AKlump\ChangeAudio\Exception\MissingDeviceException
   * @throws \AKlump\ChangeAudio\Exception\EngineFeatureException If the engine
   *   cannot address a device the way the option does (levels are skipped).
   */
  private function getEngineCommands(array $option): array {
    $get_level = new GetDeviceLevel();
    $commands = ['levels' => []];

    $input = empty($option['input']) ? NULL : DeviceReference::fromConfig($option['input']);
    $output = empty($option['output']) ? NULL : DeviceReference::fromConfig($option['output']);

    if ($input) {
      $commands['input'] = $this->engine->getCommandChangeInput($input);
    }
    if ($output) {
      $commands['output'] = $this->engine->getCommandChangeOutput($output);
    }

    $level = $get_level($option, DeviceTypes::INPUT);
    if (isset($level)) {
      try {
        $commands['levels'][] = $this->engine->getCommandSetInputLevel($input, $level);
      }
      catch (EngineFeatureException $exception) {
        // Level feature not supported by engine.
      }
    }

    $level = $get_level($option, DeviceTypes::OUTPUT);
    if (isset($level)) {
      try {
        $commands['levels'][] = $this->engine->getCommandSetOutputLevel($output, $level);
      }
      catch (EngineFeatureException $exception) {
        // Level feature not supported by engine.
      }
    }

    return $commands;
  }

  private function getUserMessage(array $option): string {
    $input = $this->getDeviceName($option['input'] ?? []);
    $output = $this->getDeviceName($option['output'] ?? []);

    $details = [];
    if ($input) {
      $details[] = '🎤 ' . $input;
    }
    if ($output) {
      $details[] = '🔈 ' . $output;
    }

    return sprintf('%s is active (%s)', $option['label'], implode('  ', $details));
  }

  /**
   * @param array $device_config An option's "input" or "output" config, or [].
   *
   * @return string The device name.  A numeric ID that matches no device yields
   *   an empty string; a UID that matches no device is shown as is.
   */
  private function getDeviceName(array $device_config): string {
    if (!$device_config) {
      return '';
    }
    if (isset($device_config[DeviceReference::UID], $device_config[DeviceReference::NAME]) && !is_numeric($device_config[DeviceReference::NAME])) {
      return (string) $device_config[DeviceReference::NAME];
    }
    $reference = DeviceReference::fromConfig($device_config);
    if (!$reference->isUid() && !is_numeric($reference->getValue())) {
      return (string) $reference;
    }

    foreach ($this->engine->getAllDevices() as $device) {
      if ($reference->matches($device)) {
        return $device->getName();
      }
    }

    return $reference->isUid() ? (string) $reference : '';
  }

}
