<?php
// SPDX-License-Identifier: BSD-3-Clause
// Run by Composer after create-project; see RecordPhp.

use AKlump\ChangeAudio\RecordPhp;

require_once __DIR__ . '/../vendor/autoload.php';
echo sprintf("Recorded the PHP for chaudio in %s\n", (new RecordPhp())(dirname(__DIR__), PHP_BINARY, getenv()));
