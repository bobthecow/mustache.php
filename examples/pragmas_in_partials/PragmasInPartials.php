<?php

class PragmasInPartials extends Mustache {
	public $say = '< RAWR!! >';
	protected $_partials = array(
		'dinosaur' => '{{say}}'
	);
}