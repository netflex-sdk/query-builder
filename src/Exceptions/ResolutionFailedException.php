<?php

namespace Netflex\Query\Exceptions;

class ResolutionFailedException extends QueryBuilderException
{
  public function __construct()
  {
    parent::__construct('Unable to resolve');
  }
}
