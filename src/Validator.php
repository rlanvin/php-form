<?php

/**
 * Licensed under the MIT license.
 *
 * For the full copyright and license information, please view the LICENSE file.
 *
 * @author Rémi Lanvin <remi@cloudconnected.fr>
 * @link https://github.com/rlanvin/php-form
 */

namespace Form;

require_once __DIR__.'/rules.php';

/**
 */
class Validator implements \ArrayAccess
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
	 * Store the parent Validator object when it's a subform, so it's accessible
	 * within a validator callback.
	 * @var Validator
	 */
	protected $parent = null;

	/**
	 * Default options array
	 * @var array
	 */
	protected $options = array(
		'use_default' => true, // use the values provided as default values
		'stop_on_error' => true, // stop at the first error
		'allow_empty' => true, // bypass all validators when the value is empty (otherwise: run validators even if the value is empty)
		'ignore_extraneous' => true // ignore values with no rules (otherwise: throws a validation error)
	);

	/**
	 * Constructor
	 *
	 * @param $rules array The form definition, an assoc array of field_name => rules
	 */
	public function __construct($rules = array(), $options = array())
	{
		$this->setOptions($options);
		$this->setRules($rules);
	}

	/**
	 * @internal
	 */
	public static function checkStringNotEmpty($field, $name = 'Field name')
	{
		if ( ! is_string($field) ) {
			throw new \InvalidArgumentException(sprintf("$name must be a string (%s given)", gettype($field)));
		}
		if ( ! $field ) {
			throw new \InvalidArgumentException("$name cannot be empty");
		}
	}

	/**
	 * @internal
	 *
	 * Returns the base field name, and a array of subfields (if any)
	 * Example:
	 * address[street][number]
	 * becomes
	 * ['address','street','number']
	 *
	 * @return array
	 */
	public static function expandFieldName($field)
	{
		if ( ! preg_match('/^(\w+)((\[\w+\])*)$/',$field, $matches) ) {
			return array($field);
		}

		if ( empty($matches[2]) ) {
			return array($field);
		}

		return array_merge(
			array($matches[1]), // base field
			preg_split('/\]\[/',trim($matches[2],'[]')) // subfields
		);
	}

	public function setOptions(array $options)
	{
		$this->options = array_merge($this->options, $options);
	}

	public function getOptions()
	{
		return $this->options;
	}

///////////////////////////////////////////////////////////////////////////////
// RULES

	/**
	 * Set the rules of the form.
	 *
	 * This methods can be called two ways:
	 * setRules(string $field_name, array|self $rules) to set the rules for a field
	 * or setRules(array $rules) to set the entire rules array
	 *
	 * @param $rules array|self An array or a sub-validator
	 * @return $this
	 */
	public function setRules($field_or_rules, $rules = array())
	{
		// set the rules for one particular field
		if ( is_string($field_or_rules) ) {
			if ( ! $field_or_rules ) {
				throw new \InvalidArgumentException("Field name cannot be empty");
			}
			if ( is_array($rules) ) {
				$this->rules[$field_or_rules] = self::expandRulesArray($rules);
			}
			elseif ( $rules instanceof self ) {
				$this->rules[$field_or_rules] = $rules;
			}
			else {
				throw new \InvalidArgumentException("Rules must be an array or an instance of ".__CLASS__);
			}
			return $this;
		}

		// set the rules of the form
		if ( is_array($field_or_rules) ) {
			$this->rules = self::parseRules($field_or_rules);
			return $this;
		}

		throw new \BadMethodCallException("Unsupported parameter type");
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
		// XXX apparently this creates a problem when trying to merge rules
		// that are subforms
		$this->rules = array_merge_recursive($this->rules, self::parseRules($rules));
		return $this;
	}

	/**
	 * Return the rules array of this form or of a single field.
	 *
	 * If the field is not set in the rules array, it'll return empty array.
	 * For nested validators, the field name can be written like this:
	 * 'a[b][c]'
	 * 
	 * @return array|Validator
	 */
	public function getRules($field = '')
	{
		if ( ! is_string($field) ) {
			throw new \InvalidArgumentException(sprintf("Field name must be a string (%s given)", gettype($field)));
		}

		if ( ! $field ) {
			return $this->rules;
		}

		if ( ! isset($this->rules[$field]) ) {
			$field = self::expandFieldName($field);
		}
		else {
			$field = array($field);
		}

		// now $field is an array, for example 'a[b][c]' is now ['a','b','c']

		$rules = $this->rules;
		foreach ( $field as $f ) {
			if ( $rules instanceof self ) {
				$rules = $rules->getRules($f);
			}
			elseif ( isset($rules[$f]) ) {
				$rules = $rules[$f];
			}
			else {
				return array(); // not found, let's stop now
			}

			// execute closure
			if ( is_callable($rules) ) {
				$rules = call_user_func_array($rules, array($this));
				if ( is_array($rules) ) {
					$rules = $this->expandRulesArray($rules);
				}
			}
		};

		return $rules;
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

		// return isset($this->rules[$field]) && ! empty($this->rules[$field]);
		$rules = $this->getRules($field);
		return !empty($rules);
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
			} elseif ( is_callable($field_rules) ) {
				// do nothing
			} else {
				throw new \InvalidArgumentException("Invalid rules for field $field, must be array, closure or ".__CLASS__);
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
				throw new \InvalidArgumentException("Rule name cannot be empty");
			}
			elseif ( $key == self::EACH ) {
				// these special keys have nested rules
				if ( is_array($param) ) {
					$new_array[$key] = self::expandRulesArray($param);
				}
				elseif ( $param instanceof self ) {
					// do nothing at this stage
					$new_array[$key] = $param;
				}
				else {
					throw new \InvalidArgumentException('The rule "each" needs an array or a '.__CLASS__);
				}
			}
			// nothing to flip
			else {
				$new_array[$key] = $param;
			}
		}
		return $new_array;
	}

