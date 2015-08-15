<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2015 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group unit
 */
class Mustache_Test_Logger_AbstractLoggerTest extends PHPUnit_Framework_TestCase
{
    public function testEverything()
    {
        $logger = new Mustache_Test_Logger_TestLogger();

        $logger->emergency('emergency message');
        $logger->alert('alert message');
        $logger->critical('critical message');
        $logger->error('error message');
        $logger->warning('warning message');
        $logger->notice('notice message');
        $logger->info('info message');
        $logger->debug('debug message');

        $expected = array(
            array(Mustache_Logger::EMERGENCY, 'emergency message', array()),
            array(Mustache_Logger::ALERT, 'alert message', array()),
            array(Mustache_Logger::CRITICAL, 'critical message', array()),
            array(Mustache_Logger::ERROR, 'error message', array()),
            array(Mustache_Logger::WARNING, 'warning message', array()),
            array(Mustache_Logger::NOTICE, 'notice message', array()),
            array(Mustache_Logger::INFO, 'info message', array()),
            array(Mustache_Logger::DEBUG, 'debug message', array()),
        );

        $this->assertEquals($expected, $logger->log);
    }
}

class Mustache_Test_Logger_TestLogger extends Mustache_Logger_AbstractLogger
{
    public $log = array();

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, array $context = array())
    {
        $this->log[] = array($level, $message, $context);
    }
}
