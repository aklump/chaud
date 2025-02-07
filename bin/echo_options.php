#!/usr/bin/env php
<?php
// SPDX-License-Identifier: BSD-3-Clause

use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\ConfigManager;

require_once __DIR__ . '/../vendor/autoload.php';
# If the file doesn't exist, it will be installed during ::get.
$config = (new ConfigManager(new CacheManager()))->get();
$indent = '     ';
foreach ($config['options'] as $option) {
  echo '🔹 ' . $option['label'] . PHP_EOL;
  if (!empty($option['aliases'])) {
    echo $indent . implode(PHP_EOL . $indent, $option['aliases']) . PHP_EOL;
  }
}
exit (0);
