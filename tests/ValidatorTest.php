<?php

use Form\Validator;

class ValidatorTest extends PHPUnit_Framework_TestCase
{

///////////////////////////////////////////////////////////////////////////////
// Options

	public function testGetSetOptions()
	{
		$form = new Validator();
		$form->setOptions(array(
			'ignore_extraneous' => false,
			'allow_empty' => false
		));
		$option = $form->getOptions();
		$this->assertFalse($option['ignore_extraneous']);
		$this->assertFalse($option['allow_empty']);

		$form->setOptions(array(
			'ignore_extraneous' => true,
		));
		$option = $form->getOptions();
		$this->assertTrue($option['ignore_extraneous']);
		$this->assertFalse($option['allow_empty']);
	}

///////////////////////////////////////////////////////////////////////////////
// Rules

	public function validRules()
	{
		// return compressed and uncompressed
		return array(
			array(
				array('required', 'min_length' => 2),
				array('required' => true, 'min_length' => 2)
			),
			array(
				array('each' => array('required')),
				array('each' => array('required' => true))
			)
		);
	}

	public function invalidArguments()
	{
		return array(
			array(''),
			array(null),
			array(array()),
			array(new stdClass),
			array(42),
			array((double) 42)
		);
	}

	public function invalidRules()
	{
		$rules = array();
		foreach ( $this->invalidArguments() as $arg )
		{
			$rules[] = array($arg);
		}
		return $rules;
	}

	/**
	 * @dataProvider validRules
	 */
	public function testExpandRules($compressed, $uncompressed)
	{
		$this->assertEquals($uncompressed, Validator::expandRulesArray($compressed));
	}

	/**
	 * @dataProvider invalidRules
	 * @expectedException InvalidArgumentException
	 */
	public function testExpandRulesInvalid($rules)
	{
		Validator::expandRulesArray($rules);
	}

	/**
	 * @dataProvider validRules
	 */
	public function testGetRules($compressed, $uncompressed)
	{
		$form = new Validator();
		$this->assertEquals(array(), $form->getRules());
		$this->assertEquals(array(), $form->getRules('unset_field'));

		$rules = array('my_field' => $uncompressed);
		$form = new Validator($rules);
		$this->assertEquals($rules, $form->getRules());
		$this->assertEquals($rules['my_field'], $form->getRules('my_field'));
		$this->assertEquals(array(), $form->getRules('unset_field'));
	}

	/**
	 * @dataProvider invalidArguments
	 * @expectedException InvalidArgumentException
	 */
	public function testGetRulesInvalidArguments($argument)
	{
		// this is allowed in getRules()
		if ( $argument === '' ) {
			throw new InvalidArgumentException();
		}

		$form = new Validator();
		$form->getRules($argument);
	}

	public function testGetRulesNested()
	{
		$validator = new Validator([
			'a' => new Validator([
				'b' => ['required']
			])
		]);
		$this->assertEquals(new Validator([
			'b' => ['required']
		]), $validator->getRules('a'));

		$this->assertEquals(['required' => true], $validator->getRules('a[b]'));
		$this->assertEquals([], $validator->getRules('a[b][c]'));
		$this->assertEquals([], $validator->getRules('a[c]'));
		$this->assertEquals(true, $validator->getRules('a[b][required]')); // unintended side effect
	}

	/**
	 * @depends testGetRules
	 * @dataProvider validRules
	 */
	public function testSetRules($compressed, $uncompressed)
	{
		$form = new Validator();
		$form->setRules(array('name' => $compressed));
		$this->assertEquals(array('name' => $uncompressed), $form->getRules());

		$form = new Validator();
		$form->setRules('name', $compressed);
		$this->assertEquals(array('name' => $uncompressed), $form->getRules());
		$this->assertEquals($uncompressed, $form->getRules('name'));
	}

	public function testSetRulesSubValidator()
	{
		$form = new Validator();
		$form->setRules('address', new Validator([
			'street' => ['required'],
			'postcode' => ['required']
		]));
	}

	/**
	 * @dataProvider invalidRules
	 * @expectedException InvalidArgumentException
	 */
	public function testSetRulesInvalid($rules)
	{
		$form = new Validator();
		$form->setRules($rules);
	}

