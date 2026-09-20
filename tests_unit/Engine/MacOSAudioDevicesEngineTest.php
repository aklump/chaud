<?php

namespace AKlump\ChangeAudio\Tests\Unit\Engine;

use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\Device;
use AKlump\ChangeAudio\DeviceTypes;
use AKlump\ChangeAudio\Engine\MacOSAudioDevicesEngine;
use AKlump\ChangeAudio\Exception\EngineFeatureException;
use AKlump\ChangeAudio\Exception\MissingDeviceException;
use AKlump\ChangeAudio\Tests\Unit\TestingTraits\TestWithFilesTrait;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

/**
 * @covers \AKlump\ChangeAudio\Engine\MacOSAudioDevicesEngine
 * @uses   \AKlump\ChangeAudio\Cache\CacheManager::getPath
 * @uses   \AKlump\ChangeAudio\Device
 * @uses   \AKlump\ChangeAudio\Exception\EngineFeatureException
 * @uses   \AKlump\ChangeAudio\Exception\MissingDeviceException
 */
class MacOSAudioDevicesEngineTest extends TestCase {

  use TestWithFilesTrait;

  private string $cacheDir;

  public function testAppliesReflectsWhetherTheNodePackageIsInstalled() {
    $expected = is_executable(__DIR__ . '/../../node_modules/.bin/macos-audio-devices');
    $this->assertSame($expected, (new MacOSAudioDevicesEngine(new CacheManager()))->applies());
  }

  public function testGetHomepage() {
    $engine = new MacOSAudioDevicesEngine(new CacheManager());
    $this->assertSame('https://github.com/karaggeorge/macos-audio-devices', $engine->getHomepage());
  }

  public function testGetCommandSetInputLevelThrows() {
    $engine = new MacOSAudioDevicesEngine(new CacheManager());
    $this->expectException(EngineFeatureException::class);
    $engine->getCommandSetInputLevel('External Microphone', 0.25);
  }

  public function testNumericDeviceIsUsedAsTheIdWithoutALookup() {
    $engine = $this->getEngineWithScript('/usr/bin/true');
    $this->assertSame('/usr/bin/true input set 73 1> /dev/null', $engine->getCommandChangeInput('73'));
    $this->assertSame('/usr/bin/true output set 47 1> /dev/null', $engine->getCommandChangeOutput('47'));
    $this->assertSame('/usr/bin/true volume set 47 0.250000 1> /dev/null', $engine->getCommandSetOutputLevel('47', 0.25));
  }

  public function testDeviceNameIsResolvedToAnIdFromTheCachedIndex() {
    $this->writeDeviceIndex(DeviceTypes::INPUT, [
      ['id' => 51, 'name' => 'External Microphone'],
    ]);
    $this->writeDeviceIndex(DeviceTypes::OUTPUT, [
      ['id' => 73, 'name' => 'MacBook Pro Speakers'],
    ]);
    $engine = $this->getEngineWithScript('/usr/bin/true');
    $this->assertSame('/usr/bin/true input set 51 1> /dev/null', $engine->getCommandChangeInput('External Microphone'));
    $this->assertSame('/usr/bin/true output set 73 1> /dev/null', $engine->getCommandChangeOutput('MacBook Pro Speakers'));
  }

  public function testUnknownDeviceNameFlushesTheIndexAndThrows() {
    $index = $this->writeDeviceIndex(DeviceTypes::INPUT, [
      ['id' => 51, 'name' => 'External Microphone'],
    ]);
    $engine = $this->getEngineWithScript('/usr/bin/true');
    try {
      $engine->getCommandChangeInput('Disconnected Headset');
      $this->fail('Expected a MissingDeviceException.');
    }
    catch (MissingDeviceException $exception) {
      $this->assertStringContainsString('Disconnected Headset', $exception->getMessage());
    }
    $this->assertSame([], require $index, 'Assert the stale device index was flushed and rebuilt.');
  }

