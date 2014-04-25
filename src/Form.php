<?php

/**
 * Licensed under the MIT license.
 *
 * For the full copyright and license information, please view the LICENSE file.
 *
 * @author Rémi Lanvin <remi@cloudconnected.fr>
 * @link https://github.com/rlanvin/php-form
 */

/**
 */
class Form implements ArrayAccess
{
	const EACH = 'each';

	/** 
	 * The values as assoc (possibly recursive) array.
	 * [field_name => field_value]
	 * @var array
	 */
	protected $values = array();

	/**
	 * The rules as a big assoc (possibly recursive) array.
	 * [
	 *   field_name => [
	 *     rule_name => rule_value
	 *     ....
	 *   ]
	 * ]
	 * @var array
	 */
	protected $rules = array();

	/**
	 * The validation errors as an array
	 * [
	 *   field_name => [
	 *     rule_name => rule_value
	 *     ...
	 *   ]
	 * ]
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Constructor
	 *
	 * @param $rules array The form definition, an assoc array of field_name => rules
	 */
	public function __construct($rules = array(), $default_values = array())
	{
		$this->values = $default_values;

		$this->setRules($rules);
	}

	/**
	 * @internal
	 */
	public static function checkStringNotEmpty($field, $name = 'Field name')
	{
		if ( ! is_string($field) ) {
			throw new InvalidArgumentException(sprintf("$name must be a string (%s given)", gettype($field)));
		}
		if ( ! $field ) {
			throw new InvalidArgumentException("$name cannot be empty");
		}
	}

// RULES

	/**
	 * Set the rules of the form.
	 *
	 * @param $rules array
	 * @return $this
	 */
	public function setRules($field_or_rules, array $rules = array())
	{
		// set the rules for one particular field
		if ( is_string($field_or_rules) ) {
			if ( ! $field_or_rules ) {
				throw new InvalidArgumentException("Field name cannot be empty");
			}
			$this->rules[$field_or_rules] = self::expandRulesArray($rules);
			return $this;
		}

		// set the rules of the form
		if ( is_array($field_or_rules) ) {
			$this->rules = self::parseRules($field_or_rules);
			return $this;
		}

		throw new InvalidArgumentException("Unsupported parameter type");
	}

	/**
	 * Add rules to existing form.
	 * Rules will be merged to existing rules.
	 *
	 * @param $rules array
	 * @return $this
	 */
	public function addRules(array $rules)
	{
		$this->rules = array_merge_recursive($this->rules, self::parseRules($rules));
		return $this;
	}

	/**
	 * Return the rules array of this form or of a single field.
	 *
	 * If the field is not set in the rules array, it'll return empty array.
	 * 
	 * @return array|Form
	 */
	public function getRules($field = '')
	{
		if ( ! is_string($field) ) {
			throw new InvalidArgumentException(sprintf("Field name must be a string (%s given)", gettype($field)));
		}

		if ( ! $field ) {
			return $this->rules;
		}

		if ( ! isset($this->rules[$field]) ) {
			return array();
		}

		return $this->rules[$field];
	}

	/**
	 * Return true if the given field name as rules associated
	 *
	 * @param $field string
	 * @return true
	 */
	public function hasRules($field)
	{
		self::checkStringNotEmpty($field);

		return isset($this->rules[$field]) && ! empty($this->rules[$field]);
	}

	/**
	 * Return the value of a given rule for a given field or null if the rule
	 * or the field is not set in this form.
	 * 
	 * @param $field string
	 * @param $rule_name string
	 * @return mixed
	 */
	public function getRuleValue($field, $rule_name)
	{
		self::checkStringNotEmpty($field);

		$rules = $this->getRules($field);

		self::checkStringNotEmpty($rule_name, 'Rule name');

		if ( ! array_key_exists($rule_name, $rules) ) {
			return null;
		}

		return $rules[$rule_name];
	}

	/**
	 * Returns true if a field is required.
	 * Shortcut for $this->getRuleValue($field, 'required');
	 *
	 * @return bool
	 */
	public function isRequired($field)
	{
		return !! $this->getRuleValue($field, 'required');
	}

