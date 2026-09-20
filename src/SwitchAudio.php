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
    // be found leaves the audio untouched rather than half switched.
    try {
      $commands = $this->getEngineCommands($option);
    }
    catch (MissingDeviceException $exception) {
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
   */
  private function getEngineCommands(array $option): array {
    $get_level = new GetDeviceLevel();
    $commands = ['levels' => []];

    if (!empty($option['input'])) {
      $commands['input'] = $this->engine->getCommandChangeInput($option['input']['device']);
    }
    if (!empty($option['output'])) {
      $commands['output'] = $this->engine->getCommandChangeOutput($option['output']['device']);
    }

    $level = $get_level($option, DeviceTypes::INPUT);
    if (isset($level)) {
      try {
        $commands['levels'][] = $this->engine->getCommandSetInputLevel($option['input']['device'], $level);
      }
      catch (EngineFeatureException $exception) {
        // Level feature not supported by engine.
      }
    }

    $level = $get_level($option, DeviceTypes::OUTPUT);
    if (isset($level)) {
      try {
        $commands['levels'][] = $this->engine->getCommandSetOutputLevel($option['output']['device'], $level);
      }
      catch (EngineFeatureException $exception) {
        // Level feature not supported by engine.
      }
    }

    return $commands;
  }

  private function getUserMessage(array $option): string {
    $input = $this->normalizeDevicePointer($option['input']['device'] ?? '');
    $output = $this->normalizeDevicePointer($option['output']['device'] ?? '');

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
   * @param string|int $pointer A device name or numeric ID.
   *
   * @return string The device name; empty if a numeric ID matches no device.
   */
  private function normalizeDevicePointer($pointer): string {
    if (!is_numeric($pointer)) {
      return (string) $pointer;
    }

    return array_reduce($this->engine->getAllDevices(), function (string $carry, Device $device) use ($pointer) {
      if ($device->getId() == $pointer) {
        return $device->getName();
      }

      return $carry;
    }, '');
  }

}
