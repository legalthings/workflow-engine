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
            $view = $view->withDecorator('pretty.' . strtolower(get_class($data)));
        }

        $response = $view->output($this->getResponse(), $data);
        $this->setResponse($response);
    }

    /**
     * Check if the given date is the last modified date.
     *
     * @param int|string|DateTime|null  $input  Timestamp, date string or DateTime
     * @return bool
     */
    protected function isModifiedSince($input): bool
    {
        if (App::env('dev') || App::env('tests')) {
            // caching may hinder developing and tests
            return true;
        }

        if (is_null($input)) {
            return true; // resource may have been deleted so we can't cache it
        }

        $ifModifiedSince = $this->getRequest()->getHeaderLine('If-Modified-Since') ?: -1;

        if (is_int($input)) {
            $date = $input;
        } elseif (is_string($input)) {
            $date = (new DateTime($input))->getTimestamp();
        } elseif ($input instanceof DateTimeInterface) {
            $date = $input->getTimestamp();
        } else {
            $date = 0; // oldest timestamp: January 1st, 1970
        }

        $lastModified = gmdate('D, d M Y H:i:s', $date) . ' GMT';
        header('Cache-Control: must-revalidate');
        header('Last-Modified: ' . $lastModified);

        return strtotime($lastModified) != strtotime($ifModifiedSince);
    }
}
