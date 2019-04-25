<?php declare(strict_types=1);

/**
 * Base class for controllers.
 */
abstract class BaseController extends Jasny\Controller
{
    use Jasny\Controller\RouteAction;

    /**
     * @var JsonView|null
     */
    protected $jsonView;

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
