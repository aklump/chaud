<?php

namespace AKlump\ChangeAudio\Tests\Unit;

use AKlump\ChangeAudio\Device;
use AKlump\ChangeAudio\DeviceTypes;
use AKlump\ChangeAudio\DeviceReference;
use AKlump\ChangeAudio\Engine\EngineInterface;
use AKlump\ChangeAudio\Exception\EngineFeatureException;
use AKlump\ChangeAudio\Exception\MissingDeviceException;
use AKlump\ChangeAudio\Process\CommandRunner;
use AKlump\ChangeAudio\SwitchAudio;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\SwitchAudio
 * @covers \AKlump\ChangeAudio\SwitchResult
 * @uses   \AKlump\ChangeAudio\GetDeviceLevel
 * @uses   \AKlump\ChangeAudio\Device
 * @uses   \AKlump\ChangeAudio\DeviceReference
 */
class SwitchAudioTest extends TestCase {

  /**
   * @param array $failing Commands (exact strings) that should exit non-zero.
   */
  private function getRunner(array $failing = []): CommandRunner {
    return new class($failing) implements CommandRunner {

      public array $ran = [];

      private array $failing;

      public function __construct(array $failing) {
        $this->failing = $failing;
      }

      public function run(string $shell_command): int {
        $this->ran[] = $shell_command;

        return in_array($shell_command, $this->failing, TRUE) ? 7 : 0;
      }
    };
  }

  /**
   * @param bool $supports_input_levels Ignored unless $supports_levels; lets a
   *   test model an engine that can set output but not input levels (as the
   *   macos-audio-devices engine does).
   */
  private function getEngine(array $devices = [], bool $supports_levels = TRUE, array $missing = [], bool $supports_input_levels = TRUE, array $unsupported = []): EngineInterface {
    return new class($devices, $supports_levels, $missing, $supports_input_levels, $unsupported) implements EngineInterface {

      private array $devices;

      private bool $supportsLevels;

      private bool $supportsInputLevels;

      private array $missing;

      private array $unsupported;

      public function __construct(array $devices, bool $supports_levels, array $missing, bool $supports_input_levels, array $unsupported) {
        $this->unsupported = $unsupported;
        $this->devices = $devices;
        $this->supportsLevels = $supports_levels;
        $this->missing = $missing;
        $this->supportsInputLevels = $supports_input_levels;
      }

      public function applies(): bool {
        return TRUE;
      }

      private function guard(DeviceReference $device): void {
        $key = $device->isUid() ? 'uid:' . $device : (string) $device;
        if (in_array($key, $this->unsupported, TRUE)) {
          throw new EngineFeatureException('Engine cannot use ' . $key);
        }
        if (in_array($key, $this->missing, TRUE)) {
          throw new MissingDeviceException(sprintf('Could not find device "%s"', $device));
        }
      }

      private function label(DeviceReference $device): string {
        return $device->isUid() ? 'uid:' . $device : (string) $device;
      }

      public function getCommandSetOutputLevel(DeviceReference $device, float $limit): string {
        if (!$this->supportsLevels) {
          throw new EngineFeatureException('no levels');
        }

        return sprintf('level-out %s %s', $this->label($device), $limit);
      }

      public function getCommandSetInputLevel(DeviceReference $device, float $limit): string {
        if (!$this->supportsLevels || !$this->supportsInputLevels) {
          throw new EngineFeatureException('no levels');
        }

        return sprintf('level-in %s %s', $this->label($device), $limit);
      }

      public function getCommandChangeInput(DeviceReference $device): string {
        $this->guard($device);

        return 'set-in ' . $this->label($device);
      }

      public function getCommandChangeOutput(DeviceReference $device): string {
        $this->guard($device);

        return 'set-out ' . $this->label($device);
      }

      public function getHomepage(): string {
        return '';
      }

      public function getAllDevices(): array {
        return $this->devices;
      }
    };
  }

