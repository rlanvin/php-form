<?php

class ValidatorTest extends PHPUnit_Framework_TestCase
{
	public function testBool()
	{
		// see http://stackoverflow.com/questions/13846769/php-in-array-0-value
		$value = 0;
		$this->assertTrue(Validator::bool($value), 'int 0 is valid boolean and is false');
		$this->assertEquals('0',$value);

		$value = 1;
		$this->assertTrue(Validator::bool($value), 'int 1 is valid boolean and is true');
		$this->assertEquals('1',$value);
	}

	public function testDate()
	{
		$this->assertTrue(Validator::date('2014-01-01'));
		$this->assertTrue(Validator::date('2014-01-01 00:00:00'));
		$this->assertFalse(Validator::date(array()));
	}

	public function testEmail()
	{
		$this->assertTrue(Validator::email('valid@email.com'));
		$this->assertFalse(Validator::email('Some random garbage'));
		$this->assertFalse(Validator::email(array()));
	}

	public function testIn()
	{
		$this->assertTrue(Validator::in('foo', array('foo', 'bar')));
		$this->assertFalse(Validator::in('foobar', array('foo', 'bar')));
		$this->assertTrue(Validator::in('1', array(1, 2)));
		$this->assertTrue(Validator::in(42, array(4, 2, 42)));
		$this->assertTrue(Validator::in(42, array('4', '2', '42')));
		$this->assertFalse(Validator::in(42, array(4, 2)));

		$object = new Stdclass();
		$this->assertTrue(Validator::in($object, array($object)));
		$this->assertFalse(Validator::in($object, array('something')));

		$value = array('foo', 'bar');
		$this->assertTrue(Validator::in($value, array('foo', 'bar', 'foobar')));
		$this->assertFalse(Validator::in($value, array('foo')));
	}

	public function testInKeys()
	{
		$this->assertTrue(Validator::in_keys('foo', array('foo' => 'XX', 'bar' => 'XX')));
		$this->assertFalse(Validator::in_keys('foobar', array('foo' => 'XX', 'bar' => 'XX')));
		$this->assertTrue(Validator::in_keys('1', array(1 => 'Foo', 2 => 'Bar')));
		$this->assertTrue(Validator::in_keys(1, array(1 => 'Foo', 2 => 'Bar')));
		$this->assertFalse(Validator::in_keys(42, array(4, 2)));

		$object = new Stdclass();
		$this->assertFalse(Validator::in_keys($object, array($object)));
		$this->assertFalse(Validator::in_keys($object, array('something')));

		$value = array('foo', 'bar');
		$this->assertTrue(Validator::in_keys($value, array('foo' => 'XX', 'bar' => 'XX', 'foobar' => 'XX')));
		$this->assertFalse(Validator::in_keys($value, array('foo' => 'XX')));
	}

	public function testMaxLength()
	{
		$this->assertTrue(Validator::max_length('1234', 10));
		$this->assertTrue(Validator::max_length('1234', 4));
		$this->assertTrue(Validator::max_length('é', 1));
		$this->assertTrue(Validator::max_length('1234', '10'));
		$this->assertTrue(Validator::max_length(1234, 10));
		$this->assertTrue(Validator::max_length(1234, '10'));
		$this->assertFalse(Validator::max_length('1234', 2));
		$this->assertFalse(Validator::max_length(array(), 2));
		$this->assertFalse(Validator::max_length(null, 2));
		$this->assertFalse(Validator::max_length(false, 2));
		$this->assertFalse(Validator::max_length(new stdClass(), 2));
		$invalid = array(array(),new stdClass(),null,42.5,'abc');
		foreach ( $invalid as $length) {
			try {
				Validator::max_length('something', $length);
				$this->fail('Expected InvalidArgumentException has not be thrown');
			} catch (InvalidArgumentException $e) { }
		}
	}

	public function testMinLength()
	{
		$this->assertTrue(Validator::min_length('1234', 2));
		$this->assertTrue(Validator::min_length('1234', 4));
		$this->assertFalse(Validator::min_length('é', 2));
		$this->assertTrue(Validator::min_length('1234', '2'));
		$this->assertTrue(Validator::min_length(1234, 2));
		$this->assertTrue(Validator::min_length(1234, '2'));
		$this->assertFalse(Validator::min_length('1234', 10));
		$this->assertFalse(Validator::min_length(array(), 2));
		$this->assertFalse(Validator::min_length(null, 2));
		$this->assertFalse(Validator::min_length(false, 2));
		$this->assertFalse(Validator::min_length(new stdClass(), 2));
		$invalid = array(array(),new stdClass(),null,42.5,'abc');
		foreach ( $invalid as $length) {
			try {
				Validator::max_length('something', $length);
				$this->fail('Expected InvalidArgumentException has not be thrown');
			} catch (InvalidArgumentException $e) { }
		}
	}

	public function testRegexp()
	{
		$regexp = '/^[0-9a-zA-Z\-]*$/';
		$this->assertTrue(Validator::regexp('this-is-valid', $regexp));
		$this->assertTrue(Validator::regexp(42, $regexp));
		$this->assertFalse(Validator::regexp('This is not!', $regexp));
		$this->assertFalse(Validator::regexp(array(), $regexp));
		$this->assertFalse(Validator::regexp(false, $regexp));
		$this->assertFalse(Validator::regexp(null, $regexp));
		$this->assertFalse(Validator::regexp(new stdClass(), $regexp));
		$this->assertFalse(Validator::regexp(42.5, $regexp));

		$invalid = array(array(),new stdClass(),null,42,42.5,'');
		foreach ( $invalid as $regexp) {
			try {
				Validator::regexp('something', $regexp);
				$this->fail('Expected InvalidArgumentException has not be thrown');
			} catch (InvalidArgumentException $e) { }
		}
	}

	public function testTime()
	{
		$this->assertTrue(Validator::time('14:14'));
		$this->assertFalse(Validator::time('25:00'));
		$this->assertFalse(Validator::time('00:61'));
		$this->assertFalse(Validator::time(-1));
		$this->assertFalse(Validator::time(array()));
		$this->assertFalse(Validator::time(new stdClass()));
		$this->assertFalse(Validator::time(false));
		$this->assertFalse(Validator::time(null));
	}

	public function testTrim()
	{
		$valid = array(
			'   trim me   ' => 'trim me',
			'42' => '42',
			42 => '42',
			"trim\t\n\r" => 'trim',
			"\t" => ''
		);
		$invalid = array(
			array(),
			new stdClass(),
			null,
			42.5,
		);

		foreach ( $valid as $original => $validated ) {
			$this->assertTrue(Validator::trim($original), "trim($original) is true");
			$this->assertEquals($validated, $original);
		}
		foreach ( $invalid as $value ) {
			$this->assertFalse(Validator::trim($value));
		}
	}

	public function testUrl()
	{
		$testUrls = array(
			'www.asdf.com' => false,
			'example.org' => false,
			'/peach.kingdom' => false,
			'x.something.co.uk' => false,
			'http:myname.com' => false,
			'https://www.sdf.org' => true
		);

		foreach ( $testUrls as $url => $valid ) {
			$this->assertEquals($valid, Validator::url($url));
		}
	}

	public function testNumeric()
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
