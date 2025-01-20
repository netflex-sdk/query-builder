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

    $status = $body->status ?? -1;
    $message = $body->message ?? 'No message';
    $reason = $body->reason ?? 'No reason';

    parent::__construct("{$status} {$message}: {$reason}", $status, $previous);

    $this->body = $body;
    $this->status = $status;
    $this->originalMessage = $message;
    $this->reason = $reason;
    $this->phase = $body->phase ?? null;
    $this->stack = $body->stack ?? [];
  }
}
