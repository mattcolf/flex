<?php

declare(strict_types=1);

namespace MattColf\Flex\Route;

/**
 * Holds the details of a single route
 */
class Route
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string[]
     */
    private $methods;

    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $stack;

    /**
     * @var array
     */
    private $meta;

    /**
     * @param string $name
     * @param string[] $methods
     * @param string $path
     * @param array $stack
     * @param array $meta
     */
    public function __construct(string $name, array $methods, string $path, array $stack = [], array $meta = [])
    {
        $this->name = $name;
        $this->methods = $methods;
        $this->path = $path;
        $this->stack = $stack;
        $this->meta = $meta;
    }

    /**
     * @return string
     */
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getMethods() : array
    {
        return $this->methods;
    }

    /**
     * @return string
     */
    public function getPath() : string
    {
        return $this->path;
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
    public function getMeta() : array
    {
        return $this->meta;
    }
}