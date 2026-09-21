<?php

namespace AKlump\ChangeAudio\Tests\Unit;

use AKlump\ChangeAudio\RecordPhp;
use AKlump\ChangeAudio\Tests\Unit\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\RecordPhp
 */
class RecordPhpTest extends TestCase {

  use TestWithFilesTrait;

  private string $dir;

  protected function setUp(): void {
    $this->dir = rtrim($this->getTestFileFilepath('app/', TRUE), '/');
  }

  protected function tearDown(): void {
    $this->deleteAllTestFiles();
  }

  public function testUnderPhpenvWritesTheVersionName() {
    $path = (new RecordPhp())($this->dir, '/usr/local/Cellar/php/8.5.10/bin/php', [
      'PHPENV_VERSION' => '8.5',
      'PHPENV_ROOT' => '/Users/you/.phpenv',
    ]);
    $this->assertSame($this->dir . '/.php-version', $path);
    $this->assertSame("8.5\n", file_get_contents($path));
      }

  public function testUnderPhpenvSystemVersionWritesTheBinaryPath() {
    $path = (new RecordPhp())($this->dir, '/opt/custom/bin/php', [
      'PHPENV_VERSION' => 'system',
      'PHPENV_ROOT' => '/Users/you/.phpenv',
    ]);
    $this->assertSame($this->dir . '/.php-version', $path);
    $this->assertSame("/opt/custom/bin/php\n", file_get_contents($path));
  }

  public function testWithoutPhpenvWritesTheBinaryPath() {
    $path = (new RecordPhp())($this->dir, '/opt/custom/bin/php', []);
    $this->assertSame($this->dir . '/.php-version', $path);
    $this->assertSame("/opt/custom/bin/php\n", file_get_contents($path));
  }

  public function testHomebrewCellarBinaryIsRecordedByItsOptPath() {
    $brew = rtrim($this->getTestFileFilepath('brew/', TRUE), '/');
    $cellar_binary = "$brew/Cellar/php@8.3/8.3.14/bin/php";
    $opt_binary = "$brew/opt/php@8.3/bin/php";
    $this->getTestFileFilepath('brew/opt/php@8.3/bin/', TRUE);
    symlink(PHP_BINARY, $opt_binary);

    $path = (new RecordPhp())($this->dir, $cellar_binary, []);
    $this->assertSame($opt_binary . "\n", file_get_contents($path));
  }

  public function testHomebrewCellarBinaryIsKeptWhenOptPathIsMissing() {
    $cellar_binary = $this->getTestFileFilepath('brew/') . 'Cellar/php/8.5.10/bin/php';
    $path = (new RecordPhp())($this->dir, $cellar_binary, []);
    $this->assertSame($cellar_binary . "\n", file_get_contents($path));
  }

}
