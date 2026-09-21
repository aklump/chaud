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

  const CONFIG_BASENAME = 'config.yml';

  /**
   * @var \AKlump\ChangeAudio\Cache\CacheManager
   */
  private CacheManager $cache;

  private string $userHome;

  private string $configDirectory;

  private string $defaultConfigPath;

  private array $validationErrors = [];

  private array $notices = [];

  public function getValidationErrors(): array {
    return $this->validationErrors;
  }

  /**
   * @return string[] Messages about what loading the config changed, such as
   *   moving a legacy config file.
   */
  public function getNotices(): array {
    return $this->notices;
  }

  /**
   * @param \AKlump\ChangeAudio\Cache\CacheManager $cache_manager
   * @param string $user_home Defaults to $_SERVER['HOME']. When given, as in
   *   tests, $XDG_CONFIG_HOME is ignored so the config stays under it.
   * @param string $default_config_path
   */
  public function __construct(CacheManager $cache_manager, string $user_home = '', string $default_config_path = '') {
    $this->cache = $cache_manager;
    $this->setUserHome($user_home ?: $_SERVER['HOME'] ?? '');
    $config_home = $user_home ? $this->userHome . '/.config' : (new GetXdgBaseDirectory())('XDG_CONFIG_HOME', '.config', $this->userHome);
    $this->configDirectory = $config_home . '/' . App::BIN;
    $this->defaultConfigPath = $default_config_path ?: __DIR__ . '/../install/config.yml';
  }

  private function setUserHome(string $user_home): void {
    if (empty($user_home) || !file_exists($user_home) || !is_dir($user_home)) {
      throw new RuntimeException('Missing $_SERVER[HOME]');
    }
    $this->userHome = $user_home;
  }

  public function get(): array {
    $config_path = $this->path();
    $config_include = $this->cache->getPath() . '/config.php';
    $hash_file = $this->cache->getPath() . '/config.hash';

    // The cached config is only good for the exact file it was built from, so
    // editing the config never needs a manual cache clear.
    if (file_exists($config_include) && $this->isCacheCurrent($config_path, $hash_file)) {
      return require $config_include;
    }

    if (!file_exists($config_path) && !$this->createConfig($config_path)) {
      $message = error_get_last()['message'] ?? '';
      throw new RuntimeException(sprintf("Failed to install config at: %s\n$message", $config_path));
    }
    $config = $this->renameLegacyDeviceKeys(Yaml::parseFile($config_path));
    $this->validationErrors = (new ValidateConfiguration())($config);
    if ($this->validationErrors) {
      @unlink($config_include);
      @unlink($hash_file);
    }
    else {
      file_put_contents($config_include, '<?php return ' . var_export($config, TRUE) . ';');
      file_put_contents($hash_file, md5_file($config_path));
    }

    return $config;
  }

  private function isCacheCurrent(string $config_path, string $hash_file): bool {
    if (!file_exists($config_path) || !file_exists($hash_file)) {
      return FALSE;
    }

    return file_get_contents($hash_file) === md5_file($config_path);
  }

  public function path(): string {
    return $this->configDirectory . '/' . self::CONFIG_BASENAME;
  }

  private function createConfig(string $config_path): bool {
    if (!is_dir($this->configDirectory) && !@mkdir($this->configDirectory, 0755, TRUE)) {
      return FALSE;
    }

    return $this->moveLegacyYamlConfig($config_path)
      || $this->migrateLegacyConfig($config_path)
      || $this->installDefaultConfig($config_path);
  }

  /**
   * Move a ~/.chaudio.yml from before the XDG layout into the config directory.
   */
  private function moveLegacyYamlConfig(string $config_path): bool {
    $legacy_path = $this->userHome . '/.' . App::BIN . '.yml';
    if (!file_exists($legacy_path) || !@rename($legacy_path, $config_path)) {
      return FALSE;
    }
    $this->notices[] = sprintf('📦 Moved your config from %s to %s', $legacy_path, $config_path);

    return TRUE;
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
   *
   * The JSON file is left in place.
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

    if (@file_put_contents($config_path, Yaml::dump($legacy, 6, 2)) === FALSE) {
      return FALSE;
    }
    $this->notices[] = sprintf('📦 Converted your config from %s to %s', $legacy_path, $config_path);

    return TRUE;
  }

  private function installDefaultConfig(string $config_path): bool {
    if (!file_exists($this->defaultConfigPath)) {
      throw new RuntimeException(sprintf('Missing default config: %s', $this->defaultConfigPath));
    }

    return @copy($this->defaultConfigPath, $config_path);
  }

}
