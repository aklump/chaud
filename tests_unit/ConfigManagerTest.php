<?php

namespace AKlump\ChangeAudio\Tests\Unit;

use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\ConfigManager;
use AKlump\ChangeAudio\Tests\Unit\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\ConfigManager
 * @uses   \AKlump\ChangeAudio\Cache\CacheManager::getPath
 */
class ConfigManagerTest extends TestCase {

  use TestWithFilesTrait;

  private string $cacheDir;

  private string $userHome;

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
    $this->deleteTestFile($this->userHome . '/' . ConfigManager::CONFIG_BASENAME);
    $b = $manager->get();
    $this->assertFileDoesNotExist($this->userHome . '/' . ConfigManager::CONFIG_BASENAME);
    $this->assertSame($a, $b);
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

    $this->defaultConfig = realpath($this->getTestFileFilepath() . '/../default_config.json');
    $this->assertFileExists($this->defaultConfig);
    parent::setUp();
  }


}
