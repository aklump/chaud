<?php

namespace AKlump\ChangeAudio\Tests\Unit;

use AKlump\ChangeAudio\ValidateConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\ValidateConfiguration
 */
class ValidateConfigurationTest extends TestCase {

  public function testDefaultConfigIsValid() {
    $config = json_decode(file_get_contents(__DIR__ . '/../install/config.json'), TRUE);
    $this->assertSame([], (new ValidateConfiguration())($config));
  }

  public static function dataForInvalidConfigProvider(): array {
    $valid_option = [
      'label' => 'Phone',
      'input' => ['device' => 'External Microphone'],
    ];
    $second_option = [
      'label' => 'Speakerphone',
      'output' => ['device' => 'MacBook Pro Speakers'],
    ];

    $tests = [];
    $tests['missing options'] = [[]];
    $tests['fewer than two options'] = [['options' => [$valid_option]]];
    $tests['option without a label'] = [
      ['options' => [['input' => ['device' => 'Mic']], $second_option]],
    ];
    $tests['option with neither input nor output'] = [
      ['options' => [['label' => 'Phone'], $second_option]],
    ];
    $tests['level above one'] = [
      [
        'options' => [
          [
            'label' => 'Phone',
            'output' => ['device' => 'Headphones', 'level' => 1.5],
          ],
          $second_option,
        ],
      ],
    ];
    $tests['device block with an unknown key'] = [
      [
        'options' => [
          [
            'label' => 'Phone',
            'output' => ['device' => 'Headphones', 'volume' => 0.5],
          ],
          $second_option,
        ],
      ],
    ];
    $tests['empty label'] = [
      [
        'options' => [
          ['label' => '', 'output' => ['device' => 'Headphones']],
          $second_option,
        ],
      ],
    ];

    return $tests;
  }

  /**
   * @dataProvider dataForInvalidConfigProvider
   */
  public function testInvalidConfigReturnsErrors(array $config) {
    $this->assertNotEmpty((new ValidateConfiguration())($config));
  }

  public function testNumericDeviceAndScriptsAreValid() {
    $config = [
      'options' => [
        [
          'label' => 'Phone',
          'aliases' => ['p'],
          'input' => ['device' => 73],
          'output' => ['device' => 'Headphones', 'level' => 0],
          'scripts' => ['nowplaying-cli pause'],
        ],
        [
          'label' => 'Speakerphone',
          'output' => ['device' => 'MacBook Pro Speakers', 'level' => 1],
        ],
      ],
    ];
    $this->assertSame([], (new ValidateConfiguration())($config));
  }
}
