<?php declare(strict_types=1);

namespace JsonSchema\Validator\Loader;

use stdClass;
use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

/**
 * Fetching json schema from local file
 */
class FileSource
{
    /**
     * Fetch schema
     *
     * @param string $path
     * @return stdClass|null
     */
    public function fetch(string $path): ?stdClass
    {
        if (!file_exists($path)) {
            return null;
        }

        $content = @file_get_contents($path);
        if (!is_string($content)) {
            trigger_error("Error obtaining schema from path: $path", E_USER_WARNING);
            return null;
        }

        $schema = json_decode($content);
        if ($schema === null) {
            $error = json_last_error_msg();

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
    public function toLocalPath(string $url): ?string
    {
        if (!is_schema_link_valid($url)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        return 'config/schemas' . $path;
    }
}
