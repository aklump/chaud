<?php

namespace AKlump\ChangeAudio\Tests\Unit;

use AKlump\ChangeAudio\FuzzyMatch;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\FuzzyMatch
 */
class FuzzyMatchTest extends TestCase {

  public static function dataFortestInvokeProvider(): array {
    $tests = [];
    $tests[] = ['', []];
    $tests[] = ['centre', ['center']];
    $tests[] = ['fone', ['Phone']];
    $tests[] = ['speaker', ['Speakerphone']];

    return $tests;
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testInvoke(string $input, array $expected) {
    $options = [
      'center',
      'Call',
      'Phone',
      'Speakerphone',
      'External audio',
      'Headphones',
    ];
    $this->assertSame($expected, (new FuzzyMatch())($input, $options));
  }
}
