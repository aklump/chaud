<?php

namespace AKlump\ChangeAudio\Tests\Unit\Console;

use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\Command\SwitchCommand;
use AKlump\ChangeAudio\ConfigManager;
use AKlump\ChangeAudio\Console\Application;
use AKlump\ChangeAudio\Console\ApplicationFactory;
use AKlump\ChangeAudio\DeviceReference;
use AKlump\ChangeAudio\Engine\EngineInterface;
use AKlump\ChangeAudio\GetAudioEngine;
use AKlump\ChangeAudio\Process\CommandRunner;
use AKlump\ChangeAudio\Tests\Unit\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Guards how `s`, `sw`, and a label that looks like a command name resolve.
 *
 * @covers \AKlump\ChangeAudio\Console\ApplicationFactory
 * @covers \AKlump\ChangeAudio\Console\Application
 * @covers \AKlump\ChangeAudio\Command\SwitchCommand
 * @uses   \AKlump\ChangeAudio\App
 * @uses   \AKlump\ChangeAudio\Cache\CacheManager
 * @uses   \AKlump\ChangeAudio\ConfigManager
 * @uses   \AKlump\ChangeAudio\ValidateConfiguration
 * @uses   \AKlump\ChangeAudio\Config\OptionResolver
 * @uses   \AKlump\ChangeAudio\FuzzyMatch
 * @uses   \AKlump\ChangeAudio\SwitchAudio
 * @uses   \AKlump\ChangeAudio\SwitchResult
 * @uses   \AKlump\ChangeAudio\GetDeviceLevel
 * @uses   \AKlump\ChangeAudio\DeviceReference
 * @uses   \AKlump\ChangeAudio\Command\ConfigCommand
 * @uses   \AKlump\ChangeAudio\Command\DevicesCommand
 * @uses   \AKlump\ChangeAudio\Command\CacheClearCommand
 * @uses   \AKlump\ChangeAudio\Process\ShellCommandRunner
 * @uses   \AKlump\ChangeAudio\GetAudioEngine
 */
class CommandAbbreviationTest extends TestCase {

  use TestWithFilesTrait;

  private ?string $originalCachePath;

  private ?string $originalHome;

  private array $ran = [];

  protected function setUp(): void {
    $this->originalCachePath = getenv('CACHE_PATH') === FALSE ? NULL : getenv('CACHE_PATH');
    $this->originalHome = $_SERVER['HOME'] ?? NULL;
    $home = $this->getTestFileFilepath('home/', TRUE);
    $_SERVER['HOME'] = $home;
    putenv('CACHE_PATH=' . $this->getTestFileFilepath('cache/', TRUE));
    file_put_contents($home . '/.chaudio.json', json_encode(['options' => [
      ['label' => 'Phone', 'output' => ['device' => 'Headphones']],
      ['label' => 'List', 'output' => ['device' => 'Speakers']],
    ]]));
    $this->ran = [];
  }

  protected function tearDown(): void {
    putenv($this->originalCachePath === NULL ? 'CACHE_PATH' : 'CACHE_PATH=' . $this->originalCachePath);
    if ($this->originalHome === NULL) {
      unset($_SERVER['HOME']);
    }
    else {
      $_SERVER['HOME'] = $this->originalHome;
    }
    $this->deleteAllTestFiles();
  }

