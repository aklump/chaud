<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Console;

use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Makes a bare -h/--help print the command list.
 *
 * Symfony's default is to show the help for the `list` command itself, which
 * describes `list` rather than listing the available commands.
 */
class Application extends SymfonyApplication {

  public function doRun(InputInterface $input, OutputInterface $output): int {
    $is_bare_help = !$input->getFirstArgument()
      && $input->hasParameterOption(['--help', '-h'], TRUE)
      && !$input->hasParameterOption(['--version', '-V'], TRUE);
    if ($is_bare_help) {
      $input = new ArrayInput(['command' => 'list']);
    }

    return parent::doRun($input, $output);
  }

}
