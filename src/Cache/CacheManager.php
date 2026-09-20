<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Cache;

use AKlump\ChangeAudio\App;
use RuntimeException;

class CacheManager {

  /**
   * Get the cache directory, creating it if necessary.
   *
   * The CACHE_PATH environment variable overrides the default, which is
   * TMPDIR (or TEMP, or /tmp) plus the app's cache directory name.
   *
   * @return string
   */
  public function getPath(): string {
    $path = getenv('CACHE_PATH');
    if (empty($path)) {
      $path = $this->getDefaultPath();
    }
    if (!file_exists($path) && !@mkdir($path, 0777, TRUE)) {
      $message = error_get_last()['message'] ?? '';
      throw new RuntimeException('The cache path does not exist and could not be created: ' . $path . PHP_EOL . $message);
    }

    return $path;
  }

  private function getDefaultPath(): string {
    $base = getenv('TMPDIR') ?: (getenv('TEMP') ?: '/tmp');

    return preg_replace('#/$#', '', $base) . '/' . App::CACHE_DIRNAME;
  }
}
