<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Command;

use AKlump\ChangeAudio\Cache\CacheManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheClearCommand extends Command {

  private CacheManager $cache;

  public function __construct(CacheManager $cache) {
    $this->cache = $cache;
    parent::__construct();
  }

  protected function configure(): void {
    $this
      ->setName('cache:clear')
      ->setAliases(['cc'])
      ->setDescription('Flush the cached config and device data')
      ->setHelp('Removes the cached configuration and device index files. They are rebuilt the next time they are needed, so run this after connecting a new device. Editing your configuration needs no clearing. The cache directory itself is kept.');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $directory = $this->cache->flush();
    $output->writeln('🧹 Cache cleared.');
    $output->writeln('🪲 ' . $directory, OutputInterface::VERBOSITY_VERBOSE);

    return Command::SUCCESS;
  }

}
