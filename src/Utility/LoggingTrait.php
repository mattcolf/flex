<?php

declare(strict_types=1);

namespace MattColf\Flex\Utility;

use Psr\Log\LoggerInterface;

trait LoggingTrait
{
    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    private function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    private function log($level, string $message, array $context = [])
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->log($level, $message, $context);
        }
    }
}