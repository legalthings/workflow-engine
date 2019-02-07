<?php declare(strict_types=1);

namespace Trigger;

use DataPatcher;
use Improved as i;
use Improved\IteratorPipeline\Pipeline;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Promise;
use function Jasny\str_starts_with;
use function Jasny\array_only;

/**
 * Execute action by posting to an HTTP webhook.
 */
class Http extends AbstractTrigger
{
    /**
     * @var HttpClient
     */
    protected $client;

    /**
     * The data patcher merges generic data with request specific data for concurrent requests.
     * @var DataPatcher
     */
    protected $patcher;


    /**
     * HTTP URL
     * @var string
     */
    protected $url = '';

    /**
     * HTTP method
     * @var string
     */
    protected $method = 'GET';
    
    /**
     * HTTP query parameters
     * @var array
     */
    protected $query = [];
    
    /**
     * HTTP request headers
     * @var array
     */
    protected $headers = [];
    
    /**
     * HTTP authentication
     * @var array|null
     */
    protected $auth;
    

    /**
     * Http trigger constructor.
     *
     * @param HttpClient  $client
     * @param DataPatcher $patcher
     * @param callable    $jmespath  "jmespath"
     */
    public function __construct(HttpClient $client, DataPatcher $patcher, callable $jmespath)
    {
        $this->client = $client;
        $this->patcher = $patcher;

        parent::__construct($jmespath);
    }

    /**
     * Create a configured clone.
     *
     * @param object|array       $settings
     * @param ContainerInterface $container
     * @return static
     */
    public function withConfig($settings, ContainerInterface $container)
    {
        return $this->cloneConfigure($settings, ['url', 'method', 'query', 'headers', 'auth', 'projection']);
    }


    /**
     * Invoke for an action.
     *
     * @param \Action $action
     * @return \Response|null
     */
    public function apply(\Action $action): ?\Response
    {
        return isset($action->requests)
            ? $this->sendConcurrentRequests($action, $this->project($action))
            : $this->sendSingleRequest($action, $this->project($action));
    }
    
    /**
     * Sends a single request.
     * Returns `null` for deferred (`202 Accepted`) responses.
     * 
     * @param \Action $action
     * @param object  $info    If no `projection` is configured then $info === $action, otherwise it's the projection.
     * @return \Response|null
     */
    protected function sendSingleRequest(\Action $action, $info): ?\Response
    {
        $method = strtoupper($info->method ?? $this->method);
        $url = strtoupper($info->url ?? $this->url);
        $options = $this->getRequestOptions(
            array_merge($this->headers, (array)($info->headers ?? [])),
            array_merge($this->query, (array)($info->query ?? [])),
            i\type_cast($info->auth ?? $this->auth, 'array'),
            $info->data ?? null
        );

        try {
            $httpResponse = $this->client->request($method, $url, $options);

            $response = $httpResponse->getStatusCode() !== 202
                ? $this->handleResponse('ok', $httpResponse, $action)
                : null;
        } catch (ClientException $exception) {
            $httpResponse = $exception->getResponse();
            $response = $this->handleResponse('error', $httpResponse, $action);
        } catch (RequestException $exception) {
            $response = $this->handleUnexpectedError($exception, $action);
        }

        return $response;
    }

    /**
     * Send concurrent requests.
     * 
     * @param \Action $action
     * @param object  $info    If no `projection` is configured then $info === $action, otherwise it's the projection.
     * @return \Response
     */
    protected function sendConcurrentRequests(\Action $action, $info): \Response
    {
        $promises = Pipeline::with((array)$action->requests)
            ->typeCast('object')
            ->map(function($request) use ($info) {
                $method = strtoupper($request->method ?? $info->method ?? $this->method);
                $url = $request->url ?? $info->url ?? $this->url;
                $options = $this->getConcurrentRequestOptions($info, $request);

                return $this->client->requestAsync($method, $url, $options);
            })
            ->map(function(Promise\PromiseInterface $promise) use ($action) {
                return $promise->then(
                    function(HttpResponse $httpResponse) use ($action) {
                        return $httpResponse->getStatusCode() !== 202
                            ? $this->handleResponse('ok', $httpResponse)
                            : null;
                    },
                    function(RequestException $exception) use ($action) {
                        return $exception instanceof ClientException
                            ? $this->handleResponse('error', $exception->getResponse())
                            : $this->handleUnexpectedError($exception, $action);
                    }
                );
            })
            ->toArray();

        $responses = Promise\settle($promises)->wait();

        // Combine responses
        $response = i\iterable_reduce($responses, function(\Response $combined, $result, $key) {
            if (!isset($result['value'])) {
                return $combined; // @CodeCoverageIgnore (edge case)
            }

            $response = i\type_check($result['value'], \Response::class);
            $combined->key = ($combined->key === 'ok' && $response->key === 'ok' ? 'ok' : 'error');

            if ($response->key === 'ok') {
                $combined->data[$key] = $response->data;
            } else {
                $combined->data[':errors'][$key] = $response->data;
            }

            return $combined;
        }, (new \Response)->setValues(['key' => 'ok', 'data' => []]));

        return $response;
    }

