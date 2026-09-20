<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Command;

use AKlump\ChangeAudio\ConfigManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigCommand extends Command {

  private ConfigManager $config;

  public function __construct(ConfigManager $config) {
    $this->config = $config;
    parent::__construct();
  }

  protected function configure(): void {
    $this
      ->setName('config')
      ->setDescription('Print the config file path, creating it if missing')
      ->setHelp('Prints the path to your configuration file. If the file does not exist yet, the default configuration is installed there first.');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $path = $this->config->path();
    if (!file_exists($path)) {
      // The default config is installed as a side effect of loading.
      $this->config->get();
    }
    $output->writeln('✏️ ' . $path);

    return Command::SUCCESS;
  }

}
