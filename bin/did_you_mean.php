#!/usr/bin/env php
<?php
// SPDX-License-Identifier: BSD-3-Clause

use AKlump\ChangeAudio\App;
use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\ConfigManager;
use AKlump\ChangeAudio\FuzzyMatch;

require_once __DIR__ . '/../vendor/autoload.php';

$input = $argv[1];

$config = (new ConfigManager(new CacheManager()))->get();
$options = [];
if (empty($config['options'])) {
  exit (0);
}
foreach ($config['options'] as $option) {
  $options[] = $option['label'];
  foreach (($option['aliases'] ?? []) as $alias) {
    $options[] = $alias;
  }
}

// FuzzyMatch returns at most one suggestion, so there is never more than one
// to name here.
$suggestions = (new FuzzyMatch())($input, $options);
if ($suggestions) {
  $suggestion = array_pop($suggestions);
  echo sprintf("🤔 Did you mean \"%s\"? (%s -l)", $suggestion, App::BIN) . PHP_EOL;
}
exit (0);
