<?php
namespace Mustache\Cache;

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2014 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Abstract Mustache Cache class.
 *
 * Provides logging support to child implementations.
 *
 * @abstract
 */
abstract class AbstractCache implements \Mustache\Cache
{
    private $logger = null;

    /**
     * Get the current logger instance.
     *
     * @return \Mustache\Logger|Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Set a logger instance.
     *
     * @param \Mustache\Logger|Psr\Log\LoggerInterface $logger
     */
    public function setLogger($logger = null)
    {
        if ($logger !== null && !($logger instanceof \Mustache\Logger || is_a($logger, 'Psr\\Log\\LoggerInterface'))) {
            throw new \Mustache\Exception\InvalidArgumentException('Expected an instance of \Mustache\Logger or Psr\\Log\\LoggerInterface.');
        }

        $this->logger = $logger;
    }

    /**
     * Add a log record if logging is enabled.
     *
     * @param integer $level   The logging level
     * @param string  $message The log message
     * @param array   $context The log context
     */
    protected function log($level, $message, array $context = array())
    {
        if (isset($this->logger)) {
            $this->logger->log($level, $message, $context);
        }
    }
}
