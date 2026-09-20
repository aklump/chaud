<?php

namespace AKlump\ChangeAudio\Tests\Unit\Engine;

use AKlump\ChangeAudio\DeviceReference;
use AKlump\ChangeAudio\Engine\SwitchAudioOSXEngine;
use AKlump\ChangeAudio\Exception\EngineFeatureException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\Engine\SwitchAudioOSXEngine
 * @uses   \AKlump\ChangeAudio\Exception\EngineFeatureException
 * @uses   \AKlump\ChangeAudio\DeviceReference
 */
class SwitchAudioOSXEngineTest extends TestCase {

  public function testAppliesReflectsWhetherTheCommandIsOnThePath() {
    $expected = is_executable(exec('which SwitchAudioSource'));
    $this->assertSame($expected, (new SwitchAudioOSXEngine())->applies());
  }

  public function testGetCommandChangeInputUsesTheInputFlag() {
    $engine = new SwitchAudioOSXEngine();
    $engine->applies();
    $this->assertStringEndsWith(' -s "External Microphone" -t input', $engine->getCommandChangeInput(new DeviceReference(DeviceReference::DEVICE, 'External Microphone')));
  }

  public function testGetCommandChangeOutputUsesTheOutputFlag() {
    $engine = new SwitchAudioOSXEngine();
    $engine->applies();
    $this->assertStringEndsWith(' -s "External Headphones" -t output', $engine->getCommandChangeOutput(new DeviceReference(DeviceReference::DEVICE, 'External Headphones')));
  }

  public function testUidIsPassedWithTheUidFlag() {
    $engine = new SwitchAudioOSXEngine();
    $engine->applies();
    $uid = new DeviceReference(DeviceReference::UID, 'BuiltInSpeakerDevice');
    $this->assertStringEndsWith(' -u "BuiltInSpeakerDevice" -t output', $engine->getCommandChangeOutput($uid));
    $this->assertStringEndsWith(' -u "BuiltInSpeakerDevice" -t input', $engine->getCommandChangeInput($uid));
  }

  public function testGetHomepage() {
    $this->assertSame('https://github.com/deweller/switchaudio-osx', (new SwitchAudioOSXEngine())->getHomepage());
  }

  public function testGetCommandSetOutputLevelThrows() {
    $this->expectException(EngineFeatureException::class);
    (new SwitchAudioOSXEngine())->getCommandSetOutputLevel(new DeviceReference(DeviceReference::DEVICE, 'External Headphones'), 0.25);
  }

  public function testGetCommandSetInputLevelThrows() {
    $this->expectException(EngineFeatureException::class);
    (new SwitchAudioOSXEngine())->getCommandSetInputLevel(new DeviceReference(DeviceReference::DEVICE, 'External Microphone'), 0.25);
  }

  public function testGetAllDevicesIsNotImplemented() {
    $this->assertSame([], (new SwitchAudioOSXEngine())->getAllDevices());
  }
}
