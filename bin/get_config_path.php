#!/usr/bin/env php
<?php

use AKlump\AudioSwitch\ConfigManager;
use AKlump\AudioSwitch\CacheManager;

require_once __DIR__ . '/../vendor/autoload.php';
echo (new ConfigManager(new CacheManager()))->path() . PHP_EOL;
exit (0);
