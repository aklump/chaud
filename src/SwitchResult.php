<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio;

class SwitchResult {

  private bool $success;

  private string $message;

  private array $errors;

  private array $commands;

  /**
   * @param bool $success
   * @param string $message The line for stdout when the switch worked.
   * @param string[] $errors Lines for stderr.
   * @param string[] $commands Every shell command that was run, in order.
   */
  public function __construct(bool $success, string $message = '', array $errors = [], array $commands = []) {
    $this->success = $success;
    $this->message = $message;
    $this->errors = $errors;
    $this->commands = $commands;
  }

  public function isSuccess(): bool {
    return $this->success;
  }

  public function getMessage(): string {
    return $this->message;
  }

  /**
   * @return string[]
   */
  public function getErrors(): array {
    return $this->errors;
  }

  /**
   * @return string[]
   */
  public function getCommands(): array {
    return $this->commands;
  }

  public function getExitCode(): int {
    return $this->success ? 0 : 1;
  }

}
