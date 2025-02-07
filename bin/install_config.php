#!/usr/bin/env php
<?php
// SPDX-License-Identifier: BSD-3-Clause

use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\ConfigManager;

require_once __DIR__ . '/../vendor/autoload.php';
# If the file doesn't exist, it will be installed during ::get.
(new ConfigManager(new CacheManager()))->get();
exit (0);
