<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio;

use AKlump\ChangeAudio\Cache\CacheManager;
use RuntimeException;


/**
 * Manages configuration for the application.
 *
 * This class is responsible for loading, caching, and installing the configuration
 * file used by the application. If a configuration file does not already exist,
 * the class will attempt to use a default set of configuration values.
 *
 * @package AKlump\ChangeAudio
 */
class ConfigManager {

  /**
   * @var \AKlump\ChangeAudio\Cache\CacheManager
   */
  private CacheManager $cache;

  public function __construct(CacheManager $cache_manager) {
    $this->cache = $cache_manager;
  }

  public function get(): array {
    $config = [];
    $config_include = $this->cache->getPath() . '/config.php';
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
      throw new RuntimeException(sprintf('Missing default config: %s', $default_config_path));
    }

    return copy($default_config_path, $config_path);
  }

}
