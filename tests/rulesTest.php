<?php

require_once __DIR__.'/../src/rules.php';

class rulesTest extends PHPUnit_Framework_TestCase
{
///////////////////////////////////////////////////////////////////////////////
// Core type rules

	public function dataTypes()
	{
		return [
			// data           empty string  array
			['',               true,  true, false],
			[null,             true, false, false],
			[0,               false, false, false],
			[42,              false, false, false],
			[4.2,             false, false, false],
			['42',            false,  true, false],
			[true,            false, false, false],
			[false,           false, false, false],
			[array(),          true, false,  true],
			[array('foobar'), false, false,  true],
			['foobar',        false,  true, false],
			[new stdClass(),  false, false, false],
		];
	}

	/**
	 * @dataProvider dataTypes
	 */
	public function testIsArray($value, $is_empty, $is_string, $is_array)
	{
		$this->assertEquals($is_array, Form\Rule\is_array($value));
	}

	/**
	 * @dataProvider dataTypes
	 */
	public function testIsEmpty($value, $is_empty, $is_string, $is_array)
	{
		$this->assertEquals($is_empty, Form\Rule\is_empty($value));
	}

	/**
	 * @dataProvider dataTypes
	 */
	public function testIsString($value, $is_empty, $is_string, $is_array)
	{
		$this->assertEquals($is_string, Form\Rule\is_string($value));
	}

///////////////////////////////////////////////////////////////////////////////
// Type rules

	public function boolValues()
	{
		return array(
			// value   is_bool casted 
			[0,          true,     0],
			[false,      true, false],
			['0',        true,   '0'],
			['false',    true,   '0'],
			['f',        true,   '0'],
			['no',       true,   '0'],
			['n',        true,   '0'],
			['off',      true,   '0'],
	
			[1,          true,     1],
			[true,       true,  true],
			['1',        true,   '1'],
			['true',     true,   '1'],
			['t',        true,   '1'],
			['yes',      true,   '1'],
			['y',        true,   '1'],
			['on',       true,   '1'],


			[0.1,       false,      0.1],
			[2,         false,        2],
			['foobar',  false, 'foobar'],
			[null,      false,     null],
			[array(),   false,  array()],
			[new stdClass(), false, new stdClass()],
		);
	}

	/**
	 * @dataProvider boolValues
	 */
	public function testBool($value, $is_bool, $casted_value)
	{
		$this->assertEquals($is_bool, Form\Rule\bool($value));
		$this->assertEquals($casted_value, $value);
	}

	/**
	 * @dataProvider boolValues
	 */
	public function testBoolNoCast($value, $is_bool, $casted_value)
	{
		$original_value = $value;
		$this->assertEquals($is_bool, Form\Rule\bool($value, false));
		$this->assertEquals($original_value, $value);
	}

	/**
	 * @dataProvider boolValues
	 */
	public function testBoolCastBool($value, $is_bool, $casted_value)
	{
		$this->assertEquals($is_bool, Form\Rule\bool($value, 'bool'));
		if ( $is_bool ) {
			$this->assertInternalType('bool', $value);
			$this->assertEquals((bool) $casted_value, $value);
		}
		else {
			$this->assertEquals($casted_value, $value);
		}
	}

	/**
	 * @dataProvider boolValues
	 */
	public function testBoolCastString($value, $is_bool, $casted_value)
	{
		$this->assertEquals($is_bool, Form\Rule\bool($value, 'string'));
		if ( $is_bool ) {
			$this->assertInternalType('string', $value);
			$this->assertEquals($casted_value ? '1' : '0', $value);
		}
		else {
			$this->assertEquals($casted_value, $value);
		}
	}

	/**
	 * @dataProvider boolValues
	 */
	public function testBoolCastInt($value, $is_bool, $casted_value)
	{
		$this->assertEquals($is_bool, Form\Rule\bool($value, 'int'));
		if ( $is_bool ) {
			$this->assertInternalType('int', $value);
			$this->assertEquals($casted_value ? 1 : 0, $value);
		}
		else {
			$this->assertEquals($casted_value, $value);
		}
	}