	/**
	 * @dataProvider invalidRules
	 * @expectedException InvalidArgumentException
	 */
	public function testSetRulesFieldInvalid($rules)
	{
		$form = new Validator();
		$form->setRules('name', $rules);
	}

	/**
	 * @dataProvider invalidArguments
	 * @expectedException BadMethodCallException
	 */
	public function testSetRulesInvalidArguments($argument)
	{
		// this is valid
		if ( $argument == array() || $argument === '' ) {
			throw new BadMethodCallException();
		}

		$form = new Validator();
		$form->setRules($argument);
	}

	/**
	 * @dataProvider invalidArguments
	 * @expectedException BadMethodCallException
	 */
	public function testSetRulesFieldInvalidArguments($argument)
	{
		// this is valid
		if ( $argument == array() || $argument === '') {
			throw new BadMethodCallException();
		}

		$form = new Validator();
		$form->setRules($argument, array());
	}

	/**
	 * @depends testGetRules
	 */
	public function testAddRules()
	{
		$form = new Validator([
			'first_name' => []
		]);
		$form->addRules([
			'last_name' => []
		]);
		$this->assertEquals([
			'first_name' => [],
			'last_name' => []
		], $form->getRules());

		$form = new Validator([
			'first_name' => ['required']
		]);
		$form->addRules([
			'first_name' => ['min_length' => 2]
		]);
		$this->assertEquals([
			'first_name' => ['required' => true, 'min_length' => 2]
		], $form->getRules());

		$form = new Validator([
			'first_name' => ['min_length' => 2]
		]);
		$form->addRules([
			'first_name' => ['required']
		]);
		$this->assertEquals([
			'first_name' => ['required' => true, 'min_length' => 2]
		], $form->getRules());
	}

	public function testAddRulesSubValidator()
	{
		$form = new Validator([
			'address' => new Validator([
				'street' => ['required']
			])
		]);
		$form->addRules([
			'first_name' => ['required']
		]);
		$this->assertEquals([
			'address' => new Validator([
				'street' => ['required' => true]
			]),
			'first_name' => ['required' => true]
		], $form->getRules());

		$form->getRules('address')->addRules([
			'postcode' => []
		]);
		$this->assertEquals([
			'address' => new Validator([
				'street' => ['required' => true],
				'postcode' => []
			]),
			'first_name' => ['required' => true]
		], $form->getRules());
	}

	/**
	 * @dataProvider validRules
	 */
	public function testGetRuleValue($compressed, $uncompressed)
	{
		$rules = array('my_field' => $uncompressed);
		$form = new Validator($rules);

		foreach ( $uncompressed as $rule_name => $rule_value ) {
			$this->assertEquals($rule_value, $form->getRuleValue('my_field', $rule_name));
		}
	}

	/**
	 * @dataProvider invalidArguments
	 * @expectedException InvalidArgumentException
	 */
	public function testGetRuleValueInvalidArguments1($argument)
	{
		$form = new Validator();
		$form->getRuleValue($argument, 'required');
	}

	/**
	 * @dataProvider invalidArguments
	 * @expectedException InvalidArgumentException
	 */
	public function testGetRuleValueInvalidArguments2($argument)
	{
		$form = new Validator();
		$form->getRuleValue('my_field', $argument);
	}

	public function testGetRuleValueNested()
	{
		$validator = new Validator([
			'a' => new Validator([
				'b' => ['required', 'max_length' => 255]
			])
		]);
		$this->assertEquals(255, $validator->getRuleValue('a[b]', 'max_length'));
	}

	public function testIsRequired()
	{
		$validator = new Validator([
			'a' => new Validator([
				'b' => ['required'],
				'c' => []
			]),
			'e' => ['required']
		]);

		$this->assertFalse($validator->isRequired('a'));
		$this->assertTrue($validator->isRequired('a[b]'));
		$this->assertFalse($validator->isRequired('a[c]'));
		$this->assertTrue($validator->isRequired('e'));
	}

