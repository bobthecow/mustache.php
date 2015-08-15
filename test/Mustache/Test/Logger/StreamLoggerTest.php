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
class Mustache_Test_Logger_StreamLoggerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider acceptsStreamData
     */
    public function testAcceptsStream($name, $stream)
    {
        $logger = new Mustache_Logger_StreamLogger($stream);
        $logger->log(Mustache_Logger::CRITICAL, 'message');

        $this->assertEquals("CRITICAL: message\n", file_get_contents($name));
    }

    public function acceptsStreamData()
    {
        $one = tempnam(sys_get_temp_dir(), 'mustache-test');
        $two = tempnam(sys_get_temp_dir(), 'mustache-test');

        return array(
            array($one, $one),
            array($two, fopen($two, 'a')),
        );
    }

    /**
     * @expectedException Mustache_Exception_LogicException
     */
    public function testPrematurelyClosedStreamThrowsException()
    {
        $stream = tmpfile();
        $logger = new Mustache_Logger_StreamLogger($stream);
        fclose($stream);

        $logger->log(Mustache_Logger::CRITICAL, 'message');
    }

    /**
     * @dataProvider getLevels
     */
    public function testLoggingThresholds($logLevel, $level, $shouldLog)
    {
        $stream = tmpfile();
        $logger = new Mustache_Logger_StreamLogger($stream, $logLevel);
        $logger->log($level, 'logged');

        rewind($stream);
        $result = fread($stream, 1024);

        if ($shouldLog) {
            $this->assertContains('logged', $result);
        } else {
            $this->assertEmpty($result);
        }
    }

    public function getLevels()
    {
        // $logLevel, $level, $shouldLog
        return array(
            // identities
            array(Mustache_Logger::EMERGENCY, Mustache_Logger::EMERGENCY, true),
            array(Mustache_Logger::ALERT,     Mustache_Logger::ALERT,     true),
            array(Mustache_Logger::CRITICAL,  Mustache_Logger::CRITICAL,  true),
            array(Mustache_Logger::ERROR,     Mustache_Logger::ERROR,     true),
            array(Mustache_Logger::WARNING,   Mustache_Logger::WARNING,   true),
            array(Mustache_Logger::NOTICE,    Mustache_Logger::NOTICE,    true),
            array(Mustache_Logger::INFO,      Mustache_Logger::INFO,      true),
            array(Mustache_Logger::DEBUG,     Mustache_Logger::DEBUG,     true),

            // one above
            array(Mustache_Logger::ALERT,     Mustache_Logger::EMERGENCY, true),
            array(Mustache_Logger::CRITICAL,  Mustache_Logger::ALERT,     true),
            array(Mustache_Logger::ERROR,     Mustache_Logger::CRITICAL,  true),
            array(Mustache_Logger::WARNING,   Mustache_Logger::ERROR,     true),
            array(Mustache_Logger::NOTICE,    Mustache_Logger::WARNING,   true),
            array(Mustache_Logger::INFO,      Mustache_Logger::NOTICE,    true),
            array(Mustache_Logger::DEBUG,     Mustache_Logger::INFO,      true),

            // one below
            array(Mustache_Logger::EMERGENCY, Mustache_Logger::ALERT,     false),
            array(Mustache_Logger::ALERT,     Mustache_Logger::CRITICAL,  false),
            array(Mustache_Logger::CRITICAL,  Mustache_Logger::ERROR,     false),
            array(Mustache_Logger::ERROR,     Mustache_Logger::WARNING,   false),
            array(Mustache_Logger::WARNING,   Mustache_Logger::NOTICE,    false),
            array(Mustache_Logger::NOTICE,    Mustache_Logger::INFO,      false),
            array(Mustache_Logger::INFO,      Mustache_Logger::DEBUG,     false),
        );
    }

    /**
     * @dataProvider getLogMessages
     */
    public function testLogging($level, $message, $context, $expected)
    {
        $stream = tmpfile();
        $logger = new Mustache_Logger_StreamLogger($stream, Mustache_Logger::DEBUG);
        $logger->log($level, $message, $context);

        rewind($stream);
        $result = fread($stream, 1024);

        $this->assertEquals($expected, $result);
    }

    public function getLogMessages()
    {
        // $level, $message, $context, $expected
        return array(
            array(Mustache_Logger::DEBUG,     'debug message',     array(),  "DEBUG: debug message\n"),
            array(Mustache_Logger::INFO,      'info message',      array(),  "INFO: info message\n"),
            array(Mustache_Logger::NOTICE,    'notice message',    array(),  "NOTICE: notice message\n"),
            array(Mustache_Logger::WARNING,   'warning message',   array(),  "WARNING: warning message\n"),
            array(Mustache_Logger::ERROR,     'error message',     array(),  "ERROR: error message\n"),
            array(Mustache_Logger::CRITICAL,  'critical message',  array(),  "CRITICAL: critical message\n"),
            array(Mustache_Logger::ALERT,     'alert message',     array(),  "ALERT: alert message\n"),
            array(Mustache_Logger::EMERGENCY, 'emergency message', array(),  "EMERGENCY: emergency message\n"),

            // with context
            array(
                Mustache_Logger::ERROR,
                'error message',
                array('name' => 'foo', 'number' => 42),
                "ERROR: error message\n",
            ),

            // with interpolation
            array(
                Mustache_Logger::ERROR,
                'error {name}-{number}',
                array('name' => 'foo', 'number' => 42),
                "ERROR: error foo-42\n",
            ),

            // with iterpolation false positive
            array(
                Mustache_Logger::ERROR,
                'error {nothing}',
                array('name' => 'foo', 'number' => 42),
                "ERROR: error {nothing}\n",
            ),

            // with interpolation injection
            array(
                Mustache_Logger::ERROR,
                '{foo}',
                array('foo' => '{bar}', 'bar' => 'FAIL'),
                "ERROR: {bar}\n",
            ),
        );
    }

    public function testChangeLoggingLevels()
    {
        $stream = tmpfile();
        $logger = new Mustache_Logger_StreamLogger($stream);

        $logger->setLevel(Mustache_Logger::ERROR);
        $this->assertEquals(Mustache_Logger::ERROR, $logger->getLevel());

        $logger->log(Mustache_Logger::WARNING, 'ignore this');

        $logger->setLevel(Mustache_Logger::INFO);
        $this->assertEquals(Mustache_Logger::INFO, $logger->getLevel());

        $logger->log(Mustache_Logger::WARNING, 'log this');

        $logger->setLevel(Mustache_Logger::CRITICAL);
        $this->assertEquals(Mustache_Logger::CRITICAL, $logger->getLevel());

        $logger->log(Mustache_Logger::ERROR, 'ignore this');

        rewind($stream);
        $result = fread($stream, 1024);

        $this->assertEquals("WARNING: log this\n", $result);
    }

    /**
     * @expectedException Mustache_Exception_InvalidArgumentException
     */
    public function testThrowsInvalidArgumentExceptionWhenSettingUnknownLevels()
    {
        $logger = new Mustache_Logger_StreamLogger(tmpfile());
        $logger->setLevel('bacon');
    }

    /**
     * @expectedException Mustache_Exception_InvalidArgumentException
     */
    public function testThrowsInvalidArgumentExceptionWhenLoggingUnknownLevels()
    {
        $logger = new Mustache_Logger_StreamLogger(tmpfile());
        $logger->log('bacon', 'CODE BACON ERROR!');
    }
}
