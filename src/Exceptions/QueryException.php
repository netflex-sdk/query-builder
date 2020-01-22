<?php

namespace Netflex\Query\Exceptions;

class QueryException extends QueryBuilderException {
  public function __construct($query)
  {
    parent::__construct("Invalid query: {$query}");
  }
}
