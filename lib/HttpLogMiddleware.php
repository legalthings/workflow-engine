<?php declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface as Response;
use GuzzleHttp\Promise\PromiseInterface as Promise;

/**
 * Determine if json prettyfication should be used (it is set in request headers)
 */
class HttpLogMiddleware
{
    /**
     * Invoke middleware.
     *
     * @param callable $handler
     * @return callable
     */
    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler): Promise {
            $promise = $handler($request, $options);

            return $promise->then(
                function (ResponseInterface $response) use ($request): ResponseInterface {
                    $this->log($request, $response);

                    return $response;
                }
            );
        };
    }

    /**
     * Log request and response
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response 
     */
    protected function log(RequestInterface $request, ResponseInterface $response)
    {
        $logItem = new HttpRequestLog($request, $response);
        $logItem->save();
    }
}