  /**
   * The real command set, except that switching runs against a fake engine.
   */
  private function getApplication(): Application {
    $application = ApplicationFactory::create();
    $application->setAutoExit(FALSE);

    $test = $this;
    $runner = new class($test) implements CommandRunner {

      private CommandAbbreviationTest $test;

      public function __construct(CommandAbbreviationTest $test) {
        $this->test = $test;
      }

      public function run(string $shell_command): int {
        $this->test->record($shell_command);

        return 0;
      }
    };
    $engine = new class implements EngineInterface {

      public function applies(): bool {
        return TRUE;
      }

      public function getCommandSetOutputLevel(DeviceReference $device, float $limit): string {
        return 'level-out ' . $device;
      }

      public function getCommandSetInputLevel(DeviceReference $device, float $limit): string {
        return 'level-in ' . $device;
      }

      public function getCommandChangeInput(DeviceReference $device): string {
        return 'set-in ' . $device;
      }

      public function getCommandChangeOutput(DeviceReference $device): string {
        return 'set-out ' . $device;
      }

      public function getHomepage(): string {
        return '';
      }

      public function getAllDevices(): array {
        return [];
      }
    };
    $get_engine = new class($engine) extends GetAudioEngine {

      private EngineInterface $engine;

      public function __construct(EngineInterface $engine) {
        $this->engine = $engine;
      }

      public function __invoke(): ?EngineInterface {
        return $this->engine;
      }
    };
    $cache = new CacheManager();
    // Registering under the same name replaces the factory's real switch.
    ApplicationFactory::registerCommand($application, new SwitchCommand(new ConfigManager($cache), $get_engine, $runner, $cache));

    return $application;
  }

  private function getTester(): ApplicationTester {
    return new ApplicationTester($this->getApplication());
  }

  /**
   * @internal Called by the fake runner.
   */
  public function record(string $shell_command): void {
    $this->ran[] = $shell_command;
  }

  public function testSAndSwAreAbbreviationsOfSwitch() {
    $application = ApplicationFactory::create();
    $this->assertInstanceOf(Application::class, $application);
    $switch = $application->find('switch');
    $this->assertSame($switch, $application->find('s'));
    $this->assertSame($switch, $application->find('sw'));
    $this->assertSame($switch, $application->find('swi'));
  }

  public function testSwitchAliasRunsTheSwitchCommand() {
    foreach (['s', 'sw'] as $name) {
      $this->ran = [];
      $tester = $this->getTester();
      $status = $tester->run(['command' => $name, 'label' => 'phone']);
      $this->assertSame(Command::SUCCESS, $status, $name);
      $this->assertStringContainsString('Phone is active', $tester->getDisplay(), $name);
      $this->assertSame(['set-out Headphones'], $this->ran, $name);
    }
  }

  public function testOptionsMayComeBeforeTheLabel() {
    $tester = $this->getTester();
    $status = $tester->run(['command' => 's', '-v' => TRUE, 'label' => 'phone']);
    $this->assertSame(Command::SUCCESS, $status);
    $this->assertStringContainsString('🪲 set-out Headphones', $tester->getDisplay());
  }

  public function testALabelNamedListIsAnArgumentNotTheListCommand() {
    $tester = $this->getTester();
    $status = $tester->run(['command' => 'switch', 'label' => 'list']);
    $this->assertSame(Command::SUCCESS, $status);
    $this->assertStringContainsString('List is active', $tester->getDisplay());
    $this->assertStringNotContainsString('Available commands', $tester->getDisplay());
    $this->assertSame(['set-out Speakers'], $this->ran);
  }

  public function testRawArgvWithALabelNamedListIsAnArgument() {
    foreach (['switch list', 's list', 'sw list', 's -v list', 's list -v'] as $line) {
      $this->ran = [];
      $output = new BufferedOutput();
      $status = $this->getApplication()->run(new StringInput($line), $output);
      $this->assertSame(Command::SUCCESS, $status, $line);
      $this->assertStringContainsString('List is active', $output->fetch(), $line);
      $this->assertSame(['set-out Speakers'], $this->ran, $line);
    }
  }

  public function testNoLabelListsOptionsThroughTheAlias() {
    $tester = $this->getTester();
    $status = $tester->run(['command' => 's']);
    $this->assertSame(Command::SUCCESS, $status);
    $this->assertStringContainsString('🔹 Phone', $tester->getDisplay());
    $this->assertSame([], $this->ran);
  }

}
