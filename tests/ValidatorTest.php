<?php

/**
 * Licensed under the MIT license.
 *
 * For the full copyright and license information, please view the LICENSE file.
 *
 * @author Rémi Lanvin <remi@cloudconnected.fr>
 * @link https://github.com/rlanvin/php-form
 */

namespace Form\Tests;

use Form\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
	///////////////////////////////////////////////////////////////////////////////
	// Options

	public function testGetSetOptions()
	{
		$form = new Validator();
		$form->setOptions([
			'ignore_extraneous' => false,
			'allow_empty' => false,
		]);
		$option = $form->getOptions();
		$this->assertFalse($option['ignore_extraneous']);
		$this->assertFalse($option['allow_empty']);

		$form->setOptions([
			'ignore_extraneous' => true,
		]);
		$option = $form->getOptions();
		$this->assertTrue($option['ignore_extraneous']);
		$this->assertFalse($option['allow_empty']);
	}

	///////////////////////////////////////////////////////////////////////////////
	// Rules

	public function validRules()
	{
		// return compressed and uncompressed
		return [
			[
				['required', 'min_length' => 2],
				['required' => true, 'min_length' => 2],
			],
			[
				['each' => ['required']],
				['each' => ['required' => true]],
			],
		];
	}

	public function invalidArguments()
	{
		return [
			[''],
			[null],
			[[]],
			[new \stdClass()],
			[42],
			[(float) 42],
		];
	}

	public function invalidRules()
	{
		$rules = [];
		foreach ($this->invalidArguments() as $arg) {
			$rules[] = [$arg];
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
	 */
	public function testExpandRulesInvalid($rules)
	{
		$this->expectException(\InvalidArgumentException::class);
		Validator::expandRulesArray($rules);
	}

	/**
	 * @dataProvider validRules
	 */
	public function testGetRules($compressed, $uncompressed)
	{
		$form = new Validator();
		$this->assertEquals([], $form->getRules());
		$this->assertEquals([], $form->getRules('unset_field'));

		$rules = ['my_field' => $uncompressed];
		$form = new Validator($rules);
		$this->assertEquals($rules, $form->getRules());
		$this->assertEquals($rules['my_field'], $form->getRules('my_field'));
		$this->assertEquals([], $form->getRules('unset_field'));
	}

	/**
	 * @dataProvider invalidArguments
	 */
	public function testGetRulesInvalidArguments($argument)
	{
		$this->expectException(\InvalidArgumentException::class);
		// this is allowed in getRules()
		if ($argument === '') {
			throw new \InvalidArgumentException();
		}

		$form = new Validator();
		$form->getRules($argument);
	}

	public function testGetRulesNested()
	{
		$validator = new Validator([
			'a' => new Validator([
				'b' => ['required'],
			]),
		]);
		$this->assertEquals(new Validator([
			'b' => ['required'],
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
		$form->setRules(['name' => $compressed]);
		$this->assertEquals(['name' => $uncompressed], $form->getRules());

		$form = new Validator();
		$form->setRules('name', $compressed);
		$this->assertEquals(['name' => $uncompressed], $form->getRules());
		$this->assertEquals($uncompressed, $form->getRules('name'));
	}

	public function testSetRulesSubValidator()
	{
		$this->expectNotToPerformAssertions();
		$form = new Validator();
		$form->setRules('address', new Validator([
			'street' => ['required'],
			'postcode' => ['required'],
		]));
	}

	/**
	 * @dataProvider invalidRules
	 */
	public function testSetRulesInvalid($rules)
	{
		$this->expectException(\InvalidArgumentException::class);
		$form = new Validator();
		$form->setRules($rules);
	}

	/**
	 * @dataProvider invalidRules
	 */
	public function testSetRulesFieldInvalid($rules)
	{
		$this->expectException(\InvalidArgumentException::class);
		$form = new Validator();
		$form->setRules('name', $rules);
	}

	/**
	 * @dataProvider invalidArguments
	 */
	public function testSetRulesInvalidArguments($argument)
	{
		// this is valid
		if ($argument == [] || $argument === '') {
			//throw new BadMethodCallException();
			$this->expectNotToPerformAssertions();

			return;
		}

		$this->expectException(\BadMethodCallException::class);
		$form = new Validator();
		$form->setRules($argument);
	}

	/**
	 * @dataProvider invalidArguments
	 */
	public function testSetRulesFieldInvalidArguments($argument)
	{
		// this is valid
		if ($argument == [] || $argument === '') {
			//throw new BadMethodCallException();
			$this->expectNotToPerformAssertions();

			return;
		}

		$this->expectException(\BadMethodCallException::class);
		$form = new Validator();
		$form->setRules($argument, []);
	}

	/**
	 * @depends testGetRules
	 */
	public function testAddRules()
	{
		$form = new Validator([
			'first_name' => [],
		]);
		$form->addRules([
			'last_name' => [],
		]);
		$this->assertEquals([
			'first_name' => [],
			'last_name' => [],
		], $form->getRules());

		$form = new Validator([
			'first_name' => ['required'],
		]);
		$form->addRules([
			'first_name' => ['min_length' => 2],
		]);
		$this->assertEquals([
			'first_name' => ['required' => true, 'min_length' => 2],
		], $form->getRules());

		$form = new Validator([
			'first_name' => ['min_length' => 2],
		]);
		$form->addRules([
			'first_name' => ['required'],
		]);
		$this->assertEquals([
			'first_name' => ['required' => true, 'min_length' => 2],
		], $form->getRules());
	}

	public function testAddRulesSubValidator()
	{
		$form = new Validator([
			'address' => new Validator([
				'street' => ['required'],
			]),
		]);
		$form->addRules([
			'first_name' => ['required'],
		]);
		$this->assertEquals([
			'address' => new Validator([
				'street' => ['required' => true],
			]),
			'first_name' => ['required' => true],
		], $form->getRules());

		$form->getRules('address')->addRules([
			'postcode' => [],
		]);
		$this->assertEquals([
			'address' => new Validator([
				'street' => ['required' => true],
				'postcode' => [],
			]),
			'first_name' => ['required' => true],
		], $form->getRules());
	}

	/**
	 * @dataProvider validRules
	 */
	public function testGetRuleValue($compressed, $uncompressed)
	{
		$rules = ['my_field' => $uncompressed];
		$form = new Validator($rules);

		foreach ($uncompressed as $rule_name => $rule_value) {
			$this->assertEquals($rule_value, $form->getRuleValue('my_field', $rule_name));
		}
	}

	/**
	 * @dataProvider invalidArguments
	 */
	public function testGetRuleValueInvalidArguments1($argument)
	{
		$this->expectException(\InvalidArgumentException::class);
		$form = new Validator();
		$form->getRuleValue($argument, 'required');
	}

	/**
	 * @dataProvider invalidArguments
	 */
	public function testGetRuleValueInvalidArguments2($argument)
	{
		$this->expectException(\InvalidArgumentException::class);
		$form = new Validator();
		$form->getRuleValue('my_field', $argument);
	}

	public function testGetRuleValueNested()
	{
		$validator = new Validator([
			'a' => new Validator([
				'b' => ['required', 'max_length' => 255],
			]),
		]);
		$this->assertEquals(255, $validator->getRuleValue('a[b]', 'max_length'));
	}

	public function testIsRequired()
	{
		$validator = new Validator([
			'a' => new Validator([
				'b' => ['required'],
				'c' => [],
			]),
			'e' => ['required'],
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

		$form->setRules(['name' => []]);
		$this->assertFalse($form->hasRules('name'));

		$form->setRules(['name' => ['required']]);
		$this->assertTrue($form->hasRules('name'));
	}

	public function testHasRulesNested()
	{
		$validator = new Validator([
			'a' => new Validator([
				'b' => ['required'],
			]),
		]);
		$this->assertTrue($validator->hasRules('a[b]'));
		$this->assertFalse($validator->hasRules('a[b][c]'));
	}

	///////////////////////////////////////////////////////////////////////////////
	// Values

	public function testGetSetValues()
	{
		$values = [
			'first_name' => 'John',
		];

		$form = new Validator();
		$form->setValues($values);
		$this->assertEquals($values, $form->getValues());
		foreach ($values as $field => $value) {
			$this->assertEquals($value, $form->getValue($field));
			$this->assertEquals($value, $form->$field);
			$this->assertEquals($value, $form[$field]);
		}

		$form = new Validator();
		foreach ($values as $field => $value) {
			$form->$field = $value;
			$this->assertEquals($value, $form->getValue($field));
		}

		$form = new Validator();
		foreach ($values as $field => $value) {
			$form[$field] = $value;
			$this->assertEquals($value, $form->getValue($field));
		}
	}

	/**
	 * @dataProvider invalidArguments
	 */
	public function testGetValueArguments($argument)
	{
		$this->expectException(\InvalidArgumentException::class);
		$form = new Validator();
		$form->getValue($argument);
	}

	public function testGetValueNested()
	{
		$validator = new Validator([
			'a' => new Validator([
				'b' => ['required'],
			]),
		]);

		$validator->setValues([
			'a' => [
				'b' => 'Foobar',
			],
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
		$this->assertEquals([], $form->getErrors());
		$this->assertEquals([], $form->getErrors('Some field'));

		$errors = ['first_name' => ['required' => true]];
		$form->setErrors($errors);
		$this->assertEquals($errors, $form->getErrors());
		$this->assertEquals($errors['first_name'], $form->getErrors('first_name'));
	}

	/**
	 * @dataProvider invalidArguments
	 */
	public function testGetErrorsInvalidArguments($argument)
	{
		// this is valid
		if ($argument === '') {
			$this->expectNotToPerformAssertions();

			return;
		}
		$this->expectException(\InvalidArgumentException::class);
		$form = new Validator();
		$form->getErrors($argument);
	}

	public function testGetErrorsNested()
	{
		$validator = new Validator([
			'a' => new Validator([
				'b' => ['required'],
			]),
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
			'list' => ['each' => ['max' => 10]],
		]);

		$this->assertFalse($validator->validate(['list' => [1, 2, 42]]));
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

		$form->setErrors(['first_name' => ['required']]);
		$this->assertTrue($form->hasErrors());
		$this->assertTrue($form->hasErrors('first_name'));
	}

	public function testHasErrorsNested()
	{
		$validator = new Validator([
			'a' => new Validator([
				'b' => ['required'],
			]),
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
		$form = new Validator([
			'id' => [],
		]);
		$this->assertTrue($form->validate([]));
		$this->assertTrue($form->validate(['id' => null]));
		$this->assertTrue($form->validate(['id' => '']));
		$this->assertTrue($form->validate(['id' => []]));
		$this->assertTrue($form->validate(['id' => 0]));
		$this->assertTrue($form->validate(['id' => false]));
		$this->assertTrue($form->validate(['id' => '1']));

		// required + no default
		$form = new Validator([
			'id' => ['required'],
		]);
		$this->assertFalse($form->validate([]));
		$this->assertFalse($form->validate(['id' => null]));
		$this->assertFalse($form->validate(['id' => '']));
		$this->assertFalse($form->validate(['id' => []]));
		$this->assertTrue($form->validate(['id' => 0]));
		$this->assertTrue($form->validate(['id' => false]));
		$this->assertTrue($form->validate(['id' => '1']));

		// required + default
		$form = new Validator([
			'id' => ['required'],
		]);
		$form->setValues(['id' => 1]);
		$this->assertTrue($form->validate([]), 'Use default value if missing');
		$this->assertFalse($form->validate(['id' => null]));
		$this->assertFalse($form->validate(['id' => '']));
		$this->assertFalse($form->validate(['id' => []]));
		$this->assertTrue($form->validate(['id' => 0]));
		$this->assertTrue($form->validate(['id' => false]));
		$this->assertTrue($form->validate(['id' => '1']));

		// strict required + default values
		$form = new Validator([
			'id' => ['required'],
		]);
		$form->setValues(['id' => 1]);
		$this->assertFalse($form->validate([], ['use_default' => false]), 'Use default is missing disabled');

		// default value not matching rules
		$form = new Validator(['name' => ['required', 'min_length' => 2]]);
		$form->setValues(['name' => 'A']);
		$this->assertFalse($form->validate([])); // false, the default value for name doesn't match 'min_length'
	}

	public function testAllowEmpty()
	{
		$test_rules = [
			['min_length' => 2],
			// array('each' => array('min_length' => 2)),
			['date'],
			['time'],
		];
		foreach ($test_rules as $rules) {
			$form = new Validator([
				'id' => $rules,
			]);
			$this->assertTrue($form->validate(['id' => '']), 'Empty value allowed by default');
			$this->assertFalse($form->validate(['id' => ''], ['allow_empty' => false]), sprintf(
				'Empty value not allowed by default (rule %s)',
				json_encode($rules)
			));
		}
	}

	public function testIgnoreExtraneous()
	{
		$values = [
			'a' => 1,
			'b' => 2,
		];
		$form = new Validator([
			'a' => ['required'],
		]);
		$this->assertTrue($form->validate($values));
		$this->assertEquals($values['a'], $form->a);
		$this->assertNull($form->b);

		$form->setValues([]);
		$this->assertFalse($form->validate($values, ['ignore_extraneous' => false]));
		$this->assertTrue($form->hasErrors('b'));

		$form->setValues([]);
		$form->setOptions(['ignore_extraneous' => false]);
		$this->assertFalse($form->validate($values));
		$this->assertTrue($form->hasErrors('b'));

		$form->setValues(['c' => 3]);
		$form->setOptions(['ignore_extraneous' => false]);
		$this->assertTrue($form->validate(['a' => 1]), 'Ignore extraneous default');
	}

	///////////////////////////////////////////////////////////////////////////////
	// Validation process

	/**
	 * @depends testGetSetValues
	 */
	public function testGetValueAfterValidation()
	{
		$valid_ids = [1, 2, 3, 4];
		$form = new Validator([
			'id' => ['in' => $valid_ids],
		]);
		$this->assertTrue($form->validate(['id' => 1]));
		$this->assertEquals(1, $form->id);

		$form->id = 2;
		$this->assertTrue($form->validate(['id' => 1]));
		$this->assertEquals(1, $form->id, 'Default value has been replaced by validated value');

		$form->id = 2;
		$this->assertFalse($form->validate(['id' => 42]));
		$this->assertEquals(42, $form->id, 'Default value has been replaced by invalid value for repopulating the form');

		$form->id = 2;
		$this->assertTrue($form->validate([]));
		$this->assertEquals(2, $form->id, "Default value hasn't been touched when not present in array");

		$form->id = 2;
		$this->assertTrue($form->validate(['id' => null]));
		$this->assertEquals(null, $form->id);
	}

	public function testValidateValue()
	{
		$form = new Validator();

		$value = '';
		$errors = [];
		$this->assertFalse($form->validateValue($value, ['required' => true], $errors));
		$this->assertEquals(['required' => true], $errors);

		$value = 'something';
		$this->assertTrue($form->validateValue($value, ['required' => true], $errors));
		$this->assertEmpty($errors);
	}

	public function testValidateMultipleValues()
	{
		$form = new Validator();

		$value = [''];
		$errors = [];

		$this->assertFalse($form->validateMultipleValues($value, ['required' => true], $errors));

		$value = [1, 2, 'garbage'];
		$this->assertFalse($form->validateMultipleValues($value, ['numeric' => true], $errors));

		$value = [1, 2, 3];
		$this->assertTrue($form->validateMultipleValues($value, ['numeric' => true], $errors));
	}

	///////////////////////////////////////////////////////////////////////////////
	// Special validators

	public function testSubValidator()
	{
		$form = new Validator([
			'subform' => new Validator([
				'first_name' => ['required'],
				'last_name' => ['required'],
			]),
		]);

		// valid data sets
		$expected_values = ['subform' => ['first_name' => 'John', 'last_name' => 'Wayne']];
		$this->assertTrue($form->validate(['subform' => ['first_name' => 'John', 'last_name' => 'Wayne']]));
		$this->assertEquals($expected_values, $form->getValues());

		$this->assertTrue($form->validate(['subform' => ['first_name' => 'John', 'last_name' => 'Wayne', 'garbage' => 'garbage']]));
		$this->assertEquals($expected_values, $form->getValues());

		// invalid data sets
		$form->setValues([]);
		$this->assertFalse($form->validate([]));
		$this->assertFalse($form->validate(['subform' => []]));
		$this->assertFalse($form->validate(['subform' => ['last_name' => 'Wayne']]));
		$this->assertFalse($form->validate(['subform' => ['first_name' => '', 'last_name' => 'Wayne']]));

		// subform + allow empty (i.e. test that values are passed to subform)
		$form->setValues(['subform' => ['first_name' => 'John']]);
		$this->assertTrue($form->validate(['subform' => ['last_name' => 'Doe']]), 'Values are passed to subform');

		// subform + extraneous (i.e. test that we are careful in passing the values to the subform)
		$form->setValues([
			'subform' => ['first_name' => 'John', 'email' => 'john@doe.com'],
		]);
		$this->assertTrue($form->validate(['subform' => ['last_name' => 'Doe']], ['ignore_extraneous' => false]), 'Subform also ignore extraneous default');

		$form->setValues([
			'subform' => ['first_name' => 'John', 'last_name' => 'Foobar', 'email' => 'john@doe.com'],
		]);
		$this->assertTrue($form->validate([], ['ignore_extraneous' => false]), 'Subform also ignore extraneous default');
	}

	public function testEach()
	{
		$form = new Validator([
			'list' => ['each' => ['min_length' => 1, 'max_length' => 4]],
		]);

		$this->assertTrue($form->validate(['list' => ['a', 'b', 'c']]));
		$this->assertEquals(['list' => ['a', 'b', 'c']], $form->getValues());

		$form->setValues([]);
		$this->assertTrue($form->validate(['garbage' => 'garbage']));
		$this->assertEquals(['list' => []], $form->getValues(), 'List is set and casted to array when missing');

		$this->assertFalse($form->validate(['list' => 'garbage']), 'When not an array, autocasted, but does not validate because max_length failed');
		$this->assertEquals([0 => ['max_length' => 4]], $form->getErrors('list'));

		$this->assertFalse($form->validate(['list' => ['garbage']]), 'When an array with garbage, does not validate');
		$this->assertEquals([0 => ['max_length' => 4]], $form->getErrors('list'));

		// with required = false and allow_empty = false
		$form->setValues([]);
		$this->assertTrue($form->validate(
			['list' => []],
			['allow_empty' => false]
		), "Since it is not required, allow_empty won't be triggered");

		// with required = true
		$form = new Validator([
			'list' => ['required' => true, 'each' => []],
		]);
		$this->assertTrue($form->validate(['list' => 'garbage']));
		$this->assertEquals(['list' => ['garbage']], $form->getValues(), 'Original value is casted to an array');

		$this->assertFalse($form->validate(['list' => []]), 'Empty array does not pass required = true');
		$this->assertEquals([
			'required' => true,
		], $form->getErrors('list'));

		$form->setValues([]);
		$this->assertFalse($form->validate(['garbage' => 'garbage']));
		$this->assertEquals(['list' => []], $form->getValues(), 'List is set and casted to array, when required and empty');

		// used in conjuction with is_array type check
		$form = new Validator([
			'list' => ['is_array', 'each' => ['min_length' => 1, 'max_length' => 4]],
		]);

		$this->assertFalse($form->validate(['list' => 'garbage']), 'When not an array, does not validate');
		$this->assertEquals(['is_array' => true], $form->getErrors('list'));
		$this->assertEquals(['list' => 'garbage'], $form->getValues(), 'Original value is not casted to an array when is_array is used');

		// with required = true inside the each
		$form = new Validator([
			'list' => ['each' => ['required' => true]],
		]);
		$this->assertTrue($form->validate(['list' => []]), 'Ok for an empty array');
		$form->setValues([]);
		$this->assertFalse($form->validate(['list' => ['']]), 'Not ok for an empty array with a empty string inside it');
		$this->assertEquals([0 => ['required' => true]], $form->getErrors('list'));
	}

	public function testEachWithSubValidator()
	{
		$form = new Validator([
			'list' => ['each' => new Validator([
				'name' => ['required'],
			])],
		]);

		$this->assertTrue($form->validate([
			'list' => [
				['name' => 'John'],
				['name' => 'Jane'],
			],
		]), 'Each can be used with a subform to validate a array of arrays');

		$form->setValues([]);
		$this->assertFalse($form->validate([
			'list' => [
				['name' => 'John', 'age' => '12'],
				['age' => '12'],
			],
		]), 'Each can be used with a subform to validate a array of arrays (validation should fail)');

		$this->assertEquals([
			1 => [
				'name' => [
					'required' => true,
				],
			],
		], $form->getErrors('list'), 'Error array contains the offset');

		// with default values
		$form->setValues([
			'list' => [
				0 => [],
				1 => ['name' => 'Jane'],
			],
		]);
		$this->assertFalse($form->validate([
			'list' => [
				['name' => 'John', 'age' => '12'],
				['age' => '12'],
			],
		]), 'Default values are not deep-merged (like for a normal each)');

		$form = new Validator([
			'list' => ['is_array', 'each' => new Validator([
				'name' => ['required'],
			])],
		]);
		$this->assertFalse($form->validate(['list' => 'foobar']), 'Not an array');
		$this->assertTrue($form->validate(['list' => [['name' => 'foobar']]]), 'List is array and has a name, all good');
	}

	public function testConditionalValue()
	{
		$form = new Validator([
			'field' => ['required' => function ($form) { return true; }],
		]);

		$this->assertTrue($form->validate(['field' => 42]), 'Required evaluates to true');
		$form->setValues([]);
		$this->assertFalse($form->validate([]), 'Required evaluates to true');
		$this->assertFalse($form->validate(['field' => '']), 'Required evaluates to true');

		$form = new Validator([
			'field' => ['required' => function ($form) { return false; }],
		]);

		$this->assertTrue($form->validate(['field' => 42]), 'Required evaluates to false');
		$form->setValues([]);
		$this->assertTrue($form->validate([]), 'Required evaluates to false');
		$form->setValues([]);
		$this->assertTrue($form->validate(['field' => '']), 'Required evaluates to false');
	}

	public function testConditionalValueWithSubform()
	{
		$form = new Validator([
			'main_field' => ['required'],
			'options' => new Validator([
				'sub_field' => ['required' => function ($form) {
					return $form->getParent()->main_field == 42;
				}],
			]),
		]);

		$this->assertTrue($form->validate(['main_field' => 42, 'options' => ['sub_field' => 1]]), 'Required evaluates to true');
		$form->setValues([]);
		$this->assertFalse($form->validate(['main_field' => 42, 'options' => []]), 'Required evaluates to true');

		$form->setValues([]);
		$this->assertTrue($form->validate(['main_field' => 0, 'options' => ['sub_field' => 1]]), 'Required evaluates to false');
		$form->setValues([]);
		$this->assertTrue($form->validate(['main_field' => 0, 'options' => []]), 'Required evaluates to false');
	}

	public function testConditionalRules()
	{
		$form = new Validator([
			'field' => [],
			'options' => function ($form) {
				return $form->field == 'x' ? ['required' => true] : [];
			},
		]);

		$this->assertTrue($form->validate(['field' => 42]));
		$this->assertFalse($form->validate(['field' => 'x']), 'Options is required when field = x');
	}

	public function testCallback()
	{
		$form = new Validator([
			'field' => ['callback' => function (&$value, $form) {
				$value = 42;
				$form->proof = 'it worked!';

				return true;
			}],
		]);

		$this->assertTrue($form->validate(['field' => 1]));
		$this->assertEquals(42, $form->getValue('field'), 'Callback can modify value');
		$this->assertEquals('it worked!', $form->getValue('proof'), 'Callback has access to form object');

		$identical_password_validator = function ($confirmation, $form) {
			return $form->password == $confirmation;
		};

		$form = new Validator([
			'password' => ['required', 'min_length' => 6],
			'password_confirm' => ['required', 'identical' => $identical_password_validator],
		]);

		$this->assertTrue($form->validate(['password' => 'abcdef', 'password_confirm' => 'abcdef']));
		$this->assertFalse($form->validate(['password' => 'abcdef', 'password_confirm' => '']));
		$this->assertFalse($form->validate(['password' => 'abcdef', 'password_confirm' => 'x']));

		// order is important!
		$form = new Validator([
			'password_confirm' => ['required', 'identical' => $identical_password_validator],
			'password' => ['required', 'min_length' => 6],
		]);

		$this->assertFalse($form->validate(['password' => 'abcdef', 'password_confirm' => 'abcdef']));
		$this->assertFalse($form->validate(['password' => 'abcdef', 'password_confirm' => '']));
		$this->assertFalse($form->validate(['password' => 'abcdef', 'password_confirm' => 'x']));
	}

	///////////////////////////////////////////////////////////////////////////////
	// Tests with various validators

	public function testDate()
	{
		$form = new Validator([
			'birthday' => ['date'],
		]);
		$this->assertTrue($form->validate([
			'birthday' => '2000-01-01',
		]));
		$this->assertFalse($form->validate([
			'birthday' => '01/01/2001',
		]));

		$form = new Validator([
			'birthday' => ['date' => null],
		]);
		$this->assertTrue($form->validate([
			'birthday' => '2000-01-01',
		]));
		$this->assertTrue($form->validate([
			'birthday' => '01/01/2001',
		]));
	}
}
