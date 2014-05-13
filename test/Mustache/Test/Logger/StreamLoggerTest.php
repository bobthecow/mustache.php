<?php
namespace Mustache\Test\Logger;

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2014 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group unit
 */
class StreamLoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider acceptsStreamData
     */
    public function testAcceptsStream($name, $stream)
    {
        $logger = new \Mustache\Logger\StreamLogger($stream);
        $logger->log(\Mustache\Logger::CRITICAL, 'message');

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
     * @expectedException \Mustache\Exception\LogicException
     */
    public function testPrematurelyClosedStreamThrowsException()
    {
        $stream = tmpfile();
        $logger = new \Mustache\Logger\StreamLogger($stream);
        fclose($stream);

        $logger->log(\Mustache\Logger::CRITICAL, 'message');
    }

    /**
     * @dataProvider getLevels
     */
    public function testLoggingThresholds($logLevel, $level, $shouldLog)
    {
        $stream = tmpfile();
        $logger = new \Mustache\Logger\StreamLogger($stream, $logLevel);
        $logger->log($level, "logged");

        rewind($stream);
        $result = fread($stream, 1024);

        if ($shouldLog) {
            $this->assertContains("logged", $result);
        } else {
            $this->assertEmpty($result);
        }
    }

    public function getLevels()
    {
        // $logLevel, $level, $shouldLog
        return array(
            // identities
            array(\Mustache\Logger::EMERGENCY, \Mustache\Logger::EMERGENCY, true),
            array(\Mustache\Logger::ALERT,     \Mustache\Logger::ALERT,     true),
            array(\Mustache\Logger::CRITICAL,  \Mustache\Logger::CRITICAL,  true),
            array(\Mustache\Logger::ERROR,     \Mustache\Logger::ERROR,     true),
            array(\Mustache\Logger::WARNING,   \Mustache\Logger::WARNING,   true),
            array(\Mustache\Logger::NOTICE,    \Mustache\Logger::NOTICE,    true),
            array(\Mustache\Logger::INFO,      \Mustache\Logger::INFO,      true),
            array(\Mustache\Logger::DEBUG,     \Mustache\Logger::DEBUG,     true),

            // one above
            array(\Mustache\Logger::ALERT,     \Mustache\Logger::EMERGENCY, true),
            array(\Mustache\Logger::CRITICAL,  \Mustache\Logger::ALERT,     true),
            array(\Mustache\Logger::ERROR,     \Mustache\Logger::CRITICAL,  true),
            array(\Mustache\Logger::WARNING,   \Mustache\Logger::ERROR,     true),
            array(\Mustache\Logger::NOTICE,    \Mustache\Logger::WARNING,   true),
            array(\Mustache\Logger::INFO,      \Mustache\Logger::NOTICE,    true),
            array(\Mustache\Logger::DEBUG,     \Mustache\Logger::INFO,      true),

            // one below
            array(\Mustache\Logger::EMERGENCY, \Mustache\Logger::ALERT,     false),
            array(\Mustache\Logger::ALERT,     \Mustache\Logger::CRITICAL,  false),
            array(\Mustache\Logger::CRITICAL,  \Mustache\Logger::ERROR,     false),
            array(\Mustache\Logger::ERROR,     \Mustache\Logger::WARNING,   false),
            array(\Mustache\Logger::WARNING,   \Mustache\Logger::NOTICE,    false),
            array(\Mustache\Logger::NOTICE,    \Mustache\Logger::INFO,      false),
            array(\Mustache\Logger::INFO,      \Mustache\Logger::DEBUG,     false),
        );
    }

    /**
     * @dataProvider getLogMessages
     */
    public function testLogging($level, $message, $context, $expected)
    {
        $stream = tmpfile();
        $logger = new \Mustache\Logger\StreamLogger($stream, \Mustache\Logger::DEBUG);
        $logger->log($level, $message, $context);

        rewind($stream);
        $result = fread($stream, 1024);

        $this->assertEquals($expected, $result);
    }

    public function getLogMessages()
    {
        // $level, $message, $context, $expected
        return array(
            array(\Mustache\Logger::DEBUG,     'debug message',     array(),  "DEBUG: debug message\n"),
            array(\Mustache\Logger::INFO,      'info message',      array(),  "INFO: info message\n"),
            array(\Mustache\Logger::NOTICE,    'notice message',    array(),  "NOTICE: notice message\n"),
            array(\Mustache\Logger::WARNING,   'warning message',   array(),  "WARNING: warning message\n"),
            array(\Mustache\Logger::ERROR,     'error message',     array(),  "ERROR: error message\n"),
            array(\Mustache\Logger::CRITICAL,  'critical message',  array(),  "CRITICAL: critical message\n"),
            array(\Mustache\Logger::ALERT,     'alert message',     array(),  "ALERT: alert message\n"),
            array(\Mustache\Logger::EMERGENCY, 'emergency message', array(),  "EMERGENCY: emergency message\n"),

            // with context
            array(
                \Mustache\Logger::ERROR,
                'error message',
                array('name' => 'foo', 'number' => 42),
                "ERROR: error message\n"
            ),

            // with interpolation
            array(
                \Mustache\Logger::ERROR,
                'error {name}-{number}',
                array('name' => 'foo', 'number' => 42),
                "ERROR: error foo-42\n"
            ),

            // with iterpolation false positive
            array(
                \Mustache\Logger::ERROR,
                'error {nothing}',
                array('name' => 'foo', 'number' => 42),
                "ERROR: error {nothing}\n"
            ),

            // with interpolation injection
            array(
                \Mustache\Logger::ERROR,
                '{foo}',
                array('foo' => '{bar}', 'bar' => 'FAIL'),
                "ERROR: {bar}\n"
            ),
        );
    }

    public function testChangeLoggingLevels()
    {
        $stream = tmpfile();
        $logger = new \Mustache\Logger\StreamLogger($stream);

        $logger->setLevel(\Mustache\Logger::ERROR);
        $this->assertEquals(\Mustache\Logger::ERROR, $logger->getLevel());

        $logger->log(\Mustache\Logger::WARNING, 'ignore this');

        $logger->setLevel(\Mustache\Logger::INFO);
        $this->assertEquals(\Mustache\Logger::INFO, $logger->getLevel());

        $logger->log(\Mustache\Logger::WARNING, 'log this');

        $logger->setLevel(\Mustache\Logger::CRITICAL);
        $this->assertEquals(\Mustache\Logger::CRITICAL, $logger->getLevel());

        $logger->log(\Mustache\Logger::ERROR, 'ignore this');

        rewind($stream);
        $result = fread($stream, 1024);

        $this->assertEquals("WARNING: log this\n", $result);
    }

    /**
     * @expectedException \Mustache\Exception\InvalidArgumentException
     */
    public function testThrowsInvalidArgumentExceptionWhenSettingUnknownLevels()
    {
        $logger = new \Mustache\Logger\StreamLogger(tmpfile());
        $logger->setLevel('bacon');
    }

    /**
     * @expectedException \Mustache\Exception\InvalidArgumentException
     */
    public function testThrowsInvalidArgumentExceptionWhenLoggingUnknownLevels()
    {
        $logger = new \Mustache\Logger\StreamLogger(tmpfile());
        $logger->log('bacon', 'CODE BACON ERROR!');
    }
}