	/**
	 * @depends testSetRules
	 */
	public function testHasRules()
	{
		$form = new Validator();
		$this->assertFalse($form->hasRules('name'));

		$form->setRules(array('name' => array()));
		$this->assertFalse($form->hasRules('name'));

		$form->setRules(array('name' => array('required')));
		$this->assertTrue($form->hasRules('name'));
	}

	public function testHasRulesNested()
	{
		$validator = new Validator([
			'a' => new Validator([
				'b' => ['required']
			])
		]);
		$this->assertTrue($validator->hasRules('a[b]'));
		$this->assertFalse($validator->hasRules('a[b][c]'));
	}

///////////////////////////////////////////////////////////////////////////////
// Values

	public function testGetSetValues()
	{
		$values = array(
			'first_name' => 'John'
		);

		$form = new Validator();
		$form->setValues($values);
		$this->assertEquals($values, $form->getValues());
		foreach ( $values as $field => $value ) {
			$this->assertEquals($value, $form->getValue($field));
			$this->assertEquals($value, $form->$field);
			$this->assertEquals($value, $form[$field]);
		}

		$form = new Validator();
		foreach ( $values as $field => $value ) {
			$form->$field = $value;
			$this->assertEquals($value, $form->getValue($field));
		}

		$form = new Validator();
		foreach ( $values as $field => $value ) {
			$form[$field] = $value;
			$this->assertEquals($value, $form->getValue($field));
		}
	}

	/**
	 * @dataProvider invalidArguments
	 * @expectedException InvalidArgumentException
	 */
	public function testGetValueArguments($argument)
	{
		$form = new Validator();
		$form->getValue($argument);
	}

	public function testGetValueNested()
	{
		$validator = new Validator([
			'a' => new Validator([
				'b' => ['required']
			])
		]);

		$validator->setValues([
			'a' => [
				'b' => 'Foobar'
			]
		]);

		$this->assertEquals(['b' => 'Foobar'], $validator->getValue('a'));
		$this->assertEquals('Foobar', $validator->getValue('a')['b']);
		$this->assertEquals('Foobar', $validator->a['b']);
		$this->assertEquals('Foobar', $validator['a']['b']);

		$this->assertEquals('Foobar', $validator->getValue('a[b]'));
	}

///////////////////////////////////////////////////////////////////////////////
// Errors

	public function testGetSetErrors()
	{
		$form = new Validator();
		$this->assertEquals(array(), $form->getErrors());
		$this->assertEquals(array(), $form->getErrors('Some field'));

		$errors = array('first_name' => array('required' => true));
		$form->setErrors($errors);
		$this->assertEquals($errors, $form->getErrors());
		$this->assertEquals($errors['first_name'], $form->getErrors('first_name'));
	}

	/**
	 * @dataProvider invalidArguments
	 * @expectedException InvalidArgumentException
	 */
	public function testGetErrorsInvalidArguments($argument)
	{
		// this is valid
		if ( $argument === '' ) {
			throw new InvalidArgumentException();
		}
		$form = new Validator();
		$form->getErrors($argument);
	}

	public function testGetErrorsNested()
	{
		$validator = new Validator([
			'a' => new Validator([
				'b' => ['required']
			])
		]);

		$validator->validate([]);
		$this->assertEquals(['a' => ['b' => ['required' => true]]], $validator->getErrors());
		$this->assertEquals(['b' => ['required' => true]], $validator->getErrors('a'));
		$this->assertEquals(['required' => true], $validator->getErrors('a[b]'));
		$this->assertEquals(true, $validator->getErrors('a[b][required]'));  // unintended side effect
	}

	public function testGetErrorsNestedEach()
	{
		$validator = new Validator([
			'list' => ['each' => ['max' => 10]]
		]);

		$this->assertFalse($validator->validate(['list' => [1,2,42]]));
		$this->assertEquals([2 => ['max' => 10]], $validator->getErrors('list'));
		$this->assertEquals(['max' => 10], $validator->getErrors('list[2]'));
	}

	/**
	 * @depends testGetSetErrors
	 */
	public function testHasErrors()
	{
		$form = new Validator();
		$this->assertFalse($form->hasErrors());
		$this->assertFalse($form->hasErrors('first_name'));

		$form->setErrors(array('first_name' => array('required')));
		$this->assertTrue($form->hasErrors());
		$this->assertTrue($form->hasErrors('first_name'));
	}