	public function dateTimeValues()
	{
		return array(
			// value    format is_date   is_datetime    istime
			[0,           null, false,       false,      false],
			[array(),     null, false,       false,      false],
			['foobar',    null, false,       false,      false],
			[true,        null, false,       false,      false],
			[false,       null, false,       false,      false],

			['2015-12-12',    null,  true,   false,      false],
			['2015-12-12', 'Y-m-d',  true,   false,      false],
			['2015-13-12',   false, false,   false,      false],
			['2015-13-12', 'Y-m-d', false,   false,      false],
			['2015-13-12', 'Y-d-m',  true,   false,      false],
			['2015-12-12', 'd-m-Y', false,   false,      false],

			['2015-12-12 00:00:00',          null,  true,   true,      false],
			['2015-12-12 00:00:00', 'Y-m-d H:i:s',  true,   true,      false],
			['2015-12-12 42:00:00',          null, false,   false,     false],
			['2015-12-12 42:00:00', 'Y-m-d H:i:s', false,   false,     false],

			['12:00',     null,   true,   false,      true],
			['42:00',     null,  false,   false,     false],
			['12:00:00',  null,   true,   false,     false],
			['12:00:00', 'H:i:s', true,   false,     false],
		);
	}

	/**
	 * @dataProvider dateTimeValues
	 */
	public function testDate($value, $format, $is_date)
	{
		$this->assertEquals($is_date, Form\Rule\date($value, $format));
	}

	/**
	 * @dataProvider dateTimeValues
	 */
	public function testDatetime($value, $format, $is_date, $is_datetime)
	{
		$this->assertEquals($is_datetime, Form\Rule\datetime($value));
	}

	/**
	 * @dataProvider dateTimeValues
	 */
	public function testTime($value, $format, $is_date, $is_datetime, $is_time)
	{
		$this->assertEquals($is_time, Form\Rule\time($value));
	}

	public function numericValues()
	{
		return array(
			//value    numeric integer intl_integer    float    intl_decimal
			[0,        true,  true, ['fr_FR' => 0], true, ['fr_FR' => 0]],
			['0',      true,  true, ['fr_FR' => 0], true, ['fr_FR' => 0]],
			[42,       true,  true, ['fr_FR' => 42], true, ['fr_FR' => 42]],
			['42',     true,  true, ['fr_FR' => 42], true, ['fr_FR' => 42]],
			[-42,      true,  true, ['fr_FR' => -42], true, ['fr_FR' => -42]],
			['-42',    true,  true, ['fr_FR' => -42], true, ['fr_FR' => -42]],
			[42.5,     true, false, ['fr_FR' => false, 'en_US' => false], true, ['en_US' => 42.5, 'fr_FR' => false]],
			['42.5',   true, false, ['en_US' => false, 'fr_FR' => false], true, ['en_US' => 42.5, 'fr_FR' => false]],
			['42,5',  false, false, ['en_US' => false, 'fr_FR' => false], false, ['en_US' => false, 'fr_FR' => 42.5]],
			['1 000', false, false, ['en_GB' => 1000, 'fr_FR' => 1000], false, ['en_GB' => 1000, 'fr_FR' => 1000]],
			['1.000',  true, false, ['en_GB' => false, 'fr_FR' => 1000], true, ['en_GB' => 1.0, 'fr_FR' => 1000]],
			['1,000', false, false, ['en_GB' => 1000, 'fr_FR' => false], false, ['en_GB' => 1000, 'fr_FR' => 1.0]],
			// sadly, this is valid in en_GB (shouldn't be)
			['1.000,42', false, false, ['en_GB' => 1, 'fr_FR' => false], false, ['en_GB' => 1, 'fr_FR' => 1000.42]],
			['1 000,42', false, false, ['en_GB' => 1000, 'fr_FR' => false], false, ['en_GB' => 1000, 'fr_FR' => 1000.42]],
			['1000,42',  false, false, ['en_GB' => false, 'fr_FR' => false], false, ['en_GB' => false, 'fr_FR' => 1000.42]],

			[null,     false, false, false, false, false],
			[array(),  false, false, false, false, false],
			['foobar', false, false, false, false, false]
		);
	}

	/**
	 * @dataProvider numericValues
	 */
	public function testNumeric($value, $is_numeric)
	{
		$this->assertEquals($is_numeric, Form\Rule\numeric($value));
	}