  public function testBothDevicesOk() {
    $runner = $this->getRunner();
    $switch = new SwitchAudio($this->getEngine(), $runner);
    $result = $switch([
      'label' => 'Phone',
      'input' => ['name' => 'Mic'],
      'output' => ['name' => 'Headphones'],
    ]);
    $this->assertTrue($result->isSuccess());
    $this->assertSame(0, $result->getExitCode());
    $this->assertSame([], $result->getErrors());
    $this->assertSame('Phone is active (🎙 Mic  🔈 Headphones)', $result->getMessage());
    $this->assertSame(['set-in Mic', 'set-out Headphones'], $runner->ran);
    $this->assertSame($runner->ran, $result->getCommands());
  }

  public function testInputFails() {
    $runner = $this->getRunner(['set-in Mic']);
    $switch = new SwitchAudio($this->getEngine(), $runner);
    $result = $switch([
      'label' => 'Phone',
      'input' => ['name' => 'Mic'],
      'output' => ['name' => 'Headphones'],
    ]);
    $this->assertFalse($result->isSuccess());
    $this->assertSame(1, $result->getExitCode());
    $this->assertSame('', $result->getMessage());
    $this->assertSame(['❌ Failed to change input device.', '⚠️ Audio remains unchanged.'], $result->getErrors());
    // Later steps still run, as the generated Bash did.
    $this->assertSame(['set-in Mic', 'set-out Headphones'], $runner->ran);
  }

  public function testOutputFails() {
    $runner = $this->getRunner(['set-out Headphones']);
    $switch = new SwitchAudio($this->getEngine(), $runner);
    $result = $switch([
      'label' => 'Phone',
      'input' => ['name' => 'Mic'],
      'output' => ['name' => 'Headphones'],
    ]);
    $this->assertSame(1, $result->getExitCode());
    $this->assertSame(['❌ Failed to change output device.', '⚠️ Audio remains unchanged.'], $result->getErrors());
  }

  public function testScriptsRunInOrderAndAFailureIsReported() {
    $runner = $this->getRunner(['bad script']);
    $switch = new SwitchAudio($this->getEngine(), $runner);
    $result = $switch([
      'label' => 'Phone',
      'output' => ['name' => 'Headphones'],
      'scripts' => ['good script', 'bad script', 'last script'],
    ]);
    $this->assertSame(1, $result->getExitCode());
    $this->assertSame(['❌ Script failed: bad script', '⚠️ Audio remains unchanged.'], $result->getErrors());
    $this->assertSame(['set-out Headphones', 'good script', 'bad script', 'last script'], $runner->ran);
  }

  public function testLevelCommandsRunBetweenDevicesAndScripts() {
    $runner = $this->getRunner();
    $switch = new SwitchAudio($this->getEngine(), $runner);
    $result = $switch([
      'label' => 'Phone',
      'input' => ['name' => 'Mic', 'level' => 0.5],
      'output' => ['name' => 'Headphones', 'level' => 25],
      'scripts' => ['after'],
    ]);
    $this->assertTrue($result->isSuccess());
    $this->assertSame([
      'set-in Mic',
      'set-out Headphones',
      'level-in Mic 0.5',
      'level-out Headphones 0.25',
      'after',
    ], $runner->ran);
  }

  public function testFailedLevelCommandDoesNotFailTheSwitch() {
    $runner = $this->getRunner(['level-out Headphones 0.5']);
    $switch = new SwitchAudio($this->getEngine(), $runner);
    $result = $switch([
      'label' => 'Phone',
      'output' => ['name' => 'Headphones', 'level' => 0.5],
    ]);
    $this->assertTrue($result->isSuccess());
  }

  public function testLevelCommandSkippedOnEngineFeatureException() {
    $runner = $this->getRunner();
    $switch = new SwitchAudio($this->getEngine([], FALSE), $runner);
    $result = $switch([
      'label' => 'Phone',
      'input' => ['name' => 'Mic', 'level' => 0.5],
      'output' => ['name' => 'Headphones', 'level' => 0.5],
    ]);
    $this->assertTrue($result->isSuccess());
    $this->assertSame(['set-in Mic', 'set-out Headphones'], $runner->ran);
  }

