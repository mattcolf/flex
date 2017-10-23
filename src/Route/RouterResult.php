<?php

declare(strict_types=1);

namespace MattColf\Flex\Route;

/**
 * Holds the result of dispatching the router
 */
class RouterResult
{
    const STATUS_MATCH = 'match';
    const STATUS_NOT_FOUND = 'not_found';
    const STATUS_NOT_ALLOWED = 'not_allowed';

    /**
     * @var string
     */
    private $status;

    /**
     * @var Route|null
     */
    private $route;

    /**
     * @var array
     */
    private $params;

    /**
     * @param string $status
     * @param Route|null $route
     * @param array $params
     */
    public function __construct(string $status, Route $route = null, array $params = [])
    {
        $this->status = $status;
        $this->route = $route;
        $this->params = $params;
    }

    /**
     * @return bool
     */
    public function isMatch() : bool
    {
        return $this->getStatus() === static::STATUS_MATCH;
    }

    /**
     * @return bool
     */
    public function isNotFound() : bool
    {
        return $this->getStatus() === static::STATUS_NOT_FOUND;
    }

    /**
     * @return bool
     */
    public function isNotAllowed() : bool
    {
        return $this->getStatus() === static::STATUS_NOT_ALLOWED;
    }

    /**
     * @return string
     */
    public function getStatus() : string
    {
        return $this->status;
    }

    /**
     * @return Route|null
     */
    public function getRoute() : ?Route
    {
        return $this->route;
    }

    /**
     * @return array
     */
    public function getParams() : array
    {
        return $this->params;
    }
}