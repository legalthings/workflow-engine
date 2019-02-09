<?php declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\ResponseInterface as Response;
use Jasny\ValidationException;

/**
 * Router middleware to give a 400 response on a EntityNotFoundException.
 */
class NotFoundMiddleware
{
    /**
     * Invoke middleware.
     *
     * @param ServerRequest  $request
     * @param Response       $response
     * @param callable       $next
     * @return Response
     */
    public function __invoke(ServerRequest $request, Response $response, callable $next): Response
    {
        try {
            $newResponse = $next($request, $response);
        } catch (ValidationException $exception) {
            $newResponse = $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json')
                ->withBody(clone $response->getBody());

            $newResponse->getBody()->write(json_encode($exception->getErrors()));
        }

        return $newResponse;
    }
}
