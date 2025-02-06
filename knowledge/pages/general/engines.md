<!--
id: engines
tags: ''
-->

# Engines

* https://github.com/deweller/switchaudio-osx
* https://github.com/hladik-dan/switch-audio?ref=iosexample.com  
* https://retina.studio/ears/download/ $$$
* https://github.com/karaggeorge/macos-audio-devices [Volume control]

on run {input, parameters}

    do shell script "/usr/local/bin/SwitchAudioSource -s 'XXXXXXXX'"

    return input
end run

https://apple.stackexchange.com/questions/213011/any-way-to-change-sound-output-device-via-applescript-or-shell
