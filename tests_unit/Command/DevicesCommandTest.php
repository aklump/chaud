<?php

namespace AKlump\ChangeAudio\Tests\Unit\Command;

use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\Command\DevicesCommand;
use AKlump\ChangeAudio\ConfigManager;
use AKlump\ChangeAudio\Device;
use AKlump\ChangeAudio\DeviceTypes;
use AKlump\ChangeAudio\DeviceReference;
use AKlump\ChangeAudio\Engine\EngineInterface;
use AKlump\ChangeAudio\GetAudioEngine;
use AKlump\ChangeAudio\Tests\Unit\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \AKlump\ChangeAudio\Command\DevicesCommand
 * @uses   \AKlump\ChangeAudio\ConfigManager
 * @uses   \AKlump\ChangeAudio\App
 * @uses   \AKlump\ChangeAudio\Cache\CacheManager
 * @uses   \AKlump\ChangeAudio\ValidateConfiguration
 * @uses   \AKlump\ChangeAudio\Device
 * @uses   \AKlump\ChangeAudio\DeviceReference
 */
class DevicesCommandTest extends TestCase {

  use TestWithFilesTrait;

  private string $userHome;

  private ?string $originalCachePath;

  protected function setUp(): void {
    $this->originalCachePath = getenv('CACHE_PATH') === FALSE ? NULL : getenv('CACHE_PATH');
    $this->userHome = $this->getTestFileFilepath('home/', TRUE);
    putenv('CACHE_PATH=' . $this->getTestFileFilepath('cache/', TRUE));
  }

  protected function tearDown(): void {
    putenv($this->originalCachePath === NULL ? 'CACHE_PATH' : 'CACHE_PATH=' . $this->originalCachePath);
    $this->deleteAllTestFiles();
  }

  private function writeConfig(array $options): void {
    file_put_contents($this->userHome . '/.chaudio.json', json_encode(['options' => $options]));
  }

  private function getDevices(): array {
    return [
      (new Device())->setId(98)->setName('External Headphones')->setUid('BuiltInHeadphoneOutputDevice')->setType(DeviceTypes::OUTPUT),
      (new Device())->setId(71)->setName('MacBook Pro Speakers')->setUid('BuiltInSpeakerDevice')->setType(DeviceTypes::OUTPUT),
      (new Device())->setId(126)->setName('LG UltraFine Display Audio')->setUid('AppleUSBAudioEngine:1')->setType(DeviceTypes::OUTPUT),
      (new Device())->setId(62)->setName('MacBook Pro Microphone')->setUid('BuiltInMicrophoneDevice')->setType(DeviceTypes::INPUT),
    ];
  }

  private function getEngine(array $devices): EngineInterface {
    return new class($devices) implements EngineInterface {

      private array $devices;

      public function __construct(array $devices) {
        $this->devices = $devices;
      }

      public function applies(): bool {
        return TRUE;
      }

      public function getCommandSetOutputLevel(DeviceReference $device, float $limit): string {
        return '';
      }

      public function getCommandSetInputLevel(DeviceReference $device, float $limit): string {
        return '';
      }

      public function getCommandChangeInput(DeviceReference $device): string {
        return '';
      }

      public function getCommandChangeOutput(DeviceReference $device): string {
        return '';
      }

      public function getHomepage(): string {
        return '';
      }

      public function getAllDevices(): array {
        return $this->devices;
      }
    };
  }

  private function getTester(?EngineInterface $engine): CommandTester {
    $config = new ConfigManager(new CacheManager(), $this->userHome, __DIR__ . '/../../install/config.json');
    $get_engine = new class($engine) extends GetAudioEngine {

      private ?EngineInterface $engine;

      public function __construct(?EngineInterface $engine) {
        $this->engine = $engine;
      }

      public function __invoke(): ?EngineInterface {
        return $this->engine;
      }
    };

    return new CommandTester(new DevicesCommand($config, $get_engine));
  }

