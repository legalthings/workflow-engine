<?php declare(strict_types=1);

/**
 * Base class for controllers.
 */
abstract class BaseController extends Jasny\Controller
{
    use Jasny\Controller\RouteAction;

    /**
     * Set the services that the controller depends on.
     *
     * @param array $services
     */
    protected function setServices(BaseController $object, string $method, array $services): void
    {
        $names = get_method_args_names(get_class($object), $method);

        for ($i = 0; $i < count($services); $i++) { 
            $name = $names[$i];
            $this->$name = $services[$i];
        }
    }

    /**
     * Output data as json
     *
     * @param mixed $result
     * @param string $format  Mime or content format
     */
    public function output($result, $format = 'json')
    {
        if ($format === 'json') {
            if ($this instanceof ScenarioController) {
                return $this->outputPrettyJson($result, 'scenario');
            }            
        }

        return parent::output($result, $format);
    }

    /**
     * Outout prettyfied json
     *
     * @param mixed $data
     * @return 
     */
    protected function outputPrettyJson($data, string $decorator)
    {
        $allDecorators = [
            'scenario' => new JsonView\PrettyScenarioDecorator()
        ];

        $view = (new JsonView($allDecorators))->withDecorator($decorator);

        $view->output($this->getResponse(), $data);
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
