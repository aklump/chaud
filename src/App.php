<?php

namespace AKlump\ChangeAudio;

class App {

  /**
   * The executable name; the config basename and cache directory follow it.
   */
  const BIN = 'chaudio';

  const NAME = 'chaudio';

  /**
   * Keep in step with .web_package/config.yml when releasing.
   */
  const VERSION = '0.0.16';

  /**
   * The leaf directory name inside the system temp directory.
   */
  const CACHE_DIRNAME = 'com.aklump.' . self::BIN;
}