  public function testDeviceIndexIsBuiltFromTheListOutputAndCached() {
    $script = $this->writeStubScript("echo '51 - External Microphone'\necho '52 - MacBook Pro Microphone'");
    $engine = $this->getEngineWithScript($script);
    $index = sprintf('%s/MacOSAudioDevicesEngine.device_index_include.%s.php', $this->cacheDir, DeviceTypes::INPUT);
    $this->assertFileDoesNotExist($index);

    $this->assertSame($script . ' input set 52 1> /dev/null', $engine->getCommandChangeInput('MacBook Pro Microphone'));

    $this->assertSame([
      ['id' => 51, 'name' => 'External Microphone'],
      ['id' => 52, 'name' => 'MacBook Pro Microphone'],
    ], require $index, 'Assert the parsed device index was cached.');
  }

  public function testOutputDeviceIndexIsBuiltWithTheOutputFlag() {
    $script = $this->writeStubScript("[[ \"$*\" == *--output* ]] && echo '73 - MacBook Pro Speakers'");
    $engine = $this->getEngineWithScript($script);
    $this->assertSame($script . ' output set 73 1> /dev/null', $engine->getCommandChangeOutput('MacBook Pro Speakers'));
  }

  public function testUnknownDeviceTypeThrows() {
    $engine = $this->getEngineWithScript('/usr/bin/true');
    $get_device_by_name = (new ReflectionObject($engine))->getMethod('getDeviceByName');
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Unknown device type: bogus');
    $get_device_by_name->invoke($engine, 'bogus', 'External Microphone');
  }

  public function testGetAllDevicesReturnsDevicesSortedByName() {
    $json = json_encode([
      ['id' => 73, 'name' => 'MacBook Pro Speakers', 'isOutput' => TRUE],
      ['id' => 51, 'name' => 'External Microphone', 'isOutput' => FALSE],
    ]);
    $engine = $this->getEngineWithScript($this->writeListScript($json));

    $devices = $engine->getAllDevices();
    $this->assertCount(2, $devices);

    $first = reset($devices);
    $this->assertInstanceOf(Device::class, $first);
    $this->assertSame('External Microphone', $first->getName());
    $this->assertSame(51, $first->getId());
    $this->assertSame(DeviceTypes::INPUT, $first->getType());

    $last = end($devices);
    $this->assertSame('MacBook Pro Speakers', $last->getName());
    $this->assertSame(DeviceTypes::OUTPUT, $last->getType());

    $this->assertSame($devices, $engine->getAllDevices(), 'Assert the second call is served from memory.');
  }

  /**
   * Get an engine whose audio engine binary is $script.
   *
   * ::applies sets the path to the real node package, which the tests must not
   * depend on nor execute; this replaces it with a harmless stand-in.
   */
  private function getEngineWithScript(string $script): MacOSAudioDevicesEngine {
    $engine = new MacOSAudioDevicesEngine(new CacheManager());
    $engine->applies();
    $property = (new ReflectionObject($engine))->getProperty('script');
    $property->setValue($engine, $script);

    return $engine;
  }

  private function writeDeviceIndex(string $device_type, array $devices): string {
    $path = sprintf('%s/MacOSAudioDevicesEngine.device_index_include.%s.php', $this->cacheDir, $device_type);
    file_put_contents($path, '<?php return ' . var_export($devices, TRUE) . ';');

    return $path;
  }

  private function writeListScript(string $json): string {
    return $this->writeStubScript("echo '$json'");
  }

  private function writeStubScript(string $body): string {
    $path = $this->getTestFileFilepath('bin/', TRUE) . '/macos-audio-devices';
    file_put_contents($path, "#!/usr/bin/env bash\n$body\n");
    chmod($path, 0755);

    return $path;
  }

  protected function setUp(): void {
    $this->cacheDir = $this->getTestFileFilepath('cache/', TRUE);
    $this->deleteTestFile($this->cacheDir);
    $this->cacheDir = $this->getTestFileFilepath('cache/', TRUE);
    putenv('CACHE_PATH=' . $this->cacheDir);
    parent::setUp();
  }

  protected function tearDown(): void {
    putenv('CACHE_PATH');
    $this->deleteAllTestFiles();
    parent::tearDown();
  }
}
