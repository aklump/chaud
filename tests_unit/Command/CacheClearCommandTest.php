<?php

namespace AKlump\ChangeAudio\Tests\Unit\Command;

use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\Command\CacheClearCommand;
use AKlump\ChangeAudio\Tests\Unit\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \AKlump\ChangeAudio\Command\CacheClearCommand
 * @uses   \AKlump\ChangeAudio\Cache\CacheManager
 * @uses   \AKlump\ChangeAudio\App
 */
class CacheClearCommandTest extends TestCase {

  use TestWithFilesTrait;

  private string $cacheDir;

  private ?string $originalCachePath;

  protected function setUp(): void {
    $this->originalCachePath = getenv('CHAUDIO_CACHE_PATH') === FALSE ? NULL : getenv('CHAUDIO_CACHE_PATH');
    $this->cacheDir = $this->getTestFileFilepath('cache/', TRUE);
    putenv('CHAUDIO_CACHE_PATH=' . $this->cacheDir);
  }

  protected function tearDown(): void {
    putenv($this->originalCachePath === NULL ? 'CHAUDIO_CACHE_PATH' : 'CHAUDIO_CACHE_PATH=' . $this->originalCachePath);
    $this->deleteAllTestFiles();
  }

  public function testRemovesCachedFilesAndKeepsTheDirectory() {
    file_put_contents($this->cacheDir . '/config.php', '<?php return [];');
    file_put_contents($this->cacheDir . '/MacOSAudioDevicesEngine.device_index_include.input.php', '<?php return [];');
    $tester = new CommandTester(new CacheClearCommand(new CacheManager()));
    $this->assertSame(Command::SUCCESS, $tester->execute([]));
    $this->assertFileDoesNotExist($this->cacheDir . '/config.php');
    $this->assertFileDoesNotExist($this->cacheDir . '/MacOSAudioDevicesEngine.device_index_include.input.php');
    $this->assertDirectoryExists($this->cacheDir);
  }

  public function testSucceedsWhenCacheIsAlreadyEmpty() {
    $tester = new CommandTester(new CacheClearCommand(new CacheManager()));
    $this->assertSame(Command::SUCCESS, $tester->execute([]));
    $this->assertDirectoryExists($this->cacheDir);
  }

  public function testNameAndDescription() {
    $command = new CacheClearCommand(new CacheManager());
    $this->assertSame('cache:clear', $command->getName());
    $this->assertSame(['cc'], $command->getAliases());
    $this->assertNotEmpty($command->getDescription());
  }

}