	/**
	 * Check a rules array.
	 *
	 * @param $rules array
	 * @return array
	 */
	public static function parseRules(array $rules)
	{
		foreach ( $rules as $field => & $field_rules ) {
			self::checkStringNotEmpty($field);

			if ( is_array($field_rules) ) {
				$field_rules = self::expandRulesArray($field_rules);
			} elseif ( $field_rules instanceof self ) {
				// do nothing
			} else {
				throw new InvalidArgumentException("Invalid rules for field $field, must be array or ".__CLASS__);
			}
		}
		return $rules;
	}

	/**
	 * Makes sure the rules array is properly formatted, i.e. with the rule
	 * name as key.
	 *
	 * This is just a small method so we can use shorter syntax in the code.
	 *
	 * For example:
	 *   ['required', 'min_length' => 2]
	 * becomes
	 *   ['required' => true, 'min_length' => 2]
	 *
	 * @param $array array
	 * @return array
	 */
	public static function expandRulesArray(array $array)
	{
		$new_array = array();
		foreach ( $array as $key => $param ) {
			// the validator has been written as array value
			if ( is_int($key) ) {
				self::checkStringNotEmpty($param, 'Rule name');
				$new_array[$param] = true;
			}
			elseif ( $key == '' ) {
				throw new InvalidArgumentException("Rule name cannot be empty");
			}
			elseif ( $key == self::EACH ) {
				// these special keys have nested rules
				$new_array[$key] = self::expandRulesArray($param);
			}
			// nothing to flip
			else {
				$new_array[$key] = $param;
			}
		}
		return $new_array;
	}

// VALUES

	/**
	 * Return all the values as an assoc array.
	 * [field_name => value]
	 *
	 * @return array
	 */
	public function getValues()
	{
		return $this->values;
	}

	/** 
	 * Return the value of a single field.
	 *
	 * @param $field string Field name
	 * @param $default mixed Value to return if not exist
	 * @return mixed
	 */
	public function getValue($field, $default = null)
	{
		self::checkStringNotEmpty($field);
		return array_key_exists($field, $this->values) ? $this->values[$field] : $default;
	}

	/**
	 * Magic getter to enable $form->field syntax to get a value.
	 *
	 * @see getValue
	 */
	public function __get($field)
	{
		return $this->getValue($field);
	}

	/**
	 * ArrayAccess interface to enable $form['field'] syntax to get a value.
	 *
	 * @see getValue
	 */
	public function offsetGet($field)
	{
		return $this->getValue($field);
	}

	/**
	 * Set default values of the form.
	 * The values won't be validated.
	 *
	 * @return $this
	 */
	public function setValues(array $values)
	{
		$this->values = $values;
		return $this;
	}

	/** 
	 * Set a single value.
	 *
	 * @param $field string
	 * @param $value mixed
	 * @return $this
	 */
	public function setValue($field, $value)
	{
		self::checkStringNotEmpty($field);
		$this->values[$field] = $value;
		return $this;
	}

	/**
	 * Magic setter to enable $form->field = 42 syntax.
	 *
	 * @see setValue
	 */
	public function __set($field, $value)
	{
		return $this->setValue($field, $value);
	}

	/**
	 * ArrayAccess interface to enable $form['field'] = 42 syntax.
	 *
	 * @see setValue
	 */
	public function offsetSet($field, $value)
	{
		return $this->setValue($field, $value);
	}

	/**
	 * Merge values with existing array.
	 *
	 * @param $values array
	 * @return $this
	 */
	public function addValues(array $values)
	{
		$this->values = array_merge($this->values, $values);
		return $this;
	}

	/**
	 * @internal
	 */
	public function offsetExists($field)
	{
		return array_key_exists($field, $this->values);
	}

	/**
	 * @internal
	 */
	public function offsetUnset($field)
	{
		if ( array_key_exists($field, $this->values) ) {
			unset($this->values[$field]);
		}
	}

// ERRORS

	/**
	 * Return the errors array of the form or of a given field
	 *
	 * Error array is like the rules array, expect is only contains the invalid
	 * fields and rules. Example:
	 * [
	 *   field_name => [
	 *     rule_name => rule_value
	 *     ...
	 *   ]
	 * ]
	 *
	 * @param $field string If empty, return the whole error array
	 * @return array
	 */
	public function getErrors($field = '')
	{
		if ( ! is_string($field) ) {
			throw new InvalidArgumentException(sprintf("Field name must be a string (%s given)", gettype($field)));
		}

		if ( ! $field ) {
			return $this->errors;
		}

		if ( ! isset($this->errors[$field]) ) {
			return array();
		}

		return $this->errors[$field];
	}

