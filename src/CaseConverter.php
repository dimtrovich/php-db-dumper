<?php

namespace Dimtrovich\DbDumper;

abstract class CaseConverter
{
    /**
     * The cache of dot-cased words.
     */
    protected static array $dotCache = [];

	/**
     * The cache of snake-cased words.
     */
    protected static array $snakeCache = [];

    /**
     * Convert a string to dot notation.
     */
    public static function toDot(string $value): string
    {
        $key = $value;

        if (isset(static::$dotCache[$key])) {
            return static::$dotCache[$key];
        }

        $value = self::toSnake($value);

        return static::$dotCache[$key] = str_replace('_', '.', $value);
    }

	/**
	 * Convert a string to snake case.
	*/
	public static function toSnake(string $value): string
	{
		$key = $value;

		if (isset(static::$snakeCache[$key])) {
			return static::$snakeCache[$key];
		}

		if (! ctype_lower($value)) {
			$value = preg_replace('/\s+/u', '', ucwords($value));

			$value = strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $value));
		}

		return static::$snakeCache[$key] = $value;
	}
}
