<?php

namespace AKlump\ChangeAudio\Tests\Unit;

use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\ConfigManager;
use AKlump\ChangeAudio\Tests\Unit\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\ConfigManager
 * @uses   \AKlump\ChangeAudio\Cache\CacheManager::getPath
 * @uses   \AKlump\ChangeAudio\ValidateConfiguration
 */
class ConfigManagerTest extends TestCase {

  use TestWithFilesTrait;

  private string $cacheDir;

  private string $userHome;

  private string $defaultConfig;

  public function testMissingDefaultConfigThrows() {
    $bogus = $this->getTestFileFilepath('bogus.json');
    $this->assertFileDoesNotExist($bogus);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Missing default config');
    (new ConfigManager(new CacheManager(), $this->userHome, $bogus))->get();
  }

  public function testFailedToInstallDefaultConfigThrows() {
    $manager = new ConfigManager(new CacheManager(), $this->userHome, $this->defaultConfig);
    chmod($this->userHome, 0444);
    $this->expectException(\RuntimeException::class);
    $manager->get();
  }

  public function testGetSecondCallUsesCachedFile() {
    $manager = new ConfigManager(new CacheManager(), $this->userHome, $this->defaultConfig);
    $a = $manager->get();
    $cache_file = $this->cacheDir . '/config.php';
    $this->assertFileExists($cache_file);
    // Poison the cache but keep the hash, to prove the cache is what is read.
    file_put_contents($cache_file, '<?php return ["cached" => TRUE];');
    $this->assertSame(['cached' => TRUE], $manager->get());
    $this->assertNotSame($a, ['cached' => TRUE]);
  }

  public function testEditingTheConfigInvalidatesTheCache() {
    $config_path = $this->userHome . '/' . ConfigManager::CONFIG_BASENAME;
    $manager = new ConfigManager(new CacheManager(), $this->userHome, $this->defaultConfig);
    $config = $manager->get();
    $this->assertArrayNotHasKey('scripts', $config['options'][0]);

    file_put_contents($config_path, file_get_contents($config_path) . "    scripts:\n      - echo hi\n");
    $config = $manager->get();
    $this->assertSame(['echo hi'], $config['options'][1]['scripts']);
  }

  public function testDeletedConfigIsReinstalledRatherThanServedFromCache() {
    $config_path = $this->userHome . '/' . ConfigManager::CONFIG_BASENAME;
    $manager = new ConfigManager(new CacheManager(), $this->userHome, $this->defaultConfig);
    $a = $manager->get();
    $this->deleteTestFile($config_path);
    $b = $manager->get();
    $this->assertFileExists($config_path);
    $this->assertSame($a, $b);
  }

  public function testInvalidEditRemovesTheStaleCache() {
    $config_path = $this->userHome . '/' . ConfigManager::CONFIG_BASENAME;
    $manager = new ConfigManager(new CacheManager(), $this->userHome, $this->defaultConfig);
    $manager->get();
    file_put_contents($config_path, json_encode(['options' => []]));
    $manager->get();
    $this->assertNotEmpty($manager->getValidationErrors());
    $this->assertFileDoesNotExist($this->cacheDir . '/config.php');
    $this->assertFileDoesNotExist($this->cacheDir . '/config.hash');
  }

  public function testGetValidationErrorsIsEmptyForTheDefaultConfig() {
    $manager = new ConfigManager(new CacheManager(), $this->userHome, $this->defaultConfig);
    $manager->get();
    $this->assertSame([], $manager->getValidationErrors());
  }

  public function testGetValidationErrorsDescribesAnInvalidConfigAndSkipsTheCache() {
    file_put_contents($this->userHome . '/' . ConfigManager::CONFIG_BASENAME, json_encode(['options' => []]));
    $manager = new ConfigManager(new CacheManager(), $this->userHome, $this->defaultConfig);
    $manager->get();
    $this->assertNotEmpty($manager->getValidationErrors());
    $this->assertFileDoesNotExist($this->cacheDir . '/config.php', 'Assert an invalid config is not cached.');
  }

  public function testLegacyJsonConfigIsMigratedToYaml() {
    $options = [['label' => 'A', 'input' => ['name' => 'x'], 'output' => ['name' => 'y']], ['label' => 'B', 'input' => ['name' => 'x'], 'output' => ['name' => 'z']]];
    file_put_contents($this->userHome . '/.chaudio.json', json_encode(['options' => $options]));
    $manager = new ConfigManager(new CacheManager(), $this->userHome, $this->defaultConfig);
    $this->assertSame(['options' => $options], $manager->get());
    $this->assertFileExists($this->userHome . '/' . ConfigManager::CONFIG_BASENAME);
    $this->assertSame([], $manager->getValidationErrors());
  }

  public function testLegacyDeviceKeyIsReadAsName() {
    file_put_contents($this->userHome . '/' . ConfigManager::CONFIG_BASENAME, "options:\n  - label: A\n    input: {device: Mic}\n    output: {device: 71}\n  - label: B\n    output: {name: Speakers}\n");
    $manager = new ConfigManager(new CacheManager(), $this->userHome, $this->defaultConfig);
    $config = $manager->get();
    $this->assertSame([], $manager->getValidationErrors());
    $this->assertSame(['name' => 'Mic'], $config['options'][0]['input']);
    $this->assertSame(['name' => 71], $config['options'][0]['output']);
  }

  public function testNonExistentUserHomeThrows() {
    $user_home = $this->getTestFileFilepath('user/');
    $this->deleteTestFile($user_home);
    $this->assertFileDoesNotExist($user_home);

    $this->expectException(\RuntimeException::class);
    (new ConfigManager(new CacheManager(), $user_home))->get();
  }

  public function testGetInstallsDefaultConfigAndCacheFiles() {
    (new ConfigManager(new CacheManager(), $this->userHome, $this->defaultConfig))->get();
    $this->assertFileExists($this->cacheDir . '/config.php', 'Assert cache was created.');

    $this->assertFileExists($this->userHome . '/' . ConfigManager::CONFIG_BASENAME, 'Assert config was created.');

    $this->assertFileExists($this->defaultConfig, 'Assert default config exists.');
    $this->assertFileEquals($this->defaultConfig, $this->userHome . '/' . ConfigManager::CONFIG_BASENAME, 'Assert config was created with default values.');
  }

  protected function setUp(): void {
    $this->userHome = $this->getTestFileFilepath('user/', TRUE);
    chmod($this->userHome, 0777);
    $this->deleteTestFile($this->userHome . '/' . ConfigManager::CONFIG_BASENAME);
    $this->assertFileDoesNotExist($this->userHome . '/' . ConfigManager::CONFIG_BASENAME);

    $this->cacheDir = $this->getTestFileFilepath('cache/');
    $this->deleteTestFile($this->cacheDir);
    putenv('CACHE_PATH=' . $this->cacheDir);
    $this->assertDirectoryDoesNotExist($this->cacheDir);

    $this->defaultConfig = realpath($this->getTestFileFilepath() . '/../default_config.yml');
    $this->assertFileExists($this->defaultConfig);
    parent::setUp();
  }


}
