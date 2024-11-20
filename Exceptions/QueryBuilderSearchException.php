<?php

namespace Netflex\Query\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

class QueryBuilderSearchException extends QueryBuilderException
{
  public object $body;
  public int $status;
  public string $originalMessage;
  public string $reason;
  public string|null $phase;
  public array $stack;

  public function __construct(ResponseInterface $response, Exception $previous)
  {
    $body = json_decode($response->getBody());

    $message = "{$body->status} {$body->message}: {$body->reason}";

    parent::__construct($message, $body->status, $previous);

    $this->body = $body;
    $this->status = $body->status;
    $this->originalMessage = $body->message;
    $this->reason = $body->reason;
    $this->phase = $body->phase;
    $this->stack = $body->stack;
  }
}
