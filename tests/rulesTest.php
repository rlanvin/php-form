<?php

class rulesTest extends PHPUnit_Framework_TestCase
{
	public function testBool()
	{
		// see http://stackoverflow.com/questions/13846769/php-in-array-0-value
		$value = 0;
		$this->assertTrue(Form\Rule\bool($value), 'int 0 is valid boolean and is false');
		$this->assertEquals('0',$value);

		$value = 1;
		$this->assertTrue(Form\Rule\bool($value), 'int 1 is valid boolean and is true');
		$this->assertEquals('1',$value);
	}

	public function testDate()
	{
		$this->assertTrue(Form\Rule\date('2014-01-01'));
		$this->assertTrue(Form\Rule\date('2014-01-01 00:00:00'));
		$this->assertFalse(Form\Rule\date(array()));
	}

	public function testEmail()
	{
		$this->assertTrue(Form\Rule\email('valid@email.com'));
		$this->assertFalse(Form\Rule\email('Some random garbage'));
		$this->assertFalse(Form\Rule\email(array()));
	}

	public function testIn()
	{
		$this->assertTrue(Form\Rule\in('foo', array('foo', 'bar')));
		$this->assertFalse(Form\Rule\in('foobar', array('foo', 'bar')));
		$this->assertTrue(Form\Rule\in('1', array(1, 2)));
		$this->assertTrue(Form\Rule\in(42, array(4, 2, 42)));
		$this->assertTrue(Form\Rule\in(42, array('4', '2', '42')));
		$this->assertFalse(Form\Rule\in(42, array(4, 2)));

		$object = new Stdclass();
		$this->assertTrue(Form\Rule\in($object, array($object)));
		$this->assertFalse(Form\Rule\in($object, array('something')));

		$value = array('foo', 'bar');
		$this->assertTrue(Form\Rule\in($value, array('foo', 'bar', 'foobar')));
		$this->assertFalse(Form\Rule\in($value, array('foo')));
	}

	public function testInKeys()
	{
		$this->assertTrue(Form\Rule\in_keys('foo', array('foo' => 'XX', 'bar' => 'XX')));
		$this->assertFalse(Form\Rule\in_keys('foobar', array('foo' => 'XX', 'bar' => 'XX')));
		$this->assertTrue(Form\Rule\in_keys('1', array(1 => 'Foo', 2 => 'Bar')));
		$this->assertTrue(Form\Rule\in_keys(1, array(1 => 'Foo', 2 => 'Bar')));
		$this->assertFalse(Form\Rule\in_keys(42, array(4, 2)));

		$object = new Stdclass();
		$this->assertFalse(Form\Rule\in_keys($object, array($object)));
		$this->assertFalse(Form\Rule\in_keys($object, array('something')));

		$value = array('foo', 'bar');
		$this->assertTrue(Form\Rule\in_keys($value, array('foo' => 'XX', 'bar' => 'XX', 'foobar' => 'XX')));
		$this->assertFalse(Form\Rule\in_keys($value, array('foo' => 'XX')));
	}

	public function testIsArray()
	{
		$this->assertTrue(Form\Rule\is_array(array()));
		$this->assertTrue(Form\Rule\is_array(array('foobar')));
		$this->assertFalse(Form\Rule\is_array('foobar'));
		$this->assertFalse(Form\Rule\is_array(null));
		$this->assertFalse(Form\Rule\is_array(42));
	}

	public function testMaxLength()
	{
		$this->assertTrue(Form\Rule\max_length('1234', 10));
		$this->assertTrue(Form\Rule\max_length('1234', 4));
		$this->assertTrue(Form\Rule\max_length('é', 1));
		$this->assertTrue(Form\Rule\max_length('1234', '10'));
		$this->assertTrue(Form\Rule\max_length(1234, 10));
		$this->assertTrue(Form\Rule\max_length(1234, '10'));
		$this->assertFalse(Form\Rule\max_length('1234', 2));
		$this->assertFalse(Form\Rule\max_length(array(), 2));
		$this->assertFalse(Form\Rule\max_length(null, 2));
		$this->assertFalse(Form\Rule\max_length(false, 2));
		$this->assertFalse(Form\Rule\max_length(new stdClass(), 2));
		$invalid = array(array(),new stdClass(),null,42.5,'abc');
		foreach ( $invalid as $length) {
			try {
				Form\Rule\max_length('something', $length);
				$this->fail('Expected InvalidArgumentException has not be thrown');
			} catch (InvalidArgumentException $e) { }
		}
	}

	public function testMinLength()
	{
		$this->assertTrue(Form\Rule\min_length('1234', 2));
		$this->assertTrue(Form\Rule\min_length('1234', 4));
		$this->assertFalse(Form\Rule\min_length('é', 2));
		$this->assertTrue(Form\Rule\min_length('1234', '2'));
		$this->assertTrue(Form\Rule\min_length(1234, 2));
		$this->assertTrue(Form\Rule\min_length(1234, '2'));
		$this->assertFalse(Form\Rule\min_length('1234', 10));
		$this->assertFalse(Form\Rule\min_length(array(), 2));
		$this->assertFalse(Form\Rule\min_length(null, 2));
		$this->assertFalse(Form\Rule\min_length(false, 2));
		$this->assertFalse(Form\Rule\min_length(new stdClass(), 2));
		$invalid = array(array(),new stdClass(),null,42.5,'abc');
		foreach ( $invalid as $length) {
			try {
				Form\Rule\max_length('something', $length);
				$this->fail('Expected InvalidArgumentException has not be thrown');
			} catch (InvalidArgumentException $e) { }
		}
	}

	public function testRegexp()
	{
		$regexp = '/^[0-9a-zA-Z\-]*$/';
		$this->assertTrue(Form\Rule\regexp('this-is-valid', $regexp));
		$this->assertTrue(Form\Rule\regexp(42, $regexp));
		$this->assertFalse(Form\Rule\regexp('This is not!', $regexp));
		$this->assertFalse(Form\Rule\regexp(array(), $regexp));
		$this->assertFalse(Form\Rule\regexp(false, $regexp));
		$this->assertFalse(Form\Rule\regexp(null, $regexp));
		$this->assertFalse(Form\Rule\regexp(new stdClass(), $regexp));
		$this->assertFalse(Form\Rule\regexp(42.5, $regexp));

		$invalid = array(array(),new stdClass(),null,42,42.5,'');
		foreach ( $invalid as $regexp) {
			try {
				Form\Rule\regexp('something', $regexp);
				$this->fail('Expected InvalidArgumentException has not be thrown');
			} catch (InvalidArgumentException $e) { }
		}
	}

	public function testTime()
	{
		$this->assertTrue(Form\Rule\time('14:14'));
		$this->assertFalse(Form\Rule\time('25:00'));
		$this->assertFalse(Form\Rule\time('00:61'));
		$this->assertFalse(Form\Rule\time(-1));
		$this->assertFalse(Form\Rule\time(array()));
		$this->assertFalse(Form\Rule\time(new stdClass()));
		$this->assertFalse(Form\Rule\time(false));
		$this->assertFalse(Form\Rule\time(null));
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
			$this->assertTrue(Form\Rule\trim($original), "trim($original) is true");
			$this->assertEquals($validated, $original);
		}
		foreach ( $invalid as $value ) {
			$this->assertFalse(Form\Rule\trim($value));
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
			$this->assertEquals($valid, Form\Rule\url($url));
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
			$this->assertTrue(Form\Rule\numeric($value));
		}
		foreach ( $invalid as $value ) {
			$this->assertFalse(Form\Rule\numeric($value));
		}
	}
}