	/**
	 * @dataProvider numericValues
	 */
	public function testInteger($value, $is_numeric, $is_integer)
	{
		$this->assertEquals($is_integer, Form\Rule\integer($value));
	}

	/**
	 * @dataProvider numericValues
	 */
	public function testDecimal($value, $is_numeric, $is_integer, $locales, $is_decimal)
	{
		$this->assertEquals($is_decimal, Form\Rule\decimal($value));
	}

	/**
	 * @dataProvider numericValues
	 */
	public function testIntlDecimal($value, $is_numeric, $is_integer, $int_locales, $is_float, $locales)
	{
		$original_value = $value;
		if ( $locales ) {
			foreach ( $locales as $locale => $float_version ) {
				if ( $float_version === false ) {
					$this->assertFalse(Form\Rule\intl_decimal($value, $locale), "$original_value is not a decimal in $locale (is it $value)");
				}
				else {
					$this->assertTrue(Form\Rule\intl_decimal($value, $locale), "$original_value is a valid decimal in $locale");
					$this->assertEquals($float_version, $value);
				}
				$value = $original_value;
			}
		}
		else {
			$this->assertFalse(Form\Rule\intl_decimal($value));
		}
	}

	public function emailValues()
	{
		return array(
			['valid@email.com', true],
			['valid+sep@email.com', true],
			['foobar', false],
			[array(), false],
		);
	}

	/**
	 * @dataProvider emailValues
	 */
	public function testEmail($value, $is_email)
	{
		$this->assertEquals($is_email, Form\Rule\email($value));
	}

	public function urlValues()
	{
		return array(
			['www.asdf.com',        null, false],
			['example.org',         null, false],
			['/peach.kingdom',      null, false],
			['x.something.co.uk',   null, false],
			['http:myname.com',     null, false],
			['https://www.sdf.org',  null, true],
			['https://www.sdf.org',  'http', false],
			['http://www.foo.bar/?f=42',  null, true],
			['http://user:password@www.foo.bar/?f=42',  null, true],
			['ssh://github.com',     null, true],
			['ssh://github.com',     'http', false],
			['ssh://github.com',     ['http','ssh'], true],
			['mailto://foo@bar.com', null, true]
		);
	}

	/** 
	 * @dataProvider urlValues
	 */
	public function testUrl($value, $protocols, $is_url)
	{
		$this->assertEquals($is_url, Form\Rule\url($value, $protocols));
	}

	public function ipValues()
	{
		return array(
			//               v4     v6
			['foobar',     false, false],
			[0,            false, false],
			['0',          false, false],
			[array(),      false, false],
			['4294967295', false, false],

			['  127.0.0.1  ',      false, false], // not trimmed by default

			['127.0.0.1',          true, false],
			['2a01:8200::',        false, true],
			['::ffff:192.0.2.128', false, true]
		);
	}

	/**
	 * @dataProvider ipValues
	 */
	public function testIp($value, $ipv4, $ipv6)
	{
		$this->assertEquals($ipv4 || $ipv6, Form\Rule\ip($value));
	}

	/**
	 * @dataProvider ipValues
	 */
	public function testIpv4($value, $ipv4, $ipv6)
	{
		$this->assertEquals($ipv4, Form\Rule\ipv4($value));
	}

	/**
	 * @dataProvider ipValues
	 */
	public function testIpv6($value, $ipv4, $ipv6)
	{
		$this->assertEquals($ipv6, Form\Rule\ipv6($value));
	}

///////////////////////////////////////////////////////////////////////////////
// Value rules

	public function inValues()
	{
		return array(
			// value, in, array
			['foo',     true, array('foo','bar')],
			['foobar', false, array('foo','bar')],
			['1',       true, array(1,2)],
			[42,        true, array(4,2,42)],
			[42,        true, array('4','2','42')],
			[42,       false, array(4,2)],
			[0,         true, array(0,1)],
			[new stdClass(),  true, array(new stdClass())],
			[new stdClass(), false, array('something')],
			[array('foo','bar'), true, array('foo','bar','foobar')],
			[array('foo','bar'), false, array('foo')],
		);
	}

	/**
	 * @dataProvider inValues
	 */
	public function testIn($value, $is_in, $array)
	{
		$this->assertEquals($is_in, Form\Rule\in($value, $array));
	}

