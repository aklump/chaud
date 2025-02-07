<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\AudioSwitch;

use RuntimeException;

class ConfigManager {

  /**
   * @var \AKlump\AudioSwitch\CacheManager
   */
  private CacheManager $cache;

  public function __construct(CacheManager $cache_manager) {
    $this->cache = $cache_manager;
  }

  public function get(): array {
    $config = [];
    $config_include = $this->cache->path() . '/config.php';
    if (file_exists($config_include)) {
      $config = require $config_include;
    }
    else {
      $config_path = $this->path();
      if (!file_exists($config_path) && !$this->installDefaultConfig($config_path)) {
        throw new RuntimeException(sprintf('Failed to install config at: %s', $config_path));
      }
      $config = json_decode(file_get_contents($config_path), TRUE);
      file_put_contents($config_include, '<?php return ' . var_export($config, TRUE) . ';');
    }

    return $config;
  }

  public function path(): string {
    return $_SERVER['HOME'] . '/.com.aklump.chaud.json';
  }

  private function installDefaultConfig(string $config_path): bool {
    $default_config_path = __DIR__ . '/../install/config.json';
    if (!file_exists($default_config_path)) {
      throw new \RuntimeException(sprintf('Missing default config: %s', $default_config_path));
    }

    return copy($default_config_path, $config_path);
  }
}
