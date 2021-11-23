<?php

namespace Netflex\Query\Traits;

use Illuminate\Support\Str;
use Netflex\Query\QueryableModel;

trait HasRelation
{
  /**
   * Gets the relation
   *
   * @return string
   */
  public function getRelation()
  {
    /** @var QueryableModel $this */
    return $this->relation ?? null;
  }

  /**
   * Gets the relation_id
   *
   * @return int|null
   */
  public function getRelationId()
  {
    /** @var QueryableModel $this */
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
    /** @var QueryableModel $this */

    $prefix = null;
    $relation = $this->getRelation();
    $relationId = $this->getRelationId();

    if (method_exists($this, 'getConnectionName') && $this->getConnectionName() !== 'default') {
      $prefix = $this->getConnectionName();
    }

    $cacheKey = array_filter([$prefix, $relation, $relationId, $identifier]);

    return implode('/', $cacheKey);
  }

  protected function getAllCacheIdentifier()
  {
    /** @var QueryableModel $this */

    $prefix = null;
    $relation = $this->getRelation();
    $relation = $relation ? Str::plural($relation) : $relation;
    $relationId = $this->getRelationId();

    if (method_exists($this, 'getConnectionName') && $this->getConnectionName() !== 'default') {
      $prefix = $this->getConnectionName();
    }

    $cacheKey = array_filter([$prefix, $relation, $relationId]);
    
    return implode('/', $cacheKey);
  }
}