	public function testHasErrorsNested()
	{
		$validator = new Validator([
			'a' => new Validator([
				'b' => ['required']
			])
		]);

		$validator->validate([]);
		$this->assertTrue($validator->hasErrors('a'));
		$this->assertTrue($validator->hasErrors('a[b]'));
		$this->assertTrue($validator->hasErrors('a[b][required]')); // unintended side effect
	}

///////////////////////////////////////////////////////////////////////////////
// Validation options

	public function testUseDefault()
	{
		// not required
		$form = new Validator(array(
			'id' => array()
		));
		$this->assertTrue($form->validate(array()));
		$this->assertTrue($form->validate(array('id' => null)));
		$this->assertTrue($form->validate(array('id' => '')));
		$this->assertTrue($form->validate(array('id' => array())));
		$this->assertTrue($form->validate(array('id' => 0)));
		$this->assertTrue($form->validate(array('id' => false)));
		$this->assertTrue($form->validate(array('id' => '1')));

		// required + no default
		$form = new Validator(array(
			'id' => array('required')
		));
		$this->assertFalse($form->validate(array()));
		$this->assertFalse($form->validate(array('id' => null)));
		$this->assertFalse($form->validate(array('id' => '')));
		$this->assertFalse($form->validate(array('id' => array())));
		$this->assertTrue($form->validate(array('id' => 0)));
		$this->assertTrue($form->validate(array('id' => false)));
		$this->assertTrue($form->validate(array('id' => '1')));

		// required + default
		$form = new Validator(array(
			'id' => array('required')
		));
		$form->setValues(array('id' => 1));
		$this->assertTrue($form->validate(array()), 'Use default value if missing');
		$this->assertFalse($form->validate(array('id' => null)));
		$this->assertFalse($form->validate(array('id' => '')));
		$this->assertFalse($form->validate(array('id' => array())));
		$this->assertTrue($form->validate(array('id' => 0)));
		$this->assertTrue($form->validate(array('id' => false)));
		$this->assertTrue($form->validate(array('id' => '1')));

		// strict required + default values
		$form = new Validator(array(
			'id' => array('required')
		));
		$form->setValues(array('id' => 1));
		$this->assertFalse($form->validate(array(), array('use_default' => false)), 'Use default is missing disabled');

		// default value not matching rules
		$form = new Validator(array('name' => array('required', 'min_length' => 2)));
		$form->setValues(array('name' => 'A'));
		$this->assertFalse($form->validate(array())); // false, the default value for name doesn't match 'min_length'
	}

	public function testAllowEmpty()
	{
		$test_rules = array(
			array('min_length' => 2),
			// array('each' => array('min_length' => 2)),
			array('date'),
			array('time'),
		);
		foreach ( $test_rules as $rules ) {
			$form = new Validator(array(
				'id' => $rules
			));
			$this->assertTrue($form->validate(array('id' => '')), 'Empty value allowed by default');
			$this->assertFalse($form->validate(array('id' => ''), array('allow_empty' => false)), sprintf(
				'Empty value not allowed by default (rule %s)',
				json_encode($rules)
			));
		}
	}

	public function testIgnoreExtraneous()
	{
		$values = array(
			'a' => 1,
			'b' => 2
		);
		$form = new Validator(array(
			'a' => array('required')
		));
		$this->assertTrue($form->validate($values));
		$this->assertEquals($values['a'], $form->a);
		$this->assertNull($form->b);

		$form->setValues(array());
		$this->assertFalse($form->validate($values, array('ignore_extraneous' => false)));
		$this->assertTrue($form->hasErrors('b'));

		$form->setValues(array());
		$form->setOptions(array('ignore_extraneous' => false));
		$this->assertFalse($form->validate($values));
		$this->assertTrue($form->hasErrors('b'));

		$form->setValues(array('c' => 3));
		$form->setOptions(array('ignore_extraneous' => false));
		$this->assertTrue($form->validate(array('a' => 1)), 'Ignore extraneous default');
	}

///////////////////////////////////////////////////////////////////////////////
// Validation process

