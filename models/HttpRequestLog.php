<?php

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class for logging single http request and it's response
 */
class HttpRequestLog extends MongoDocument
{
    /**
     * @var array 
     **/
    public $request;

    /**
     * @var array
     **/
    public $response;

    /**
     * Create instance
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response 
     */
    public function __construct(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->request = $request;
        $this->response = $response;

        $this->cast();
    }

    /**
     * {@inheritdoc}
     */
    public function cast()
    {
        if ($this->request instanceof ServerRequestInterface) {
            $this->request = [
                'uri' => (string)$this->request->getUri(),
                'method' => $this->request->getMethod(),
                'headers' => $this->request->getHeaders(),
                'body' => $this->formatBody($this->request)
            ];
        }

        if ($this->response instanceof ResponseInterface) {
            $this->response = [
                'status' => $this->response->getStatusCode(),
                'headers' => $this->response->getHeaders(),
                'body' => $this->formatBody($this->response)
            ];
        }
    }

    /**
     * Format body of request or response
     *
     * @param ServerRequestInterface|ResponseInterface $message
     * @return array|string
     */
    protected function formatBody($message)
    {        
        $body = (string)$message->getBody();
        $contentType = $message->getHeaderLine('Content-Type');

        $regexp = '#\b(application/json|application/x-www-form-urlencoded|multipart/form-data)\b#';
        preg_match($regexp, $contentType, $match);

        switch ($match[1] ?? null) {
            case 'application/json': 
                return json_decode($body, true);
            case 'multipart/form-data': 
            case 'application/x-www-form-urlencoded': 
                return $message instanceof ServerRequestInterface ? $message->getParsedBody() : $body;
        }

        return $body;
     }
}
