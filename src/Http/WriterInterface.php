<?php

declare(strict_types=1);

namespace MattColf\Flex\Http;

use Psr\Http\Message\ResponseInterface;

interface WriterInterface
{
    /**
     * Start the writer
     */
    public function start() : void;

    /**
     * Clear all content
     */
    public function clear() : void;

    /**
     * End the writer, outputting any remaining content
     */
    public function end() : void;

    /**
     * Finalize the content
     *
     * @param ResponseInterface $response
     */
    public function finalize(ResponseInterface $response) : void;
}