<?php

namespace Netflex\Query\Exceptions;

class NotQueryableException extends QueryBuilderException
{
  public function __construct()
  {
    parent::__construct('Object not queryable');
  }
}
