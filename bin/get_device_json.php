#!/usr/bin/env php
<?php
// SPDX-License-Identifier: BSD-3-Clause

use AKlump\AudioSwitch\CacheManager;
use AKlump\AudioSwitch\GetAudioConfigByAlias;
use AKlump\AudioSwitch\GetAudioConfigByLabel;
use AKlump\AudioSwitch\ConfigManager;
use AKlump\AudioSwitch\GetDefaultAudioConfig;

require_once __DIR__ . '/../vendor/autoload.php';

// Install the example configuration if necessary.
$config = (new ConfigManager(new CacheManager()))->get();

$input = $argv[1] ?? '';
if (!$input) {
  $input = (new GetDefaultAudioConfig($config))();
}
$audio_config = (new GetAudioConfigByLabel($config))($input);
if (!$audio_config) {
  $audio_config = (new GetAudioConfigByAlias($config))($input);
}
echo json_encode($audio_config) . PHP_EOL;
exit(0);
