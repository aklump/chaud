<?php

namespace AKlump\ChangeAudio\Tests\Unit;

use AKlump\ChangeAudio\Device;
use AKlump\ChangeAudio\DeviceReference;
use AKlump\ChangeAudio\DeviceTypes;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\DeviceReference
 * @uses   \AKlump\ChangeAudio\Device
 */
class DeviceReferenceTest extends TestCase {

  private function getDevice(): Device {
    return (new Device())
      ->setId(71)
      ->setName('MacBook Pro Speakers')
      ->setUid('BuiltInSpeakerDevice')
      ->setType(DeviceTypes::OUTPUT);
  }

  public function testFromConfigWithDevice() {
    $reference = DeviceReference::fromConfig(['name' => 'Headphones', 'level' => 0.5]);
    $this->assertSame(DeviceReference::NAME, $reference->getKind());
    $this->assertFalse($reference->isUid());
    $this->assertSame('Headphones', $reference->getValue());
    $this->assertSame('Headphones', (string) $reference);
  }

  public function testFromConfigKeepsANumericDeviceValue() {
    $this->assertSame(73, DeviceReference::fromConfig(['name' => 73])->getValue());
  }

  public function testFromConfigWithUid() {
    $reference = DeviceReference::fromConfig(['uid' => 'BuiltInSpeakerDevice']);
    $this->assertSame(DeviceReference::UID, $reference->getKind());
    $this->assertTrue($reference->isUid());
    $this->assertSame('BuiltInSpeakerDevice', $reference->getValue());
  }

  public function testFromConfigWithUidAndDevicePrefersUidAndKeepsNameAsFallback() {
    $reference = DeviceReference::fromConfig(['uid' => 'BuiltInSpeakerDevice', 'name' => 'Speakers']);
    $this->assertTrue($reference->isUid());
    $this->assertSame('BuiltInSpeakerDevice', $reference->getValue());
    $this->assertSame('Speakers', $reference->getFallback()->getValue());
    $this->assertNull(DeviceReference::fromConfig(['uid' => 'x'])->getFallback());
  }

  public function testFromConfigWithNeitherKeyThrows() {
    $this->expectException(InvalidArgumentException::class);
    DeviceReference::fromConfig(['level' => 0.5]);
  }

  public function testUnknownKindThrows() {
    $this->expectException(InvalidArgumentException::class);
    new DeviceReference('bogus', 'x');
  }

  public function testMatchesByIdNameAndUid() {
    $device = $this->getDevice();
    $this->assertTrue((new DeviceReference(DeviceReference::NAME, 71))->matches($device));
    $this->assertTrue((new DeviceReference(DeviceReference::NAME, '71'))->matches($device));
    $this->assertTrue((new DeviceReference(DeviceReference::NAME, 'MacBook Pro Speakers'))->matches($device));
    $this->assertFalse((new DeviceReference(DeviceReference::NAME, 'Other'))->matches($device));
    $this->assertTrue((new DeviceReference(DeviceReference::UID, 'BuiltInSpeakerDevice'))->matches($device));
    $this->assertFalse((new DeviceReference(DeviceReference::UID, 'Other'))->matches($device));
  }

  public function testUidIsNotMatchedAgainstNameOrId() {
    $device = (new Device())->setId(71)->setName('BuiltInSpeakerDevice')->setType(DeviceTypes::OUTPUT);
    $this->assertFalse((new DeviceReference(DeviceReference::UID, 'BuiltInSpeakerDevice'))->matches($device));
    $this->assertFalse((new DeviceReference(DeviceReference::UID, '71'))->matches($device));
  }

  public function testEmptyUidNeverMatchesADeviceWithoutOne() {
    $device = (new Device())->setId(71)->setName('X')->setType(DeviceTypes::OUTPUT);
    $this->assertFalse((new DeviceReference(DeviceReference::UID, ''))->matches($device));
  }

}
