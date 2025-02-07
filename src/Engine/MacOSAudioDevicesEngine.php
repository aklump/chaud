<?php

namespace AKlump\AudioSwitch\Engine;

use AKlump\AudioSwitch\Cache\CacheManager;
use AKlump\AudioSwitch\DeviceTypes;
use AKlump\AudioSwitch\Exception\AudioChangeException;
use ReflectionClass;

class MacOSAudioDevicesEngine implements EngineInterface {

  private CacheManager $cache;

  public function __construct(CacheManager $cache) {
    $this->cache = $cache;
  }

  private string $script;

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

  public function getCommandChangeInput(string $device_name): string {
    $device = $this->getDeviceByName(DeviceTypes::INPUT, $device_name);

    return sprintf("%s input set '%d'", $this->script, $device['id']);
  }

  public function getCommandChangeOutput(string $device_name): string {
    $device = $this->getDeviceByName(DeviceTypes::OUTPUT, $device_name);

    return sprintf("%s output set '%d'", $this->script, $device['id']);
  }

  public function getHomepage(): string {
    return 'https://github.com/karaggeorge/macos-audio-devices';
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
}