	/**
	 * @internal
	 * Used for testing and debugging
	 */
	public function setErrors(array $errors)
	{
		$this->errors = $errors;
		return $this;
	}

	/**
	 * Return true if a field or the entire form has errors
	 *
	 * @param $field string optional
	 * @return bool
	 */
	public function hasErrors($field = '')
	{
		if ( ! is_string($field) ) {
			throw new InvalidArgumentException(sprintf("Field name must be a string (%s given)", gettype($field)));
		}

		if ( ! $field ) {
			return ! empty($this->errors);
		}

		return isset($this->errors[$field]);
	}

	public function addError($message, $field = '', $param = true)
	{
		$this->errors[$field][$message] = $param;
	}

// VALIDATION

	/**
	 * The values that are not in $rules array will be ignored (and not saved in the class).
	 * @return bool
	 */
	public function validate(array $values, array $opt = array())
	{
		$opt = array_merge(array(
			'use_default' => true,
			'stop_on_error' => true,
			'allow_empty' => true
		), $opt);

		// reset errors
		$this->errors = array();

		foreach ( $this->rules as $field => $rules ) {
			$value = null;

			// use provided value if exists
			if ( array_key_exists($field, $values) ) {
				$value = $values[$field];
			}
			// otherwise, use default if exists
			elseif ( $opt['use_default'] && array_key_exists($field, $this->values) ) {
				$value = $this->values[$field];
			}

			// subform
			if ( $rules instanceof self ) {
				if ( ! is_array($value) ) {
					$value = array();
				}
				$errors = $rules->validate($value, $opt);
				$value = $rules->getValues();
				if ( $errors !== true ) {
					$errors = $rules->getErrors();
				}
			}
			// normal rules array
			else {
				$errors = $this->validateValue($value, $rules, $opt);
			}

			if ( $errors !== true ) {
				$this->errors[$field] = $errors;
			}

			// merge with the $values array of the class for later use (e.g. repopulate the form)
			$this->values[$field] = $value;
		}

		return empty($this->errors);
	}

	/**
	 * Validates one single value ($value) against a set of rules ($rules)
	 */
	public function validateValue(& $value, array $rules, array $opt = array())
	{
		$opt = array_merge(array(
			'stop_on_error' => true,
			'allow_empty' => true
		), $opt);

		$errors = array();

		// check if value is required.
		// if the value is NOT required and NOT present, we do no run any other validator
		if ( $value === null || (is_array($value) && empty($value)) || (is_string($value) && trim($value) === '') ) {
			if ( isset($rules['required']) && $rules['required'] === true ) {
				$errors['required'] = true;
				if ( $opt['stop_on_error'] ) {
					return $errors;
				}
			}
			elseif ( $opt['allow_empty'] ) {
				// cast to an array if necessasry
				if ( isset($rules[self::EACH]) ) {
					$value = array();
				}
				return true;
			}
		}
		unset($rules['required']);

		foreach ( $rules as $validator => $param ) {
			$ret = true;

			// special iterative validator for arrays
			if ( $validator === self::EACH ) {
				$ret = $this->validateMultipleValues($value, $param, $errors);
			}
			else {
				// validator function (in Validator class)
				if ( method_exists('Validator', $validator) ) {
					$ret = call_user_func_array(array('Validator', $validator), array(&$value, $param));
				}
				// callback function (custom validator)
				elseif ( is_callable($param) ) {
					$ret = call_user_func_array($param, array(&$value, $this));
					$param = true; // I don't want to set a callback into the errors array
				}
				else {
					throw new InvalidArgumentException("Validator $validator not found");
				}
			}
			// if the validator failed, we store the name of the validator in the $errors array
			// XXX shoudln't it be if $ret !== true ?
			if ( $ret === false ) {
				$errors[$validator] = $param;
				if ( $opt['stop_on_error'] ) {
					return $errors;
				}
			}
		}

		return empty($errors) ? true : $errors;
	}

	protected function validateMultipleValues(& $values, $rules, & $errors = array())
	{
		if ( is_null($values) ) {
			$values = array();
		}

		// if the value is not an array : error
		if ( ! is_array($values) ) {
			return false;
		}

		foreach ( $values as &$value ) {
			$ret = $this->validateValue($value, $rules);
			if ( $ret !== true ) {
				$errors += $ret;
			}
		}

		// return true because $errors is already filled 
		// we dont want validate() to fill it again
		return true;
	}
}