	public function inKeysValues()
	{
		return array(
			// value, in, array
			['foo',     true, array('foo' => 'XX', 'bar' => 'XX')],
			['foo',     false, array('foo','bar')],
			['foobar', false, array('foo' => 'XX', 'bar' => 'XX')],
			['1',       true, array(1 => 'Foo', 2 => 'Bar')],
			[1,        true, array(1 => 'Foo', 2 => 'Bar')],
			[42,       false, array(42)],
			[new stdClass(),  false, array(new stdClass())],
			[new stdClass(), false, array('something')],
			[array('foo','bar'), false, array('foo','bar','foobar')],
			[array('foo','bar'), true, array('foo' => 'XX', 'bar' => 'XX', 'foobar' => 'XX')],
			[array('foo','bar'), false, array('foo')],
			[array('foo','bar'), false, array('foo' => 'XX')],
		);
	}

	/**
	 * @dataProvider inKeysValues()
	 */
	public function testInKeys($value, $is_in_keys, $array)
	{
		$this->assertEquals($is_in_keys, Form\Rule\in_keys($value, $array));
	}


	public function minMaxValues()
	{
		return array(
			// value    between     min      max  between
			[42,         [0,50],  true,    true,    true],
			[42,       [50,100], false,    true,   false],
			[42,         [0,30],  true,   false,   false],

			[42,        [42,42],  true,    true,    true],
			[42,      [null,50],  true,    true,    true],
			[42,      [40,null],  true,    true,    true],

			[-42,      [-50,0],     true,    true,    true],
			[-42,      [0,50],     false,    true,   false],
			[-42,      [-100,-50],  true,   false,   false],

			// with numeric string
			['42',         [0,50],  true,    true,    true],
			['42',       [50,100], false,    true,   false],
			['42',         [0,30],  true,   false,   false],

			// with dates (this is basic)
			['2015-02-20', ['2015-01-01','2015-12-31'],   true,    true,    true],
			['2015-02-20', ['2015-01-01','2015-02-15'],   true,   false,   false],
			['2015-02-20', ['2015-03-01','2015-12-31'],  false,    true,   false],

			// with times
			['13:00', ['00:00','23:59'],   true,    true,    true],
			['13:00', ['00:00','12:00'],   true,   false,   false],
			['13:00', ['15:00','23:59'],  false,    true,   false],

			// with strings is not recommended
			['b',     ['a','z'],  true,    true,    true],
			['a',     ['a','z'],  true,    true,    true],
			['z',     ['a','e'],  true,    false,  false],
			['é',     ['a','z'],  true,    false,  false], // unexpected
			['abc', ['aaa','zzzz'], true,  true,  true],
		);
	}

	/**
	 * @dataProvider minMaxValues
	 */
	public function testValueMin($value, $between, $is_valid)
	{
		list($min,$max) = $between;
		if ( $min !== null ) {
			$this->assertEquals($is_valid, Form\Rule\min($value, $min));
		}
	}

	/**
	 * @dataProvider minMaxValues
	 */
	public function testValueMax($value, $between, $min_valid, $is_valid)
	{
		list($min,$max) = $between;
		if ( $max !== null ) {
			$this->assertEquals($is_valid, Form\Rule\max($value, $max));
		}
	}

	/**
	 * @dataProvider minMaxValues
	 */
	public function testValueBetween($value, $between, $min_valid, $max_valid, $is_valid)
	{
		$this->assertEquals($is_valid, Form\Rule\between($value, $between));
	}

	public function lengthValues()
	{
		return array(
			// value     between    min    max    between
			['1234',    [4,10],    true,   true,    true],
			['1234',    [10,20],  false,   true,    false],
			['1234',    [1,3],     true,  false,    false],

			[1234,    [4,10],    true,   true,    true],
			[1234,    [10,20],  false,   true,   false],
			[1234,    [1,3],     true,  false,   false],

			['é',    [1,1],     true,  true,    true],

			[array(),    [1,1],     false,  false,    false],
			[null,    [1,1],     false,  false,    false],
			[false,    [1,1],     false,  false,    false],
			[new stdClass(),    [1,1],     false,  false,    false],
		);
	}

