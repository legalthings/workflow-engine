<?php declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Router middleware to give a 404 response on a EntityNotFoundException.
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
        } catch (EntityNotFoundException $exception) {
            $newResponse = $response
                ->withStatus(404)
                ->withBody(clone $response->getBody());

            $newResponse->getBody()->write($exception->getMessage());
        }

        return $newResponse;
    }
}
