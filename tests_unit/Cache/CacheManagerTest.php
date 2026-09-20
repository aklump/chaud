<?php

namespace AKlump\ChangeAudio\Tests\Unit\Cache;

use AKlump\ChangeAudio\App;
use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\Tests\Unit\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \AKlump\ChangeAudio\Cache\CacheManager
 * @uses \AKlump\ChangeAudio\ValidateConfiguration
 * @uses \AKlump\ChangeAudio\App
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

  public function testGetPathDefaultsToTmpdirWhenCachePathIsUnset() {
    $tmp = $this->getTestFileFilepath('tmpdir/', TRUE);
    $original = [getenv('CACHE_PATH'), getenv('TMPDIR'), getenv('TEMP')];
    try {
      putenv('CACHE_PATH');
      putenv('TEMP');
      // The trailing slash must not be doubled.
      putenv('TMPDIR=' . $tmp . '/');
      $path = (new CacheManager())->getPath();
      $this->assertSame($tmp . '/' . App::CACHE_DIRNAME, $path);
      $this->assertDirectoryExists($path);
      $this->assertSame('com.aklump.chaudio', basename($path));
    }
    finally {
      foreach (['CACHE_PATH', 'TMPDIR', 'TEMP'] as $i => $name) {
        putenv($original[$i] === FALSE ? $name : "$name=$original[$i]");
      }
    }
  }

  public function testGetPathDefaultFallsBackToTempThenSlashTmp() {
    $tmp = $this->getTestFileFilepath('temp/', TRUE);
    $original = [getenv('CACHE_PATH'), getenv('TMPDIR'), getenv('TEMP')];
    try {
      putenv('CACHE_PATH');
      putenv('TMPDIR');
      putenv('TEMP=' . $tmp);
      $this->assertSame($tmp . '/' . App::CACHE_DIRNAME, (new CacheManager())->getPath());
    }
    finally {
      foreach (['CACHE_PATH', 'TMPDIR', 'TEMP'] as $i => $name) {
        putenv($original[$i] === FALSE ? $name : "$name=$original[$i]");
      }
    }
  }

  public function testFlushRemovesFilesAndKeepsDirectories() {
    $cache_dir = $this->getTestFileFilepath('cache/', TRUE);
    $this->deleteTestFile($cache_dir);
    $cache_dir = $this->getTestFileFilepath('cache/', TRUE);
    mkdir($cache_dir . '/subdir');
    file_put_contents($cache_dir . '/config.php', '<?php return [];');
    putenv('CACHE_PATH=' . $cache_dir);
    $this->assertSame($cache_dir, (new CacheManager())->flush());
    $this->assertFileDoesNotExist($cache_dir . '/config.php');
    $this->assertDirectoryExists($cache_dir . '/subdir');
  }

}
