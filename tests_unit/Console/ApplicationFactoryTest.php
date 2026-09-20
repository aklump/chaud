<?php

namespace AKlump\ChangeAudio\Tests\Unit\Console;

use AKlump\ChangeAudio\App;
use AKlump\ChangeAudio\Console\Application;
use AKlump\ChangeAudio\Console\ApplicationFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @covers \AKlump\ChangeAudio\Console\ApplicationFactory
 * @covers \AKlump\ChangeAudio\Console\Application
 * @uses   \AKlump\ChangeAudio\App
 * @uses   \AKlump\ChangeAudio\Cache\CacheManager
 * @uses   \AKlump\ChangeAudio\ConfigManager
 * @uses   \AKlump\ChangeAudio\Command\ConfigCommand
 * @uses   \AKlump\ChangeAudio\Command\SwitchCommand
 * @uses   \AKlump\ChangeAudio\Process\ShellCommandRunner
 * @uses   \AKlump\ChangeAudio\Command\DevicesCommand
 * @uses   \AKlump\ChangeAudio\Command\CacheClearCommand
 * @uses   \AKlump\ChangeAudio\GetAudioEngine
 */
class ApplicationFactoryTest extends TestCase {

  private ?string $originalHome;

  protected function setUp(): void {
    $this->originalHome = $_SERVER['HOME'] ?? NULL;
    $_SERVER['HOME'] = sys_get_temp_dir();
  }

  protected function tearDown(): void {
    if ($this->originalHome === NULL) {
      unset($_SERVER['HOME']);
    }
    else {
      $_SERVER['HOME'] = $this->originalHome;
    }
  }

  private function getTester(): ApplicationTester {
    $application = ApplicationFactory::create();
    $application->setAutoExit(FALSE);

    return new ApplicationTester($application);
  }

  public function testNameVersionAndRegisteredCommands() {
    $application = ApplicationFactory::create();
    $this->assertInstanceOf(Application::class, $application);
    $this->assertSame(App::NAME, $application->getName());
    $this->assertSame(App::VERSION, $application->getVersion());
    $this->assertTrue($application->has('switch'));
    $this->assertTrue($application->has('s'));
    $this->assertTrue($application->has('config'));
    $this->assertTrue($application->has('devices'));
    $this->assertTrue($application->has('cache:clear'));
  }

  public function testDevicesAliasHasTheSameHelpAsDevices() {
    $application = ApplicationFactory::create();
    $this->assertSame($application->find('devices'), $application->find('d'));
    $outputs = [];
    foreach (['devices', 'd'] as $name) {
      $tester = $this->getTester();
      $this->assertSame(0, $tester->run(['command' => $name, '-h' => TRUE]));
      $outputs[] = $tester->getDisplay();
    }
    $this->assertSame($outputs[0], $outputs[1]);
    $this->assertStringContainsString('List audio devices', $outputs[0]);
  }

  public function testBareHelpFlagsListTheCommands() {
    foreach (['-h', '--help'] as $flag) {
      $tester = $this->getTester();
      $this->assertSame(0, $tester->run([$flag => TRUE]), $flag);
      $display = $tester->getDisplay();
      $this->assertStringContainsString('Available commands', $display, $flag);
      $this->assertStringContainsString('config', $display, $flag);
    }
  }

  public function testCommandHelpIsStillCommandSpecific() {
    $tester = $this->getTester();
    $this->assertSame(0, $tester->run(['command' => 'config', '-h' => TRUE]));
    $this->assertStringContainsString('Print the config file path', $tester->getDisplay());
    $this->assertStringNotContainsString('Available commands', $tester->getDisplay());
  }

  public function testVersionFlagWinsOverHelp() {
    $tester = $this->getTester();
    $this->assertSame(0, $tester->run(['-h' => TRUE, '-V' => TRUE]));
    $this->assertStringContainsString(App::VERSION, $tester->getDisplay());
  }

}
