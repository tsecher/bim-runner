<?php

namespace BimRunner\Tools\Traits;

trait StringTrait {

    protected $str_content_id = '_@_';

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
  public function s($string, array $values, array $idWrappers=[]) {

      $dataToReplace = $values;

      if (!empty($idWrappers)) {
          foreach ($idWrappers as $idWrapper) {
              foreach ($values as $key => $value) {
                  $dataToReplace[str_replace($this->str_content_id, $key, $idWrapper)] = $value;
              }
          }
          $dataToReplace = array_reverse($dataToReplace);
      }


    return str_replace(
      array_keys($dataToReplace),
      array_values($dataToReplace),
      $string
    );
  }

}
