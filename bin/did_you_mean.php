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
  foreach (($options['aliases'] ?? []) as $alias) {
    $options[] = $option['alias'];
  }
}

$suggestions = (new FuzzyMatch())($input, $options);
if ($suggestions) {
  $suffix = array_pop($suggestions);
  if (count($suggestions) > 1) {
    $suffix = implode(', ', $suggestions) . ' or ' . $suffix . '?';
  }
  echo sprintf("🤔 Did you mean \"$suffix\"? (%s -l)", App::BIN) . PHP_EOL;
}
exit (0);
