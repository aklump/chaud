<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio\Process;

/**
 * Runs commands with bash, sharing this process's stdin, stdout and stderr.
 *
 * Engine commands and the user's config "scripts" are shell strings, and were
 * previously sourced into a bash function, so bash remains the interpreter.
 */
class ShellCommandRunner implements CommandRunner {

  public function run(string $shell_command): int {
    $process = @proc_open(['bash', '-c', $shell_command], [STDIN, STDOUT, STDERR], $pipes);
    if (!is_resource($process)) {
      return 1;
    }
    $status = proc_close($process);

    return $status < 0 ? 1 : $status;
  }

}
