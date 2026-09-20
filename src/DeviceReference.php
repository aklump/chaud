<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio;

use InvalidArgumentException;

/**
 * How a configured input or output points at a device.
 *
 * A UID cannot be told apart from a name by its value alone, so the kind is
 * carried explicitly, from the config key that was used.
 */
class DeviceReference {

  /**
   * The value is a device name or numeric ID (config key "device").
   */
  const DEVICE = 'device';

  /**
   * The value is a CoreAudio UID (config key "uid"), which survives reboot.
   */
  const UID = 'uid';

  private string $kind;

  /**
   * @var string|int
   */
  private $value;

  /**
   * @param string $kind One of the class constants.
   * @param string|int $value
   */
  public function __construct(string $kind, $value) {
    if (!in_array($kind, [self::DEVICE, self::UID], TRUE)) {
      throw new InvalidArgumentException(sprintf('Unknown device reference kind: %s', $kind));
    }
    $this->kind = $kind;
    $this->value = $value;
  }

  /**
   * @param array $device_config An option's "input" or "output" config, which
   *   has either a "uid" or a "device" key.
   *
   * @return static
   */
  public static function fromConfig(array $device_config): self {
    if (isset($device_config[self::UID])) {
      return new self(self::UID, (string) $device_config[self::UID]);
    }
    if (isset($device_config[self::DEVICE])) {
      return new self(self::DEVICE, $device_config[self::DEVICE]);
    }
    throw new InvalidArgumentException('A device configuration needs a "device" or a "uid".');
  }

  public function getKind(): string {
    return $this->kind;
  }

  /**
   * @return string|int
   */
  public function getValue() {
    return $this->value;
  }

  public function isUid(): bool {
    return $this->kind === self::UID;
  }

  /**
   * @return bool TRUE if this reference points at $device.  A name matches
   *   every device with that name, since names are not unique.
   */
  public function matches(Device $device): bool {
    if ($this->isUid()) {
      return $device->getUid() !== '' && (string) $this->value === $device->getUid();
    }

    return (string) $this->value === (string) $device->getId() || (string) $this->value === $device->getName();
  }

  public function __toString(): string {
    return (string) $this->value;
  }

}
