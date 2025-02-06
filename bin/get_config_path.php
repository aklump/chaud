#!/usr/bin/env php
<?php

use AKlump\AudioSwitch\GetConfigPath;

require_once __DIR__ . '/../vendor/autoload.php';
echo (new GetConfigPath())() . PHP_EOL;
exit (0);



