<?php declare(strict_types=1);

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\PromiseInterface as Promise;
use GuzzleHttp\Exception\RequestException;

/**
 * Guzzle middleware to log outgoing HTTP requests.
 */
class HttpRequestLogMiddleware
{
    /** @var HttpRequestLogger */
    protected $logger;

    /**
     * HttpRequestLogMiddleware constructor.
     *
     * @param HttpRequestLogger $logger
     */
    public function __construct(HttpRequestLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Invoke middleware.
     *
     * @param callable $handler
     * @return callable
     */
    public function __invoke($collection, callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler): Promise {
            /** @var Promise $promise */
            $promise = $handler($request, $options);

            return $promise->then(
                function (ResponseInterface $response) use ($request): ResponseInterface {
                    $this->logger->log($request, $response);
                    return $response;
                },
                function (RequestException $exception) use ($request): RequestException {
                    $this->logger->log($request, $exception->getResponse());
                    return $exception;
                }
            );
        };
    }
}
