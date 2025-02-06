# chaudio

A configurable, command-line tool to easily switch between different audio configurations. This is different from other solutions because you create names (and aliases) that represent input/output configurations.

## Install

Assuming `~/bin` is in your `$PATH` variable, create a symlink to wherever you've installed this app.

```shell
cd ~/bin
ln -s /Users/aklump/Code/Packages/mac/chaudio/app/chaudio .
```

This will allow you to call `chaudio <LABEL>` from anywhere

## Configure

* `chaudio -c` to print the configuration filepath.
* Open and modify the configuration.
* A single option may be defined as:
    * input & output
    * input only
    * output only

```
{
    "options": [
        {
            "label": "Phone",
            "aliases": [
                "p"
            ],
            "input": {
                "deviceId": "External Microphone"
            },
            "output": {
                "deviceId": "External Headphones"
            }
        },
        {
            "label": "Speakerphone",
            "aliases": [
                "sp"
            ],
            "input": {
                "deviceId": "USB audio CODEC"
            },
            "output": {
                "deviceId": "MacBook Pro Speakers"
            }
        }
    ]
}
```

## Usage

This shows using the label and alias argument.

```
$ chaudio phone
Phone is active (🎤 External Microphone 🔈 External Headphones)
$ chaudio p
Phone is active (🎤 External Microphone 🔈 External Headphones)
```

### Without an Argument

This will switch to the next option. When you have only two options this works as a toggle.

```shell
$ chaudio
Phone is active (🎤 External Microphone 🔈 External Headphones)
$ chaudio
Speakerphone is active (🎤 USB audio CODEC 🔈 MacBook Pro Speakers)
$
```
