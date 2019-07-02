<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use h4cc\Multipart\Parser\MultipartParser;

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
     * @param RequestInterface $request
     * @param ResponseInterface $response 
     */
    public function __construct(RequestInterface $request, ResponseInterface $response = null)
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
        if ($this->request instanceof RequestInterface) {
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
     * @param RequestInterface|ResponseInterface $message
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
            case 'application/x-www-form-urlencoded': 
                parse_str($body, $values);

                return $values;
            case 'multipart/form-data': 
                return $this->parseMultipartBody($body);
        }

        return $body;
     }

    /**
     * Parse multipart body
     *
     * @param string $body
     * @return array
     */
    protected function parseMultipartBody(string $body): array
    {
        preg_match('|^--(\S*)|', $body, $match);

        $boundary = $match[1] ?? null;
        if (empty($boundary)) {
            return $body;
        }

        $parser = new MultipartParser();
        $parser->setBoundary($boundary);

        return $parser->parse($body);
     }
}
