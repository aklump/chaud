#!/usr/bin/env php
<?php
// SPDX-License-Identifier: BSD-3-Clause

use AKlump\AudioSwitch\Cache\CreateDeviceIndex;


require_once __DIR__ . '/../vendor/autoload.php';

$device_index_path = $argv[1] ?? '';
if (empty($device_index_path)) {
  throw new RuntimeException('Missing argument for device index path.');
}
(new CreateDeviceIndex())($device_index_path);
