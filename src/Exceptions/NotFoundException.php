<?php

namespace Netflex\Query\Exceptions;

class NotFoundException extends QueryBuilderException
{
  public function __construct()
  {
    parent::__construct('Not found');
  }
}
