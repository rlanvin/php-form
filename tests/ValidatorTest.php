<?php

class ValidatorTest extends PHPUnit_Framework_TestCase
{
	public function testValidatorNumeric()
	{
		$valid = array(
			42,
			'42',
			-42,
			'-42',
			42.5,
			'42.5'
		);
		$invalid = array(
			'abc'
		);

		foreach ( $valid as $value ) {
			$this->assertTrue(Validator::numeric($value));
		}
		foreach ( $invalid as $value ) {
			$this->assertFalse(Validator::numeric($value));
		}

	}
}
