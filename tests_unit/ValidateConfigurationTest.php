<?php

namespace AKlump\ChangeAudio\Tests\Unit;

use AKlump\ChangeAudio\ValidateConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\ValidateConfiguration
 */
class ValidateConfigurationTest extends TestCase {

  public function testDefaultConfigIsValid() {
    $config = \Symfony\Component\Yaml\Yaml::parseFile(__DIR__ . '/../install/config.yml');
    $this->assertSame([], (new ValidateConfiguration())($config));
  }

  public static function dataForInvalidConfigProvider(): array {
    $valid_option = [
      'label' => 'Phone',
      'input' => ['name' => 'External Microphone'],
    ];
    $second_option = [
      'label' => 'Speakerphone',
      'output' => ['name' => 'MacBook Pro Speakers'],
    ];

    $tests = [];
    $tests['missing options'] = [[]];
    $tests['fewer than two options'] = [['options' => [$valid_option]]];
    $tests['option without a label'] = [
      ['options' => [['input' => ['name' => 'Mic']], $second_option]],
    ];
    $tests['option with neither input nor output'] = [
      ['options' => [['label' => 'Phone'], $second_option]],
    ];
    $tests['level above one'] = [
      [
        'options' => [
          [
            'label' => 'Phone',
            'output' => ['name' => 'Headphones', 'level' => 1.5],
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
            'output' => ['name' => 'Headphones', 'volume' => 0.5],
          ],
          $second_option,
        ],
      ],
    ];
    $tests['device block with neither device nor uid'] = [
      [
        'options' => [
          [
            'label' => 'Phone',
            'output' => ['level' => 0.5],
          ],
          $second_option,
        ],
      ],
    ];
    $tests['empty uid'] = [
      [
        'options' => [
          ['label' => 'Phone', 'output' => ['uid' => '']],
          $second_option,
        ],
      ],
    ];
    $tests['numeric uid'] = [
      [
        'options' => [
          ['label' => 'Phone', 'output' => ['uid' => 73]],
          $second_option,
        ],
      ],
    ];
    $tests['empty label'] = [
      [
        'options' => [
          ['label' => '', 'output' => ['name' => 'Headphones']],
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
          'input' => ['name' => 73],
          'output' => ['name' => 'Headphones', 'level' => 0],
          'scripts' => ['nowplaying-cli pause'],
        ],
        [
          'label' => 'Speakerphone',
          'output' => ['name' => 'MacBook Pro Speakers', 'level' => 1],
        ],
      ],
    ];
    $this->assertSame([], (new ValidateConfiguration())($config));
  }

  public function testUidIsValidInPlaceOfDevice() {
    $config = [
      'options' => [
        [
          'label' => 'Phone',
          'input' => ['uid' => 'BuiltInMicrophoneDevice'],
          'output' => ['uid' => 'BuiltInHeadphoneOutputDevice', 'level' => 0.5],
        ],
        [
          'label' => 'Speakerphone',
          'output' => ['name' => 'MacBook Pro Speakers'],
        ],
      ],
    ];
    $this->assertSame([], (new ValidateConfiguration())($config));
  }

  public function testUidAndDeviceTogetherAreValid() {
    $config = [
      'options' => [
        [
          'label' => 'Phone',
          'output' => ['name' => 'Headphones', 'uid' => 'BuiltInHeadphoneOutputDevice'],
        ],
        [
          'label' => 'Speakerphone',
          'output' => ['name' => 'MacBook Pro Speakers'],
        ],
      ],
    ];
    $this->assertSame([], (new ValidateConfiguration())($config));
  }
}
