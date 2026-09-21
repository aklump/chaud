<?php

namespace AKlump\ChangeAudio\Tests\Unit;

use AKlump\ChangeAudio\GetXdgBaseDirectory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\GetXdgBaseDirectory
 */
class GetXdgBaseDirectoryTest extends TestCase {

  /**
   * @var string|false
   */
  private $original;

  protected function setUp(): void {
    $this->original = getenv('XDG_TEST_HOME');
  }

  protected function tearDown(): void {
    putenv($this->original === FALSE ? 'XDG_TEST_HOME' : 'XDG_TEST_HOME=' . $this->original);
  }

  public static function dataFortestInvokeProvider(): array {
    return [
      'unset uses the default' => [NULL, '/Users/you/.config'],
      'empty uses the default' => ['', '/Users/you/.config'],
      'relative is ignored, as the spec requires' => ['relative/dir', '/Users/you/.config'],
      'absolute is used' => ['/custom/config', '/custom/config'],
      'trailing slash is trimmed' => ['/custom/config/', '/custom/config'],
    ];
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testInvoke(?string $value, string $expected) {
    putenv($value === NULL ? 'XDG_TEST_HOME' : "XDG_TEST_HOME=$value");
    $this->assertSame($expected, (new GetXdgBaseDirectory())('XDG_TEST_HOME', '.config', '/Users/you/'));
  }

}
