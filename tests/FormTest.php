<?php

class FormTest extends PHPUnit_Framework_TestCase
{

// RULES TESTS

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
		$this->assertEquals($uncompressed, Form::expandRulesArray($compressed));
	}

	/**
	 * @dataProvider invalidRules
	 * @expectedException InvalidArgumentException
	 */
	public function testExpandRulesInvalid($rules)
	{
		Form::expandRulesArray($rules);
	}

	/**
	 * @dataProvider validRules
	 */
	public function testGetRules($compressed, $uncompressed)
	{
		$form = new Form();
		$this->assertEquals(array(), $form->getRules());
		$this->assertEquals(array(), $form->getRules('unset_field'));

		$rules = array('my_field' => $uncompressed);
		$form = new Form($rules);
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

		$form = new Form();
		$form->getRules($argument);
	}

	/**
	 * @depends testGetRules
	 * @dataProvider validRules
	 */
	public function testSetRules($compressed, $uncompressed)
	{
		$form = new Form();
		$form->setRules(array('name' => $compressed));
		$this->assertEquals(array('name' => $uncompressed), $form->getRules());

		$form = new Form();
		$form->setRules('name', $compressed);
		$this->assertEquals(array('name' => $uncompressed), $form->getRules());
		$this->assertEquals($uncompressed, $form->getRules('name'));
	}

	/**
	 * @dataProvider invalidRules
	 * @expectedException InvalidArgumentException
	 */
	public function testSetRulesInvalid($rules)
	{
		$form = new Form();
		$form->setRules($rules);
	}

	/**
	 * @dataProvider invalidRules
	 * @expectedException InvalidArgumentException
	 */
	public function testSetRulesFieldInvalid($rules)
	{
		$form = new Form();
		$form->setRules('name', $rules);
	}

	/**
	 * @dataProvider invalidArguments
	 * @expectedException InvalidArgumentException
	 */
	public function testSetRulesInvalidArguments($argument)
	{
		// this is valid
		if ( $argument == array() ) {
			throw new InvalidArgumentException();
		}

		$form = new Form();
		$form->setRules($argument);
	}

	/**
	 * @dataProvider invalidArguments
	 * @expectedException InvalidArgumentException
	 */
	public function testSetRulesFieldInvalidArguments($argument)
	{
		// this is valid
		if ( $argument == array() ) {
			throw new InvalidArgumentException();
		}

		$form = new Form();
		$form->setRules($argument, array());
	}

	/**
	 * @depends testGetRules
	 */
	public function testAddRules()
	{
		$form = new Form(array('first_name' => array()));
		$form->addRules(array('last_name' => array()));

		$this->assertEquals(array('first_name' => array(), 'last_name' => array()), $form->getRules());

		$form = new Form(array('first_name' => array('required')));
		$form->addRules(array('first_name' => array('min_length' => 2)));

		$this->assertEquals(array('first_name' => array('required' => true, 'min_length' => 2)), $form->getRules());

		$form = new Form(array('first_name' => array('min_length' => 2)));
		$form->addRules(array('first_name' => array('required')));

		$this->assertEquals(array('first_name' => array('required' => true, 'min_length' => 2)), $form->getRules());
	}

	/**
	 * @dataProvider validRules
	 */
	public function testGetRuleValue($compressed, $uncompressed)
	{
		$rules = array('my_field' => $uncompressed);
		$form = new Form($rules);

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
		$form = new Form();
		$form->getRuleValue($argument, 'required');
	}

	/**
	 * @dataProvider invalidArguments
	 * @expectedException InvalidArgumentException
	 */
	public function testGetRuleValueInvalidArguments2($argument)
	{
		$form = new Form();
		$form->getRuleValue('my_field', $argument);
	}

	/**
	 * @depends testSetRules
	 */
	public function testHasRules()
	{
		$form = new Form();
		$this->assertFalse($form->hasRules('name'));

		$form->setRules(array('name' => array()));
		$this->assertFalse($form->hasRules('name'));

		$form->setRules(array('name' => array('required')));
		$this->assertTrue($form->hasRules('name'));
	}

