<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Engine;

use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\Device;
use AKlump\ChangeAudio\DeviceReference;
use AKlump\ChangeAudio\DeviceTypes;
use AKlump\ChangeAudio\Exception\MissingDeviceException;
use InvalidArgumentException;
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

  public function getCommandChangeInput(DeviceReference $device): string {
    return sprintf("%s input set %d 1> /dev/null", $this->script, $this->getIdByDeviceReference(DeviceTypes::INPUT, $device));
  }

  public function getCommandChangeOutput(DeviceReference $device): string {
    return sprintf("%s output set %d 1> /dev/null", $this->script, $this->getIdByDeviceReference(DeviceTypes::OUTPUT, $device));
  }

  public function getCommandSetOutputLevel(DeviceReference $device, float $limit): string {
    return sprintf("%s volume set %d %f 1> /dev/null", $this->script, $this->getIdByDeviceReference(DeviceTypes::OUTPUT, $device), $limit);
  }

  public function getCommandSetInputLevel(DeviceReference $device, float $limit): string {
    throw new EngineFeatureException("MacOSAudioDevicesEngine does not support input levels.");
  }

  public function getHomepage(): string {
    return 'https://github.com/karaggeorge/macos-audio-devices';
  }

  /**
   * @return string|int The numeric device ID.
   *
   * @throws \AKlump\ChangeAudio\Exception\MissingDeviceException
   */
  private function getIdByDeviceReference(string $device_type, DeviceReference $device) {
    if (!$device->isUid() && is_numeric($device->getValue())) {
      return $device->getValue();
    }

    $pointer = $this->getDeviceFromIndex($device_type, $device)['id'] ?? '';
    if (empty($pointer)) {
      throw new MissingDeviceException(sprintf($device->isUid() ? 'Could not find device with uid "%s"' : 'Could not find device "%s"', $device));
    }

    return $pointer;
  }

  /**
   * @return array A device index datum with keys "id", "name" and "uid"; empty
   *   if the device cannot be found.
   */
  private function getDeviceFromIndex(string $device_type, DeviceReference $reference, bool $try_flush = TRUE): array {
    if ($device_type !== DeviceTypes::INPUT && $device_type !== DeviceTypes::OUTPUT) {
      throw new InvalidArgumentException(sprintf('Unknown device type: %s', $device_type));
    }
    $device_index_include = $this->cache->getPath() . '/' . (new ReflectionClass($this))->getShortName() . '.device_index_include.' . $device_type . '.php';
    if (file_exists($device_index_include)) {
      $device_index = require $device_index_include;
    }
    else {
      $device_index = array_values(array_map(function (array $datum) {
        return [
          'id' => (int) $datum['id'],
          'name' => (string) $datum['name'],
          'uid' => (string) ($datum['uid'] ?? ''),
        ];
      }, array_filter($this->listDevices(), function (array $datum) use ($device_type) {
        return !empty($datum['id']) && !empty($datum[$device_type === DeviceTypes::INPUT ? 'isInput' : 'isOutput']);
      })));
      file_put_contents($device_index_include, '<?php return ' . var_export($device_index, TRUE) . ';');
    }

    $device = array_filter($device_index, function ($datum) use ($reference) {
      if ($reference->isUid()) {
        // A cache written before UIDs were indexed has no "uid" key, which
        // simply misses and is rebuilt below.
        return ($datum['uid'] ?? '') === (string) $reference->getValue();
      }

      return $datum['name'] === (string) $reference->getValue();
    });

    $device = reset($device);
    if (!$device && $try_flush) {
      unlink($device_index_include);
      $device = $this->getDeviceFromIndex($device_type, $reference, FALSE);
    }

    return $device ?: [];
  }

  /**
   * @return array The raw `list --json` data, one datum per device.
   */
  private function listDevices(): array {
    exec(sprintf('%s list --json', $this->script), $lines);
    $data = json_decode(implode('', $lines), TRUE);

    return is_array($data) ? $data : [];
  }

  public function getAllDevices(): array {
    if (!isset($this->allDevices)) {
      $json = exec(sprintf('%s list --json', $this->script));
      $this->allDevices = json_decode($json, TRUE);
      $this->allDevices = array_map(function ($device) {
        $type = $device['isOutput'] ? DeviceTypes::OUTPUT : DeviceTypes::INPUT;

        return (new Device())->setId($device['id'])
          ->setName($device['name'])
          ->setUid($device['uid'] ?? '')
          ->setType($type);
      }, $this->allDevices);

      uasort($this->allDevices, fn($a, $b) => $a->getName() <=> $b->getName());
    }

    return $this->allDevices;
  }
}
