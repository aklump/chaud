<?php

namespace AKlump\ChangeAudio;

class Device {

  private string $name;

  private int $id;
  private string $type;

  public function getType(): string {
    return $this->type;
  }

  /**
   * @param string $type
   *
   * @return $this
   *
   * @see \AKlump\ChangeAudio\DeviceTypes
   */
  public function setType(string $type): self {
    $this->type = $type;

    return $this;
  }

  public function getName(): string {
    return $this->name;
  }

  public function setName(string $name): self {
    $this->name = $name;

    return $this;
  }

  public function getId(): int {
    return $this->id;
  }

  public function setId(int $id): self {
    $this->id = $id;

    return $this;
  }

  public function __toString(): string {
    return sprintf('%s (%s) %d', $this->name, $this->type, $this->id);
  }

}
