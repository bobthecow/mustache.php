<?php

/**
 * TraversableMustache class.
 *
 * This is an implementaiton of the DOT_NOTATION pragma.
 *
 * A Mustache subclass which allows variable traversal via dots, i.e.:
 *
 * @code
 *    class Foo extends TraversableMustache {
 *        var $one = array(
 *            'two' => array(
 *                'three' => 'wheee!'
 *            )
 *        );
 *        
 *        protected $template = '{{one.two.three}}';
 *    }
 *    $foo = new Foo;
 *    print $foo;
 * @endcode
 *
 * (The above code prints 'wheee!')
 * 
 * @extends Mustache
 */
class TraversableMustache extends Mustache {
	
	/**
	 * Override default getVariable method to allow object traversal via dots.
	 * This might be cool. Also, might be heinous.
	 * 
	 * @access protected
	 * @param string $tag_name
	 * @param array &$context
	 * @return string
	 */
	protected function getVariable($tag_name, &$context) {
		$chunks = explode('.', $tag_name);
		$first = array_shift($chunks);
		
		$ret = parent::getVariable($first, $context);
		while ($next = array_shift($chunks)) {
			$c = array($ret);
			$ret = parent::getVariable($next, $c);
		}
		
		return $ret;
	}
}