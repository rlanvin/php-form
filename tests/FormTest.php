<?php

class FormTest extends PHPUnit_Framework_TestCase
{
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

	public function forms()
	{
		// return form rules, valid data set and invalid data set
		return array(
			array(
				// form
				array(
					'data' => new Form(array(
						'first_name' => array('required'),
						'last_name' => array('required')
					))
				),
				// valid data set
				array(
					array('data' => array('first_name' => 'John', 'last_name' => 'Wayne'))
				),
				// invalid data set
				array(
					array('data' => array()),
					array('data' => array('last_name' => 'Wayne')),
					array('data' => array('first_name' => '', 'last_name' => 'Wayne'))
				)
			)
		);
	}

	/**
	 * @dataProvider forms
	 */
	public function testConstruct($form)
	{
		new Form($form); // should not throw an exception
	}

	/**
	 * @depends testSetRules
	 * @dataProvider forms
	 */
	public function testValidates($form, $valid_values, $invalid_values)
	{
		$form = new Form($form);
		foreach ( $valid_values as $values ) {
			$this->assertTrue($form->validates($values));
		}
		foreach ( $invalid_values as $values ) {
			$this->assertFalse($form->validates($values));
		}
	}

	// public function testSubForm()
	// {
	// 	$data = array(
	// 		'id' => 42,
	// 		'data' => array(
	// 			'first_name' => 'Bruce',
	// 			'last_name' => 'Wayne'
	// 		)
	// 	);

	// 	$form = new Form(array(
	// 		'id' => array(),
	// 		'data' => new Form(array(
	// 			'first_name' => array(),
	// 			'last_name' => array()
	// 		))
	// 	));
	// 	$this->assertTrue($form->validates($data));
	// }

	// public function testArray()
	// {
	// 	$data = array(
	// 		'names' => array('John', 'Jack', 'Joe')
	// 	);

	// 	$form = new Form(array(
	// 		'names' => array('each' => array(
	// 			'max_length' => 4
	// 		))
	// 	));
	// 	$this->assertTrue($form->validates($data));
	// }
}
