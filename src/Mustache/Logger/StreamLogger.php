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
class StreamLogger extends Mustache_Logger_AbstractLogger
{
    protected $stream = null;
    protected $url    = null;

    /**
     * @param string  $stream Resource instance or URL
     * @param integer $level  The minimum logging level at which this handler will be triggered
     */
    public function __construct($stream, $level = Mustache_Logger::ERROR)
    {
        parent::__construct($level);

        if (is_resource($stream)) {
            $this->stream = $stream;
        } else {
            $this->url = $stream;
        }
    }

    /**
     * Write a record to the log.
     *
     * @param  integer $level   The logging level
     * @param  string  $message The log message
     * @param  array   $context The log context
     */
    protected function write($level, $message, array $context = array())
    {
        if ($this->stream === null) {
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
}
