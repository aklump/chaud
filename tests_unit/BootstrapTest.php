<?php

namespace AKlump\ChangeAudio\Tests\Unit;

use AKlump\ChangeAudio\App;
use AKlump\ChangeAudio\Tests\Unit\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;

/**
 * Runs the real executable as a subprocess to guard the bootstrap.
 *
 * @coversNothing
 */
class BootstrapTest extends TestCase {

  use TestWithFilesTrait;

  private string $executable;

  protected function setUp(): void {
    $this->executable = realpath(__DIR__ . '/../' . App::BIN);
  }

  protected function tearDown(): void {
    $this->deleteAllTestFiles();
  }

  /**
   * @return array{0: int, 1: string, 2: string} Exit code, stdout, stderr.
   */
  private function runScript(string $script, array $args): array {
    $env = [
      'PATH' => getenv('PATH'),
      'HOME' => $this->getTestFileFilepath('home/', TRUE),
      'CACHE_PATH' => $this->getTestFileFilepath('cache/', TRUE),
    ];
    $command = array_merge([PHP_BINARY, $script], $args);
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, sys_get_temp_dir(), $env);
    $this->assertIsResource($process);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($process), $stdout, $stderr];
  }

  public function testVersion() {
    [$code, $stdout] = $this->runScript($this->executable, ['-V']);
    $this->assertSame(0, $code);
    $this->assertSame(sprintf("%s %s\n", App::NAME, App::VERSION), $stdout);
  }

  public function testHelpFlagListsCommands() {
    foreach (['-h', '--help'] as $flag) {
      [$code, $stdout] = $this->runScript($this->executable, [$flag]);
      $this->assertSame(0, $code, $flag);
      $this->assertStringContainsString('Available commands', $stdout);
      $this->assertStringContainsString('config', $stdout);
    }
  }

  public function testNoArgumentsListsCommands() {
    [$code, $stdout] = $this->runScript($this->executable, []);
    $this->assertSame(0, $code);
    $this->assertStringContainsString('Available commands', $stdout);
  }

  public function testUnknownOptionFailsOnStderr() {
    [$code, $stdout, $stderr] = $this->runScript($this->executable, ['--bogus']);
    $this->assertSame(1, $code);
    $this->assertSame('', $stdout);
    $this->assertStringContainsString('"--bogus" option does not exist', $stderr);
  }

  public function testWorksThroughASymlinkInAnotherDirectory() {
    $link = $this->getTestFileFilepath('bin/', TRUE) . '/' . App::BIN . '-link';
    $this->assertTrue(symlink($this->executable, $link));
    [$code, $stdout] = $this->runScript($link, ['-V']);
    $this->assertSame(0, $code);
    $this->assertStringContainsString(App::NAME, $stdout);
    [$code, $stdout] = $this->runScript($link, ['-h']);
    $this->assertSame(0, $code);
    $this->assertStringContainsString('Available commands', $stdout);
  }

}
