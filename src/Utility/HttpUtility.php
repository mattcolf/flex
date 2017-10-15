<?php

declare(strict_types=1);

namespace MattColf\Flex\Utility;

use Psr\Http\Message\UriInterface;

class HttpUtility
{
    /**
     * Parse a query string
     *
     * @param string $query
     * @return array
     */
    public static function parseQuery(string $query) : array
    {
        $parsed = [];

        parse_str(html_entity_decode($query), $parsed);

        return $parsed;
    }

    /**
     * Retrieve a query parameter by name from the query string
     *
     * @param UriInterface $uri
     * @param string $name
     * @return string|string[]|null
     */
    public static function getQueryParam(UriInterface $uri, string $name)
    {
        $params = static::parseQuery($uri->getQuery());

        return array_key_exists($name, $params) ? $params[$name] : null;
    }

    /**
     * Build a query string from an array of keys
     *
     * @param array $query
     * @param bool $filterEmpty
     * @return string
     */
    public static function buildQuery(array $query, bool $filterEmpty = false) : string
    {
        if ($filterEmpty) {
            $query = array_filter($query, function ($value) {
                return !empty($value);
            });
        }

        ksort($query, SORT_ASC | SORT_NATURAL);

        return urldecode(http_build_query($query, '', '&'));
    }

    /**
     *
     *
     * @param UriInterface $uri
     * @param array $query
     * @return UriInterface
     */
    public static function mergeQuery(UriInterface $uri, array $query) : UriInterface
    {
        $query = array_replace(static::parseQuery($uri->getQuery()), $query);

        return $uri->withQuery(static::buildQuery($query));
    }
}