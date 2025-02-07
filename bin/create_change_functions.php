#!/usr/bin/env php
<?php
// SPDX-License-Identifier: BSD-3-Clause

use AKlump\AudioSwitch\Cache\CacheManager;
use AKlump\AudioSwitch\Cache\CreateChangeFunctions;
use AKlump\AudioSwitch\GetAudioEngine;

require_once __DIR__ . '/../vendor/autoload.php';

$change_scripts_path = $argv[1] ?? '';
if (empty($change_scripts_path)) {
  $change_scripts_path = (new CacheManager())->getPath() . '/change_audio.sh';
}
$engine = (new GetAudioEngine())();
(new CreateChangeFunctions($engine))($change_scripts_path);
