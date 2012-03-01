<?php

namespace Mustache;

/**
 * Mustache Template Loader interface.
 */
interface Loader {

	/**
	 * Load a Template by name.
	 *
	 * @param string $name
	 *
	 * @return string Mustache Template source
	 */
	function load($name);
}
