<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Command;

use AKlump\ChangeAudio\ConfigManager;
use AKlump\ChangeAudio\Device;
use AKlump\ChangeAudio\GetAudioEngine;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DevicesCommand extends Command {

  private ConfigManager $config;

  private GetAudioEngine $getEngine;

  public function __construct(ConfigManager $config, GetAudioEngine $get_engine) {
    $this->config = $config;
    $this->getEngine = $get_engine;
    parent::__construct();
  }

  protected function configure(): void {
    $this
      ->setName('devices')
      ->setAliases(['d'])
      ->setDescription('List audio devices and which of your options use them')
      ->setHelp('Lists every available audio device with its ID and UID, and shows which of your configured options reference each device (matched by ID or name). Only the macos-audio-devices engine can list devices.');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $error_output = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

    $engine = ($this->getEngine)();
    if (!$engine) {
      $error_output->writeln('❌ No supported audio engine is installed; cannot list devices.');

      return Command::FAILURE;
    }
    $devices = $engine->getAllDevices();
    if (!$devices) {
      $error_output->writeln('❌ Listing devices is unsupported for this engine.');

      return Command::FAILURE;
    }

    $options = $this->config->get()['options'] ?? [];
    $table = new Table($output);
    $table->setHeaders(['Type', 'ID', 'Name', 'UID', 'In your options']);
    foreach ($devices as $device) {
      $used_by = $this->getOptionLabelsUsingDevice($options, $device);
      $table->addRow([
        $device->getType(),
        $device->getId(),
        $device->getName(),
        $device->getUid() ?: '-',
        $used_by ? implode(', ', $used_by) : '-',
      ]);
    }
    $table->render();

    return Command::SUCCESS;
  }

  /**
   * @param array $options The config options.
   * @param \AKlump\ChangeAudio\Device $device
   *
   * @return string[] The labels of options referencing $device by ID or name.
   *   A name marks every device with that name, since names are not unique.
   */
  private function getOptionLabelsUsingDevice(array $options, Device $device): array {
    $labels = [];
    foreach ($options as $option) {
      foreach (['input', 'output'] as $direction) {
        $value = $option[$direction]['device'] ?? NULL;
        if ($value === NULL) {
          continue;
        }
        if ((string) $value === (string) $device->getId() || (string) $value === $device->getName()) {
          $labels[] = (string) ($option['label'] ?? '');
          break;
        }
      }
    }

    return array_values(array_unique($labels));
  }

}
