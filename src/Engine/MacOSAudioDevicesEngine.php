<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Engine;

use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\Device;
use AKlump\ChangeAudio\DeviceTypes;
use AKlump\ChangeAudio\Exception\AudioChangeException;
use ReflectionClass;
use AKlump\ChangeAudio\Exception\EngineFeatureException;

class MacOSAudioDevicesEngine implements EngineInterface {

  private CacheManager $cache;

  private array $allDevices;

  private string $script;

  public function __construct(CacheManager $cache) {
    $this->cache = $cache;
  }

  public function applies(): bool {
    $this->script = __DIR__ . '/../../node_modules/.bin/macos-audio-devices';

    return is_executable($this->script);
  }

  private function getDeviceTypeFlag(string $device_type): string {
    switch ($device_type) {
      case DeviceTypes::INPUT:
        return '--input';
      case DeviceTypes::OUTPUT:
        return '--output';
      default:
        throw new AudioChangeException();
    }
  }

  public function getCommandChangeInput(string $device): string {
    return sprintf("%s input set %d &> /dev/null", $this->script, $this->getIdByDevicePointer(DeviceTypes::INPUT, $device));
  }

  public function getCommandChangeOutput(string $device): string {
    return sprintf("%s output set %d &> /dev/null", $this->script, $this->getIdByDevicePointer(DeviceTypes::OUTPUT, $device));
  }

  public function getCommandSetOutputLevel(string $device, float $limit): string {
    return sprintf("%s volume set %d %f", $this->script, $this->getIdByDevicePointer(DeviceTypes::OUTPUT, $device), $limit);
  }

  public function getCommandSetInputLevel(string $device, float $limit): string {
    throw new EngineFeatureException("MacOSAudioDevicesEngine does not support input levels.");
  }

  public function getHomepage(): string {
    return 'https://github.com/karaggeorge/macos-audio-devices';
  }

  private function getIdByDevicePointer(string $device_type, string $device) {
    if (is_numeric($device)) {
      return $device;
    }

    return $this->getDeviceByName($device_type, $device)['id'];
  }

  private function getDeviceByName(string $device_type, string $name, bool $try_flush = TRUE): array {
    $device_index = [];
    $device_index_include = $this->cache->getPath() . '/' . (new ReflectionClass($this))->getShortName() . '.device_index_include.' . $device_type . '.php';
    if (file_exists($device_index_include)) {
      $device_index = require $device_index_include;
    }
    else {
      exec(sprintf('%s list %s', $this->script, $this->getDeviceTypeFlag($device_type)), $device_index);
      $device_index = array_map(function ($line) {
        preg_match('#(\d+) - (.+)#', $line, $matches);

        return [
          'id' => (int) ($matches[1] ?? 0),
          'name' => $matches[2] ?? '',
        ];
      }, $device_index);
      $device_index = array_filter($device_index, function ($datum) use ($name) {
        return $datum['id'];
      });
      file_put_contents($device_index_include, '<?php return ' . var_export($device_index, TRUE) . ';');
    }

    $device = array_filter($device_index, function ($datum) use ($name) {
      return $datum['name'] === $name;
    });

    $device = reset($device) ?? NULL;
    if (!$device && $try_flush) {
      unlink($device_index_include);
      $device = $this->getDeviceByName($device_type, $name, FALSE);
    }

    return $device ?? [];
  }

  public function getAllDevices(): array {
    if (!isset($this->allDevices)) {
      $json = exec(sprintf('%s list --json', $this->script));
      $this->allDevices = json_decode($json, TRUE);
      $this->allDevices = array_map(function ($device) {
        $type = $device['isOutput'] ? DeviceTypes::OUTPUT : DeviceTypes::INPUT;

        return (new Device())->setId($device['id'])
          ->setName($device['name'])
          ->setType($type);
      }, $this->allDevices);

      uasort($this->allDevices, fn($a, $b) => $a->getName() <=> $b->getName());
    }

    return $this->allDevices;
  }
}
