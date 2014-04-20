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
	protected $default_values = array();
	protected $values = array();

	protected $rules = array();
	protected $errors = array();

	public function __construct($rules = array(), $default_values = array())
	{
		$this->default_values = $default_values;
		$this->values = $default_values;

		$this->setRules($rules);
	}

	/**
	 * @return $this
	 */
	public function setRules($rules)
	{
		$this->rules = self::parseRules($rules);
		return $this;
	}

	public function getRules()
	{
		return $this->rules;
	}

	public function hasRule($field_name)
	{
		return isset($this->rules[$field_name]);
	}

	public function setRule($field_name, $rules)
	{
		$this->rules[$field_name] = self::parseRules(array($field_name => $rules));
	}

	public function addRules($rules)
	{
		$this->rules = array_merge($this->rules, self::parseRules($rules));
		return $this;
	}

	/**
	 * Returns if a field is required
	 */
	public function isRequired($field)
	{
		return isset($this->rules[$field]) && isset($this->rules[$field]['required']) && $this->rules[$field]['required'];
	}

	/**
	 * Makes sure the array is properly formatted.
	 * For example ['required', 'min_length' => 2] becomes ['required' => true, 'min_length' => 2]
	 * This is just a small method so we can use shorter syntax in the code.
	 * @return array
	 */
	static public function parseRules($rules)
	{
		foreach ( $rules as $field => & $field_rules ) {
			$field_rules = self::arrayFlip($field_rules);
		}
		return $rules;
	}
	static public function arrayFlip($array)
	{
		$new_array = array();
		foreach ( $array as $key => $param ) {
			if ( is_integer($key) ) {
				$new_array[$param] = true;
			}
			// these special keys have nested rules
			elseif ( $key == 'sanitize' || $key == 'multiple' ) {
				$new_array[$key] = self::arrayFlip($param);
			}
			else {
				$new_array[$key] = $param;
			}
		}
		return $new_array;
	}

	public function getRuleParam($field, $validator)
	{
		if ( array_key_exists($field, $this->rules) && array_key_exists($validator, $this->rules[$field]) ) {
			return $this->rules[$field][$validator];
		}
		return null;
	}

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

	/**
	 * The values that are not in $rules array will be ignored (and not saved in the class).
	 * @return bool
	 */
	public function validate($values = array(), array $opt = array())
	{
		$opt = array_merge(array(
			'rules' => $this->rules,
			'use_default_if_missing' => false
		), $opt);

		// foreach "field_name" => $rules
		foreach ( $opt['rules'] as $key => $rules ) {
			$value = null;
			// use provided value if exists
			if ( array_key_exists($key, $values) ) {
				$value = $values[$key];
			}
			// otherwise, use default
			elseif ( $opt['use_default_if_missing'] && array_key_exists($key, $this->values) ) {
				$value = $this->values[$key];
			}

			// first we sanitize the value
			// if ( array_key_exists('sanitize', $rules) ) {
			// 	$value = $this->sanitizeValue($value, $rules["sanitize"]);
			// 	unset($rules['sanitize']);
			// }

			$errors = $this->validateValue($value, $rules, $opt);
			if ( $errors !== true ) {
				$this->errors[$key] = $errors;
			}

			// merge with the $values array of the class for later use (e.g. repopulate the form)
			$this->values[$key] = $value;
		}
		return empty($this->errors);
	}

	// public function sanitizeValue($value, $rules, array $opt = array())
	// {
	// 	foreach ( $rules as $sanitizer => $param ) {

	// 		// Sanitizer function (in Sanitizer class)
	// 		if ( method_exists('Sanitizer', $sanitizer) ) {
	// 			$value = call_user_func_array(array('Sanitizer', $sanitizer), array($value, $param));
	// 		}
	// 		// callback function (custom sanitizer)
	// 		elseif ( is_callable($param) ) {
	// 			$value = call_user_func_array($param, array($value, $this));
	// 		}
	// 		else {
	// 			throw new InvalidArgumentException("Sanitizer $sanitizer not found");
	// 		}
	// 	}

	// 	return $value;
	// }

	/**
	 * Validates one single value ($value) against a set of rules ($rules)
	 */
	public function validateValue(&$value, $rules, array $opt = array())
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
				if ( isset($rules['multiple']) ) {
					$value = array();
				}
				return true;
			}
		}
		unset($rules['required']);

		foreach ( $rules as $validator => $param ) {
			$ret = true;

			// special iterative & recursive validator for arrays
			if ( $validator === 'multiple' ) {
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