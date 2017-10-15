<?php

declare(strict_types=1);

namespace MattColf\Flex\Route;

class RouteDetails
{
    const STATUS_MATCH = 'match';
    const STATUS_NOT_FOUND = 'not_found';
    const STATUS_NOT_ALLOWED = 'not_allowed';

    /**
     * @var null|string
     */
    private $name;

    /**
     * @var string
     */
    private $status;

    /**
     * @var callable[]
     */
    private $stack;

    /**
     * @var array
     */
    private $params;

    /**
     * @param string $status
     * @param null|string $name
     * @param callable[] $stack
     * @param array $params
     */
    public function __construct(string $status, string $name = null, array $stack = [], array $params = [])
    {
        $this->name = $name;
        $this->status = $status;
        $this->stack = $stack;
        $this->params = $params;
    }

    /**
     * @return string
     */
    public function getStatus() : string
    {
        return $this->status;
    }

    /**
     * @return null|string
     */
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * @return callable[]
     */
    public function getStack() : array
    {
        return $this->stack;
    }

    /**
     * @return array
     */
    public function getParams() : array
    {
        return $this->params;
    }
}