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
class Form
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
	 *     rule_name => rule_option
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
			throw new InvalidArgumentException("Field name must be a string");
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
		if ( ! is_string($field) ) {
			throw new InvalidArgumentException("Field name must be a string");
		}
		if ( ! $field ) {
			throw new InvalidArgumentException("Field name cannot be empty");
		}

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
		if ( ! $field ) {
			throw new InvalidArgumentException("Field name cannot be empty");
		}

		$rules = $this->getRules($field);

		if ( ! is_string($rule_name) ) {
			throw new InvalidArgumentException("Rule name must a string");
		}

		if ( ! $rule_name ) {
			throw new InvalidArgumentException("Rule name cannot be empty");
		}

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
			if ( ! is_string($field) ) {
				throw new InvalidArgumentException(sprintf("Field name must be a string (%s given)", gettype($field)));
			}

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
				if ( ! is_string($param) ) {
					throw new InvalidArgumentException("Rule name must be a string");
				}
				if ( ! $param ) {
					throw new InvalidArgumentException("Rule name cannot be empty");
				}
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

	public function getValues()
	{
		return $this->values;
	}

	public function getValue($name)
	{
		return array_key_exists($name, $this->values) ? $this->values[$name] : null;
	}

	public function __get($name)
	{
		return $this->getValue($name);
	}

	/**
	 * Set default values of the form.
	 * The values won't be validated.
	 * @return $this
	 */
	public function setValues($values)
	{
		$this->values = $values;
		return $this;
	}

	public function setValue($name, $value)
	{
		$this->values[$name] = $value;
		return $this;
	}

	public function __set($name, $value)
	{
		return $this->setValue($name, $value);
	}

	public function addValues($values)
	{
		$this->values = array_merge($this->values, $values);
		return $this;
	}

//@{
	/**
	 * Error management
	 */

	/**
	 * If $name is not given, returns all the errors.
	 * @return array
	 */
	public function getErrors($name = null)
	{
		if ( $name === null ) {
			return $this->errors;
		}
		else {
			return isset($this->errors[$name]) ? $this->errors[$name] : array();
		}
	}

	/**
	 * Return true if a field has errors
	 */
	public function hasErrors($name = null)
	{
		if ( $name === null ) {
			return ! empty($this->errors);
		}
		else {
			return isset($this->errors[$name]);
		}
	}

	public function addError($message, $field = '', $param = true)
	{
		$this->errors[$field][$message] = $param;
	}

//@}

	public function validate($values = array(), array $opt = array())
	{
		return $this->validates($values, $opt);
	}

	/**
	 * The values that are not in $rules array will be ignored (and not saved in the class).
	 * @return bool
	 */
	public function validates($values = array(), array $opt = array())
	{
		$opt = array_merge(array(
			'rules' => $this->rules,
			'use_default_if_missing' => false
		), $opt);

		foreach ( $opt['rules'] as $field => $rules ) {
			$value = null;

			// use provided value if exists
			if ( array_key_exists($field, $values) ) {
				$value = $values[$field];
			}
			// otherwise, use default
			elseif ( $opt['use_default_if_missing'] && array_key_exists($field, $this->values) ) {
				$value = $this->values[$field];
			}

			if ( $rules instanceof self ) {
				$rules->validates($value, array(
					'use_default_if_missing' => $opt['use_default_if_missing']
				));
				$errors = $rules->getErrors();
			}
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
			'stop_on_error' => true
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
			else {
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

			// special iterative & recursive validator for arrays
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

		return true; // return true because $errors is already filled
	}
}