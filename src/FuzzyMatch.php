<?php

namespace AKlump\ChangeAudio;

class FuzzyMatch {


  const THRESHOLD = 1.4;

  public function __invoke(string $input, array $options) {
    $input_scores = array_count_values(str_split($input));
    $matches = [];

    foreach ($options as $option) {
      $options_scores = array_count_values(str_split($option));
      $intersection = array_intersect_key($input_scores, $options_scores);

      // Calculate a similarity score based on common character frequencies
      $score = 0;
      foreach ($intersection as $char => $count) {
        $score += min($input_scores[$char], $options_scores[$char]);
      }

      if ($score > strlen($input) / self::THRESHOLD) { // Adjust the threshold as needed
        $matches[$option] = $score;
      }
    }

    // Sort matches by similarity score in descending order
    arsort($matches);

    return array_slice(array_keys($matches), 0, 1);
  }
}
