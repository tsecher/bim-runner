<?php

namespace BimRunner\Tools\Traits;

trait StringTrait {

  /**
   * Replace shortcode with values.
   *
   * @param $string
   *   The string to replace.
   * @param array $values
   *   The array of key => value
   *
   * @return string
   */
  public function s($string, array $values) {
    return str_replace(
      array_keys($values),
      array_values($values),
      $string
    );
  }

}