///////////////////////////////////////////////////////////////////////////////
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

		if ( array_key_exists($field, $this->values) ) {
			return $this->values[$field];
		}
		else {
			$field = self::expandFieldName($field);
			$values = $this->values;
			foreach ( $field as $f ) {
				if ( ! isset($values[$f]) ) {
					return $default;
				}
				$values = $values[$f];
			}
			return $values;
		}
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

///////////////////////////////////////////////////////////////////////////////
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
			throw new \InvalidArgumentException(sprintf("Field name must be a string (%s given)", gettype($field)));
		}

		if ( ! $field ) {
			return $this->errors;
		}

		if ( isset($this->errors[$field]) ) {
			$errors = $this->errors[$field];
		}
		else {
			$field = self::expandFieldName($field);
			$errors = $this->errors;
			foreach ( $field as $f ) {
				if ( ! isset($errors[$f]) ) {
					return array();
				}
				$errors = $errors[$f];
			}
		}

		return $errors;
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
			throw new \InvalidArgumentException(sprintf("Field name must be a string (%s given)", gettype($field)));
		}

		$errors = $this->errors;

		if ( $field ) {
			$errors = $this->getErrors($field);
		}

		return ! empty($errors);
	}

	public function addError($message, $field = '', $param = true)
	{
		$this->errors[$field][$message] = $param;
	}

