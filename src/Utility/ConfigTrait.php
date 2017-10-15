<?php

declare(strict_types=1);

namespace MattColf\Flex\Utility;

trait ConfigTrait
{
    /**
     * @var array
     */
    private $config = [];

    /**
     * @param array $user
     * @param array $defaults
     * @param array $overrides
     */
    private function setConfig(array $user, array $defaults = [], array $overrides = [])
    {
        $this->config = array_merge($defaults, $user, $overrides);
    }

    /**
     * @param string $key
     * @return bool
     */
    private function hasConfig(string $key) : bool
    {
        return array_key_exists($key, $this->config);
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function getConfig(string $key, $default = null)
    {
        return $this->hasConfig($key) ? $this->config[$key] : $default;
    }
}