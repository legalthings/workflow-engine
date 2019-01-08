<?php

/**
 * Base class for controllers.
 */
abstract class BaseController extends Jasny\Controller
{
    use Jasny\Controller\RouteAction;

    /**
     * Show a view.
     *
     * @param string $name     Filename of Twig template
     * @param array  $context  Data
     */
    protected function view($name=null, $context=[])
    {
        View::getEnvironment(); // Init Twig view
        return parent::view($name, $context);
    }

    /**
     * Output data as json
     *
     * @param mixed $result
     * @param string $format  Mime or content format
     */
    protected function output($result, $format = 'json')
    {
        return parent::output($result, $format);
    }

    /**
     * Redirect to another page.
     *
     * @param string $url
     * @param int    $httpCode  301 (Moved Permanently), 303 (See Other) or 307 (Temporary Redirect)
     */
    protected function redirect($url, $httpCode = 303)
    {
        if (defined('BASE_REWRITE') && $url !== '/' && str_starts_with($url, '/')) {
            $url = BASE_REWRITE . $url;
        }

        parent::redirect($url, $httpCode);
    }
    
    /**
     * Get the use session
     *
     * @return array|null
     */
    protected function getSession(): ?array
    {
        return App::getContainer()->get(SessionManager::class)->getSession();
    }    
    
    /**
     * Check if the given date is the last modified date
     *
     * @param int|string|DateTime|object|null  $input  Timestamp, date string, DateTime or object
     */
    protected function isModifiedSince($input)
    {
        if (App::env('dev') || App::env('tests')) {
            // caching may hinder developing and tests
            return true;
        }

        if (is_null($input)) {
            return true; // resource may have been deleted so we can't cache it
        }
        
        $ifModifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : -1;

        if (is_int($input)) {
            $date = $input;
        } elseif (is_string($input)) {
            $date = (new DateTime($input))->getTimestamp();
        } elseif ($input instanceof DateTime) {
            $date = $input->getTimestamp();
        } elseif (is_object($input)) {
            $date = isset($input->last_modified) ? $input->last_modified :
                (isset($input->last_updated) ? $input->last_updated : null);
            $date = is_object($date) && isset($date->date) ? $date->date : $date;
            return $this->isModifiedSince($date);
        } else {
            $date = 0; // oldest timestamp: January 1st, 1970
        }

        $lastModified = gmdate('D, d M Y H:i:s', $date) . ' GMT';
        header('Cache-Control: must-revalidate');
        header('Last-Modified: ' . $lastModified);
        return strtotime($lastModified) != strtotime($ifModifiedSince);
    }
    
    /**
     * Set the Not Modified (304) header
     */
    protected function notModified()
    {
        header("HTTP/1.1 304 Not Modified");
    }

    /**
     * Get client ip
     *
     * @return string
     */
    protected function getClientIp()
    {
        $proxy = getenv('PROXY') ?: '10.0.0.0/8';
        return Jasny\MVC\Request::getClientIp($proxy);
    }

    /**
     * Check if the client IP is considered trusted
     */
    protected function isTrustedIp()
    {
        if (!empty($_SERVER['HTTP_X_SESSION'])) {
            return false;
        }

        $ip = $this->getClientIp();

        if (!isset(App::config()->trusted_ips)) return false;

        foreach ((array)App::config()->trusted_ips as $cidr) {
            if ($cidr === 'header') { // Special case for tests
                if (!empty($_SERVER['HTTP_X_TRUSTED_IP'])) return true;
                continue;
            }

            if (strpos($cidr, '/') === false) $cidr .= '/32';
            if (ip_in_cidr($ip, $cidr)) return true;
        }

        return false;
    }
}
