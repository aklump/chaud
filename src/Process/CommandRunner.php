<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Process;

interface CommandRunner {

  /**
   * Run a shell command to completion.
   *
   * @param string $shell_command The command line, as a shell would read it.
   *
   * @return int The exit status; 0 means success.
   */
  public function run(string $shell_command): int;

}
