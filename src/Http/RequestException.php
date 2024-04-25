<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Http;

use GuzzleHttp\Psr7\Message;

class RequestException extends HttpClientException
{
    /**
     * The response instance.
     *
     * @var Response
     */
    public Response $response;

    /**
     * Create a new exception instance.
     *
     * @param Response $response
     * @return void
     */
    public function __construct(Response $response)
    {
        parent::__construct($this->prepareMessage($response), $response->status());

        $this->response = $response;
    }

    /**
     * Prepare the exception message.
     *
     * @param Response $response
     * @return string
     */
    protected function prepareMessage(Response $response): string
    {
        $message = "HTTP request returned status code {$response->status()}";

        $summary = Message::bodySummary($response->toPsrResponse());

        return is_null($summary) ? $message : $message .= ":\n{$summary}\n";
    }
}
