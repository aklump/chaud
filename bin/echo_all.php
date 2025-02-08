#!/usr/bin/env php
<?php
// SPDX-License-Identifier: BSD-3-Clause

use AKlump\ChangeAudio\GetAudioEngine;

require_once __DIR__ . '/../vendor/autoload.php';
# If the file doesn't exist, it will be installed during ::get.
$devices = (new GetAudioEngine())()->getAllDevices();
foreach ($devices as $device) {
  echo '🔹 ' . $device . PHP_EOL;
}
exit (0);