  public function testTableShowsColumnsAndMarksConfiguredDevices() {
    $this->writeConfig([
      [
        'label' => 'Phone',
        'input' => ['device' => 'MacBook Pro Microphone'],
        'output' => ['device' => 'External Headphones'],
      ],
      [
        'label' => 'Speakerphone',
        'output' => ['device' => 71],
      ],
      [
        'label' => 'Desk',
        'input' => ['device' => 'MacBook Pro Microphone'],
        'output' => ['device' => 'MacBook Pro Speakers'],
      ],
    ]);
    $tester = $this->getTester($this->getEngine($this->getDevices()));
    $this->assertSame(Command::SUCCESS, $tester->execute([]));
    $display = $tester->getDisplay();
    foreach (['Type', 'ID', 'Name', 'UID', 'In your options'] as $header) {
      $this->assertStringContainsString($header, $display);
    }
    $lines = explode(PHP_EOL, $display);
    $row = function (string $name) use ($lines): string {
      foreach ($lines as $line) {
        if (strpos($line, $name) !== FALSE) {
          return $line;
        }
      }
      $this->fail("No row for $name");
    };
    $this->assertStringContainsString('BuiltInHeadphoneOutputDevice', $row('External Headphones'));
    $this->assertStringContainsString('Phone', $row('External Headphones'));
    $this->assertStringContainsString('Speakerphone, Desk', $row('MacBook Pro Speakers'));
    $this->assertStringContainsString('Phone, Desk', $row('MacBook Pro Microphone'));
    $this->assertMatchesRegularExpression('/\| -\s+\|$/', $row('LG UltraFine Display Audio'));
  }

  public function testNameMatchMarksEveryDeviceWithThatName() {
    $this->writeConfig([
      ['label' => 'Both', 'output' => ['device' => 'USB Headset']],
    ]);
    $devices = [
      (new Device())->setId(10)->setName('USB Headset')->setType(DeviceTypes::OUTPUT),
      (new Device())->setId(11)->setName('USB Headset')->setType(DeviceTypes::INPUT),
      (new Device())->setId(12)->setName('Other')->setType(DeviceTypes::OUTPUT),
    ];
    $tester = $this->getTester($this->getEngine($devices));
    $this->assertSame(Command::SUCCESS, $tester->execute([]));
    $this->assertSame(2, substr_count($tester->getDisplay(), 'Both'));
  }

  public function testUidMatchMarksExactlyOneDevice() {
    $this->writeConfig([
      ['label' => 'Pinned', 'output' => ['uid' => 'AppleUSBAudioEngine:2']],
    ]);
    $devices = [
      (new Device())->setId(126)->setName('LG UltraFine Display Audio')->setUid('AppleUSBAudioEngine:1')->setType(DeviceTypes::OUTPUT),
      (new Device())->setId(116)->setName('LG UltraFine Display Audio')->setUid('AppleUSBAudioEngine:2')->setType(DeviceTypes::OUTPUT),
      (new Device())->setId(12)->setName('Other')->setUid('Other')->setType(DeviceTypes::OUTPUT),
    ];
    $tester = $this->getTester($this->getEngine($devices));
    $this->assertSame(Command::SUCCESS, $tester->execute([]));
    $display = $tester->getDisplay();
    $this->assertSame(1, substr_count($display, 'Pinned'));
    foreach (explode(PHP_EOL, $display) as $line) {
      if (strpos($line, 'Pinned') !== FALSE) {
        $this->assertStringContainsString('AppleUSBAudioEngine:2', $line);
      }
    }
  }

  public function testConfigWithoutDeviceOrUidDoesNotBreakTheListing() {
    $this->writeConfig([
      ['label' => 'Broken', 'output' => ['level' => 0.5]],
      ['label' => 'Fine', 'output' => ['device' => 71]],
    ]);
    $tester = $this->getTester($this->getEngine($this->getDevices()));
    $this->assertSame(Command::SUCCESS, $tester->execute([]));
    $this->assertStringNotContainsString('Broken', $tester->getDisplay());
    $this->assertStringContainsString('Fine', $tester->getDisplay());
  }

  public function testFailsWhenNoEngineApplies() {
    $tester = $this->getTester(NULL);
    $this->assertSame(Command::FAILURE, $tester->execute([]));
    $this->assertStringContainsString('No supported audio engine', $tester->getDisplay());
  }

  public function testFailsWhenEngineListsNoDevices() {
    $tester = $this->getTester($this->getEngine([]));
    $this->assertSame(Command::FAILURE, $tester->execute([]));
    $this->assertStringContainsString('unsupported', $tester->getDisplay());
  }

  public function testNameAndAlias() {
    $config = new ConfigManager(new CacheManager(), $this->userHome, __DIR__ . '/../../install/config.json');
    $command = new DevicesCommand($config, new GetAudioEngine());
    $this->assertSame('devices', $command->getName());
    $this->assertSame(['d'], $command->getAliases());
    $this->assertNotEmpty($command->getDescription());
  }

}