	/**
	 * @depends testGetSetValues
	 */
	public function testGetValueAfterValidation()
	{
		$valid_ids = array(1,2,3,4);
		$form = new Validator(array(
			'id' => array('in' => $valid_ids)
		));
		$this->assertTrue($form->validate(array('id' => 1)));
		$this->assertEquals(1, $form->id);

		$form->id = 2;
		$this->assertTrue($form->validate(array('id' => 1)));
		$this->assertEquals(1, $form->id, 'Default value has been replaced by validated value');

		$form->id = 2;
		$this->assertFalse($form->validate(array('id' => 42)));
		$this->assertEquals(42, $form->id, 'Default value has been replaced by invalid value for repopulating the form');

		$form->id = 2;
		$this->assertTrue($form->validate(array()));
		$this->assertEquals(2, $form->id, "Default value hasn't been touched when not present in array");

		$form->id = 2;
		$this->assertTrue($form->validate(array('id' => null)));
		$this->assertEquals(null, $form->id);
	}

	public function testValidateValue()
	{
		$form = new Validator();

		$value = '';
		$errors = array();
		$this->assertFalse($form->validateValue($value, array('required' => true), $errors));
		$this->assertEquals(array('required' => true), $errors);

		$value = 'something';
		$this->assertTrue($form->validateValue($value, array('required' => true), $errors));
		$this->assertEmpty($errors);
	}

	public function testValidateMultipleValues()
	{
		$form = new Validator();

		$value = array('');
		$errors = array();

		$this->assertFalse($form->validateMultipleValues($value, array('required' => true), $errors));

		$value = array(1,2,'garbage');
		$this->assertFalse($form->validateMultipleValues($value, array('numeric' => true), $errors));

		$value = array(1,2,3);
		$this->assertTrue($form->validateMultipleValues($value, array('numeric' => true), $errors));
	}

///////////////////////////////////////////////////////////////////////////////
// Special validators

	public function testSubValidator()
	{
		$form = new Validator([
			'subform' => new Validator([
				'first_name' => ['required'],
				'last_name' => ['required']
			])
		]);

		// valid data sets
		$expected_values = array('subform' => array('first_name' => 'John', 'last_name' => 'Wayne'));
		$this->assertTrue($form->validate(array('subform' => array('first_name' => 'John', 'last_name' => 'Wayne'))));
		$this->assertEquals($expected_values, $form->getValues());

		$this->assertTrue($form->validate(array('subform' => array('first_name' => 'John', 'last_name' => 'Wayne', 'garbage' => 'garbage'))));
		$this->assertEquals($expected_values, $form->getValues());
		
		// invalid data sets
		$form->setValues(array());
		$this->assertFalse($form->validate(array()));
		$this->assertFalse($form->validate(array('subform' => array())));
		$this->assertFalse($form->validate(array('subform' => array('last_name' => 'Wayne'))));
		$this->assertFalse($form->validate(array('subform' => array('first_name' => '', 'last_name' => 'Wayne'))));

		// subform + allow empty (i.e. test that values are passed to subform)
		$form->setValues(array('subform' => array('first_name' => 'John')));
		$this->assertTrue($form->validate(array('subform' => array('last_name' => 'Doe'))), 'Values are passed to subform');

		// subform + extraneous (i.e. test that we are careful in passing the values to the subform)
		$form->setValues(array(
			'subform' => array('first_name' => 'John', 'email' => 'john@doe.com')
		));
		$this->assertTrue($form->validate(array('subform' => array('last_name' => 'Doe')), array('ignore_extraneous' => false)), 'Subform also ignore extraneous default');

		$form->setValues(array(
			'subform' => array('first_name' => 'John', 'last_name' => 'Foobar', 'email' => 'john@doe.com')
		));
		$this->assertTrue($form->validate(array(), array('ignore_extraneous' => false)), 'Subform also ignore extraneous default');
	}

