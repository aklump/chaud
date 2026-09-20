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

  public function testNormalizedFormMatchesAsAFallback() {
    $resolver = new OptionResolver($this->getOptions());
    $this->assertSame('Desk setup', $resolver->resolve('desk-setup')['label']);
    $this->assertSame('Desk setup', $resolver->resolve('desk_setup')['label']);
    $this->assertSame('Desk setup', $resolver->resolve('Desk.Setup')['label']);
  }

  public function testNormalizedFormMatchesAliases() {
    $resolver = new OptionResolver([
      ['label' => 'Phone (BT)', 'aliases' => ['my phone']],
      ['label' => 'Other'],
    ]);
    $this->assertSame('Phone (BT)', $resolver->resolve('my-phone')['label']);
    $this->assertSame('Phone (BT)', $resolver->resolve('phone__bt_')['label']);
  }

  public function testExactMatchBeatsNormalizedMatch() {
    $resolver = new OptionResolver([
      ['label' => 'Desk-setup'],
      ['label' => 'Desk setup'],
    ]);
    // Each is an exact (case-insensitive) match for itself, even though both
    // normalize to "desk_setup".
    $this->assertSame('Desk-setup', $resolver->resolve('desk-setup')['label']);
    $this->assertSame('Desk setup', $resolver->resolve('DESK SETUP')['label']);
    // Only the fallback applies here.
    $this->assertNotNull($resolver->resolve('desk_setup'));
  }

  public function testExactLabelBeatsNormalizedAlias() {
    $resolver = new OptionResolver([
      ['label' => 'Desk setup'],
      ['label' => 'Other', 'aliases' => ['desk-setup']],
    ]);
    $this->assertSame('Desk setup', $resolver->resolve('desk setup')['label']);
    $this->assertSame('Other', $resolver->resolve('desk-setup')['label']);
    // Neither is exact; the alias wins the fallback, as it does for exact.
    $this->assertSame('Other', $resolver->resolve('desk_setup')['label']);
  }

  public function testUnknownReturnsNull() {
    $resolver = new OptionResolver($this->getOptions());
    $this->assertNull($resolver->resolve('spekerphone'));
    $this->assertNull($resolver->resolve(''));
    $this->assertNull($resolver->resolve('desk-setups'));
  }

  public function testGetNamesKeepsOriginalCase() {
    $resolver = new OptionResolver($this->getOptions());
    $this->assertSame(['Phone', 'p', 'Cell', 'Speakerphone', 'sp', 'Desk setup'], $resolver->getNames());
  }

}
