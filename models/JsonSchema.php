<?php declare(strict_types=1);

use Improved\IteratorPipeline\Pipeline;
use Jasny\DB\BasicEntity;
use function Jasny\object_get_properties;

/**
 * JSONSchema representation.
 */
class JsonSchema extends BasicEntity
{
    use DeepClone;

    /**
     * @var string
     */
    public $schema = 'http://json-schema.org/draft-07/schema#';

    /**
     * @var string|null
     */
    public $id;

    /**
     * @var string|null
     */
    public $title;

    /**
     * @var string|null
     */
    public $description;

    /**
     * @var string|null
     */
    public $type;

    /**
     * @var string|null
     */
    public $comment;

    /**
     * @var mixed
     */
    public $default;

    /**
     * JsonSchema constructor.
     * @param array|\stdClass $schema
     */
    public function __construct($schema)
    {
        foreach ($schema as $key => $value) {
            $key = ltrim($key, '$');
            $this->$key = $value;
        }
    }

    /**
     * Build an value from a schema
     *
     * @return mixed
     */
    public function build()
    {
        if ($this->type === 'object') {
            return $this->buildObject();
        } elseif ($this->type === 'array') {
            return [];
        }

        $value = $this->default;

        if (isset($value)) {
            settype($value, $this->type === 'number' ? 'float' : $this->type);
        }

        return $value;
    }

    /**
     * Build object from schema.
     *
     * @return object
     */
    protected function buildObject(): object
    {
        $value = (object)[];

        $properties = isset($this->properties) ? $this->properties : [];

        foreach ($properties as $key => $property) {
            $value->$key = (new static($property))->build();
        }

        return $value;
    }

    /**
     * Cast value from a schema
     *
     * @param mixed $value
     * @return mixed
     */
    public function typeCast($value)
    {
        if ($this->type === 'object') {
            return $this->typeCastObject($value);
        }

        if (isset($value)) {
            settype($value, $this->type === 'number' ? 'float' : $this->type);
        }

        return $value;
    }

    /**
     * Cast object from schema.
     *
     * @param mixed $input
     * @return object
     */
    protected function typeCastObject($input)
    {
        $value = (object)$input;

        $properties = isset($this->properties) ? $this->properties : [];

        foreach ($properties as $key => $property) {
            $propValue = isset($value->$key) ? $value->$key : null;
            $value->$key = (new static($property))->typeCast($propValue);
        }

        return $value;
    }


    /**
     * Convert loaded values to a model object.
     *
     * @param object|array $values
     * @return static
     */
    public static function fromData($values)
    {
        return new static($values);
    }

    /**
     * Get data that needs to be stored in the DB.
     *
     * @return array
     */
    public function toData()
    {
        return Pipeline::with(object_get_properties($this, true))
            ->cleanup()
            ->toArray();
    }

    /**
     * Prepare for json serialization
     *
     * @return object
     */
    public function jsonSerialize()
    {
        $data = object_get_properties($this, true);

        foreach (['schema', 'id', 'comment'] as $key) {
            if ($data[$key] !== null) {
                $data['$' . $key] = $data[$key];
            }
            unset($data[$key]);
        }

        foreach ($data as $key => $value) {
            if ($value === null) {
                unset($data[$key]);
            }
        }

        return (object)$data;
    }
}
