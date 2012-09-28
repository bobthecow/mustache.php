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
 * An abstract Mustache Logger implementation.
 */
abstract class Mustache_Logger_AbstractLogger implements Mustache_Logger
{
    protected static $levels = array(
        100 => 'DEBUG',
        200 => 'INFO',
        250 => 'NOTICE',
        300 => 'WARNING',
        400 => 'ERROR',
        500 => 'CRITICAL',
        550 => 'ALERT',
        600 => 'EMERGENCY',
    );

    /**
     * Abstract Logger constructor.
     *
     * @throws InvalidArgumentException if the logging level is unknown.
     *
     * @param  integer $level The minimum logging level which will be written
     */
    public function __construct($level = self::ERROR)
    {
        $this->setLevel($level);
    }

    /**
     * Set the minimum logging level.
     *
     * @throws InvalidArgumentException if the logging level is unknown.
     *
     * @param  integer $level The minimum logging level which will be written
     */
    public function setLevel($level)
    {
        if (!array_key_exists($level, self::$levels)) {
            throw new InvalidArgumentException('Unexpected logging level: ' . $level);
        }

        $this->level = $level;
    }

    /**
     * Get the current minimum logging level.
     *
     * @return integer
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Adds a log record.
     *
     * @see Mustache_Logger_AbstractLogger::write
     *
     * @param  integer $level   The logging level
     * @param  string  $message The log message
     * @param  array   $context The log context
     */
    public function log($level, $message, array $context = array())
    {
        if ($level >= $this->level) {
            $this->writeLog($level, $message, $context);
        }
    }

    /**
     * Gets the name of the logging level.
     *
     * @throws InvalidArgumentException if the logging level is unknown.
     *
     * @param  integer $level
     *
     * @return string
     */
    public static function getLevelName($level)
    {
        if (!array_key_exists($level, self::$levels)) {
            throw new InvalidArgumentException('Unexpected logging level: ' . $level);
        }

        return self::$levels[$level];
    }

    /**
     * Format a log line for output.
     *
     * @param  integer $level   The logging level
     * @param  string  $message The log message
     * @param  array   $context The log context
     */
    public static function formatLine($level, $message, array $context = array())
    {
        return sprintf('%s: %s %s', self::getLevelName($level), (string) $message, json_encode($context));
    }

    /**
     * Write a record to the log. Implemented by subclasses.
     *
     * @param  integer $level   The logging level
     * @param  string  $message The log message
     * @param  array   $context The log context
     */
    abstract protected function write($level, $message, array $context = array());
}
