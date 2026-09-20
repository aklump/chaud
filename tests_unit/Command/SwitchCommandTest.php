<?php

namespace AKlump\ChangeAudio\Tests\Unit\Command;

use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\Command\SwitchCommand;
use AKlump\ChangeAudio\ConfigManager;
use AKlump\ChangeAudio\Engine\EngineInterface;
use AKlump\ChangeAudio\GetAudioEngine;
use AKlump\ChangeAudio\Process\CommandRunner;
use AKlump\ChangeAudio\Tests\Unit\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \AKlump\ChangeAudio\Command\SwitchCommand
 * @uses   \AKlump\ChangeAudio\ConfigManager
 * @uses   \AKlump\ChangeAudio\App
 * @uses   \AKlump\ChangeAudio\Cache\CacheManager
 * @uses   \AKlump\ChangeAudio\ValidateConfiguration
 * @uses   \AKlump\ChangeAudio\Config\OptionResolver
 * @uses   \AKlump\ChangeAudio\FuzzyMatch
 * @uses   \AKlump\ChangeAudio\SwitchAudio
 * @uses   \AKlump\ChangeAudio\SwitchResult
 * @uses   \AKlump\ChangeAudio\GetDeviceLevel
 */
class SwitchCommandTest extends TestCase {

  use TestWithFilesTrait;

  private string $userHome;

  private ?string $originalCachePath;

  private CommandRunner $runner;

  protected function setUp(): void {
    $this->originalCachePath = getenv('CACHE_PATH') === FALSE ? NULL : getenv('CACHE_PATH');
    $this->userHome = $this->getTestFileFilepath('home/', TRUE);
    putenv('CACHE_PATH=' . $this->getTestFileFilepath('cache/', TRUE));
    $this->writeConfig([
      [
        'label' => 'Phone',
        'aliases' => ['p'],
        'input' => ['device' => 'Mic'],
        'output' => ['device' => 'Headphones'],
      ],
      [
        'label' => 'Speakerphone',
        'aliases' => ['sp'],
        'output' => ['device' => 'Speakers'],
        'scripts' => ['pause <music>'],
      ],
    ]);
  }

  protected function tearDown(): void {
    putenv($this->originalCachePath === NULL ? 'CACHE_PATH' : 'CACHE_PATH=' . $this->originalCachePath);
    $this->deleteAllTestFiles();
  }

  private function writeConfig(array $options): void {
    file_put_contents($this->userHome . '/.chaudio.json', json_encode(['options' => $options]));
  }

  private function getEngine(): EngineInterface {
    return new class implements EngineInterface {

      public function applies(): bool {
        return TRUE;
      }

      public function getCommandSetOutputLevel(string $device, float $limit): string {
        return 'level-out ' . $device;
      }

      public function getCommandSetInputLevel(string $device, float $limit): string {
        return 'level-in ' . $device;
      }

      public function getCommandChangeInput(string $device): string {
        return 'set-in ' . $device;
      }

      public function getCommandChangeOutput(string $device): string {
        return 'set-out ' . $device;
      }

      public function getHomepage(): string {
        return '';
      }

      public function getAllDevices(): array {
        return [];
      }
    };
  }

  private function getTester(?EngineInterface $engine, array $failing = []): CommandTester {
    $this->runner = new class($failing) implements CommandRunner {

      public array $ran = [];

      private array $failing;

      public function __construct(array $failing) {
        $this->failing = $failing;
      }

      public function run(string $shell_command): int {
        $this->ran[] = $shell_command;

        return in_array($shell_command, $this->failing, TRUE) ? 1 : 0;
      }
    };
    $cache = new CacheManager();
    $config = new ConfigManager($cache, $this->userHome, __DIR__ . '/../../install/config.json');
    $get_engine = new class($engine) extends GetAudioEngine {

      private ?EngineInterface $engine;

      public function __construct(?EngineInterface $engine) {
        $this->engine = $engine;
      }

      public function __invoke(): ?EngineInterface {
        return $this->engine;
      }
    };

    return new CommandTester(new SwitchCommand($config, $get_engine, $this->runner, $cache));
  }

  public function testSuccessPrintsActiveMessageOnStdout() {
    $tester = $this->getTester($this->getEngine());
    $status = $tester->execute(['label' => 'phone'], ['capture_stderr_separately' => TRUE]);
    $this->assertSame(Command::SUCCESS, $status);
    $this->assertSame('Phone is active (🎤 Mic  🔈 Headphones)' . PHP_EOL, $tester->getDisplay());
    $this->assertSame('', $tester->getErrorOutput());
    $this->assertSame(['set-in Mic', 'set-out Headphones'], $this->runner->ran);
  }

