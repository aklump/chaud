# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

- Edits to `~/.chaudio.yml` now take effect on the next run. The cached copy of the config is stored with a hash of the file and is rebuilt when the file changes, so `cache:clear` and `--refresh` are only needed after connecting a new device. Before, a change such as adding `scripts` was ignored until the cache was cleared.
- The default config now uses your Mac's built-in devices, so it works on any Mac, and shows how to use emoji in labels, with commented examples for USB and Bluetooth devices. It only applies to new installs; an existing `~/.chaudio.yml` is not changed.
- The Alfred workflow keyword is now `chauds` (was `chaudio`), quicker to type for switching; it runs `chaudio switch`. Re-import `Change Audio.alfredworkflow`.
- **BREAKING:** The config file is now YAML, `~/.chaudio.yml`, instead of `~/.chaudio.json`. An existing `~/.chaudio.json` is converted automatically the first time you run chaudio and no `.yml` exists; you may then delete the JSON file.
- Running `chaudio s` in a terminal shows a cursor-driven select list; otherwise options are listed one per line with aliases in parentheses.
- **BREAKING:** The `device` key of `input` and `output` is renamed `name`, matching the columns of `chaudio devices` (`name`, `uid`). A config that still says `device` is read as `name`, but update it.
- `input` and `output` may now have both `name` and `uid`; the `uid` is used when present, otherwise the `name`.
- Add `cc` as an alias of `cache:clear`.
- Add -v flag for verbose output
- Add `scripts` to config to execute on enable

### Added

- `chaudio -h`, `--help` and `chaudio <command> -h` show the commands and their help; `-V` shows the version.
- `uid` as an alternative to `device` in `input` and `output` config. A device UID survives a restart, and is shown by `chaudio devices`. Use exactly one of `device` and `uid`.
- `chaudio devices` shows each device's UID and which of your options use it.
- `chaudio s <label> --refresh` clears the cache, then switches.

### Changed

- **BREAKING:** The command is renamed from `chaud` to `chaudio`, and the interface is now sub-commands (`chaudio switch <label>`, alias `chaudio s <label>`) instead of flags. Run `chaudio -h` for help.
  - `chaud <label>` becomes `chaudio s <label>`
  - `chaud -l` becomes `chaudio s` (no label lists your options)
  - `chaud -a` becomes `chaudio devices`
  - `chaud -c` becomes `chaudio config` and `chaudio cache:clear`
- **BREAKING:** The config file moved from `~/.chaud.json` to `~/.chaudio.json`, and the cache directory from `com.aklump.chaud` to `com.aklump.chaudio`.
- **BREAKING:** PHP 8.1 or newer is required (was 7.4). The Bash launcher is gone, so Bash is no longer required.
- Errors are written to stderr, and failures now exit with a non-zero status, including a failed `chaudio devices`.
- A device in your config that cannot be found now stops the switch with a message; before, the option was silently left out.
- The `ENABLE_CACHE` setting is removed; use `chaudio cache:clear` or `chaudio s <label> --refresh`.
- The Alfred workflow is renamed (keyword `chaudio`) and runs `chaudio s`, so error messages appear in its notification.

#### Migrating from `chaud`

1. Rename your config file: `mv ~/.chaud.json ~/.chaudio.json`. Its contents do not change. If you skip this step, `chaudio` installs the default config at `~/.chaudio.json` and your options will appear to be missing.
2. Replace the symlink: `rm ~/bin/chaud && ln -s ~/opt/chaud/chaudio ~/bin/chaudio` (adjust the paths if you installed elsewhere).
3. Re-import `Change Audio.alfredworkflow` in Alfred and delete the old workflow. It now runs `chaudio s`.
4. Optional: keep the short name with a shell alias, e.g. `alias chaud='chaudio s'` in `~/.zshrc`.
5. Optional cleanup: `rm -rf "${TMPDIR:-/tmp}/com.aklump.chaud"` removes the old cache. It is disposable and is rebuilt on first use.

## [0.0.5] - 2025-02-07

### Added

- `chaud -a` to show all available devices.
- Support for device numbers (in addition to names) in config.
- Unit tests.
- Config validation

### Changed

- `deviceId` changed to `device` in configuration schema.

## [0.0.4] - 2025-02-07

### Added

- `chaud -l` to list your options.
