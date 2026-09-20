<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Console;

use AKlump\ChangeAudio\App;
use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\Command\CacheClearCommand;
use AKlump\ChangeAudio\Command\ConfigCommand;
use AKlump\ChangeAudio\Command\DevicesCommand;
use AKlump\ChangeAudio\ConfigManager;
use AKlump\ChangeAudio\GetAudioEngine;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command;

class ApplicationFactory {

  public static function create(): Application {
    $application = new Application(App::NAME, App::VERSION);
    $cache_manager = new CacheManager();
    $config_manager = new ConfigManager($cache_manager);

    static::registerCommand($application, new ConfigCommand($config_manager));
    static::registerCommand($application, new DevicesCommand($config_manager, new GetAudioEngine()));
    static::registerCommand($application, new CacheClearCommand($cache_manager));

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
