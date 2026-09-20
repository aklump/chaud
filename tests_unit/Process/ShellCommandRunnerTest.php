<?php

namespace AKlump\ChangeAudio\Tests\Unit\Process;

use AKlump\ChangeAudio\Process\ShellCommandRunner;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\Process\ShellCommandRunner
 */
class ShellCommandRunnerTest extends TestCase {

  public function testSuccessReturnsZero() {
    $this->assertSame(0, (new ShellCommandRunner())->run('true'));
  }

  public function testExitStatusIsReturned() {
    $this->assertSame(3, (new ShellCommandRunner())->run('exit 3'));
  }

  public function testCompoundCommandsAreInterpretedByTheShell() {
    $this->assertSame(0, (new ShellCommandRunner())->run('[ 1 -eq 1 ] && true'));
    $this->assertNotSame(0, (new ShellCommandRunner())->run('false || false'));
  }

}
