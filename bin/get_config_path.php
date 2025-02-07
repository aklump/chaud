#!/usr/bin/env php
<?php
// SPDX-License-Identifier: BSD-3-Clause

use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\ConfigManager;

require_once __DIR__ . '/../vendor/autoload.php';
echo (new ConfigManager(new CacheManager()))->path() . PHP_EOL;
exit (0);
