<?php declare(strict_types=1);

use Improved\IteratorPipeline\Pipeline;
use Psr\Http\Message\ResponseInterface as Response;
use function Jasny\object_get_properties;

/**
 * Service to encode and output JSON.
 * Output is done via a PSR-7 Response object.
 */
class JsonView
{
    /**
     * @var callable[]
     */
    protected $availableDecorators;

    /**
     * @var callable[]
     */
    protected $decorators = [];


    /**
     * Class constructor
     *
     * @param callable[] $availableDecorators
     */
    public function __construct(array $availableDecorators = [])
    {
        $this->availableDecorators = $availableDecorators;
    }

    /**
     * Enable a decorator.
     *
     * @param string $key  Decorator key
     * @return static
     */
    public function withDecorator(string $key)
    {
        if (!isset($this->availableDecorators[$key])) {
            throw new DomainException("Unknown decorator '$key' for JSON encoder");
        }

        if (isset($this->decorators[$key])) {
            return $this; // Already enabled
        }

        $clone = clone $this;
        $clone->decorators[$key] = $this->availableDecorators[$key];

        return $clone;
    }

    /**
     * Disaable a decorator.
     *
     * @param string $key  Decorator key
     * @return static
     */
    public function withoutDecorator(string $key)
    {
        if (!isset($this->availableDecorators[$key])) {
            throw new DomainException("Unknown decorator '$key' for JSON encoder");
        }

        if (!isset($this->decorators[$key])) {
            return $this; // Already disabled
        }

        $clone = clone $this;
        unset($clone->decorators[$key]);

        return $clone;
    }


    /**
     * Output as JSON
     *
     * @param Response $response
     * @param mixed    $data
     */
    public function output(Response $response, $data): void
    {
        $serialized = $this->serialize($data);

        $etag = $this->calculateEtag(json_encode($serialized), 'W/');
        $json = $this->encodeSerialized($data, $serialized);

        $response = $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('ETag', $etag);

        if (is_object($data) && method_exists($data, 'getLastModified')) {
            $response = $response->withHeader('Last-Modified', $data->getLastModified());
        }

        $response->getBody()->write($json);
    }

    /**
     * JSON encode the data, applying all decorators.
     *
     * @param mixed $data
     * @return string
     */
    public function encode($data): string
    {
        $serialized = $this->serialize($data);

        return $this->encode($data, $serialized);
    }

    /**
     * Serialize the data recursively.
     *
     * @param mixed $data
     * @return mixed
     */
    protected function serialize($data)
    {
        if ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        if (is_iterable($data)) {
            $data = Pipeline::with($data)->map([$this, __FUNCTION__])->toArray();
        } elseif (is_object($data)) {
            $data = (object)Pipeline::with(object_get_properties($data))->map([$this, __FUNCTION__])->toArray();
        }

        return $data;
    }

    /**
     * Encode the serialized data, applying all decorators.
     *
     * @param mixed $subject
     * @param mixed $serialized
     * @return string
     */
    public function encodeSerialized($subject, $serialized): string
    {
        $options = 0;

        foreach ($this->decorators as $decorator) {
            $serialized = $decorator($subject, $serialized);

            if (is_object($decorator) && method_exists($decorator, 'getJsonOptions')) {
                $options |= $decorator->getJsonOptions();
            }
        }

        return json_encode($serialized, $options);
    }

    /**
     * Calculate the HTTP ETag for the JSON.
     *
     * @param string $json
     * @param string $prefix  Prefix, mostly `W/` for weak.
     * @return string
     */
    protected function calculateEtag(string $json, string $prefix = ''): string
    {
        return $prefix . base58_encode(hash('sha256', $json, true));
    }
}