  public function testAliasSwitches() {
    $tester = $this->getTester($this->getEngine());
    $this->assertSame(Command::SUCCESS, $tester->execute(['label' => 'SP']));
    $this->assertStringContainsString('Speakerphone is active', $tester->getDisplay());
    $this->assertSame(['set-out Speakers', 'pause <music>'], $this->runner->ran);
  }

  public function testUnknownLabelFailsOnStderrWithSuggestion() {
    $tester = $this->getTester($this->getEngine());
    $status = $tester->execute(['label' => 'spekerphone'], ['capture_stderr_separately' => TRUE]);
    $this->assertSame(Command::FAILURE, $status);
    $this->assertSame('', $tester->getDisplay());
    $this->assertStringContainsString('❌ Unknown audio configuration: spekerphone', $tester->getErrorOutput());
    $this->assertStringContainsString('🤔 Did you mean "Speakerphone"? (chaudio s)', $tester->getErrorOutput());
    $this->assertSame([], $this->runner->ran);
  }

  public function testUnknownLabelWithNoSuggestion() {
    $tester = $this->getTester($this->getEngine());
    $status = $tester->execute(['label' => 'zzzzzzzz'], ['capture_stderr_separately' => TRUE]);
    $this->assertSame(Command::FAILURE, $status);
    $this->assertStringNotContainsString('Did you mean', $tester->getErrorOutput());
  }

  public function testFailedSwitchExitsOneWithStderr() {
    $tester = $this->getTester($this->getEngine(), ['set-out Headphones']);
    $status = $tester->execute(['label' => 'Phone'], ['capture_stderr_separately' => TRUE]);
    $this->assertSame(1, $status);
    $this->assertSame('', $tester->getDisplay());
    $this->assertStringContainsString('❌ Failed to change output device.', $tester->getErrorOutput());
    $this->assertStringContainsString('⚠️ Audio remains unchanged.', $tester->getErrorOutput());
  }

  public function testFailedScriptIsReportedVerbatim() {
    $tester = $this->getTester($this->getEngine(), ['pause <music>']);
    $status = $tester->execute(['label' => 'sp'], ['capture_stderr_separately' => TRUE]);
    $this->assertSame(1, $status);
    $this->assertStringContainsString('❌ Script failed: pause <music>', $tester->getErrorOutput());
  }

  public function testNoEngineFails() {
    $tester = $this->getTester(NULL);
    $status = $tester->execute(['label' => 'Phone'], ['capture_stderr_separately' => TRUE]);
    $this->assertSame(Command::FAILURE, $status);
    $this->assertStringContainsString('No supported audio engine', $tester->getErrorOutput());
  }

  public function testInvalidConfigFails() {
    $this->writeConfig([['label' => 'Only one']]);
    $tester = $this->getTester($this->getEngine());
    $status = $tester->execute(['label' => 'Only one'], ['capture_stderr_separately' => TRUE]);
    $this->assertSame(Command::FAILURE, $status);
    $this->assertStringContainsString('❌ Invalid configuration:', $tester->getErrorOutput());
    $this->assertSame([], $this->runner->ran);
  }

  public function testVerbosePrintsDetailLines() {
    $tester = $this->getTester($this->getEngine());
    $tester->execute(['label' => 'Phone'], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);
    $display = $tester->getDisplay();
    $this->assertStringContainsString('🪲 ' . getenv('CACHE_PATH'), $display);
    $this->assertStringContainsString('🪲 set-in Mic', $display);
    $this->assertStringContainsString('🪲 set-out Headphones', $display);
    $this->assertStringContainsString('Phone is active', $display);
  }

  public function testDetailLinesAreHiddenByDefault() {
    $tester = $this->getTester($this->getEngine());
    $tester->execute(['label' => 'Phone']);
    $this->assertStringNotContainsString('🪲', $tester->getDisplay());
  }

  public function testNameAndAlias() {
    $tester = $this->getTester($this->getEngine());
    $command = new SwitchCommand(new ConfigManager(new CacheManager(), $this->userHome), new GetAudioEngine(), $this->runner, new CacheManager());
    $this->assertSame('switch', $command->getName());
    $this->assertSame(['s'], $command->getAliases());
    $this->assertNotEmpty($command->getDescription());
    $this->assertTrue($command->getDefinition()->getArgument('label')->isRequired());
  }

}
