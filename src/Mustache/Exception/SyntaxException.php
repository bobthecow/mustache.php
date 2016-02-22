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
 * Mustache syntax exception.
 */
class Mustache_Exception_SyntaxException extends LogicException implements Mustache_Exception
{
    protected $token;

    /**
     * @param string    $msg
     * @param array     $token
     * @param Exception $previous
     */
    public function __construct($msg, array $token, Exception $previous = null)
    {
        $this->token = $token;
        if (method_exists(__CLASS__, 'getPrevious')) {
            parent::__construct($msg, 0, $previous);
        } else {
            parent::__construct($msg);
        }
    }

    /**
     * @return array
     */
    public function getToken()
    {
        return $this->token;
    }
}
