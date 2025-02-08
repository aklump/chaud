<?php

namespace AKlump\ChangeAudio\Tests\Unit;

use AKlump\ChangeAudio\DeviceTypes;
use AKlump\ChangeAudio\GetDeviceLevel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\GetDeviceLevel
 */
class GetDeviceLevelTest extends TestCase {

  public static function dataFortestInvokeProvider(): array {
    $tests = [];
    $tests[] = [
      [],
      DeviceTypes::INPUT,
      NULL,
    ];
    $tests[] = [
      [],
      DeviceTypes::OUTPUT,
      NULL,
    ];
    $tests[] = [
      [
        'input' => ['level' => 0.5],
        'output' => ['level' => 0.75],
      ],
      DeviceTypes::INPUT,
      0.5,
    ];
    $tests[] = [
      [
        'input' => ['level' => 0.5],
        'output' => ['level' => 0.75],
      ],
      DeviceTypes::OUTPUT,
      0.75,
    ];
    $tests[] = [
      [
        'input' => ['level' => 50],
        'output' => ['level' => 75],
      ],
      DeviceTypes::INPUT,
      0.5,
    ];
    $tests[] = [
      [
        'input' => ['level' => 50],
        'output' => ['level' => 75],
      ],
      DeviceTypes::OUTPUT,
      0.75,
    ];
    $tests[] = [
      [
        'input' => ['level' => -10],
        'output' => ['level' => -20],
      ],
      DeviceTypes::INPUT,
      0.0,
    ];
    $tests[] = [
      [
        'input' => ['level' => -10],
        'output' => ['level' => -20],
      ],
      DeviceTypes::OUTPUT,
      0.0,
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testInvoke($config, $device_type, $expected) {
    $result = (new GetDeviceLevel())($config, $device_type);
    $this->assertSame($expected, $result);
  }
}
