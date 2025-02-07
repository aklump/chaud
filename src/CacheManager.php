<?php

namespace AKlump\AudioSwitch;

class CacheManager {

  public function flush() {
    if (file_exists($this->path())) {
      array_map('unlink', glob($this->path() . '/*'));
    }
  }

  public function path(): string {
    $cache_dir = sys_get_temp_dir() . '/com.aklump.chaud';
    if (!file_exists($cache_dir)) {
      mkdir($cache_dir, 0777, TRUE);
    }

    return $cache_dir;
  }
}