	public function testEach()
	{
		$form = new Validator(array(
			'list' => array('each' => array('min_length' => 1, 'max_length' => 4))
		));

		$this->assertTrue($form->validate(array('list' => array('a','b','c'))));
		$this->assertEquals(array('list' => array('a','b','c')), $form->getValues());

		$form->setValues(array());
		$this->assertTrue($form->validate(array('garbage' => 'garbage')));
		$this->assertEquals(array('list' => array()), $form->getValues(), 'List is set and casted to array when missing');

		$this->assertFalse($form->validate(array('list' => 'garbage')), 'When not an array, autocasted, but does not validate because max_length failed');
		$this->assertEquals([0 => ['max_length' => 4]], $form->getErrors('list'));

		$this->assertFalse($form->validate(array('list' => array('garbage'))), 'When an array with garbage, does not validate');
		$this->assertEquals([0 => ['max_length' => 4]], $form->getErrors('list'));

		// with required = false and allow_empty = false
		$form->setValues(array());
		$this->assertTrue($form->validate(
			array('list' => array()),
			array('allow_empty' => false)
		), "Since it is not required, allow_empty won't be triggered");

		// with required = true
		$form = new Validator(array(
			'list' => array('required' => true, 'each' => array())
		));
		$this->assertTrue($form->validate(array('list' => 'garbage')));
		$this->assertEquals(array('list' => array('garbage')), $form->getValues(), 'Original value is casted to an array');

		$this->assertFalse($form->validate(array('list' => array())), 'Empty array does not pass required = true');
		$this->assertEquals(array(
			'required' => true
		), $form->getErrors('list'));

		$form->setValues(array());
		$this->assertFalse($form->validate(array('garbage' => 'garbage')));
		$this->assertEquals(array('list' => array()), $form->getValues(), 'List is set and casted to array, when required and empty');

		// used in conjuction with is_array type check
		$form = new Validator(array(
			'list' => array('is_array', 'each' => array('min_length' => 1, 'max_length' => 4))
		));

		$this->assertFalse($form->validate(array('list' => 'garbage')), 'When not an array, does not validate');
		$this->assertEquals(array('is_array' => true), $form->getErrors('list'));
		$this->assertEquals(array('list' => 'garbage'), $form->getValues(), 'Original value is not casted to an array when is_array is used');

		// with required = true inside the each
		$form = new Validator(array(
			'list' => array('each' => array('required' => true))
		));
		$this->assertTrue($form->validate(array('list' => array())), 'Ok for an empty array');
		$form->setValues(array());
		$this->assertFalse($form->validate(array('list' => array(''))), 'Not ok for an empty array with a empty string inside it');
		$this->assertEquals([0 => array('required' => true)], $form->getErrors('list'));
	}

	public function testEachWithSubValidator()
	{
		$form = new Validator(array(
			'list' => array('each' => new Validator(array(
				'name' => array('required')
			)))
		));

		$this->assertTrue($form->validate(array(
			'list' => array(
				array('name' => 'John'),
				array('name' => 'Jane')
			)
		)), "Each can be used with a subform to validate a array of arrays");

		$form->setValues(array());
		$this->assertFalse($form->validate(array(
			'list' => array(
				array('name' => 'John', 'age' => '12'),
				array('age' => '12')
			)
		)), "Each can be used with a subform to validate a array of arrays (validation should fail)");

		$this->assertEquals(array(
			1 => array(
				'name' => array(
					'required' => true
				)
			)
		), $form->getErrors('list'), 'Error array contains the offset');

		// with default values
		$form->setValues(array(
			'list' => array(
				0 => array(),
				1 => array('name' => 'Jane')
			)
		));
		$this->assertFalse($form->validate(array(
			'list' => array(
				array('name' => 'John', 'age' => '12'),
				array('age' => '12')
			)
		)), "Default values are not deep-merged (like for a normal each)");

		$form = new Validator(array(
			'list' => array('is_array', 'each' => new Validator(array(
				'name' => array('required')
			)))
		));
		$this->assertFalse($form->validate(array('list' => 'foobar')), 'Not an array');
		$this->assertTrue($form->validate(array('list' => array(array('name'=>'foobar')))), 'List is array and has a name, all good');
	}

