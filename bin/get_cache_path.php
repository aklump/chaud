#!/Applications/MAMP/bin/php/php7.4.33/bin/php
<?php

use AKlump\AudioSwitch\ConfigManager;
use AKlump\AudioSwitch\CacheManager;

require_once __DIR__ . '/../vendor/autoload.php';
echo (new CacheManager())->path() . PHP_EOL;
exit (0);
