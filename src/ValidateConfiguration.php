<?php

namespace AKlump\ChangeAudio;

use AKlump\JsonSchema\JsonDecodeLossless;
use AKlump\JsonSchema\ValidateWithSchema;

class ValidateConfiguration {

  /**
   * @param array $config
   *
   * @return array An array of errors if invalid
   * @throws \InvalidArgumentException If the configuration is invalid
   */
  public function __invoke(array $config): array {
    $path_to_schema = __DIR__ . '/../json_schema/config.schema.json';
    $schema_json = file_get_contents($path_to_schema);

    // Create the validator for this schema.
    $validate = new ValidateWithSchema($schema_json, dirname($path_to_schema));

    // Validate some data against the schema.
    $config = (new JsonDecodeLossless())(json_encode($config));

    return $validate($config);
  }
}
