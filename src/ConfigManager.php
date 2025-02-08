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

  const CONFIG_BASENAME = '.chaud.json';

  /**
   * @var \AKlump\ChangeAudio\Cache\CacheManager
   */
  private CacheManager $cache;

  private string $userHome;

  private string $defaultConfigPath;

  public function __construct(CacheManager $cache_manager, string $user_home = '', string $default_config_path = '') {
    $this->cache = $cache_manager;
    $user_home = $user_home ?: $_SERVER['HOME'] ?? '';
    $this->setUserHome($user_home);
    $this->defaultConfigPath = $default_config_path ?: __DIR__ . '/../install/config.json';
  }

  private function setUserHome(string $user_home): void {
    if (empty($user_home) || !file_exists($user_home) || !is_dir($user_home)) {
      throw new RuntimeException('Missing $_SERVER[HOME]');
    }
    $this->userHome = $user_home;
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
        $message = error_get_last()['message'] ?? '';
        throw new RuntimeException(sprintf("Failed to install config at: %s\n$message", $config_path));
      }
      $config = json_decode(file_get_contents($config_path), TRUE);
      file_put_contents($config_include, '<?php return ' . var_export($config, TRUE) . ';');
    }

    return $config;
  }

  public function path(): string {
    return $this->userHome . '/' . self::CONFIG_BASENAME;
  }

  private function installDefaultConfig(string $config_path): bool {
    if (!file_exists($this->defaultConfigPath)) {
      throw new RuntimeException(sprintf('Missing default config: %s', $this->defaultConfigPath));
    }

    return @copy($this->defaultConfigPath, $config_path);
  }

}
