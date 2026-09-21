<?php

namespace AKlump\ChangeAudio\Tests\Unit\TestingTraits;

/**
 * Writes a user's config where ConfigManager looks for it under a test home.
 */
trait WriteUserConfigTrait {

  /**
   * @return string The path of the config file under $home.
   */
  private function getUserConfigPath(string $home): string {
    return rtrim($home, '/') . '/.config/chaudio/config.yml';
  }

  /**
   * @return string The path that was written.
   */
  private function writeUserConfig(string $home, string $contents): string {
    $path = $this->getUserConfigPath($home);
    if (!is_dir(dirname($path))) {
      mkdir(dirname($path), 0755, TRUE);
    }
    file_put_contents($path, $contents);

    return $path;
  }

}
