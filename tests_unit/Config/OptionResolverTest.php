<?php

namespace AKlump\ChangeAudio\Tests\Unit\Config;

use AKlump\ChangeAudio\Config\OptionResolver;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\ChangeAudio\Config\OptionResolver
 */
class OptionResolverTest extends TestCase {

  private function getOptions(): array {
    return [
      ['label' => 'Phone', 'aliases' => ['p', 'Cell']],
      ['label' => 'Speakerphone', 'aliases' => ['sp']],
      ['label' => 'Desk setup'],
    ];
  }

  public function testResolveByLabelIgnoresCase() {
    $resolver = new OptionResolver($this->getOptions());
    $this->assertSame('Speakerphone', $resolver->resolve('speakerphone')['label']);
    $this->assertSame('Desk setup', $resolver->resolve('DESK SETUP')['label']);
  }

  public function testResolveByAliasIgnoresCase() {
    $resolver = new OptionResolver($this->getOptions());
    $this->assertSame('Phone', $resolver->resolve('cell')['label']);
    $this->assertSame('Speakerphone', $resolver->resolve('SP')['label']);
  }

  public function testAliasWinsOverLabel() {
    $resolver = new OptionResolver([
      ['label' => 'Phone'],
      ['label' => 'Desk', 'aliases' => ['phone']],
    ]);
    $this->assertSame('Desk', $resolver->resolve('Phone')['label']);
  }

  public function testUnknownReturnsNull() {
    $resolver = new OptionResolver($this->getOptions());
    $this->assertNull($resolver->resolve('spekerphone'));
    $this->assertNull($resolver->resolve(''));
  }

  public function testGetNamesKeepsOriginalCase() {
    $resolver = new OptionResolver($this->getOptions());
    $this->assertSame(['Phone', 'p', 'Cell', 'Speakerphone', 'sp', 'Desk setup'], $resolver->getNames());
  }

}
