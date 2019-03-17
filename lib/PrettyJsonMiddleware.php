<?php declare(strict_types=1);

use Improved as i;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Determine if json prettyfication should be used (it is set in request headers)
 */
class PrettyJsonMiddleware
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
        $accept = $request->getHeaderLine('Accept');
        preg_match('|;view=([^, ]+)|i', $accept, $view);

        $usePretty = !isset($view[1]) || $view[1] === 'pretty';
        $request = $request->withAttribute('pretty-json', $usePretty);

        return $next($request, $response);
    }
}
