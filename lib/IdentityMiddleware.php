<?php declare(strict_types=1);

use Improved as i;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * If the request was signed by our node, accept the `X-Identity` header. In that case, this will determine the actor of
 * the process, rather than the HTTP signature.
 */
class IdentityMiddleware
{
    /**
     * @var LTO\Account
     */
    protected $node;

    /**
     * @var bool
     */
    protected $noAuth;


    /**
     * IdentityMiddleware constructor.
     *
     * @param LTO\Account $node    The LTO account of our node.
     * @param bool        $noAuth  Trust an identity if request isn't signed.
     */
    public function __construct(LTO\Account $node, bool $noAuth)
    {
        $this->node = $node;
        $this->noAuth = $noAuth;
    }

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
        /** @var Account|null $account */
        $account = i\type_check($request->getAttribute('account'), [LTO\Account::class, 'null']);

        if ($this->isOurAccount($account) && $request->hasHeader('X-Identity')) {
            $identity = $request->getHeaderLine('X-Identity');
            $request = $request->withAttribute('identity', $identity);
        }

        return $next($request, $response);
    }

    /**
     * Check is given account is the account of the node.
     *
     * @param Account|null $account
     * @return bool
     */
    protected function isOurAccount(?LTO\Account $account): bool
    {
        return $this->noAuth || ($account !== null && $account->getAddress() === $this->node->getAddress());
    }
}
