<?php

namespace Netflex\Query\Exceptions;

use Facade\IgnitionContracts\ProvidesSolution;
use Facade\IgnitionContracts\Solution;
use Facade\IgnitionContracts\BaseSolution;

class IndexNotFoundException extends QueryBuilderException implements ProvidesSolution
{
  /** @var string */
  protected $index;

  public function __construct($index)
  {
    $this->index = $index;
    parent::__construct('Search index not found');
  }

  public function getSolution(): Solution
  {
    return BaseSolution::create('The search index "' . $this->index . '" doesn\'t seem to exist.')
      ->setSolutionDescription('Please run the analyzer in Netflexapp to create the index manually, or in case of bad index, re-index.')
      ->setDocumentationLinks([
        'Netflex SDK documentation' => 'https://netflex-sdk.github.io/#/docs/faq?id=queryexception-invalid-query-when-using-models',
      ]);
  }
}
