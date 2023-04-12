<?php

namespace Appwrite\Utopia\Database\Validator\Query;

use Appwrite\Utopia\Database\Validator\Query\Base;
use Utopia\Database\Database;
use Utopia\Database\Query;

class Filter extends Base
{
    /**
     * @var string
     */
    protected $message = 'Invalid query';

    /**
     * @var array
     */
    protected $schema = [];

    private int $maxValuesCount;

    /**
     * Query constructor
     *
     * @param int $maxValuesCount
     */
    public function __construct(array $attributes = [], int $maxValuesCount = 100)
    {
        foreach ($attributes as $attribute) {
            $this->schema[$attribute->getAttribute('key')] = $attribute->getArrayCopy();
        }

        $this->maxValuesCount = $maxValuesCount;
    }

    protected function isValidAttribute($attribute): bool
    {
        if (\str_contains($attribute, '.')) {
            // For relationships, just validate the top level.
            // Utopia will validate each nested level during the recursive calls.
            $attribute = \explode('.', $attribute)[0];

            // TODO: Remove this when nested queries are supported
            if (isset($this->schema[$attribute])) {
                $this->message = 'Cannot query nested attribute on: ' . $attribute;
                return false;
            }
        }

        // Search for attribute in schema
        if (!isset($this->schema[$attribute])) {
            $this->message = 'Attribute not found in schema: ' . $attribute;
            return false;
        }

        return true;
    }

    protected function isValidAttributeAndValues(string $attribute, array $values): bool
    {
        if (!$this->isValidAttribute($attribute)) {
            return false;
        }

        if (\str_contains($attribute, '.')) {
            // For relationships, just validate the top level.
            // Utopia will validate each nested level during the recursive calls.
            $attribute = \explode('.', $attribute)[0];
        }

        $attributeSchema = $this->schema[$attribute];

        if (count($values) > $this->maxValuesCount) {
            $this->message = 'Query on attribute has greater than ' . $this->maxValuesCount . ' values: ' . $attribute;
            return false;
        }

        // Extract the type of desired attribute from collection $schema
        $attributeType = $attributeSchema['type'];

        foreach ($values as $value) {
            $condition = match ($attributeType) {
                Database::VAR_RELATIONSHIP => true,
                Database::VAR_DATETIME => gettype($value) === Database::VAR_STRING,
                Database::VAR_FLOAT => (gettype($value) === Database::VAR_FLOAT || gettype($value) === Database::VAR_INTEGER),
                default => gettype($value) === $attributeType
            };

            if (!$condition) {
                $this->message = 'Query type does not match expected: ' . $attributeType;
                return false;
            }
        }

        return true;
    }

    /**
     * Is valid.
     *
     * Returns true if method is a filter method, attribute exists, and value matches attribute type
     *
     * Otherwise, returns false
     *
     * @param Query $value
     *
     * @return bool
     */
    public function isValid($query): bool
    {
        // Validate method
        $method = $query->getMethod();
        $attribute = $query->getAttribute();

        switch ($method) {
            case Query::TYPE_EQUAL:
            case Query::TYPE_NOTEQUAL:
            case Query::TYPE_LESSER:
            case Query::TYPE_LESSEREQUAL:
            case Query::TYPE_GREATER:
            case Query::TYPE_GREATEREQUAL:
            case Query::TYPE_SEARCH:
            case Query::TYPE_STARTS_WITH:
            case Query::TYPE_ENDS_WITH:
            case Query::TYPE_BETWEEN:
            case Query::TYPE_IS_NULL:
            case Query::TYPE_IS_NOT_NULL:
                $values = $query->getValues();
                return $this->isValidAttributeAndValues($attribute, $values);

            default:
                return false;
        }
    }

    public function getMethodType(): string
    {
        return self::METHOD_TYPE_FILTER;
    }
}
