<?php

namespace AKlump\ChangeAudio\Tests\Unit\Cache;

use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\Tests\Unit\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \AKlump\ChangeAudio\Cache\CacheManager
 * @uses \AKlump\ChangeAudio\ValidateConfiguration
 */
class CacheManagerTest extends TestCase {

  use TestWithFilesTrait;

  public function testGetPathCannotCreateDirectoryThrows() {
    $cache_dir = $this->getTestFileFilepath('cache/readonly/', TRUE);
    $this->deleteTestFile($cache_dir);
    chmod(dirname($cache_dir), 0555);
    $this->assertDirectoryDoesNotExist($cache_dir);
    putenv('CACHE_PATH=' . $cache_dir);
    $this->expectException(RuntimeException::class);
    (new CacheManager())->getPath();
  }

  public function testGetPathCreatesDirectory() {
    $cache_dir = $this->getTestFileFilepath('cache/');
    $this->deleteTestFile($cache_dir);
    $this->assertDirectoryDoesNotExist($cache_dir);
    putenv('CACHE_PATH=' . $cache_dir);
    (new CacheManager())->getPath();
    $this->assertDirectoryExists($cache_dir);
  }

  public function testGetPathThrowsWhenEnvIsEmpty() {
    $this->expectException(RuntimeException::class);
    (new CacheManager())->getPath();
  }
}