  public function testInputLevelSkippedWhileOutputLevelStillRuns() {
    $runner = $this->getRunner();
    $switch = new SwitchAudio($this->getEngine([], TRUE, [], FALSE), $runner);
    $result = $switch([
      'label' => 'Phone',
      'input' => ['name' => 'Mic', 'level' => 0.5],
      'output' => ['name' => 'Headphones', 'level' => 0.25],
    ]);
    $this->assertTrue($result->isSuccess());
    $this->assertSame(['set-in Mic', 'set-out Headphones', 'level-out Headphones 0.25'], $runner->ran);
  }

  public function testInputOnlyOptionSkipsInputLevelWithoutError() {
    $runner = $this->getRunner();
    $switch = new SwitchAudio($this->getEngine([], TRUE, [], FALSE), $runner);
    $result = $switch([
      'label' => 'Phone',
      'input' => ['name' => 'Mic', 'level' => 0.5],
    ]);
    $this->assertTrue($result->isSuccess());
    $this->assertSame(['set-in Mic'], $runner->ran);
  }

  public function testMessageOmitsTheInputWhenOnlyAnOutputIsConfigured() {
    $switch = new SwitchAudio($this->getEngine(), $this->getRunner());
    $result = $switch([
      'label' => 'Speakerphone',
      'output' => ['name' => 'MacBook Pro Speakers'],
    ]);
    $this->assertSame('Speakerphone is active (🔈 MacBook Pro Speakers)', $result->getMessage());
  }

  public function testMessageOmitsTheOutputWhenOnlyAnInputIsConfigured() {
    $switch = new SwitchAudio($this->getEngine(), $this->getRunner());
    $result = $switch([
      'label' => 'Mic only',
      'input' => ['name' => 'External Microphone'],
    ]);
    $this->assertSame('Mic only is active (🎙 External Microphone)', $result->getMessage());
  }

  public function testOneUnavailableOptionDoesNotAffectAnother() {
    // The old generator dropped an option whose device was missing without
    // touching the others; each switch is now independent.
    $runner = $this->getRunner();
    $switch = new SwitchAudio($this->getEngine([], TRUE, ['Disconnected Headset']), $runner);
    $missing = $switch(['label' => 'Headset', 'input' => ['name' => 'Disconnected Headset']]);
    $this->assertFalse($missing->isSuccess());
    $ok = $switch(['label' => 'Speakerphone', 'output' => ['name' => 'MacBook Pro Speakers']]);
    $this->assertTrue($ok->isSuccess());
    $this->assertSame(['set-out MacBook Pro Speakers'], $runner->ran);
  }

  public function testMissingDeviceRunsNothingAndReportsIt() {
    $runner = $this->getRunner();
    $switch = new SwitchAudio($this->getEngine([], TRUE, ['Headphones']), $runner);
    $result = $switch([
      'label' => 'Phone',
      'input' => ['name' => 'Mic'],
      'output' => ['name' => 'Headphones'],
      'scripts' => ['never'],
    ]);
    $this->assertFalse($result->isSuccess());
    $this->assertSame(1, $result->getExitCode());
    $this->assertSame(['❌ Could not find device "Headphones"', '⚠️ Audio remains unchanged.'], $result->getErrors());
    $this->assertSame([], $runner->ran, 'Nothing may run when a device cannot be resolved.');
  }

