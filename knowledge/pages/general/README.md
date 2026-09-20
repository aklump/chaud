<!--
id: readme
tags: ''
-->

# chaudio

> Switch your Mac's microphone and speakers together with one short command, like `chaudio s phone`.

![chaudio](../../images/chaud.jpg)

## Summary

On a Mac, moving from a headset call to speakerphone means two trips into Sound settings: one for the input, one for the output. **chaudio** (change audio; the project and package are still named `chaud`) lets you name those pairings once, give them short aliases, and switch with a single command. Each named option can set the input, the output, or both, adjust the output level, and run your own shell commands, for example pausing music when you pick up a call. It is a small PHP command-line app over existing macOS audio switchers, so the operating system still does the actual switching, and your configuration and device lookups are cached so each switch runs quickly.

## Quick Start

Install into `~/opt/chaud`, add the default audio engine, and link the command onto your `$PATH` (this assumes `~/bin` is on it):

```shell
mkdir -p ~/opt && cd ~/opt
{{ composer.create_project|raw }}
cd chaud && npm install
ln -s ~/opt/chaud/chaudio ~/bin/chaudio
```

Print the configuration file's path. The first run creates it from the defaults:

```
$ chaudio config
✏️ /Users/you/.chaudio.json
```

List the devices on your Mac with `chaudio devices` (this needs the default engine you just installed), put their names, or better their UIDs, into the two example options in that file, then switch:

```
$ chaudio s phone
Phone is active (🎤 External Microphone  🔈 External Headphones)
$ chaudio s sp
Speakerphone is active (🎤 MacBook Pro Microphone  🔈 MacBook Pro Speakers)
```

Run `chaudio -h` at any time to see the commands, and `chaudio <command> -h` for the details of one.

## Requirements

