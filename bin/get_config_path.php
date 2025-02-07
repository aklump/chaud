#!/usr/bin/env php
<?php
// SPDX-License-Identifier: BSD-3-Clause

use AKlump\AudioSwitch\Cache\CacheManager;
use AKlump\AudioSwitch\ConfigManager;

require_once __DIR__ . '/../vendor/autoload.php';
echo (new ConfigManager(new CacheManager()))->path() . PHP_EOL;
exit (0);
