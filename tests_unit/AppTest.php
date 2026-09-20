<?php

namespace AKlump\ChangeAudio\Tests\Unit;

use AKlump\ChangeAudio\App;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\App
 */
class AppTest extends TestCase {

  public function testBinMatchesTheExecutableInTheProjectRoot() {
    $this->assertSame('chaud', App::BIN);
    $this->assertFileExists(__DIR__ . '/../' . App::BIN);
  }
}
