<?php

declare(strict_types=1);

namespace MattColf\Flex\Utility;

use InvalidArgumentException;

/**
 * A utility to assist with the validation of input since PHP can't handle all type edge cases with type hints.
 *
 * Supports validating the following types.
 *
 *  - Scalars: null, string, int, number, float, bool, array
 *  - Objects: any fully qualified class or interface name
 *  - Typed Arrays: <type>[]
 *  - Multiple Typed Arrays: (<type>|<type>...)[]
 *  - Combinations: <type>|<type>...
 */
class TypeValidation
{
    const DEFAULT_ERROR = 'Invalid type %s. Expected %s.';

    /**
     * Validate that a value is of the expected type
     *
     * @param mixed $input
     * @param string $type
     * @param string $error
     * @throws InvalidArgumentException
     */
    public static function validateType($input, string $type, string $error = self::DEFAULT_ERROR)
    {
        if (!static::isType($input, $type)) {
            $type = preg_replace('#\|#', ' OR ', $type);
            throw new InvalidArgumentException(sprintf($error, static::getDisplayType($input), $type));
        }
    }

    /**
     * Check if input is of the expected type
     *
     * @param $input
     * @param string $type
     * @return bool
     */
    public static function isType($input, string $type) : bool
    {
        // Type|Type
        if (1 == preg_match('#^([^(|]+)[|]{1}(.+)$#', $type, $matches)) {
            return static::isType($input, $matches[1]) || static::isType($input, $matches[2]);
        }

        // Type[] or (Type|Type|Type)[]
        if (1 == preg_match('#^\(?([^)]+)\)?\[\]$#', $type, $matches)) {
            return static::isTypedArray($input, $matches[1]);
        }

        // Exact String Match
        if (1 == preg_match('#^[\'"](.+)[\'"]$#', $type, $matches)) {
            return static::isType($input, 'string') && 0 === strcmp($input, $matches[1]);
        }

        switch (strtolower($type)) {
            case 'mixed':
                return true;
            case 'null':
                return is_null($input);
            case 'string':
                return is_string($input);
            case 'int':
            case 'integer':
                return is_int($input);
            case 'number':
                return is_numeric($input);
            case 'float':
                return is_float($input);
            case 'bool':
            case 'boolean':
                return is_bool($input);
            case 'array':
                return is_array($input);
            case 'callable':
                return is_callable($input);
            default:
                return is_object($input) && $input instanceof $type;
        }
    }

    /**
     * Check if input is an array containing only instances of the expected type
     *
     * @param mixed $input
     * @param string $type
     * @return bool
     */
    public static function isTypedArray($input, string $type) : bool
    {
        if (!is_array($input)) {
            return false;
        }

        foreach ($input as $item) {
            if (!static::isType($item, $type)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the type of a value for display
     *
     * @param mixed $input
     * @return string
     */
    private static function getDisplayType($input) : string
    {
        return is_object($input) ? get_class($input) : gettype($input);
    }
}