<?php

namespace Netflex\Query\Traits;

trait HasRelation
{
  /**
   * Gets the relation
   *
   * @return string
   */
  public function getRelation()
  {
    return $this->relation ?? null;
  }

  /**
   * Gets the relation_id
   *
   * @return int|null
   */
  public function getRelationId()
  {
    return $this->relationId ?? null;
  }
}
