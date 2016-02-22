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
 * Unknown filter exception.
 */
class Mustache_Exception_UnknownFilterException extends UnexpectedValueException implements Mustache_Exception
{
    protected $filterName;

    /**
     * @param string    $filterName
     * @param Exception $previous
     */
    public function __construct($filterName, Exception $previous = null)
    {
        $this->filterName = $filterName;
        $message = sprintf('Unknown filter: %s', $filterName);
        if (method_exists(__CLASS__, 'getPrevious')) {
            parent::__construct($message, 0, $previous);
        } else {
            parent::__construct($message);
        }
    }

    public function getFilterName()
    {
        return $this->filterName;
    }
}
