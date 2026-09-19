#!/usr/bin/env php
<?php
// SPDX-License-Identifier: BSD-3-Clause

use AKlump\ChangeAudio\GetAudioEngine;

require_once __DIR__ . '/../vendor/autoload.php';
# If the file doesn't exist, it will be installed during ::get.
$engine = (new GetAudioEngine())();
if (!$engine) {
  echo '❌ No supported audio engine is installed; cannot list devices.' . PHP_EOL;
  exit(1);
}
$devices = $engine->getAllDevices();
foreach ($devices as $device) {
  echo '🔹 ' . $device . PHP_EOL;
}
exit (0);
