<?php

namespace Netflex\Query\Exceptions;

use Facade\IgnitionContracts\ProvidesSolution;
use Facade\IgnitionContracts\Solution;
use Facade\IgnitionContracts\BaseSolution;
use Netflex\Query\Builder;

class QueryException extends QueryBuilderException implements ProvidesSolution
{
  /** @var string */
  protected $query;

  /** @var object */
  protected $error;

  public function __construct($query, $error)
  {
    $this->query = $query;
    $this->error = $error;

    foreach (Builder::REPLACEMENT_ENTITIES as $original => $replacement)
    {
      $this->query = str_replace($replacement, $original, $this->query);
    }

    $message = ucfirst(implode(' ', explode('_', $this->error->message)));
    $reason = ucfirst(implode(' ', explode('_', $this->error->reason)));

    parent::__construct("Query exception: " . $message . ' - ' . $reason);
  }

  public function getSolution(): Solution
  {
    $type = 'Invalid query';
    $reason = $this->query;

    if (count($this->error->stack)) {
      $type = implode(', ', array_unique(array_map(function ($stack) {
        return $stack->type;
      }, $this->error->stack)));

      $reason = implode(', ', array_unique(array_map(function ($stack) {
        return $stack->reason;
      }, $this->error->stack)));
    }

    $reason = str_replace($type . ': ', '', $reason);
    $type = ucfirst(implode(' ', explode('_', $type)));

    return BaseSolution::create($type)
      ->setSolutionDescription($reason)
      ->setDocumentationLinks([
        'Netflex SDK documentation' => 'https://netflex-sdk.github.io/#/docs/models?id=retrieving-entries-and-performing-queries',
      ]);
  }
}
