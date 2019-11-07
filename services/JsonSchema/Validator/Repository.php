<?php declare(strict_types=1);

namespace JsonSchema\Validator;

use stdClass;
use RuntimeException;
use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

/**
 * Gateway for fetching json schemas
 */
class Repository
{
    /**
     * Cache for schema files
     * @var array
     **/
    protected $cache = [];

    /**
     * Loaders of json schemas from different sources
     * @var string
     **/
    protected $loaders;

    /**
     * Constructor
     *
     * @param array $loaders
     */
    public function __construct(array $loaders)
    {
        $this->loaders = $loaders;
    }

    /**
     * Get schema contents
     *
     * @param string $url
     * @return stdClass|null
     */
    public function get(string $url): ?stdClass
    {
        if (array_key_exists($url, $this->cache)) {
            return $this->cache[$url];
        }
        
        $schema = $this->fetch($url);
        if ($schema !== null) {
            $schema = $this->expandNestedSchemas($schema);
        }
        
        $this->cache[$url] = $schema;

        return $schema;
    }

    /**
     * Fetch schema
     *
     * @param string $url
     * @return stdClass|null
     */
    protected function fetch(string $url): ?stdClass
    {
        if (!isset($this->loaders['file'])) {
            throw new RuntimeException('Json schema file loader is not set');
        }

        $loader = $this->loaders['file'];
        $path = $loader->toLocalPath($url);
        $schema = isset($path) ? $loader->fetch($path) : null;

        return $schema;
    }

    /**
     * Resolve references to other schemas
     *
     * @param stdClass|array|null $data
     * @return stdClass|array|null
     */
    protected function expandNestedSchemas($data)
    {
        $isObject = is_object($data);
        $data = (array)$data;

        foreach ($data as $key => $item) {
            if ($key === '$ref' && is_string($item)) {
                return $this->get($item);
            }

            if (is_array($item) || is_object($item)) {
                $data[$key] = $this->expandNestedSchemas($item);
            }
        }

        if ($isObject) {
            $data = (object)$data;
        }

        return $data;
    }
}
