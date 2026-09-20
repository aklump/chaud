<?php

/** @var string $command */
/** @var string $book_path */
/** @var \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher */

$dispatcher->addListener(\AKlump\Knowledge\Events\GetVariables::NAME, function (\AKlump\Knowledge\Events\GetVariables $event) use ($book_path) {
  $event->setVariable('example_config', rtrim(file_get_contents($book_path . '/../install/config.yml')));
});
