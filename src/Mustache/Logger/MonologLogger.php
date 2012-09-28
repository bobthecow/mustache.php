<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2012 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * A Mustache Monolog Logger adapter.
 */
class MonologLogger implements Mustache_Logger
{
    protected $logger;

    /**
     * @param \Monolog\Logger $logger
     */
    public function __construct($logger)
    {
        // not typehinting this because PHP 5.2 that's why.
        if (!is_a($logger, 'Monolog\\Logger')) {
            throw new InvalidArgumentException('MonologLogger requires a Monolog\\Logger instance.');
        }

        $this->logger = $logger;
    }

    /**
     * Adds a log record.
     *
     * Overload the AbstractLogger::log method, because all log messages should
     * be passed through to Monolog regardless of the log level. Monolog will
     * handle ignoring the messages it doesn't care about.
     *
     * @param  integer $level   The logging level
     * @param  string  $message The log message
     * @param  array   $context The log context
     */
    public function log($level, $message, array $context = array())
    {
        $this->logger->addRecord($level, $message, $context);
    }
}
