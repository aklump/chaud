<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Console;

use AKlump\ChangeAudio\App;
use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\Command\ConfigCommand;
use AKlump\ChangeAudio\ConfigManager;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command;

class ApplicationFactory {

  public static function create(): Application {
    $application = new Application(App::NAME, App::VERSION);
    $config_manager = new ConfigManager(new CacheManager());

    static::registerCommand($application, new ConfigCommand($config_manager));

    return $application;
  }

  /**
   * The one seam for registering commands across symfony/console versions.
   *
   * 6.4 has only add(); 7.4 deprecates add() in favor of addCommand(); 8.0
   * removed add().
   */
  public static function registerCommand(SymfonyApplication $application, Command $command): void {
    if (method_exists($application, 'addCommand')) {
      $application->addCommand($command);
    }
    else {
      $application->add($command);
    }
  }

}
