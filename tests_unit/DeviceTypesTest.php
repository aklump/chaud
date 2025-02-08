<?php

namespace AKlump\ChangeAudio\Tests\Unit;

use AKlump\ChangeAudio\DeviceTypes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\DeviceTypes
 */
class DeviceTypesTest extends TestCase {

  public function testConstants() {
    $this->assertNotEmpty(DeviceTypes::INPUT);
    $this->assertNotEmpty(DeviceTypes::OUTPUT);
  }
}
