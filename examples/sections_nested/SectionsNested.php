<?php

class SectionsNested extends Mustache {
	public $name = 'Little Mac';

	public function enemies() {
		return array(
			array(
				'name' => 'Von Kaiser',
				'enemies' => array(
					array('name' => 'Super Macho Man'),
					array('name' => 'Piston Honda'),
					array('name' => 'Mr. Sandman'),
				)
			),
			array(
				'name' => 'Mike Tyson',
				'enemies' => array(
					array('name' => 'Soda Popinski'),
					array('name' => 'King Hippo'),
					array('name' => 'Great Tiger'),
					array('name' => 'Glass Joe'),
				)
			),
			array(
				'name' => 'Don Flamenco',
				'enemies' => array(
					array('name' => 'Bald Bull'),
				)
			),
		);
	}
}