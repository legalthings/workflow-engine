<?php

declare(strict_types=1);

use Jasny\DB\Mongo\DB;
use Jasny\DB\Mongo\Collection;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Riverline\MultiPartParser\StreamedPart;

/**
 * Log http requests to the database.
 */
class HttpRequestLogger
{
    /** @var Collection */
    protected $collection;

    /**
     * HttpLogGateway constructor.
     *
     * @param DB $db
     */
    public function __construct(DB $db)
    {
        $this->collection = $db->selectCollection('http_request_logs');
    }

    /**
     * Log request and response
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     */
    public function log(RequestInterface $request, ResponseInterface $response)
    {
        $data = [
            'request' => [
                'uri' => (string)$request->getUri(),
                'method' => $request->getMethod(),
                'headers' => $request->getHeaders(),
                'body' => $this->formatBody($request)
            ],
            'response' => [
                'status' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body' => $this->formatBody($response)
            ]
        ];

        $this->collection->save($data, ['w' => 0]);
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
                $body = $this->normalizeMultipartBody($body);
                return $this->parseMultipartBody($body);
        }

        return $body;
    }

    /**
     * Prepend headers to body
     *
     * @param string $body
     * @return string
     */
    protected function normalizeMultipartBody(string $body): string
    {
        preg_match('|^--(\S+)\r?\n|', $body, $match);

        $boundary = $match[1] ?? null;
        if (!empty($boundary)) {
            //At least this header is needed for parser to work
            $body = "Content-Type: multipart/form-data; boundary=$boundary\n\n$body";
        }

        return $body;
    }

    /**
     * Parse multipart body
     *
     * @param string $body
     * @return array|string
     */
    protected function parseMultipartBody(string $body)
    {
        $stream = fopen('php://temp', 'rw');
        fwrite($stream, $body);
        rewind($stream);

        $document = new StreamedPart($stream);
        if (!$document->isMultiPart()) {
            return $body;
        }

        $result = [];
        $parts = $document->getParts();

        foreach ($parts as $part) {
            $result[$part->getName()] = $part->getBody();
        }

        return $result;
    }
}
