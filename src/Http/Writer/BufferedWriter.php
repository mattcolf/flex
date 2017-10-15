<?php

declare(strict_types=1);

namespace MattColf\Flex\Http\Writer;

use MattColf\Flex\Http\WriterInterface;
use MattColf\Flex\Utility\ConfigTrait;
use MattColf\Flex\Utility\LoggingTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class BufferedWriter implements WriterInterface
{
    use ConfigTrait;
    use LoggingTrait;

    // Config Keys
    const CHUNK_SIZE = 'chunk_size';
    const MAX_BUFFER_SIZE = 'max_buffer_size';
    const UNEXPECTED_OUTPUT_WARNING = 'unexpected_output_warning';
    const UNEXPECTED_OUTPUT_MESSAGE = 'unexpected_output_message';
    const EXCEEDED_MAX_BUFFER_SIZE_WARNING = 'exceeded_max_buffer_size_warning';
    const EXCEEDED_MAX_BUFFER_SIZE_MESSAGE = 'exceeded_max_buffer_size_message';
    const SUPPRESS_UNEXPECTED_OUTPUT = 'suppress_unexpected_output';

    // Config Defaults
    const DEFAULT_CHUNK_SIZE = '8096'; //8KB
    const DEFAULT_MAX_BUFFER_SIZE = '5242880'; // 5MB
    const DEFAULT_UNEXPECTED_OUTPUT_WARNING = false;
    const DEFAULT_UNEXPECTED_OUTPUT_MESSAGE = 'Output buffer contains unexpected content. Maybe you have characters before your PHP opening tags? (<?php)';
    const DEFAULT_EXCEEDED_MAX_BUFFER_SIZE_WARNING = false;
    const DEFAULT_EXCEEDED_MAX_BUFFER_SIZE_MESSAGE = 'Maximum output buffer size %s bytes exceeded and content was written to the client. Consider adjusting your buffer size.';
    const DEFAULT_SUPPRESS_UNEXPECTED_OUTPUT = false;

    /**
     * @var bool
     */
    private $isBuffering;

    /**
     * @param LoggerInterface $logger
     * @param array $config
     */
    public function __construct(LoggerInterface $logger = null, array $config = [])
    {
        $this->setLogger($logger);
        $this->isBuffering = false;

        $this->setConfig($config, [
            static::MAX_BUFFER_SIZE => static::DEFAULT_MAX_BUFFER_SIZE,
            static::UNEXPECTED_OUTPUT_WARNING => static::DEFAULT_UNEXPECTED_OUTPUT_WARNING,
            static::UNEXPECTED_OUTPUT_MESSAGE => static::DEFAULT_UNEXPECTED_OUTPUT_MESSAGE,
            static::EXCEEDED_MAX_BUFFER_SIZE_WARNING => static::DEFAULT_EXCEEDED_MAX_BUFFER_SIZE_WARNING,
            static::EXCEEDED_MAX_BUFFER_SIZE_MESSAGE => static::DEFAULT_EXCEEDED_MAX_BUFFER_SIZE_MESSAGE,
            static::SUPPRESS_UNEXPECTED_OUTPUT => static::DEFAULT_SUPPRESS_UNEXPECTED_OUTPUT
        ]);
    }

    /**
     * Start the writer
     */
    public function start() : void
    {
        $this->isBuffering = true;

        $callback = function (string $buffer, int $phase) {

            $shouldLog = $this->getConfig(static::EXCEEDED_MAX_BUFFER_SIZE_WARNING);

            if ($shouldLog && $phase === PHP_OUTPUT_HANDLER_WRITE && $this->isBuffering && strlen($buffer) > 0) {
                $this->logger->warning(sprintf(
                    $this->getConfig(static::EXCEEDED_MAX_BUFFER_SIZE_MESSAGE),
                    $this->getConfig(static::MAX_BUFFER_SIZE)
                ));
            }

            return false;
        };

        ob_start($callback, (int)$this->getConfig(static::MAX_BUFFER_SIZE));
    }

    /**
     * Clear all content
     */
    public function clear() : void
    {
        ob_clean();
    }

    /**
     * End the writer, outputting any remaining content
     */
    public function end() : void
    {
        $this->isBuffering = false;

        if (ob_get_length() > 0 && $this->getConfig(static::UNEXPECTED_OUTPUT_WARNING)) {
            $this->logger->warning(sprintf($this->getConfig(static::UNEXPECTED_OUTPUT_MESSAGE)));
        }

        if ($this->getConfig(static::SUPPRESS_UNEXPECTED_OUTPUT)) {
            ob_end_clean();
        } else {
            ob_end_flush();
        }
    }

    /**
     * Finalize the content
     *
     * @param ResponseInterface $response
     */
    public function finalize(ResponseInterface $response) : void
    {
        $this->end();

        $this->sendHeaders($response);
        $this->sendBody($response);
    }

    /**
     * @param ResponseInterface $response
     */
    private function sendHeaders(ResponseInterface $response) : void
    {
        if (headers_sent()) {
            return;
        }

        // Status
        header(sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ));

        // Headers
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value));
            };
        }
    }

    /**
     * @param ResponseInterface $response
     */
    private function sendBody(ResponseInterface $response) : void
    {
        if (in_array($response->getStatusCode(), [2014, 205, 304])) {
            return;
        }

        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        $length = $response->hasHeader('Content-Length')
            ? (int) $response->getHeaderLine('Content-Length')
            : $body->getSize();

        if (is_int($length) & $length > 0) {
            $this->sendStream($body, $length);
        } else {
            $this->sendStream($body);
        }
    }

    /**
     * @param StreamInterface $stream
     * @param int|null $length
     */
    private function sendStream(StreamInterface $stream, int $length = null) : void
    {
        $out = fopen('php://output', 'w');

        while (($length === null || $length > 0) && !$stream->eof()) {

            $content = $stream->read(min($length, $length ?? (int)$this->getConfig(static::CHUNK_SIZE)));

            fwrite($out, $content);

            $length -= strlen($content);

            // end output if the connection was closed
            if (connection_status() !== CONNECTION_NORMAL) {
                break;
            };
        }

        fclose($out);
    }
}