<?php


namespace BimRunner\Actions\Manager\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Action
{
  /**
   * @Required
   *
   * @var string
   */
  public $name;

  /**
   * @Required
   *
   * @var int
   */
  public $weight;

  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * @return int
   */
  public function getWeight()
  {
    return $this->weight;
  }
}