    /**
     * Handle an HTTP response.
     *
     * @param string       $key
     * @param HttpResponse $response
     * @param \Action      $action
     * @return \Response
     */
    protected function handleResponse(string $key, HttpResponse $response, ?\Action $action = null): \Response
    {
        $result = new \Response();

        $status = (string)$response->getStatusCode();
        $result->key = isset($action->responses[$status]) ? $status : $key;

        $result->data = static::responseMessage($response);

        return $result;
    }

    /**
     * Handle a HTTP 50x response or other exception.
     *
     * @param HttpResponse $response
     * @param \Action      $action
     * @return \Response
     */
    protected function handleUnexpectedError(\Exception $exception, \Action $action): \Response
    {
        $ref = isset($action->process->id)
            ? sprintf("action '%s' of process '%s'", $action->key, $action->process->id)
            : sprintf("action '%s'", $action->key);

        trigger_error("Unexpected error on HTTP request for $ref. " . $exception->getMessage(), E_USER_WARNING);

        return (new \Response)->setValues([
            'key' => 'error',
            'data' => 'Unexpected error',
        ]);
    }

    /**
     * Get request options for a concurrent request.
     *
     * @param object $generic
     * @param object $request
     * @return array
     */
    protected function getConcurrentRequestOptions($generic, $request): array
    {
        $data = $generic->data;

        if (isset($request->data)) {
            $data = $this->patcher->merge(is_object($data) ? clone $data : $data, $request->data);
        }

        return $this->getRequestOptions(
            array_merge($this->headers, (array)($generic->headers ?? []), (array)($request->headers ?? [])),
            array_merge($this->query, (array)($generic->query ?? []), (array)($request->query ?? [])),
            i\type_cast($request->auth ?? $generic->auth ?? $this->auth, 'array'),
            $data
        );
    }

    /**
     * Get the Guzzle request options
     * 
     * @param array      $headers
     * @param array      $query
     * @param array|null $auth
     * @param mixed      $data
     * @return array
     */
    protected function getRequestOptions(array $headers, array $query, ?array $auth = [], $data = null): array
    {
        $options = ['headers' => $headers, 'query' => $query];
        
        if ($auth !== null) {
            $options['auth'] = array_values(array_only($auth, ['username', 'password', 'type']));
        }
        
        if ($data !== null) {
            $this->addRequestOptionsForData($options, $data);
        }

        return $options;
    }
    
    /**
     * Add request options for data
     * 
     * @param array $options  INPUT/OUTPUT
     * @param mixed $data
     */
    protected function addRequestOptionsForData(&$options, $data): void
    {
        if (!isset($options['headers']['Content-Type'])) {
            $options['headers']['Content-Type'] = is_string($data) ? "text/plain" : "application/json";
        }

        switch ($options['headers']['Content-Type']) {
            case 'application/x-www-form-urlencoded':
                $options['form_params'] = $data;
                break;
            case 'multipart/form-data':
                // you need to do this to let guzzle set its own custom multipart boundary
                unset($options['headers']['Content-Type']);
                $options['multipart'] = (array)$data;
                break;
            case 'application/json':
                $options['json'] = $data;
                break;
            default:
                $options['body'] = $data;
        }
    }

    /**
     * Get contents from response.
     *
     * @param HttpResponse $response
     * @return mixed
     */
    protected static function responseMessage(HttpResponse $response)
    {
        $contents = (string)$response->getBody();
        $contentType = $response->getHeaderLine('Content-Type');

        if (str_starts_with($contentType, 'application/json')) {
            $contents = json_decode($contents, false);
        }

        return $contents;
    }
}