	public function testConditionalValue()
	{
		$form = new Validator(array(
			'field' => array('required' => function($form) { return true; })
		));

		$this->assertTrue($form->validate(array('field' => 42)), 'Required evaluates to true');
		$form->setValues(array());
		$this->assertFalse($form->validate(array()), 'Required evaluates to true');
		$this->assertFalse($form->validate(array('field' => '')), 'Required evaluates to true');

		$form = new Validator(array(
			'field' => array('required' => function($form) { return false; })
		));

		$this->assertTrue($form->validate(array('field' => 42)), 'Required evaluates to false');
		$form->setValues(array());
		$this->assertTrue($form->validate(array()), 'Required evaluates to false');
		$form->setValues(array());
		$this->assertTrue($form->validate(array('field' => '')), 'Required evaluates to false');
	}

	public function testConditionalValueWithSubform()
	{
		$form = new Validator(array(
			'main_field' => array('required'),
			'options' => new Validator(array(
				'sub_field' => array('required' => function($form) {
					return $form->getParent()->main_field == 42;
				})
			))
		));

		$this->assertTrue($form->validate(array('main_field' => 42, 'options' => array('sub_field' => 1))), 'Required evaluates to true');
		$form->setValues(array());
		$this->assertFalse($form->validate(array('main_field' => 42, 'options' => array())), 'Required evaluates to true');

		$form->setValues(array());
		$this->assertTrue($form->validate(array('main_field' => 0, 'options' => array('sub_field' => 1))), 'Required evaluates to false');
		$form->setValues(array());
		$this->assertTrue($form->validate(array('main_field' => 0, 'options' => array())), 'Required evaluates to false');
	}

	public function testConditionalRules()
	{
		$form = new Validator(array(
			'field' => array(),
			'options' => function($form) { 
				return $form->field == "x" ? array("required" => true) : array();
			}
		));

		$this->assertTrue($form->validate(array('field' => 42)));
		$this->assertFalse($form->validate(array('field' => 'x')), 'Options is required when field = x');
	}

	public function testCallback()
	{
		$form = new Validator(array(
			'field' => array('callback' => function(&$value, $form) {
				$value = 42;
				$form->proof = "it worked!";
				return true;
			})
		));

		$this->assertTrue($form->validate(array('field' => 1)));
		$this->assertEquals(42, $form->getValue('field'), 'Callback can modify value');
		$this->assertEquals("it worked!", $form->getValue('proof'), 'Callback has access to form object');

		$identical_password_validator = function($confirmation, $form) {
			return $form->password == $confirmation;
		};

		$form = new Validator(array(
			'password' => array('required', 'min_length' => 6),
			'password_confirm' => array('required', 'identical' => $identical_password_validator)
		));

		$this->assertTrue($form->validate(array('password' => 'abcdef', 'password_confirm' => 'abcdef')));
		$this->assertFalse($form->validate(array('password' => 'abcdef', 'password_confirm' => '')));
		$this->assertFalse($form->validate(array('password' => 'abcdef', 'password_confirm' => 'x')));

		// order is important!
		$form = new Validator(array(
			'password_confirm' => array('required', 'identical' => $identical_password_validator),
			'password' => array('required', 'min_length' => 6)
		));

		$this->assertFalse($form->validate(array('password' => 'abcdef', 'password_confirm' => 'abcdef')));
		$this->assertFalse($form->validate(array('password' => 'abcdef', 'password_confirm' => '')));
		$this->assertFalse($form->validate(array('password' => 'abcdef', 'password_confirm' => 'x')));
	}

///////////////////////////////////////////////////////////////////////////////
// Tests with various validators

	public function testDate()
	{
		$form = new Validator([
			'birthday' => ['date']
		]);
		$this->assertTrue($form->validate([
			'birthday' => '2000-01-01'
		]));
		$this->assertFalse($form->validate([
			'birthday' => '01/01/2001'
		]));

		$form = new Validator([
			'birthday' => ['date' => null]
		]);
		$this->assertTrue($form->validate([
			'birthday' => '2000-01-01'
		]));
		$this->assertTrue($form->validate([
			'birthday' => '01/01/2001'
		]));
	}
}