///////////////////////////////////////////////////////////////////////////////
// VALIDATION

	/**
	 * Validate a array of values, using the rules stored in the class.
	 * The values that are not in the rules array will be ignored (and not saved in the class).
	 * The values that are in the rules array will be saved in the class for
	 * later access.
	 * The validation errors will also be saved.
	 * @return bool
	 */
	public function validate(array $values, array $opt = array())
	{
		$opt = array_merge($this->options, $opt);

		// reset errors
		$this->errors = array();
		$errors = array();

		foreach ( $this->rules as $field => $rules ) {
			$value = null;

			// closure
			if ( is_callable($rules) ) {
				$rules = call_user_func_array($rules, array($this));
				if ( is_array($rules) ) {
					$rules = $this->expandRulesArray($rules);
				}
				elseif ( $rules instanceof self ) {
					// do nothing
				}
				else {
					throw new \RuntimeException(sprintf(
						'Rules closure for field %s must return an array of rules or a '.__CLASS__.' (%s returned)',
						$field,
						gettype($rules))
					);
				}
			}

			// subform => recursive check
			if ( $rules instanceof self ) {
				// use provided value if exists
				if ( array_key_exists($field, $values) ) {
					$value = $values[$field];
				}

				if ( ! is_array($value) ) {
					$value = array();
				}
				// set the parent so it's accessible from a callback function
				$rules->setParent($this);
				// pass default values to the subform
				$rules->setValues($this->getValue($field) ?: array());
				// pass the value as it (the subform will take care of using default)
				$ret = $rules->validate($value, $opt);
				$value = $rules->getValues();
				$errors = $rules->getErrors();
			}
			// normal
			else {
				// use provided value if exists
				if ( array_key_exists($field, $values) ) {
					$value = $values[$field];
				}
				// otherwise, use default if exists
				elseif ( $opt['use_default'] && array_key_exists($field, $this->values) ) {
					$value = $this->values[$field];
				}

				$ret = $this->validateValue($value, $rules, $errors, $opt);
			}

			if ( $ret !== true ) {
				$this->errors[$field] = $errors;
			}

			// merge with the $values array of the class for later use (e.g. repopulate the form)
			$this->values[$field] = $value;
		}

		if ( ! $opt['ignore_extraneous'] ) {
			$extraneous = array_diff(array_keys($values), array_keys($this->rules));
			foreach ( $extraneous as $field ) {
				$this->errors[$field] = array('extraneous' => true);
			}
		}

		return empty($this->errors);
	}

	/**
	 * Validates one single value ($value) against a set of rules ($rules).
	 *
	 * This method is designed to be used internaly by validate(), but if needed
	 * it can work an its own.
	 *
	 * @see validate()
	 * @param $value  mixed The value to be validated. This is a reference, as the
	 *                      value can be altered (sanitized, casted, etc.) by 
	 *                      validators
	 * @param $rules  array An array of rules (must have been previously expanded)
	 * @param $errors array (optional) An array where the errors will be returned
	 * @param $opt    array (optional) An array of options
	 * @return bool
	 */
	public function validateValue(& $value, array $rules, array & $errors = array(), array $opt = array())
	{
		$opt = array_merge($this->options, $opt);

		$errors = array();

		// check if value is required.
		// if the value is NOT required and NOT present, we do no run any other validator
		if ( Rule\is_empty($value) ) {
			$required = false;
			if ( array_key_exists('required', $rules) ) {
				$required = $rules['required'];
				if ( is_callable($required) ) {
					$required = call_user_func_array($required, array($this));
				}
			}

			// cast to an array if necessary
			if ( isset($rules[self::EACH]) ) {
				$value = array();
			}

			if ( $required ) {
				$errors['required'] = true;
				if ( $opt['stop_on_error'] ) {
					return false;
				}
			}
			elseif ( $opt['allow_empty'] ) {
				return true;
			}
			// else we pass the value through the validators, even if it's empty
		}
		unset($rules['required']);

		foreach ( $rules as $rule => $param ) {
			$local_errors = array();
			$ret = true;

			// special iterative validator for arrays
			if ( $rule === self::EACH ) {
				$ret = $this->validateMultipleValues($value, $param, $local_errors, $opt);
			}
			else {
				$func = __NAMESPACE__.'\Rule\\'.$rule;
				if ( function_exists($func) ) {
					if ( is_callable($param) ) {
						$param = call_user_func_array($param, array($this));
					}

					if ( $param === true ) { // use default value from the rule
						$ret = call_user_func_array($func, array(&$value));
					} else {
						$ret = call_user_func_array($func, array(&$value, $param));
					}
				}
				// callback function (custom validator)
				elseif ( is_callable($param) ) {
					$ret = call_user_func_array($param, array(&$value, $this));
					$param = true; // I don't want to set a callback into the errors array
				}
				else {
					throw new \InvalidArgumentException("Rule '$rule' not found");
				}

				$local_errors = $param;
			}

			// if the validator failed, we store the name of the validator in the $errors array
			if ( $ret === false ) {
				if ( $rule === self::EACH ) {
					// skip each validator
					$errors += $local_errors;
				}
				else {
					$errors[$rule] = $local_errors;
				}
				if ( $opt['stop_on_error'] ) {
					return false;
				}
			}
		}

		// return empty($errors) ? true : $errors;
		return empty($errors);
	}

	/**
	 * Validate a value that is expected to be an array of values ($values) against
	 * a set of rules ($rules) or a subform
	 * 
	 * This method is designed to be used internaly by validate(), but if needed
	 * it can work an its own.
	 *
	 * @param $values  mixed The value to validate, should be an array or will be
	 *                       casted
	 * @param $rules   mixed An array of rules (expanded), or a subform
	 * @param $errors array (optional) An array where the errors will be returned
	 * @param $opt    array (optional) An array of options
	 * @return bool
	 */
	public function validateMultipleValues(& $values, $rules, array & $errors = array(), array $opt = array())
	{
		$opt = array_merge($this->options, $opt);

		$errors = array();

		// if the value is not an array, cast it
		if ( ! is_array($values) ) {
			$values = array($values);
		}
		// validate against a set of rules
		if ( is_array($rules) ) {
			$local_errors = array();
			foreach ( $values as $key => &$value ) {
				$ret = $this->validateValue($value, $rules, $local_errors, $opt);
				if ( $ret !== true ) {
					$errors[$key] = $local_errors;
				}
			}
		}
		// validate against a subform (to validate array of assoc arrays)
		elseif ( $rules instanceof self ) {
			// set the parent so it's accessible from a callback function
			$rules->setParent($this);

			// subform => recursive check
			foreach ( $values as $key => &$value ) {
				if ( ! is_array($value) ) {
					$value = array();
				}
				// default values are not passed here, because in the context of an array
				// we do not merge (it's how it behaves with a normal "each")
				$rules->setValues(array());
				$ret = $rules->validate($value, $opt);
				$value = $rules->getValues();
				if ( $ret !== true ) {
					$errors[$key] = $rules->getErrors();
				}
			}
		}
		else {
			throw new \InvalidArgumentException("$rules must be an array or a instance of ".__CLASS__);
		}

		// return true because $errors is already filled 
		// we dont want validate() to fill it again
		return empty($errors);
	}

///////////////////////////////////////////////////////////////////////////////
// SUB-FORMS HELPERS

	public function setParent(self $parent)
	{
		$this->parent = $parent;
	}

	public function getParent()
	{
		return $this->parent;
	}
}