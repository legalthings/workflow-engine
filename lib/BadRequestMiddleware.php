<?php declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\ResponseInterface as Response;
use Jasny\ValidationException;

/**
 * Router middleware to give a 40x response on specific exceptions.
 */
class BadRequestMiddleware
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
            return $next($request, $response);
        } catch (AuthException $exception) {
            return $this->badRequest($response, $exception->getCode() === 401 ? 401 : 403, $exception->getMessage());
        } catch (EntityNotFoundException $exception) {
            return $this->badRequest($response, 404, $exception->getMessage());
        } catch (TypeCastException $exception) {
            return $this->badRequest($response, 400, $exception->getMessage());
        } catch (ValidationException $exception) {
            return $this->badRequest($response, 400, $exception->getErrors());
        }
    }

    /**
     * Return a 40x response.
     *
     * @param Response $response
     * @param int      $status
     * @param mixed    $content
     * @return Response
     */
    protected function badRequest(Response $response, int $status, $content)
    {
        $json = !is_string($content);

        $body = clone $response->getBody();
        $body->write($json ? json_encode($content) : $content);

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', $json ? 'application/json' : 'text/plain')
            ->withBody($body);
    }
}