	public function invalidLengths()
	{
		return array(
			[null],
			[new stdClass()],
			[array()],
			[-1]
		);
	}

	/**
	 * @dataProvider lengthValues
	 */
	public function testMinLength($value, $between, $is_valid)
	{
		list($min,$max) = $between;
		if ( $min !== null ) {
			$this->assertEquals($is_valid, Form\Rule\min_length($value, $min));
		}
	}

	/**
	 * @dataProvider lengthValues
	 */
	public function testMaxLength($value, $between, $min_valid, $is_valid)
	{
		list($min,$max) = $between;
		if ( $max !== null ) {
			$this->assertEquals($is_valid, Form\Rule\max_length($value, $max));
		}
	}

	/**
	 * @dataProvider lengthValues
	 */
	public function testLength($value, $between, $min_valid, $max_valid, $is_valid)
	{
		$this->assertEquals($is_valid, Form\Rule\length($value, $between));
	}

	/**
	 * @dataProvider invalidLengths
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidMinLength($length)
	{
		Form\Rule\min_length('foobar', $length);
	}

	/**
	 * @dataProvider invalidLengths
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidMaxLength($length)
	{
		Form\Rule\max_length('foobar', $length);
	}

	/**
	 * @dataProvider invalidLengths
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidLength($length)
	{
		Form\Rule\length('foobar', $length);
	}

	public function regexpValues()
	{
		// $regexp = ;
		// $this->assertTrue(Form\Rule\regexp('this-is-valid', $regexp));
		// $this->assertTrue(Form\Rule\regexp(42, $regexp));
		// $this->assertFalse(Form\Rule\regexp('This is not!', $regexp));
		// $this->assertFalse(Form\Rule\regexp(array(), $regexp));
		// $this->assertFalse(Form\Rule\regexp(false, $regexp));
		// $this->assertFalse(Form\Rule\regexp(null, $regexp));
		// $this->assertFalse(Form\Rule\regexp(new stdClass(), $regexp));
		// $this->assertFalse(Form\Rule\regexp(42.5, $regexp));

		return array(
			// value                  regexp            is_valid
			['this-is-valid',  '/^[0-9a-zA-Z\-]*$/',     true],
			[42,               '/^[0-9a-zA-Z\-]*$/',     true],
			['This is not!',   '/^[0-9a-zA-Z\-]*$/',     false],
			[array(),          '/^[0-9a-zA-Z\-]*$/',     false],
			[false,            '/^[0-9a-zA-Z\-]*$/',     false],
			[null,             '/^[0-9a-zA-Z\-]*$/',     false],
			[new stdClass(),   '/^[0-9a-zA-Z\-]*$/',     false],
			[42.5,             '/^[0-9a-zA-Z\-]*$/',     false],
		);
	}

	public function invalidRegexps()
	{
		return array(
			[array()],
			[new stdClass()],
			[null],
			[42],
			[42.5],
			['']
		);
	}

	/**
	 * @dataProvider regExpValues
	 */
	public function testRegexp($value, $regexp, $is_valid)
	{
		$this->assertEquals($is_valid, Form\Rule\regexp($value, $regexp));
	}

	/**
	 * @dataProvider invalidRegexps
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidRegexps($regexp)
	{
		Form\Rule\regexp('something', $regexp);
	}

///////////////////////////////////////////////////////////////////////////////
// Special rules

	public function trimValues()
	{
		return array(
			['   trim me   ', 'trim me'],
			['42', '42'],
			[42, '42'],
			["trim\t\n\r", 'trim'],
			["\t", ''],

			[array(), array()],
			[new stdClass(), new stdClass()],
			[null, null],
			[42.5, 42.5]
		);
	}

	public function invalidTrim()
	{
		return array(
			[array()],
			[new stdClass()],
			[42.5],
		);
	}

	/**
	 * @dataProvider trimValues
	 */
	public function testTrim($value, $trimmed_value, $mask = null)
	{
		$original_value = $value;
		$this->assertTrue(Form\Rule\trim($value, $mask));
		$this->assertEquals($trimmed_value, $value);
	}

	/**
	 * @dataProvider invalidTrim
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidTrim($mask)
	{
		$value = 'foobar';
		Form\Rule\trim($value, $mask);
	}
}
