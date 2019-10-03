<?php declare(strict_types=1);

/**
 * Base class for controllers.
 */
abstract class BaseController extends Jasny\Controller
{
    use Jasny\Controller\RouteAction;

    /**
     * @var LTO\Account|null
     */
    protected $node;

    /**
     * @var IdentityGateway
     */
    protected $identities;

    /**
     * @var JsonView|null
     */
    protected $jsonView;

    /**
     * Check if signer is authorized.
     *
     * @param int         $level    Minimum authorization level.
     * @param string      $message
     * @throws AuthException
     */
    protected function authz(int $level, string $message): void
    {
        if (!isset($this->identities)) {
            throw new BadMethodCallException("Identity gateway not configured for this controller");
        }

        /** @var LTO\Account|null $account */
        $account = $this->request->getAttribute('account');

        if ($account === null) {
            throw new AuthException('Request not signed', 401);
        }

        $originalSignKey = $this->request->getAttribute('signature_key_id') ?? $account->getPublicSignKey();

        if ($this->node !== null && $this->node->getPublicSignKey() === $originalSignKey) {
            return; // The node signed off on this. Should have done authz before doing so.
        }

        $filter = ['signkeys.default' => $account->getPublicSignKey()] +
            ($level === 1 ? ['authz(not)' => 0] : ['authz(min)' => $level]); // BC for identities without authz prop
        $signerIsAuth = $this->identities->exists($filter);

        if (!$signerIsAuth) {
            throw new AuthException($message, 403);
        }
    }

    /**
     * Output data as json
     *
     * @param mixed $result
     * @param string $format  Mime or content format
     */
    public function output($result, $format = 'json'): void
    {
        if ($format === 'json' && $this->jsonView !== null) {
            $this->viewJson($result);
            return;
        }

        parent::output($result, $format);
    }

    /**
     * Output json using json view.
     *
     * @param mixed $data
     */
    protected function viewJson($data): void
    {
        $view = $this->jsonView;
        $pretty = (bool)$this->getRequest()->getAttribute('pretty-json');

        if ($pretty && ($data instanceof Scenario || $data instanceof Process)) {
            $type = $data instanceof Scenario ? 'scenario' : 'process';
            $view = $view->withDecorator('pretty.' . $type);
        }

        $response = $view->output($this->getResponse(), $data);
        $this->setResponse($response);
    }
}
