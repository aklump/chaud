<?php

namespace AKlump\ChangeAudio\Tests\Unit;

use AKlump\ChangeAudio\Device;
use AKlump\ChangeAudio\DeviceTypes;
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

  private function getEngine(array $devices = [], bool $supports_levels = TRUE, array $missing = []): EngineInterface {
    return new class($devices, $supports_levels, $missing) implements EngineInterface {

      private array $devices;

      private bool $supportsLevels;

      private array $missing;

      public function __construct(array $devices, bool $supports_levels, array $missing) {
        $this->devices = $devices;
        $this->supportsLevels = $supports_levels;
        $this->missing = $missing;
      }

      public function applies(): bool {
        return TRUE;
      }

      private function guard(string $device): void {
        if (in_array($device, $this->missing, TRUE)) {
          throw new MissingDeviceException(sprintf('Could not find device "%s"', $device));
        }
      }

      public function getCommandSetOutputLevel(string $device, float $limit): string {
        if (!$this->supportsLevels) {
          throw new EngineFeatureException('no levels');
        }

        return sprintf('level-out %s %s', $device, $limit);
      }

      public function getCommandSetInputLevel(string $device, float $limit): string {
        if (!$this->supportsLevels) {
          throw new EngineFeatureException('no levels');
        }

        return sprintf('level-in %s %s', $device, $limit);
      }

      public function getCommandChangeInput(string $device): string {
        $this->guard($device);

        return 'set-in ' . $device;
      }

      public function getCommandChangeOutput(string $device): string {
        $this->guard($device);

        return 'set-out ' . $device;
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
      'input' => ['device' => 'Mic'],
      'output' => ['device' => 'Headphones'],
    ]);
    $this->assertTrue($result->isSuccess());
    $this->assertSame(0, $result->getExitCode());
    $this->assertSame([], $result->getErrors());
    $this->assertSame('Phone is active (🎤 Mic  🔈 Headphones)', $result->getMessage());
    $this->assertSame(['set-in Mic', 'set-out Headphones'], $runner->ran);
    $this->assertSame($runner->ran, $result->getCommands());
  }

  public function testInputFails() {
    $runner = $this->getRunner(['set-in Mic']);
    $switch = new SwitchAudio($this->getEngine(), $runner);
    $result = $switch([
      'label' => 'Phone',
      'input' => ['device' => 'Mic'],
      'output' => ['device' => 'Headphones'],
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
      'input' => ['device' => 'Mic'],
      'output' => ['device' => 'Headphones'],
    ]);
    $this->assertSame(1, $result->getExitCode());
    $this->assertSame(['❌ Failed to change output device.', '⚠️ Audio remains unchanged.'], $result->getErrors());
  }

  public function testScriptsRunInOrderAndAFailureIsReported() {
    $runner = $this->getRunner(['bad script']);
    $switch = new SwitchAudio($this->getEngine(), $runner);
    $result = $switch([
      'label' => 'Phone',
      'output' => ['device' => 'Headphones'],
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
      'input' => ['device' => 'Mic', 'level' => 0.5],
      'output' => ['device' => 'Headphones', 'level' => 25],
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
      'output' => ['device' => 'Headphones', 'level' => 0.5],
    ]);
    $this->assertTrue($result->isSuccess());
  }

  public function testLevelCommandSkippedOnEngineFeatureException() {
    $runner = $this->getRunner();
    $switch = new SwitchAudio($this->getEngine([], FALSE), $runner);
    $result = $switch([
      'label' => 'Phone',
      'input' => ['device' => 'Mic', 'level' => 0.5],
      'output' => ['device' => 'Headphones', 'level' => 0.5],
    ]);
    $this->assertTrue($result->isSuccess());
    $this->assertSame(['set-in Mic', 'set-out Headphones'], $runner->ran);
  }

  public function testMissingDeviceRunsNothingAndReportsIt() {
    $runner = $this->getRunner();
    $switch = new SwitchAudio($this->getEngine([], TRUE, ['Headphones']), $runner);
    $result = $switch([
      'label' => 'Phone',
      'input' => ['device' => 'Mic'],
      'output' => ['device' => 'Headphones'],
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
      'input' => ['device' => 62],
      'output' => ['device' => '71'],
    ]);
    $this->assertSame('Desk is active (🎤 MacBook Pro Microphone  🔈 MacBook Pro Speakers)', $result->getMessage());
  }

  public function testUnknownNumericDeviceLeavesItOutOfTheMessage() {
    $switch = new SwitchAudio($this->getEngine(), $this->getRunner());
    $result = $switch([
      'label' => 'Desk',
      'output' => ['device' => 5],
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

}