// VALUES TESTS

	public function testGetSetValues()
	{
		$values = array(
			'first_name' => 'John'
		);

		$form = new Form();
		$form->setValues($values);
		$this->assertEquals($values, $form->getValues());
		foreach ( $values as $field => $value ) {
			$this->assertEquals($value, $form->getValue($field));
			$this->assertEquals($value, $form->$field);
			$this->assertEquals($value, $form[$field]);
		}

		$form = new Form();
		foreach ( $values as $field => $value ) {
			$form->$field = $value;
			$this->assertEquals($value, $form->getValue($field));
		}

		$form = new Form();
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
		$form = new Form();
		$form->getValue($argument);
	}

// ERRORS TESTS

	public function testGetSetErrors()
	{
		$form = new Form();
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
		$form = new Form();
		$form->getErrors($argument);
	}

	/**
	 * @depends testGetSetErrors
	 */
	public function testHasErrors()
	{
		$form = new Form();
		$this->assertFalse($form->hasErrors());
		$this->assertFalse($form->hasErrors('first_name'));

		$form->setErrors(array('first_name' => array('required')));
		$this->assertTrue($form->hasErrors());
		$this->assertTrue($form->hasErrors('first_name'));
	}

// VALIDATION TESTS

	public function testForm()
	{

	}

	public function testRequired()
	{
		// not required
		$form = new Form(array(
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
		$form = new Form(array(
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
		$form = new Form(array(
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
		$form = new Form(array(
			'id' => array('required')
		));
		$form->setValues(array('id' => 1));
		$this->assertFalse($form->validate(array(), array('use_default' => false)), 'Strict required works');
	}

	public function testSubForm()
	{
		$form = new Form(array(
			'subform' => new Form(array(
				'first_name' => array('required'),
				'last_name' => array('required')
			))
		));

		// valid data sets
		$expected_values = array('subform' => array('first_name' => 'John', 'last_name' => 'Wayne'));
		$this->assertTrue($form->validate(array('subform' => array('first_name' => 'John', 'last_name' => 'Wayne'))));
		$this->assertEquals($expected_values, $form->getValues());

		$this->assertTrue($form->validate(array('subform' => array('first_name' => 'John', 'last_name' => 'Wayne', 'garbage' => 'garbage'))));
		$this->assertEquals($expected_values, $form->getValues());
		
		// invalid data sets
		$form->setValues(array());
		$form->getRules('subform')->setValues(array());
		$this->assertFalse($form->validate(array()));
		$this->assertFalse($form->validate(array('subform' => array())));
		$this->assertFalse($form->validate(array('subform' => array('last_name' => 'Wayne'))));
		$this->assertFalse($form->validate(array('subform' => array('first_name' => '', 'last_name' => 'Wayne'))));
	}

	public function testEach()
	{
		$form = new Form(array(
			'list' => array('each' => array(
				'max_length' => 4
			))
		));

		$this->assertTrue($form->validate(array('list' => array('a','b','c'))));
		$this->assertEquals(array('list' => array('a','b','c')), $form->getValues());

		$form->setValues(array());
		$this->assertTrue($form->validate(array('garbage' => 'garbage')));
		$this->assertEquals(array('list' => array()), $form->getValues(), 'List is set and casted to array');

		$this->assertFalse($form->validate(array('list' => 'garbage')));
	}

	// public function testPhpNative()
	// {
	// 	$form = new Form(array(
	// 		'field' => array('php' => 'is_int')
	// 	));

	// 	$this->assertTrue($form->validate(array('field' => 42)));
	// }

	// public function testRequired()
	// {

	// }

	public function testCallback()
	{
		$callback = create_function('&$value,$form', '$value = 42; $form->proof = "it worked!"; return true;');
		// $callback = function(&$value, $form) {
		// 	$value = 42;
		// 	return true;
		// };

		$form = new Form(array(
			'field' => array('callback' => $callback)
		));
		// $this->assertTrue($form->validate(array()));
		// $this->assertEquals(array('field' => 42), $form->getValues(), 'Callback can set value');

		$this->assertTrue($form->validate(array('field' => 1)));
		$this->assertEquals(42, $form->getValue('field'), 'Callback can modify value');
		$this->assertEquals("it worked!", $form->getValue('proof'), 'Callback has access to form object');
	}

	/**
	 * @depends testGetSetValues
	 */
	public function testGetValueAfterValidation()
	{
		$valid_ids = array(1,2,3,4);
		$form = new Form(array(
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
}
