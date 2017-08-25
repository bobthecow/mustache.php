<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2017 Enalean
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Mustache_Exception_UnknownVariableException extends UnexpectedValueException implements Mustache_Exception
{
    /**
     * @var string
     */
    protected $variableName;

    /**
     * @param string    $variableName
     * @param Exception $previous
     */
    public function __construct($variableName, Exception $previous = null)
    {
        $this->variableName = $variableName;
        $message = sprintf('Unknown variable: %s', $variableName);
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            parent::__construct($message, 0, $previous);
        } else {
            parent::__construct($message); // @codeCoverageIgnore
        }
    }

    /**
     * @return string
     */
    public function getVariableName()
    {
        return $this->variableName;
    }
}
