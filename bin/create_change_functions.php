#!/usr/bin/env php
<?php
// SPDX-License-Identifier: BSD-3-Clause

use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\Cache\CreateChangeFunctions;
use AKlump\ChangeAudio\GetAudioEngine;
use AKlump\ChangeAudio\ConfigManager;

require_once __DIR__ . '/../vendor/autoload.php';

$change_scripts_path = $argv[1] ?? '';
if (empty($change_scripts_path)) {
  $change_scripts_path = (new CacheManager())->getPath() . '/change_audio.sh';
}

$manager = new ConfigManager(new CacheManager());
$manager->get();
$validate_errors = $manager->getValidationErrors();
if ($validate_errors) {
  echo '❌ Invalid configuration:' . PHP_EOL;
  foreach ($validate_errors as $error) {
    echo '⚠️ ' . $error . PHP_EOL;
  }
  exit(1);
}

$engine = (new GetAudioEngine())();
(new CreateChangeFunctions($engine))($change_scripts_path);
