<?php

namespace AKlump\ChangeAudio\Tests\Unit\Engine;

use AKlump\ChangeAudio\Engine\SwitchAudioOSXEngine;
use AKlump\ChangeAudio\Exception\EngineFeatureException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\Engine\SwitchAudioOSXEngine
 * @uses   \AKlump\ChangeAudio\Exception\EngineFeatureException
 */
class SwitchAudioOSXEngineTest extends TestCase {

  public function testAppliesReflectsWhetherTheCommandIsOnThePath() {
    $expected = is_executable(exec('which SwitchAudioSource'));
    $this->assertSame($expected, (new SwitchAudioOSXEngine())->applies());
  }

  public function testGetCommandChangeInputUsesTheInputFlag() {
    $engine = new SwitchAudioOSXEngine();
    $engine->applies();
    $this->assertStringEndsWith(' -s "External Microphone" -t input', $engine->getCommandChangeInput('External Microphone'));
  }

  public function testGetCommandChangeOutputUsesTheOutputFlag() {
    $engine = new SwitchAudioOSXEngine();
    $engine->applies();
    $this->assertStringEndsWith(' -s "External Headphones" -t output', $engine->getCommandChangeOutput('External Headphones'));
  }

  public function testGetHomepage() {
    $this->assertSame('https://github.com/deweller/switchaudio-osx', (new SwitchAudioOSXEngine())->getHomepage());
  }

  public function testGetCommandSetOutputLevelThrows() {
    $this->expectException(EngineFeatureException::class);
    (new SwitchAudioOSXEngine())->getCommandSetOutputLevel('External Headphones', 0.25);
  }

  public function testGetCommandSetInputLevelThrows() {
    $this->expectException(EngineFeatureException::class);
    (new SwitchAudioOSXEngine())->getCommandSetInputLevel('External Microphone', 0.25);
  }

  public function testGetAllDevicesIsNotImplemented() {
    $this->assertSame([], (new SwitchAudioOSXEngine())->getAllDevices());
  }
}
