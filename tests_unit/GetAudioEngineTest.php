<?php

namespace AKlump\ChangeAudio\Tests\Unit;

use AKlump\ChangeAudio\Engine\EngineInterface;
use AKlump\ChangeAudio\Engine\SwitchAudioCommandEngine;
use AKlump\ChangeAudio\GetAudioEngine;
use AKlump\ChangeAudio\Tests\Unit\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\GetAudioEngine
 * @uses   \AKlump\ChangeAudio\Cache\CacheManager
 * @uses   \AKlump\ChangeAudio\Engine\MacOSAudioDevicesEngine
 * @uses   \AKlump\ChangeAudio\Engine\SwitchAudioCommandEngine::applies
 * @uses   \AKlump\ChangeAudio\Engine\SwitchAudioOSXEngine::applies
 */
class GetAudioEngineTest extends TestCase {

  use TestWithFilesTrait;

  private string $userHome;

  private ?string $originalHome;

  private ?string $originalPath;

  public function testReturnsNullWhenNoEngineApplies() {
    $this->assertNull((new GetAudioEngine())());
  }

  public function testReturnsTheFirstEngineThatApplies() {
    $script = $this->getTestFileFilepath('home/bin/', TRUE) . '/SwitchAudio';
    touch($script);
    chmod($script, 0755);
    $this->assertInstanceOf(SwitchAudioCommandEngine::class, (new GetAudioEngine())());
    $this->assertInstanceOf(EngineInterface::class, (new GetAudioEngine())());
  }

  protected function setUp(): void {
    if (is_executable(__DIR__ . '/../node_modules/.bin/macos-audio-devices')) {
      $this->markTestSkipped('The macos-audio-devices engine is installed and takes priority.');
    }
    $this->originalHome = getenv('HOME') ?: NULL;
    $this->originalPath = getenv('PATH') ?: NULL;

    // Both remaining engines look outside the project: one for
    // SwitchAudioSource on $PATH, one for ~/bin/SwitchAudio. Point them at a
    // sandbox so the result does not depend on what this machine has
    // installed.
    $this->userHome = $this->getTestFileFilepath('home/', TRUE);
    $this->deleteTestFile($this->userHome);
    $this->userHome = $this->getTestFileFilepath('home/', TRUE);
    putenv('HOME=' . $this->userHome);
    putenv('PATH=' . $this->userHome . '/bin:/usr/bin:/bin');
    if (exec('which SwitchAudioSource')) {
      $this->markTestSkipped('SwitchAudioSource is installed in a system directory and takes priority.');
    }
    putenv('CACHE_PATH=' . $this->getTestFileFilepath('cache/', TRUE));
    parent::setUp();
  }

  protected function tearDown(): void {
    putenv(isset($this->originalHome) ? 'HOME=' . $this->originalHome : 'HOME');
    putenv(isset($this->originalPath) ? 'PATH=' . $this->originalPath : 'PATH');
    putenv('CACHE_PATH');
    $this->deleteAllTestFiles();
    parent::tearDown();
  }
}
