<?php

/**
 * @file Set the version in the Symfony Console controller.
 */
$controller = "src/App.php";
$content = file_get_contents($controller);
$content = preg_replace("#VERSION = '(.+?)'#", "VERSION = '$argv[2]'", $content);
file_put_contents($controller, $content);
