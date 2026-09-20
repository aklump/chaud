<?php

namespace AKlump\ChangeAudio\Tests\Unit\Cache;

use AKlump\ChangeAudio\Cache\CreateChangeFunctions;
use AKlump\ChangeAudio\Device;
use AKlump\ChangeAudio\DeviceTypes;
use AKlump\ChangeAudio\Engine\EngineInterface;
use AKlump\ChangeAudio\Exception\EngineFeatureException;
use AKlump\ChangeAudio\Exception\MissingDeviceException;
use AKlump\ChangeAudio\Tests\Unit\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\Cache\CreateChangeFunctions
 * @uses   \AKlump\ChangeAudio\Cache\CacheManager::getPath
 * @uses   \AKlump\ChangeAudio\ConfigManager
 * @uses   \AKlump\ChangeAudio\Device
 * @uses   \AKlump\ChangeAudio\Exception\EngineFeatureException
 * @uses   \AKlump\ChangeAudio\Exception\MissingDeviceException
 * @uses   \AKlump\ChangeAudio\GetDeviceLevel
 * @uses   \AKlump\ChangeAudio\ValidateConfiguration
 */
class CreateChangeFunctionsTest extends TestCase {

  use TestWithFilesTrait;

  private string $userHome;

  private string $outputPath;

  public function testFileIsBashWithAFunctionPerLabelAndAlias() {
    $bash = $this->createFunctions([
      [
        'label' => 'Phone',
        'aliases' => ['p'],
        'input' => ['device' => 'External Microphone'],
      ],
      [
        'label' => 'Speakerphone',
        'aliases' => ['sp'],
        'output' => ['device' => 'MacBook Pro Speakers'],
      ],
    ]);

    $this->assertStringStartsWith('#!/usr/bin/env bash', $bash);
    $this->assertStringContainsString('change_to_phone(){', $bash);
    $this->assertStringContainsString('change_to_p(){', $bash);
    $this->assertStringContainsString('change_to_speakerphone(){', $bash);
    $this->assertStringContainsString('change_to_sp(){', $bash);
  }

  public function testLabelWithSpacesBecomesAnUnderscoredFunctionName() {
    $bash = $this->createFunctions([
      [
        'label' => 'Desk Setup',
        'input' => ['device' => 'External Microphone'],
      ],
    ]);
    $this->assertStringContainsString('change_to_desk_setup(){', $bash);
  }

  public function testInputOutputAndLevelCommandsComeFromTheEngine() {
    $bash = $this->createFunctions([
      [
        'label' => 'Phone',
        'input' => ['device' => 'External Microphone'],
        'output' => ['device' => 'External Headphones', 'level' => 0.25],
      ],
    ]);

    $this->assertStringContainsString('INPUT:External Microphone', $bash);
    $this->assertStringContainsString('OUTPUT:External Headphones', $bash);
    $this->assertStringContainsString('OUTPUT_LEVEL:External Headphones:0.25', $bash);
    $this->assertStringContainsString('❌ Failed to change input device.', $bash);
    $this->assertStringContainsString('❌ Failed to change output device.', $bash);
    $this->assertStringContainsString('⚠️ Audio remains unchanged.', $bash);
  }

  public function testInputLevelIsSkippedWhenTheEngineDoesNotSupportIt() {
    $bash = $this->createFunctions([
      [
        'label' => 'Phone',
        'input' => ['device' => 'External Microphone', 'level' => 0.5],
      ],
    ]);
    $this->assertStringContainsString('INPUT:External Microphone', $bash);
    $this->assertStringNotContainsString('INPUT_LEVEL', $bash);
  }

  public function testScriptsAreAppendedWithAFailureMessage() {
    $bash = $this->createFunctions([
      [
        'label' => 'Phone',
        'input' => ['device' => 'External Microphone'],
        'scripts' => ['nowplaying-cli pause'],
      ],
    ]);
    $this->assertStringContainsString('  nowplaying-cli pause', $bash);
    $this->assertStringContainsString('❌ Script failed: nowplaying-cli pause', $bash);
  }

  public function testUserMessageNamesBothDevices() {
    $bash = $this->createFunctions([
      [
        'label' => 'Phone',
        'input' => ['device' => 'External Microphone'],
        'output' => ['device' => 'External Headphones'],
      ],
    ]);
    $this->assertStringContainsString('echo "Phone is active (🎤 External Microphone  🔈 External Headphones)"', $bash);
  }

