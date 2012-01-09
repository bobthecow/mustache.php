#!/usr/bin/env php
<?php

/**
 * A commandline script to create an example and the needed files:
 *
 *     $ bin/create_example.php my_new_example
 *
 * ... and the folder my_new_example will be created in the examples/ folder containing 3 files:
 *
 *     my_new_example/my_new_example.mustache
 *     my_new_example/my_new_example.txt
 *     my_new_example/MyNewExample.php
 */

// some constants
define('USAGE', <<<USAGE
USAGE: {$argv[0]} example_name

This creates a new example and the corresponding files in the examples/ directory

USAGE
);

define('DS', DIRECTORY_SEPARATOR);
define('EXAMPLE_PATH', realpath(dirname(__FILE__) . DS . ".." . DS . "examples"));


/**
 * transform a string to lowercase using underlines.
 * Examples:
 * String -> string
 * AString -> a_string
 * SomeStrings -> some_strings
 * AStringMore -> a_string_more
 *
 * @param string $name
 * @access public
 * @return string
 */
function getLowerCaseName($name) {
	return preg_replace_callback("/([A-Z])/", create_function (
		'$match',
		'return "_" . strtolower($match[1]);'
	), lcfirst($name));
}

/**
 * transform a string to Uppercase (camelcase)
 * Examples
 * string -> String
 * a_string -> AString
 * some_strings -> SomeStrings
 * a_string_more -> AStringMore -> a_string_more
 *
 * @param string $name
 * @access public
 * @return string
 */
function getUpperCaseName($name) {
	return preg_replace_callback("/_([a-z])/", create_function (
		'$match',
		'return strtoupper($match{1});'
	), ucfirst($name));
}


/**
 * return the given value and echo it out appending "\n"
 *
 * @param mixed $value
 * @access public
 * @return mixed
 */
function out($value) {
	echo $value . "\n";
	return $value;
}

/**
 * create Path for certain files in an example
 * returns the directory name if only $directory is given.
 * if an extension is given a complete filename is returned.
 * the returned filename will be echoed out
 *
 * @param string $directory directory without / at the end
 * @param string $filename filename without path and extension
 * @param string $extension extension of the file without "."
 * @access public
 * @return string
 */
function buildPath($directory, $filename = null,  $extension = null) {
	return out(EXAMPLE_PATH . DS . $directory.
					($extension !== null && $filename !== null ? DS . $filename. "." . $extension : ""));
}

/**
 * creates the directory for the example
 * the script die()'s if mkdir() fails
 *
 * @param string $directory
 * @access public
 * @return void
 */
function createDirectory($directory) {
	if(!@mkdir(buildPath($directory))) {
		die("FAILED to create directory\n");
	}
}

/**
 * create a file for the example with the given $content
 * the script die()'s if fopen() fails
 *
 * @param string $directory directory without / at the end
 * @param string $filename filename without path and extension
 * @param string $extension extension of the file without "."
 * @param string $content the content of the file
 * @access public
 * @return void
 */
function createFile($directory, $filename, $extension, $content = "") {
	$handle = @fopen(buildPath($directory, $filename, $extension), "w");
	if($handle) {
		fwrite($handle, $content);
		fclose($handle);
	} else {
		die("FAILED to create file\n");
	}
}


/**
 * routine to create the example directory and 3 files
 *
 * if the $example_name is "SomeThing" the following files will be created
 * examples/some_thing
 * examples/some_thing/some_thing.mustache
 * examples/some_thing/some_thing.txt
 * examples/some_thing/SomeThing.php
 *
 * @param mixed $example_name
 * @access public
 * @return void
 */
function main($example_name) {
	$lowercase = getLowerCaseName($example_name);
	$uppercase = getUpperCaseName($example_name);
	createDirectory($lowercase);
	createFile($lowercase, $lowercase, "mustache");
	createFile($lowercase, $lowercase, "txt");
	createFile($lowercase, $uppercase, "php", <<<CONTENT
<?php

class {$uppercase} extends Mustache {

}

CONTENT
	);
}

// check if enougth arguments are given
if(count($argv) > 1) {
	// get the name of the example
	$example_name = $argv[1];

	main($example_name);

} else {
	echo USAGE;
}
