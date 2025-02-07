<?php

namespace AKlump\AudioSwitch\Cache;

use RuntimeException;

class CacheManager {

  public function getPath(): string {
    $path = getenv('CACHE_PATH');
    if (empty($path)) {
      throw new RuntimeException('$_ENV[CACHE_PATH] is empty.');
    }
    if (!file_exists($path) && !mkdir($path, 0777, TRUE)) {
      throw new RuntimeException('$_ENV[CACHE_PATH] does not exist and could not be created.');
    }

    return $path;
  }
}