- macOS. Every supported audio engine is a macOS tool.
- PHP 8.1 or newer with the `json` extension, and Composer.
- One audio engine (see [Installation](#installation)). The default engine needs Node.js with npm or Yarn.

## Installation

chaudio is installed from its GitHub repository, <https://github.com/aklump/chaud>. In a terminal, change to where you want the app to live (the examples use `~/opt`), then install it with Composer:

```shell
{{ composer.create_project|raw }}
```

This creates a `chaud` folder (that is the package name). Link its `chaudio` script into a directory on your `$PATH`, such as `~/bin`:

```shell
cd ~/bin
ln -s ~/opt/chaud/chaudio .
```

### Audio engine

chaudio looks for an engine in the following order and uses the first one it finds. The order is fixed; no setting prefers a different engine when more than one is installed.

1. [macos-audio-devices](https://github.com/karaggeorge/macos-audio-devices), the default, and the only engine that can set output levels or list your devices. It is declared in `package.json`, so run `npm install` (or `yarn install`) inside the `chaud` folder.
2. [switchaudio-osx](https://github.com/deweller/switchaudio-osx), when its `SwitchAudioSource` command is on your `$PATH`.
3. [SwitchAudio](https://www.macscripter.net/t/switchaudio-a-command-line-tool-to-change-the-audio-input-and-output-device/75630/1), when it is installed as an executable at `~/bin/SwitchAudio`.

Only macos-audio-devices can list your devices. Under either of the other two, `chaudio devices` says that listing is unsupported and exits with status 1, so you will need the device names from System Settings.

### Alfred workflow

The repository also holds `Change Audio.alfredworkflow`. Double-click it to add the workflow to [Alfred](https://www.alfredapp.com), then type `chaudio` followed by a label or alias to switch without opening a terminal. The workflow runs `~/bin/chaudio s "$1" 2>&1`, so it expects the symlink above at exactly that path. Error messages, which chaudio writes to stderr, are merged into the output so that they appear in the notification too.

### Updating

Delete the `chaud` folder you installed earlier, then repeat the installation, including the audio engine. Your configuration lives in your home directory, so deleting the folder does not remove it.

## Configuration

Run `chaudio config` to print the configuration file's path, which is `~/.chaudio.json`. If the file does not exist, chaudio creates it from the defaults shown below. Open it and edit the `options` list, which needs at least two entries.

```
{{ example_config|raw }}
```

Each option takes these keys:

- `label` (required) is the name you type, matched case-insensitively. A label with spaces, such as `Desk Setup`, is typed quoted (`chaudio s "desk setup"`) or with underscores (`chaudio s desk_setup`).
- `aliases` are shorter names for the same option.
- `input` and `output` each identify a device with a `device` (its name, or its number) or with a `uid` (see below), never both, plus an optional `level` from 0 to 1. An option needs at least one of the two; leave one out to change only the other. Only the macos-audio-devices engine applies `level`, and only to the output; a `level` on `input` is accepted but ignored.
- `scripts` are shell commands run after the switch. The default config's `nowplaying-cli pause` needs [nowplaying-cli](https://github.com/kirtan-shah/nowplaying-cli), which you install separately; remove that line if you don't use it. If a script fails, chaudio reports it and exits with status 1.

### Identifying devices: prefer `uid`

Every device has a UID, an identifier assigned by macOS that survives a restart. Copy it from the `UID` column of `chaudio devices` and use it in place of `device`:

```json
{
  "label": "Desk",
  "output": {
    "uid": "BuiltInSpeakerDevice",
    "level": 0.5
  }
}
```

A name is the next best choice, but two devices can share a name (in which case the first is used), and macOS or you can rename a device. A device number is the least reliable, because macOS reassigns those when the computer restarts.

A `uid` works with macos-audio-devices and with `SwitchAudioSource` (which matches it as a substring, so paste the whole UID). The `~/bin/SwitchAudio` engine cannot be verified to understand UIDs, so it stops the switch with a message; use `device` with that engine.

If a device cannot be found, for example a Bluetooth headset that is disconnected, chaudio prints what it could not find on stderr, leaves your audio unchanged, and exits with status 1.

The file is validated against `json_schema/config.schema.json` before it is cached. A file that does not fit the schema stops the switch and prints what is wrong:

```
❌ Invalid configuration:
⚠️ "/options" -- Array should have at least 2 items, 1 found
```

### Cache

chaudio caches the validated configuration and the device lookups in `$TMPDIR/com.aklump.chaudio`. Run `chaudio cache:clear` after editing the file or connecting a new device, or add `--refresh` to a switch to clear the cache and switch in one step. The cache holds nothing you cannot rebuild, so deleting the folder is always safe.

## Usage

```
chaudio                       list the commands (same as -h and --help)
chaudio <command> -h          help for one command
chaudio -V                    print the name and version
chaudio switch <label>        switch to a configured option (alias: s)
chaudio s                     list your configured options and their aliases
chaudio s <label> --refresh   clear the cache, then switch
chaudio s <label> -v          switch, and print the engine, cache directory and commands run
chaudio devices               list every audio device, with UIDs (alias: d)
chaudio config                print the configuration path, creating the file if missing
chaudio cache:clear           clear the cache
```

`<label>` is a label or an alias from your configuration. The built-in `list`, `help`, and `completion` commands are available too, and command names can be abbreviated to any unique prefix (`chaudio sw phone`). Normal output goes to stdout and errors to stderr; the exit status is 0 on success and non-zero otherwise, so scripts can rely on it.

Listing your options shows each label with its aliases beneath it:

```
$ chaudio s
🔹 Phone
     p
🔹 Speakerphone
     sp
```

Listing your devices marks the ones your options use. The `UID` column is what you copy into a `uid` key; a name in your config marks every device that has that name:

```
$ chaudio devices
+--------+-----+----------------------------+------------------------------+-----------------+
| Type   | ID  | Name                       | UID                          | In your options |
+--------+-----+----------------------------+------------------------------+-----------------+
| output | 98  | External Headphones        | BuiltInHeadphoneOutputDevice | Phone           |
| output | 71  | MacBook Pro Speakers       | BuiltInSpeakerDevice         | Speakerphone    |
| input  | 62  | MacBook Pro Microphone     | BuiltInMicrophoneDevice      | Speakerphone    |
| output | 126 | LG UltraFine Display Audio | AppleUSBAudioEngine:...      | -               |
+--------+-----+----------------------------+------------------------------+-----------------+
```

If a name matches nothing, chaudio says so and suggests the nearest match:

```
$ chaudio s spekerphone
❌ Unknown audio configuration: spekerphone
🤔 Did you mean "Speakerphone"? (chaudio s)
```

When a Bluetooth device is disconnected, switching to an option that uses it fails and leaves your audio unchanged; reconnect it and run `chaudio s <name> --refresh` to rebuild the cache and switch in one step. Not every device responds to level control, so a configured `level` may have no effect on some hardware.

For a shorter command, add an alias to your shell profile, for example `alias chaud='chaudio s'` in `~/.zshrc`, then type `chaud phone`. Aliases do not apply to Alfred or scripts, which should call `chaudio` directly.

## Support

{{ funding|raw }}

## License

[BSD-3-Clause](../../../LICENSE)
