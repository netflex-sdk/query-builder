<?php

namespace Netflex\Query\Traits;

use Illuminate\Support\Str;

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

  /**
   * Creates a cacheKey
   *
   * @param mixed $identifier
   * @param string $prefix = null
   * @return string
   */
  protected function getCacheIdentifier($identifier = null)
  {
    $relation = $this->getRelation();
    $relationId = $this->getRelationId();

    $cacheKey = array_filter([$relation, $relationId, $identifier]);

    return implode('/', $cacheKey);
  }

  protected function getAllCacheIdentifier()
  {
    $relation = $this->getRelation();
    $relation = $relation ? Str::plural($relation) : $relation;
    $relationId = $this->getRelationId();

    $cacheKey = array_filter([$relation, $relationId]);
    
    return implode('/', $cacheKey);
  }
}
