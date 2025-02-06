#!/usr/bin/env php
<?php
// SPDX-License-Identifier: BSD-3-Clause

use AKlump\AudioSwitch\GetAudioEngine;
use AKlump\AudioSwitch\GetConfigPath;

require_once __DIR__ . '/../vendor/autoload.php';

$device_json = $argv[1] ?? '{}';
$device_config = json_decode($device_json, TRUE);
if (empty($device_config)) {
  echo "❌ Audio configuration not found. Try again with -c" . PHP_EOL;
  exit(1);
}

$input_device = $device_config['input']['deviceId'] ?? '';
$output_device = $device_config['output']['deviceId'] ?? '';
$command = '';
$return_code = 0;
if (empty($input_device) && empty($output_device)) {
  echo "❌ Missing input and/or output device." . PHP_EOL;
  exit(1);
}

$engine = (new GetAudioEngine())();
if (empty($engine)) {
  echo "No low-level audio switching utility found." . PHP_EOL;
  exit(1);
}
$engine->setInput($input_device);
$engine->setOutput($output_device);

$config_path = (new GetConfigPath())();
$config = json_decode(file_get_contents($config_path), TRUE);
$config['current']['label'] = $device_config['label'];
file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo sprintf('%s is active (🎤 %s 🔈 %s)', $device_config['label'], $input_device, $output_device) . PHP_EOL;
exit($return_code);
