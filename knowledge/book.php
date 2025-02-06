<?php

/** @var string $command */
/** @var string $book_path */
/** @var \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher */

$dispatcher->addListener(\AKlump\Knowledge\Events\GetVariables::NAME, function (\AKlump\Knowledge\Events\GetVariables $event) use ($book_path) {
  $config = json_decode(file_get_contents($book_path . '/../install/config.json'), TRUE);
  $event->setVariable('example_config', json_encode($config, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});
