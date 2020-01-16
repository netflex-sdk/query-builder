<?php

namespace Netflex;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;


class API
{
  /** @var MockHandler */
  private static $handler;

  /** @var Client */
  protected $client;

  private function __construct()
  {
    static::$handler = static::$handler ?? new MockHandler([]);
    $handlerStack = HandlerStack::create(static::$handler);
    $this->client = new Client(['handler' => $handlerStack]);
  }

  /**
   * @param int $code
   * @param string $body
   * @param array $headers
   * @return void
   */
  public static function mockResponse($body = null, $code = 200, $headers = ['Content-Type' => 'application/json'])
  {
    static::$handler = static::$handler ?? new MockHandler([]);
    static::$handler->append(new Response($code, $headers, ($body && is_array($body)) ? json_encode($body) : $body));
  }

  /**
   * @return void
   */
  public static function reset()
  {
    static::$handler->reset();
  }

  /**
   * @param Response $response
   * @return mixed
   */
  private function parseResponse(Response $response, $assoc = false)
  {
    $body = $response->getBody();

    $contentType = strtolower($response->getHeaderLine('Content-Type'));

    if (strpos($contentType, 'json') !== false) {
      $jsonBody = json_decode($body, $assoc);

      if (json_last_error() === JSON_ERROR_NONE) {
        return $jsonBody;
      }
    }

    if (strpos($contentType, 'text') !== false) {
      return $body->getContents();
    }

    return null;
  }

  /**
   * @param string $url
   * @param boolean $assoc = false
   * @return mixed
   * @throws Exception
   */
  public function get($url, $assoc = false)
  {
    return $this->parseResponse(
      $this->client->get($url),
      $assoc
    );
  }

  /**
   * @throws Exception
   * @return static
   */
  public static function getClient()
  {
    return new static;
  }
}
