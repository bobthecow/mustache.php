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
 * A Mustache Stream Logger.
 *
 * The Stream Logger wraps a file resource instance (such as a stream) or a
 * stream URL. All log messages over the threshold level will be appended to
 * this stream.
 *
 * Hint: Try `php://stderr` for your stream URL.
 */
class StreamLogger implements Mustache_Logger
{
    protected static $levels = array(
        self::DEBUG     => 100,
        self::INFO      => 200,
        self::NOTICE    => 250,
        self::WARNING   => 300,
        self::ERROR     => 400,
        self::CRITICAL  => 500,
        self::ALERT     => 550,
        self::EMERGENCY => 600,
    );

    protected $stream = null;
    protected $url    = null;

    /**
     * @param string  $stream Resource instance or URL
     * @param integer $level  The minimum logging level at which this handler will be triggered
     */
    public function __construct($stream, $level = Mustache_Logger::ERROR)
    {
        $this->setLevel($level);

        if (is_resource($stream)) {
            $this->stream = $stream;
        } else {
            $this->url = $stream;
        }
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
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function emergency($message, array $context = array())
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function alert($message, array $context = array())
    {
        $this->log(self::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function critical($message, array $context = array())
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function error($message, array $context = array())
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function warning($message, array $context = array())
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function notice($message, array $context = array())
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function info($message, array $context = array())
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function debug($message, array $context = array())
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        if (!array_key_exists($level, self::$levels)) {
            throw new InvalidArgumentException('Unexpected logging level: ' . $level);
        }

        if (self::$levels[$level] >= $this->level) {
            $this->writeLog($level, $message, $context);
        }
    }

    /**
     * Write a record to the log.
     *
     * @param  integer $level   The logging level
     * @param  string  $message The log message
     * @param  array   $context The log context
     */
    protected function writeLog($level, $message, array $context = array())
    {
        if (!is_resource($this->stream)) {
            if (!isset($this->url)) {
                throw new LogicException('Missing stream url, the stream can not be opened. This may be caused by a premature call to close().');
            }

            $this->stream = fopen($this->url, 'a');
            if (!is_resource($this->stream)) {
                throw new UnexpectedValueException(sprintf('The stream or file "%s" could not be opened.', $this->url));
            }
        }

        fwrite($this->stream, self::formatLine($level, $message, $context));
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
    protected static function getLevelName($level)
    {
        if (!array_key_exists($level, self::$levels)) {
            throw new InvalidArgumentException('Unexpected logging level: ' . $level);
        }

        return strtoupper($level);
    }

    /**
     * Format a log line for output.
     *
     * @param  integer $level   The logging level
     * @param  string  $message The log message
     * @param  array   $context The log context
     */
    protected static function formatLine($level, $message, array $context = array())
    {
        $message = (string) $message;
        foreach ($context as $key => $val) {
            $message = str_replace('%'.$key.'%', $val, $message);
        }

        return sprintf('%s: %s %s', self::getLevelName($level), (string) $message, json_encode($context));
    }
}