  public function testUserMessageOmitsTheDeviceThatIsNotConfigured() {
    $bash = $this->createFunctions([
      [
        'label' => 'Speakerphone',
        'output' => ['device' => 'MacBook Pro Speakers'],
      ],
    ]);
    $this->assertStringContainsString('echo "Speakerphone is active (🔈 MacBook Pro Speakers)"', $bash);
  }

  public function testNumericDeviceIsNamedInTheUserMessage() {
    $bash = $this->createFunctions([
      [
        'label' => 'Phone',
        'output' => ['device' => 73],
      ],
    ]);
    $this->assertStringContainsString('🔈 MacBook Pro Speakers', $bash);
  }

  public function testOptionWithAMissingDeviceIsOmitted() {
    $bash = $this->createFunctions([
      [
        'label' => 'Headset',
        'aliases' => ['h'],
        'input' => ['device' => 'Disconnected Headset'],
      ],
      [
        'label' => 'Speakerphone',
        'output' => ['device' => 'MacBook Pro Speakers'],
      ],
    ]);
    $this->assertStringNotContainsString('change_to_headset', $bash);
    $this->assertStringNotContainsString('change_to_h(', $bash);
    $this->assertStringContainsString('change_to_speakerphone(){', $bash);
  }

  public function testOutputLevelIsSkippedWhenTheEngineDoesNotSupportIt() {
    $bash = $this->createFunctions([
      [
        'label' => 'Phone',
        'output' => ['device' => 'External Headphones', 'level' => 0.25],
      ],
    ], FALSE);
    $this->assertStringContainsString('OUTPUT:External Headphones', $bash);
    $this->assertStringNotContainsString('OUTPUT_LEVEL', $bash);
  }

  public function testUnmatchedNumericDeviceLeavesTheNameEmpty() {
    $bash = $this->createFunctions([
      [
        'label' => 'Phone',
        'output' => ['device' => 999],
      ],
    ]);
    $this->assertStringContainsString('echo "Phone is active ()"', $bash);
  }

  /**
   * Write $options as the user config, generate the functions, return the bash.
   */
  private function createFunctions(array $options, bool $supports_output_level = TRUE): string {
    file_put_contents($this->userHome . '/.chaud.json', json_encode(['options' => $options]));
    (new CreateChangeFunctions($this->getEngine($supports_output_level)))($this->outputPath);
    $this->assertFileExists($this->outputPath);

    return file_get_contents($this->outputPath);
  }

  /**
   * An engine that records what it was asked for instead of changing audio.
   */
  private function getEngine(bool $supports_output_level = TRUE): EngineInterface {
    return new class($supports_output_level) implements EngineInterface {

      private bool $supportsOutputLevel;

      public function __construct(bool $supports_output_level) {
        $this->supportsOutputLevel = $supports_output_level;
      }

      public function applies(): bool {
        return TRUE;
      }

      public function getCommandChangeInput(string $device): string {
        if ($device === 'Disconnected Headset') {
          throw new MissingDeviceException(sprintf('Could not find device "%s"', $device));
        }

        return sprintf('INPUT:%s', $device);
      }

      public function getCommandChangeOutput(string $device): string {
        return sprintf('OUTPUT:%s', $device);
      }

      public function getCommandSetOutputLevel(string $device, float $limit): string {
        if (!$this->supportsOutputLevel) {
          throw new EngineFeatureException('This engine does not support output levels.');
        }

        return sprintf('OUTPUT_LEVEL:%s:%s', $device, $limit);
      }

      public function getCommandSetInputLevel(string $device, float $limit): string {
        throw new EngineFeatureException('This engine does not support input levels.');
      }

      public function getHomepage(): string {
        return 'https://example.com';
      }

      public function getAllDevices(): array {
        return [
          (new Device())->setId(73)
            ->setName('MacBook Pro Speakers')
            ->setType(DeviceTypes::OUTPUT),
        ];
      }
    };
  }

  protected function setUp(): void {
    $this->userHome = $this->getTestFileFilepath('home/', TRUE);
    $this->deleteTestFile($this->userHome);
    $this->userHome = $this->getTestFileFilepath('home/', TRUE);
    $_SERVER['HOME'] = $this->userHome;

    $cache_dir = $this->getTestFileFilepath('cache/', TRUE);
    $this->deleteTestFile($cache_dir);
    putenv('CACHE_PATH=' . $this->getTestFileFilepath('cache/', TRUE));

    $this->outputPath = $this->getTestFileFilepath('cache/change_audio.sh');
    parent::setUp();
  }

  protected function tearDown(): void {
    putenv('CACHE_PATH');
    $this->deleteAllTestFiles();
    parent::tearDown();
  }
}
