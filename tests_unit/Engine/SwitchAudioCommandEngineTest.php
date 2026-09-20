<?php

namespace AKlump\ChangeAudio\Tests\Unit\Engine;

use AKlump\ChangeAudio\DeviceReference;
use AKlump\ChangeAudio\Engine\SwitchAudioCommandEngine;
use AKlump\ChangeAudio\Exception\EngineFeatureException;
use AKlump\ChangeAudio\Tests\Unit\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\Engine\SwitchAudioCommandEngine
 * @uses   \AKlump\ChangeAudio\Exception\EngineFeatureException
 * @uses   \AKlump\ChangeAudio\DeviceReference
 */
class SwitchAudioCommandEngineTest extends TestCase {

  use TestWithFilesTrait;

  private string $userHome;

  private ?string $originalHome;

  public function testAppliesIsFalseWhenTheScriptIsMissing() {
    $this->assertFileDoesNotExist($this->userHome . '/bin/SwitchAudio');
    $this->assertFalse((new SwitchAudioCommandEngine())->applies());
  }

  public function testAppliesIsFalseWhenTheScriptIsNotExecutable() {
    $this->installScript(0644);
    $this->assertFalse((new SwitchAudioCommandEngine())->applies());
  }

  public function testAppliesIsTrueWhenTheScriptIsExecutable() {
    $this->installScript(0755);
    $this->assertTrue((new SwitchAudioCommandEngine())->applies());
  }

  public function testGetCommandChangeInputUsesTheScriptAndInputFlag() {
    $script = $this->installScript(0755);
    $engine = new SwitchAudioCommandEngine();
    $engine->applies();
    $this->assertSame(sprintf("%s -i 'External Microphone'", $script), $engine->getCommandChangeInput(new DeviceReference(DeviceReference::DEVICE, 'External Microphone')));
  }

  public function testGetCommandChangeOutputUsesTheScriptAndOutputFlag() {
    $script = $this->installScript(0755);
    $engine = new SwitchAudioCommandEngine();
    $engine->applies();
    $this->assertSame(sprintf("%s -o 'MacBook Pro Speakers'", $script), $engine->getCommandChangeOutput(new DeviceReference(DeviceReference::DEVICE, 'MacBook Pro Speakers')));
  }

  public function testUidThrowsBecauseItsSupportCannotBeVerified() {
    $this->installScript(0755);
    $engine = new SwitchAudioCommandEngine();
    $engine->applies();
    $uid = new DeviceReference(DeviceReference::UID, 'BuiltInSpeakerDevice');
    foreach (['getCommandChangeInput', 'getCommandChangeOutput'] as $method) {
      try {
        $engine->$method($uid);
        $this->fail("Expected an EngineFeatureException from $method.");
      }
      catch (EngineFeatureException $exception) {
        $this->assertStringContainsString('uid', $exception->getMessage());
      }
    }
  }

  public function testGetHomepage() {
    $this->assertStringStartsWith('https://', (new SwitchAudioCommandEngine())->getHomepage());
  }

  public function testGetCommandSetOutputLevelThrows() {
    $this->expectException(EngineFeatureException::class);
    (new SwitchAudioCommandEngine())->getCommandSetOutputLevel(new DeviceReference(DeviceReference::DEVICE, 'MacBook Pro Speakers'), 0.25);
  }

  public function testGetCommandSetInputLevelThrows() {
    $this->expectException(EngineFeatureException::class);
    (new SwitchAudioCommandEngine())->getCommandSetInputLevel(new DeviceReference(DeviceReference::DEVICE, 'External Microphone'), 0.25);
  }

  public function testGetAllDevicesIsNotImplemented() {
    $this->assertSame([], (new SwitchAudioCommandEngine())->getAllDevices());
  }

  private function installScript(int $mode): string {
    $script = $this->getTestFileFilepath('home/bin/', TRUE) . '/SwitchAudio';
    touch($script);
    chmod($script, $mode);

    return $script;
  }

  protected function setUp(): void {
    $this->originalHome = getenv('HOME') ?: NULL;
    $this->userHome = $this->getTestFileFilepath('home/', TRUE);
    $this->deleteTestFile($this->userHome . '/bin');
    putenv('HOME=' . $this->userHome);
    parent::setUp();
  }

  protected function tearDown(): void {
    putenv(isset($this->originalHome) ? 'HOME=' . $this->originalHome : 'HOME');
    $this->deleteTestFile($this->userHome);
    parent::tearDown();
  }
}
