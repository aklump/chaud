<?php

namespace AKlump\ChangeAudio\Tests\Unit\Engine;

use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\Device;
use AKlump\ChangeAudio\DeviceReference;
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
 * @uses   \AKlump\ChangeAudio\DeviceReference
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
    $engine->getCommandSetInputLevel($this->name('External Microphone'), 0.25);
  }

  public function testNumericDeviceIsUsedAsTheIdWithoutALookup() {
    $engine = $this->getEngineWithScript('/usr/bin/true');
    $this->assertSame('/usr/bin/true input set 73 1> /dev/null', $engine->getCommandChangeInput($this->name('73')));
    $this->assertSame('/usr/bin/true output set 47 1> /dev/null', $engine->getCommandChangeOutput($this->name(47)));
    $this->assertSame('/usr/bin/true volume set 47 0.250000 1> /dev/null', $engine->getCommandSetOutputLevel($this->name('47'), 0.25));
  }

  public function testDeviceNameIsResolvedToAnIdFromTheCachedIndex() {
    $this->writeDeviceIndex(DeviceTypes::INPUT, [
      ['id' => 51, 'name' => 'External Microphone', 'uid' => 'ExternalMic'],
    ]);
    $this->writeDeviceIndex(DeviceTypes::OUTPUT, [
      ['id' => 73, 'name' => 'MacBook Pro Speakers', 'uid' => 'BuiltInSpeakerDevice'],
    ]);
    $engine = $this->getEngineWithScript('/usr/bin/true');
    $this->assertSame('/usr/bin/true input set 51 1> /dev/null', $engine->getCommandChangeInput($this->name('External Microphone')));
    $this->assertSame('/usr/bin/true output set 73 1> /dev/null', $engine->getCommandChangeOutput($this->name('MacBook Pro Speakers')));
  }

  public function testDeviceUidIsResolvedToAnIdFromTheCachedIndex() {
    $this->writeDeviceIndex(DeviceTypes::INPUT, [
      ['id' => 51, 'name' => 'External Microphone', 'uid' => 'ExternalMic'],
    ]);
    $this->writeDeviceIndex(DeviceTypes::OUTPUT, [
      ['id' => 126, 'name' => 'LG UltraFine Display Audio', 'uid' => 'AppleUSBAudioEngine:1'],
      ['id' => 116, 'name' => 'LG UltraFine Display Audio', 'uid' => 'AppleUSBAudioEngine:2'],
    ]);
    $engine = $this->getEngineWithScript('/usr/bin/true');
    $this->assertSame('/usr/bin/true input set 51 1> /dev/null', $engine->getCommandChangeInput($this->uid('ExternalMic')));
    $this->assertSame('/usr/bin/true output set 116 1> /dev/null', $engine->getCommandChangeOutput($this->uid('AppleUSBAudioEngine:2')));
    $this->assertSame('/usr/bin/true volume set 116 0.500000 1> /dev/null', $engine->getCommandSetOutputLevel($this->uid('AppleUSBAudioEngine:2'), 0.5));
  }

  public function testNumericLookingUidIsNotTreatedAsAnId() {
    $this->writeDeviceIndex(DeviceTypes::OUTPUT, [
      ['id' => 9, 'name' => 'Odd', 'uid' => '12345'],
    ]);
    $engine = $this->getEngineWithScript('/usr/bin/true');
    $this->assertSame('/usr/bin/true output set 9 1> /dev/null', $engine->getCommandChangeOutput($this->uid('12345')));
  }

  public function testUnknownDeviceNameFlushesTheIndexAndThrows() {
    $index = $this->writeDeviceIndex(DeviceTypes::INPUT, [
      ['id' => 51, 'name' => 'External Microphone', 'uid' => 'ExternalMic'],
    ]);
    $engine = $this->getEngineWithScript('/usr/bin/true');
    try {
      $engine->getCommandChangeInput($this->name('Disconnected Headset'));
      $this->fail('Expected a MissingDeviceException.');
    }
    catch (MissingDeviceException $exception) {
      $this->assertStringContainsString('Disconnected Headset', $exception->getMessage());
    }
    $this->assertSame([], require $index, 'Assert the stale device index was flushed and rebuilt.');
  }

  public function testUnknownDeviceUidFlushesTheIndexAndThrows() {
    $index = $this->writeDeviceIndex(DeviceTypes::INPUT, [
      ['id' => 51, 'name' => 'External Microphone', 'uid' => 'ExternalMic'],
    ]);
    $engine = $this->getEngineWithScript('/usr/bin/true');
    try {
      $engine->getCommandChangeInput($this->uid('GoneDevice'));
      $this->fail('Expected a MissingDeviceException.');
    }
    catch (MissingDeviceException $exception) {
      $this->assertStringContainsString('uid "GoneDevice"', $exception->getMessage());
    }
    $this->assertSame([], require $index, 'Assert the stale device index was flushed and rebuilt.');
  }

  public function testUidMissingFromAStaleIndexIsFoundAfterOneRebuild() {
    $this->writeDeviceIndex(DeviceTypes::OUTPUT, [
      ['id' => 73, 'name' => 'MacBook Pro Speakers', 'uid' => 'BuiltInSpeakerDevice'],
    ]);
    $script = $this->writeListScript(json_encode([
      ['id' => 88, 'name' => 'Newly Connected', 'isInput' => FALSE, 'isOutput' => TRUE, 'uid' => 'NewDevice'],
    ]));
    $engine = $this->getEngineWithScript($script);
    $this->assertSame($script . ' output set 88 1> /dev/null', $engine->getCommandChangeOutput($this->uid('NewDevice')));
  }

  public function testIndexCachedBeforeUidsWereIndexedIsRebuiltForAUid() {
    $this->writeDeviceIndex(DeviceTypes::OUTPUT, [
      ['id' => 73, 'name' => 'MacBook Pro Speakers'],
    ]);
    $script = $this->writeListScript(json_encode([
      ['id' => 73, 'name' => 'MacBook Pro Speakers', 'isInput' => FALSE, 'isOutput' => TRUE, 'uid' => 'BuiltInSpeakerDevice'],
    ]));
    $engine = $this->getEngineWithScript($script);
    $this->assertSame($script . ' output set 73 1> /dev/null', $engine->getCommandChangeOutput($this->uid('BuiltInSpeakerDevice')));
  }

  public function testDeviceIndexIsBuiltFromTheJsonListAndCachedPerType() {
    $script = $this->writeListScript(json_encode([
      ['id' => 51, 'name' => 'External Microphone', 'isInput' => TRUE, 'isOutput' => FALSE, 'uid' => 'ExternalMic'],
      ['id' => 52, 'name' => 'MacBook Pro Microphone', 'isInput' => TRUE, 'isOutput' => FALSE, 'uid' => 'BuiltInMicrophoneDevice'],
      ['id' => 73, 'name' => 'MacBook Pro Speakers', 'isInput' => FALSE, 'isOutput' => TRUE, 'uid' => 'BuiltInSpeakerDevice'],
      ['id' => 62, 'name' => 'Microsoft Teams Audio', 'isInput' => TRUE, 'isOutput' => TRUE, 'uid' => 'MSTeamsAudioDevice'],
    ]));
    $engine = $this->getEngineWithScript($script);
    $index = sprintf('%s/MacOSAudioDevicesEngine.device_index_include.%s.php', $this->cacheDir, DeviceTypes::INPUT);
    $this->assertFileDoesNotExist($index);

    $this->assertSame($script . ' input set 52 1> /dev/null', $engine->getCommandChangeInput($this->name('MacBook Pro Microphone')));

    $this->assertSame([
      ['id' => 51, 'name' => 'External Microphone', 'uid' => 'ExternalMic'],
      ['id' => 52, 'name' => 'MacBook Pro Microphone', 'uid' => 'BuiltInMicrophoneDevice'],
      ['id' => 62, 'name' => 'Microsoft Teams Audio', 'uid' => 'MSTeamsAudioDevice'],
    ], require $index, 'Assert the parsed input index was cached, without output-only devices.');
  }

  public function testOutputDeviceIndexOnlyHoldsOutputDevices() {
    $script = $this->writeListScript(json_encode([
      ['id' => 51, 'name' => 'External Microphone', 'isInput' => TRUE, 'isOutput' => FALSE, 'uid' => 'ExternalMic'],
      ['id' => 73, 'name' => 'MacBook Pro Speakers', 'isInput' => FALSE, 'isOutput' => TRUE, 'uid' => 'BuiltInSpeakerDevice'],
    ]));
    $engine = $this->getEngineWithScript($script);
    $this->assertSame($script . ' output set 73 1> /dev/null', $engine->getCommandChangeOutput($this->name('MacBook Pro Speakers')));
    $this->expectException(MissingDeviceException::class);
    $engine->getCommandChangeOutput($this->name('External Microphone'));
  }

  public function testUnknownDeviceTypeThrows() {
    $engine = $this->getEngineWithScript('/usr/bin/true');
    $get_device = (new ReflectionObject($engine))->getMethod('getDeviceFromIndex');
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Unknown device type: bogus');
    $get_device->invoke($engine, 'bogus', $this->name('External Microphone'));
  }

  private function name($value): DeviceReference {
    return new DeviceReference(DeviceReference::NAME, $value);
  }

  private function uid(string $value): DeviceReference {
    return new DeviceReference(DeviceReference::UID, $value);
  }

  public function testGetAllDevicesReturnsDevicesSortedByName() {
    $json = json_encode([
      ['id' => 73, 'name' => 'MacBook Pro Speakers', 'isOutput' => TRUE, 'uid' => 'BuiltInSpeakerDevice'],
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
    $this->assertSame('', $first->getUid(), 'Assert a missing uid becomes an empty string.');

    $last = end($devices);
    $this->assertSame('MacBook Pro Speakers', $last->getName());
    $this->assertSame(DeviceTypes::OUTPUT, $last->getType());
    $this->assertSame('BuiltInSpeakerDevice', $last->getUid());

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
    putenv('CHAUDIO_CACHE_PATH=' . $this->cacheDir);
    parent::setUp();
  }

  protected function tearDown(): void {
    putenv('CHAUDIO_CACHE_PATH');
    $this->deleteAllTestFiles();
    parent::tearDown();
  }
}
