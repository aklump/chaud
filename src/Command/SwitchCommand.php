<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Command;

use AKlump\ChangeAudio\App;
use AKlump\ChangeAudio\Cache\CacheManager;
use AKlump\ChangeAudio\Config\OptionResolver;
use AKlump\ChangeAudio\ConfigManager;
use AKlump\ChangeAudio\FuzzyMatch;
use AKlump\ChangeAudio\GetAudioEngine;
use AKlump\ChangeAudio\Process\CommandRunner;
use AKlump\ChangeAudio\SwitchAudio;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\select;

class SwitchCommand extends Command {

  private ConfigManager $config;

  private GetAudioEngine $getEngine;

  private CommandRunner $runner;

  private CacheManager $cache;

  public function __construct(ConfigManager $config, GetAudioEngine $get_engine, CommandRunner $runner, CacheManager $cache) {
    $this->config = $config;
    $this->getEngine = $get_engine;
    $this->runner = $runner;
    $this->cache = $cache;
    parent::__construct();
  }

  protected function configure(): void {
    $this
      ->setName('switch')
      ->setAliases(['s'])
      ->setDescription('Switch audio to a configured option')
      ->addArgument('label', InputArgument::OPTIONAL, 'The label or alias of an option in your configuration; omit to list your options')
      ->addOption('refresh', NULL, InputOption::VALUE_NONE, 'Flush the cache first, so the configuration and devices are read again')
      ->setHelp('Switches the audio input and output to the configuration option with the given label or alias (letter case is ignored), then runs that option\'s scripts. With no label, lets you choose from your configured options with the cursor keys (or lists them, with aliases in parentheses, when not run in a terminal). Use --refresh after connecting a new device. Add -v to see the engine, cache directory and each command that is run.');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $error_output = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

    if ($input->getOption('refresh')) {
      $directory = $this->cache->flush();
      $output->writeln('🪲 Flushed ' . $directory, OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_VERBOSE);
    }

    $config = $this->config->get();
    foreach ($this->config->getNotices() as $notice) {
      $error_output->writeln($notice, OutputInterface::OUTPUT_RAW);
    }
    if ($this->config->getValidationErrors()) {
      $error_output->writeln('❌ Invalid configuration:');
      foreach ($this->config->getValidationErrors() as $error) {
        $error_output->writeln('⚠️ ' . $error, OutputInterface::OUTPUT_RAW);
      }

      return Command::FAILURE;
    }

    $label = $input->getArgument('label');
    if ($label === NULL) {
      if ($this->canPrompt($input)) {
        $label = $this->promptForLabel($config['options'] ?? []);
      }
      else {
        $this->listOptions($output, $config['options'] ?? []);

        return Command::SUCCESS;
      }
    }

    $resolver = new OptionResolver($config['options'] ?? []);
    $label = (string) $label;
    $option = $resolver->resolve($label);
    if (!$option) {
      $error_output->writeln('❌ Unknown audio configuration: ' . $label, OutputInterface::OUTPUT_RAW);
      // FuzzyMatch returns at most one suggestion.
      $suggestions = (new FuzzyMatch())($label, $resolver->getNames());
      if ($suggestions) {
        $error_output->writeln(sprintf('🤔 Did you mean "%s"? (%s s)', reset($suggestions), App::BIN), OutputInterface::OUTPUT_RAW);
      }

      return Command::FAILURE;
    }

    $engine = ($this->getEngine)();
    if (!$engine) {
      $error_output->writeln('❌ No supported audio engine is installed.');

      return Command::FAILURE;
    }

    $output->writeln('🪲 ' . (new \ReflectionClass($engine))->getShortName(), OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_VERBOSE);
    $output->writeln('🪲 ' . $this->cache->getPath(), OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_VERBOSE);

    $result = (new SwitchAudio($engine, $this->runner))($option);
    foreach ($result->getCommands() as $command) {
      $output->writeln('🪲 ' . $command, OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_VERBOSE);
    }
    foreach ($result->getErrors() as $error) {
      $error_output->writeln($error, OutputInterface::OUTPUT_RAW);
    }
    if ($result->isSuccess()) {
      $output->writeln($result->getMessage(), OutputInterface::OUTPUT_RAW);
    }

    return $result->getExitCode();
  }

  /**
   * Print one option per line, with its aliases in parentheses at the end.
   */
  private function listOptions(OutputInterface $output, array $options): void {
    foreach ($options as $option) {
      $output->writeln('🔹 ' . $this->describeOption($option), OutputInterface::OUTPUT_RAW);
    }
  }

  private function describeOption(array $option): string {
    $aliases = $option['aliases'] ?? [];

    return $option['label'] . ($aliases ? ' (' . implode(', ', $aliases) . ')' : '');
  }

  private function canPrompt(InputInterface $input): bool {
    return $input->isInteractive() && stream_isatty(STDIN) && stream_isatty(STDOUT);
  }

  /**
   * Let the user pick an option with the cursor keys.
   */
  private function promptForLabel(array $options): string {
    $choices = [];
    foreach ($options as $option) {
      $choices[$option['label']] = $this->describeOption($option);
    }

    return (string) select('Switch audio to', $choices, scroll: count($choices));
  }

}
