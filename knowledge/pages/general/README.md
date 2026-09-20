<!--
id: readme
tags: ''
-->

# chaud

> Switch your Mac's microphone and speakers together with one short command, like `chaud phone`.

![chaud](../../images/chaud.jpg)

## Summary

On a Mac, moving from a headset call to speakerphone means two trips into Sound settings: one for the input, one for the output. **chaud** (change audio) lets you name those pairings once, give them short aliases, and switch with a single command. Each named option can set the input, the output, or both, adjust the output level, and run your own shell commands, for example pausing music when you pick up a call. It is a thin Bash and PHP layer over existing macOS audio switchers, so the operating system still does the actual switching, and the switch commands are generated once and cached so each switch runs quickly.

## Quick Start

Install into `~/opt/chaud`, add the default audio engine, and link the command onto your `$PATH` (this assumes `~/bin` is on it):

```shell
mkdir -p ~/opt && cd ~/opt
{{ composer.create_project|raw }}
cd chaud && npm install
ln -s ~/opt/chaud/chaud ~/bin/chaud
```

Print the configuration file's path. The first run creates it from the defaults:

```
$ chaud -c
✏️ /Users/you/.chaud.json
```

List the devices on your Mac with `chaud -a` (this needs the default engine you just installed), put their names into the two example options in that file, then switch:

```
$ chaud phone
Phone is active (🎤 External Microphone  🔈 External Headphones)
$ chaud sp
Speakerphone is active (🎤 MacBook Pro Microphone  🔈 MacBook Pro Speakers)
```

## Requirements

- macOS. Every supported audio engine is a macOS tool.
- Bash, PHP 7.4 or newer with the `json` extension, and Composer.
- One audio engine (see [Installation](#installation)). The default engine needs Node.js with npm or Yarn.

## Installation

chaud is installed from its GitHub repository, <https://github.com/aklump/chaud>. In a terminal, change to where you want the app to live (the examples use `~/opt`), then install it with Composer:

```shell
{{ composer.create_project|raw }}
```

This creates a `chaud` folder. Link its `chaud` script into a directory on your `$PATH`, such as `~/bin`:

```shell
cd ~/bin
ln -s ~/opt/chaud/chaud .
```

### Audio engine

chaud looks for an engine in the following order and uses the first one it finds. The order is fixed; no setting prefers a different engine when more than one is installed.

1. [macos-audio-devices](https://github.com/karaggeorge/macos-audio-devices), the default, and the only engine that can set output levels or list your devices. It is declared in `package.json`, so run `npm install` (or `yarn install`) inside the `chaud` folder.
2. [switchaudio-osx](https://github.com/deweller/switchaudio-osx), when its `SwitchAudioSource` command is on your `$PATH`.
3. [SwitchAudio](https://www.macscripter.net/t/switchaudio-a-command-line-tool-to-change-the-audio-input-and-output-device/75630/1), when it is installed as an executable at `~/bin/SwitchAudio`.

Only macos-audio-devices can list your devices. Under either of the other two, `chaud -a` prints nothing at all rather than saying it cannot list them, so you will need the device names from System Settings.

### Alfred workflow

The repository also holds `Change Audio.alfredworkflow`. Double-click it to add the workflow to [Alfred](https://www.alfredapp.com), then type `chaud` followed by a label or alias to switch without opening a terminal. The workflow runs `~/bin/chaud`, so it expects the symlink above at exactly that path.

### Updating

Delete the `chaud` folder you installed earlier, then repeat the installation, including the audio engine. Your configuration lives in your home directory, so deleting the folder does not remove it.

## Configuration

Run `chaud -c` to print the configuration file's path, which is `~/.chaud.json`. If the file does not exist, chaud creates it from the defaults shown below. Open it and edit the `options` list, which needs at least two entries.

```
{{ example_config|raw }}
```

Each option takes these keys:

- `label` (required) is the name you type, matched case-insensitively. A label with spaces, such as `Desk Setup`, is typed quoted (`chaud "desk setup"`) or with underscores (`chaud desk_setup`).
- `aliases` are shorter names for the same option.
- `input` and `output` each take a `device` (the name shown by `chaud -a`) and an optional `level` from 0 to 1. An option needs at least one of the two; leave one out to change only the other. Only the macos-audio-devices engine applies `level`, and only to the output; a `level` on `input` is accepted but ignored.
- `scripts` are shell commands run after the switch. The default config's `nowplaying-cli pause` needs [nowplaying-cli](https://github.com/kirtan-shah/nowplaying-cli), which you install separately; remove that line if you don't use it. If a script fails, chaud reports it and exits with status 1.

A `device` may also be the device's number rather than its name, but macOS reassigns those numbers when the computer restarts, so names are the safer choice.

The file is validated against `json_schema/config.schema.json` before it is cached. A file that does not fit the schema stops the switch and prints what is wrong:

```
❌ Invalid configuration:
⚠️ "/options" -- Array should have at least 2 items, 1 found
```

chaud caches the configuration and the generated switch commands in `$TMPDIR/com.aklump.chaud`. Run `chaud -c` after editing the file or connecting a new device to rebuild the cache. To skip the cache entirely, set `ENABLE_CACHE=false` at the top of the `chaud` script; every run then rereads the configuration and the device list, which is slower but always current. The setting lives in the installed script, so reinstalling or updating resets it to `true`.

## Usage

```
chaud <label-or-alias>    switch to a configured option
chaud -l                  list your configured options and their aliases
chaud -a                  list every audio device (macos-audio-devices engine only)
chaud -c                  print the configuration path and rebuild the cache
chaud -c <label-or-alias> rebuild the cache, then switch
chaud <label> -v          switch, and print the cache file and function used
```

Listing your options shows each label with its aliases beneath it:

```
$ chaud -l
🔹 Phone
     p
🔹 Speakerphone
     sp
```

If a name matches nothing, chaud says so and suggests the nearest match:

```
$ chaud sonyy
❌ Unknown audio configuration: sonyy
🤔 Did you mean "Sony bluetooth headphones"? (chaud -l)
```

When a Bluetooth device was disconnected at the time the cache was built, options that use it are left out; reconnect it and run `chaud -c <name>` to rebuild and switch in one step. Not every device responds to level control, so a configured `level` may have no effect on some hardware.

## Support

{{ funding|raw }}

## License

[BSD-3-Clause](../../../LICENSE)
