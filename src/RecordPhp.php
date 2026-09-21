<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio;

/**
 * Records which PHP the chaudio launcher should run, at install time.
 *
 * Writes .php-version. Under phpenv it holds the version name, as phpenv
 * itself does, so `phpenv local` can change it later. Otherwise it holds the
 * binary's absolute path, which the launcher tells apart by its leading
 * slash; a Homebrew binary is recorded by its stable opt path rather than the
 * versioned Cellar path that PHP_BINARY resolves to, which `brew upgrade`
 * removes.
 */
class RecordPhp {

  const FILENAME = '.php-version';

  /**
   * @param string $dir The chaudio install directory.
   * @param string $php_binary The PHP binary running the install.
   * @param array $env The environment of that PHP process.
   *
   * @return string The path of the file that was written.
   */
  public function __invoke(string $dir, string $php_binary, array $env): string {
    $phpenv_version = $env['PHPENV_VERSION'] ?? '';
    $under_phpenv = $phpenv_version !== '' && $phpenv_version !== 'system' && !empty($env['PHPENV_ROOT']);
    $path = $dir . '/' . self::FILENAME;
    file_put_contents($path, ($under_phpenv ? $phpenv_version : $this->getStableBinary($php_binary)) . PHP_EOL);

    return $path;
  }

  /**
   * @param string $php_binary
   *
   * @return string The Homebrew opt path for a Cellar binary, if it exists;
   *   otherwise $php_binary.
   */
  private function getStableBinary(string $php_binary): string {
    if (preg_match('#^(.+)/Cellar/([^/]+)/[^/]+/bin/php$#', $php_binary, $matches)) {
      $opt_binary = "$matches[1]/opt/$matches[2]/bin/php";
      if (is_executable($opt_binary)) {
        return $opt_binary;
      }
    }

    return $php_binary;
  }

}
