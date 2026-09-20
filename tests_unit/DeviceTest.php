<?php

namespace AKlump\ChangeAudio\Tests\Unit;

use AKlump\ChangeAudio\Device;
use AKlump\ChangeAudio\DeviceTypes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\Device
 */
class DeviceTest extends TestCase {

  public function testSettersReturnSelfForChaining() {
    $device = new Device();
    $this->assertSame($device, $device->setName('External Microphone'));
    $this->assertSame($device, $device->setId(47));
    $this->assertSame($device, $device->setType(DeviceTypes::INPUT));
  }

  public function testGettersReturnWhatWasSet() {
    $device = (new Device())
      ->setName('External Headphones')
      ->setId(52)
      ->setType(DeviceTypes::OUTPUT);
    $this->assertSame('External Headphones', $device->getName());
    $this->assertSame(52, $device->getId());
    $this->assertSame(DeviceTypes::OUTPUT, $device->getType());
  }

  public function testToStringMatchesTheFormatUsedByEchoAll() {
    $device = (new Device())
      ->setName('MacBook Pro Speakers')
      ->setId(73)
      ->setType(DeviceTypes::OUTPUT);
    $this->assertSame('MacBook Pro Speakers (output) 73', (string) $device);
  }
}
