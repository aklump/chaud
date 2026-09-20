<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio;

use AKlump\ChangeAudio\Cache\CacheManager;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

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

  const CONFIG_BASENAME = '.' . App::BIN . '.yml';

  /**
   * @var \AKlump\ChangeAudio\Cache\CacheManager
   */
  private CacheManager $cache;

  private string $userHome;

  private string $defaultConfigPath;

  private array $validationErrors = [];

  public function getValidationErrors(): array {
    return $this->validationErrors;
  }

  public function __construct(CacheManager $cache_manager, string $user_home = '', string $default_config_path = '') {
    $this->cache = $cache_manager;
    $user_home = $user_home ?: $_SERVER['HOME'] ?? '';
    $this->setUserHome($user_home);
    $this->defaultConfigPath = $default_config_path ?: __DIR__ . '/../install/config.yml';
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
      if (!file_exists($config_path) && !$this->migrateLegacyConfig($config_path) && !$this->installDefaultConfig($config_path)) {
        $message = error_get_last()['message'] ?? '';
        throw new RuntimeException(sprintf("Failed to install config at: %s\n$message", $config_path));
      }
      $config = $this->renameLegacyDeviceKeys(Yaml::parseFile($config_path));
      $this->validationErrors = (new ValidateConfiguration())($config);
      if (!$this->validationErrors) {
        file_put_contents($config_include, '<?php return ' . var_export($config, TRUE) . ';');
      }
    }

    return $config;
  }

  public function path(): string {
    return $this->userHome . '/' . self::CONFIG_BASENAME;
  }

  /**
   * Accept the former "device" key as "name", matching `chaudio devices`.
   */
  private function renameLegacyDeviceKeys($config) {
    if (!is_array($config) || !is_array($config['options'] ?? NULL)) {
      return $config;
    }
    foreach ($config['options'] as $i => $option) {
      foreach (['input', 'output'] as $direction) {
        if (is_array($option[$direction] ?? NULL) && array_key_exists('device', $option[$direction]) && !array_key_exists('name', $option[$direction])) {
          $option[$direction]['name'] = $option[$direction]['device'];
          unset($option[$direction]['device']);
        }
      }
      $config['options'][$i] = $option;
    }

    return $config;
  }

  /**
   * Convert a pre-YAML ~/.chaudio.json into the YAML config file.
   */
  private function migrateLegacyConfig(string $config_path): bool {
    $legacy_path = $this->userHome . '/.' . App::BIN . '.json';
    if (!file_exists($legacy_path)) {
      return FALSE;
    }
    $legacy = json_decode(file_get_contents($legacy_path), TRUE);
    if (!is_array($legacy)) {
      return FALSE;
    }

    return @file_put_contents($config_path, Yaml::dump($legacy, 6, 2)) !== FALSE;
  }

  private function installDefaultConfig(string $config_path): bool {
    if (!file_exists($this->defaultConfigPath)) {
      throw new RuntimeException(sprintf('Missing default config: %s', $this->defaultConfigPath));
    }

    return @copy($this->defaultConfigPath, $config_path);
  }

}
