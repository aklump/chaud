<?php
// SPDX-License-Identifier: BSD-3-Clause

namespace AKlump\ChangeAudio;

/**
 * Resolves an XDG base directory, such as ~/.config or ~/.cache.
 *
 * @see https://specifications.freedesktop.org/basedir-spec/latest/
 */
class GetXdgBaseDirectory {

  /**
   * @param string $env_name For example "XDG_CONFIG_HOME".
   * @param string $default_relative The default relative to $home, for
   *   example ".config".
   * @param string $home The user's home directory.
   *
   * @return string The value of $env_name when it is an absolute path, as the
   *   spec requires; otherwise the default under $home.
   */
  public function __invoke(string $env_name, string $default_relative, string $home): string {
    $value = getenv($env_name);
    if (is_string($value) && str_starts_with($value, '/')) {
      return rtrim($value, '/');
    }

    return rtrim($home, '/') . '/' . $default_relative;
  }

}
