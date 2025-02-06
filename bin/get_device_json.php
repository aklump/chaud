#!/usr/bin/env php
<?php
// SPDX-License-Identifier: BSD-3-Clause

use AKlump\AudioSwitch\GetAudioConfigByAlias;
use AKlump\AudioSwitch\GetAudioConfigByLabel;
use AKlump\AudioSwitch\GetConfigPath;
use AKlump\AudioSwitch\GetDefaultAudioConfig;

require_once __DIR__ . '/../vendor/autoload.php';

// Install the example configuration if necessary.
$config_path = (new GetConfigPath)();
if (!file_exists($config_path)) {
  copy(__DIR__ . '/../install/config.json', $config_path);
}

$input = $argv[1] ?? '';
$config = json_decode(file_get_contents($config_path), TRUE);
if (!$input) {
  $input = (new GetDefaultAudioConfig($config))();
}
$audio_config = (new GetAudioConfigByLabel($config))($input);
if (!$audio_config) {
  $audio_config = (new GetAudioConfigByAlias($config))($input);
}
echo json_encode($audio_config) . PHP_EOL;
exit(0);
