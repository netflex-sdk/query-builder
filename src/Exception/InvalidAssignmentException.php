<?php

namespace Netflex\Query\Exception;

class InvalidAssignmentException extends QueryBuilderException {
  public function __construct ($method) {
    parent::__construct("Invalid assignment, left-hand side missing for $method query");
  }
}