  public function testNumericDevicesAreShownByName() {
    $devices = [
      (new Device())->setId(62)->setName('MacBook Pro Microphone')->setType(DeviceTypes::INPUT),
      (new Device())->setId(71)->setName('MacBook Pro Speakers')->setType(DeviceTypes::OUTPUT),
    ];
    $switch = new SwitchAudio($this->getEngine($devices), $this->getRunner());
    $result = $switch([
      'label' => 'Desk',
      'input' => ['name' => 62],
      'output' => ['name' => '71'],
    ]);
    $this->assertSame('Desk is active (🎙 MacBook Pro Microphone  🔈 MacBook Pro Speakers)', $result->getMessage());
  }

  public function testUnknownNumericDeviceLeavesItOutOfTheMessage() {
    $switch = new SwitchAudio($this->getEngine(), $this->getRunner());
    $result = $switch([
      'label' => 'Desk',
      'output' => ['name' => 5],
    ]);
    $this->assertSame('Desk is active ()', $result->getMessage());
  }

  public function testOptionWithOnlyScripts() {
    $runner = $this->getRunner();
    $switch = new SwitchAudio($this->getEngine(), $runner);
    $result = $switch(['label' => 'Just scripts', 'scripts' => ['one']]);
    $this->assertTrue($result->isSuccess());
    $this->assertSame(['one'], $runner->ran);
  }

  public function testUidIsPassedToTheEngineAsAUidReference() {
    $runner = $this->getRunner();
    $engine = $this->getEngine();
    $switch = new SwitchAudio($engine, $runner);
    $result = $switch([
      'label' => 'Desk',
      'output' => ['uid' => 'BuiltInSpeakerDevice', 'level' => 0.5],
    ]);
    $this->assertTrue($result->isSuccess());
    $this->assertSame(['set-out uid:BuiltInSpeakerDevice', 'level-out uid:BuiltInSpeakerDevice 0.5'], $runner->ran);
  }

  public function testUidIsShownByDeviceNameInTheMessage() {
    $devices = [
      (new Device())->setId(71)->setName('MacBook Pro Speakers')->setUid('BuiltInSpeakerDevice')->setType(DeviceTypes::OUTPUT),
    ];
    $switch = new SwitchAudio($this->getEngine($devices), $this->getRunner());
    $result = $switch([
      'label' => 'Desk',
      'output' => ['uid' => 'BuiltInSpeakerDevice'],
    ]);
    $this->assertSame('Desk is active (🔈 MacBook Pro Speakers)', $result->getMessage());
  }

  public function testUidWithNoKnownDeviceIsShownAsIs() {
    $switch = new SwitchAudio($this->getEngine(), $this->getRunner());
    $result = $switch([
      'label' => 'Desk',
      'output' => ['uid' => 'SomeUid'],
    ]);
    $this->assertSame('Desk is active (🔈 SomeUid)', $result->getMessage());
  }

  public function testMissingUidRunsNothingAndReportsIt() {
    $runner = $this->getRunner();
    $switch = new SwitchAudio($this->getEngine([], TRUE, ['uid:Gone']), $runner);
    $result = $switch([
      'label' => 'Desk',
      'input' => ['name' => 'Mic'],
      'output' => ['uid' => 'Gone'],
    ]);
    $this->assertFalse($result->isSuccess());
    $this->assertSame(1, $result->getExitCode());
    $this->assertSame([], $runner->ran);
    $errors = $result->getErrors();
    $this->assertSame('⚠️ Audio remains unchanged.', end($errors));
  }

  public function testEngineThatCannotUseAUidReportsItAndRunsNothing() {
    $runner = $this->getRunner();
    $switch = new SwitchAudio($this->getEngine([], TRUE, [], TRUE, ['uid:Nope']), $runner);
    $result = $switch([
      'label' => 'Desk',
      'input' => ['name' => 'Mic'],
      'output' => ['uid' => 'Nope'],
      'scripts' => ['never'],
    ]);
    $this->assertFalse($result->isSuccess());
    $this->assertSame(1, $result->getExitCode());
    $this->assertSame(['❌ Engine cannot use uid:Nope', '⚠️ Audio remains unchanged.'], $result->getErrors());
    $this->assertSame([], $runner->ran);
  }

}
