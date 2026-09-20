<!--
id: audio_ids
tags: ''
-->

# Audio Ids

Audio IDs (the numbers) get reassigned when the computer restarts, and names can change or be shared by more than one device. A device's UID is the identifier macOS keeps across restarts, so prefer it: run `chaudio devices`, copy the value from the `UID` column, and use it as the `uid` key in place of `device`.

```json
{
  "label": "Desk",
  "output": {
    "uid": "BuiltInSpeakerDevice"
  }
}
```

Use exactly one of `device` and `uid` for each input or output.
