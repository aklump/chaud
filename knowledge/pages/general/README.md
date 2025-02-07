<!--
id: readme
tags: ''
-->

# chaudio

A configurable, command-line tool to easily switch between different audio configurations. This is different from other solutions because you create names (and aliases) that represent input/output configurations.

## Requirements

* BASH
* PHP
* Yarn or NPM



## Install
* `{{ composer.create_project|raw }}`
* Assuming `~/bin` is in your `$PATH` variable, create a symlink to wherever you've installed this app.
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
{{ example_config|raw }}
```

## Usage

This shows using the label and alias argument.

```
$ chaudio phone
Phone is active (🎤 External Microphone 🔈 External Headphones)
$ chaudio p
Phone is active (🎤 External Microphone 🔈 External Headphones)
```

## Troubleshooting

* Sometimes you might need to *refresh the device* list by running `chaud -c`, which is the command for showing the configuration file. This command reloads system audio information as well.

##
/Applications/MAMP/bin/php/php7.4.33/bin/php
export PATH="~/bin/php:$PATH";~/bin/chaud "{query}"
