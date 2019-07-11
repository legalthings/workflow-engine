<?php declare(strict_types=1);

namespace JsonSchema\Validator;

use stdClass;
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
     * Get schema contents
     *
     * @param string $url
     * @return stdClass|null
     */
    public function get(string $url): ?stdClass
    {
        if (isset($this->cache[$url])) {
            return $this->cache[$url];
        }
        
        $schema = $this->fetch($url);
        $schema = $this->expandNestedSchemas($schema);

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
        $path = $this->getLocalPath($url);
        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if (!is_string($content)) {
            trigger_error("Error obtaining schema from path: $path", E_USER_WARNING);
            return null;
        }

        $schema = json_decode($content);
        if ($schema === null) {
            $error = json_last_error_msg();
            if (strtolower($error) === 'no error') {
                $error = "data: $content";
            }

            trigger_error("Invalid JSON Schema in path $path: $error", E_USER_WARNING);
            return null;
        }

        return $schema;
    }

    /**
     * Derive schema local path from schema url
     *
     * @param string $url
     * @return string
     */
    protected function getLocalPath(string $url): ?string
    {
        if (!is_schema_link_valid($url)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        return 'config/schemas' . $path;
    }

    /**
     * Resolve references to other schemas
     *
     * @param stdClass|array|null $data
     * @return stdClass
     */
    protected function expandNestedSchemas($data): ?stdClass
    {
        $data = (array)$data;

        foreach ($data as $key => $item) {
            if ($key === '$ref' && is_string($item)) {
                return $this->get($item);
            }

            if (is_array($item) || is_object($item)) {
                $data[$key] = $this->expandNestedSchemas($item);
            }
        }

        return (object)$data;
    }
}
