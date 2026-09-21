<?php

namespace AKlump\ChangeAudio\Tests\Unit\Command;

use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\Command\ConfigCommand;
use AKlump\ChangeAudio\ConfigManager;
use AKlump\ChangeAudio\Tests\Unit\TestingTraits\TestWithFilesTrait;
use AKlump\ChangeAudio\Tests\Unit\TestingTraits\WriteUserConfigTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \AKlump\ChangeAudio\Command\ConfigCommand
 * @uses   \AKlump\ChangeAudio\ConfigManager
 * @uses   \AKlump\ChangeAudio\App
 * @uses   \AKlump\ChangeAudio\Cache\CacheManager
 * @uses   \AKlump\ChangeAudio\ValidateConfiguration
 */
class ConfigCommandTest extends TestCase {

  use TestWithFilesTrait;
  use WriteUserConfigTrait;

  private string $userHome;

  private ?string $originalCachePath;

  protected function setUp(): void {
    $this->originalCachePath = getenv('CHAUDIO_CACHE_PATH') === FALSE ? NULL : getenv('CHAUDIO_CACHE_PATH');
    $this->userHome = $this->getTestFileFilepath('home/', TRUE);
    putenv('CHAUDIO_CACHE_PATH=' . $this->getTestFileFilepath('cache/', TRUE));
  }

  protected function tearDown(): void {
    putenv($this->originalCachePath === NULL ? 'CHAUDIO_CACHE_PATH' : 'CHAUDIO_CACHE_PATH=' . $this->originalCachePath);
    $this->deleteAllTestFiles();
  }

  private function getTester(): CommandTester {
    $config = new ConfigManager(new CacheManager(), $this->userHome, __DIR__ . '/../../install/config.yml');

    return new CommandTester(new ConfigCommand($config));
  }

  public function testInstallsDefaultConfigWhenMissingAndPrintsPath() {
    $path = $this->getUserConfigPath($this->userHome);
    $this->assertFileDoesNotExist($path);
    $tester = $this->getTester();
    $this->assertSame(Command::SUCCESS, $tester->execute([]));
    $this->assertFileExists($path);
    $this->assertStringContainsString($path, $tester->getDisplay());
  }

  public function testExistingConfigIsNotOverwritten() {
    $contents = '{"options":[]}';
    $path = $this->writeUserConfig($this->userHome, $contents);
    $tester = $this->getTester();
    $this->assertSame(Command::SUCCESS, $tester->execute([]));
    $this->assertSame($contents, file_get_contents($path));
    $this->assertStringContainsString($path, $tester->getDisplay());
  }

  public function testNameAndDescription() {
    $config = new ConfigManager(new CacheManager(), $this->userHome, __DIR__ . '/../../install/config.yml');
    $command = new ConfigCommand($config);
    $this->assertSame('config', $command->getName());
    $this->assertNotEmpty($command->getDescription());
  }

}
