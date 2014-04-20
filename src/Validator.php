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
 * Static class Validator (could be a namespace)
 *
 * Each validator is a method that returns a boolean indicating whether the
 * value provided is valid (true) or invalid (false).
 *
 * Validators can optionally alter the value provided, acting as a sanitizer.
 * 
 * Validators can take one additional parameter. To pass the parameter is the
 * Form context, use the following syntax:
 * array('trim'); // no option
 * array('trim' => '/'); // will trim "/"
 */
class Validator
{
	static public function trim(&$value, $charlist)
	{
		// trim will trigger an error if called with something else than a string
		if ( ! is_string($value) || is_int($value) ) {
			return false;
		}

		if ( $charlist === true ) {
			$charlist = " \t\n\r\0\x0B";
		}

		$value = trim($value, $charlist);
		return true;
	}

	static public function regexp($value, $regexp)
	{
		return !! preg_match($regexp, $value);
	}

	static public function max_length($value, $param)
	{
		return strlen($value) <= $param;
	}

	static public function min_length($value, $param)
	{
		return strlen($value) >= $param;
	}

	/**
	 * Check that the input is a valid email address.
	 */
	static public function email($value)
	{
		return filter_var($value, FILTER_VALIDATE_EMAIL);
	}

	/** 
	 * Check that the input is a valid date.
	 */
	static public function date($value)
	{
		return strtotime($value) !== false;
	}

	// static public function date_max($value, $param)
	// {
	// 	return strtotime($value) <= $param;
	// }

	// static public function date_min($value, $param)
	// {
	// 	return strtotime($value) >= $param;
	// }

	/**
	 * HH:MM
	 */
	static public function time($value)
	{
		$h = substr($value, 0, 2);
		$m = substr($value, 3, 2);

		return ($value[2] == ':' && is_numeric($h) && $h < 24 && is_numeric($m) && $m < 60);
	}

	static public function numeric($value)
	{
		return is_numeric($value);
	}

	static public function max_value($value, $param)
	{
		return $value <= $param;
	}
	
	static public function min_value($value, $param)
	{
		return $value >= $param;
	}

	static public function enum($value, $param)
	{
		if ( is_array($value) ) {
			$ret = array_diff($value, $param);
			$ret = empty($ret);
		}
		else {
			$ret = in_array($value, $param);
		}
		return $ret;
	}
	/**
	 * Check that $value is a key of $param.
	 */
	static public function enum_keys($value, $param)
	{
		if ( is_array($value) ) {
			$ret = array_diff($value, array_keys($param));
			$ret = empty($ret);
		}
		else {
			$ret = array_key_exists($value, $param);
		}

		return $ret;
	}

	/**
	 * Note: we return '0' or '1' as *string*.
	 * This is made to accomodate PDO/MySQL that don't handle boolean directly.
	 * A SELECT statement will return '0' or '1' as strings too, so this way
	 * we're consistant accross the board.
	 */
	static public function bool(&$value)
	{
		$true_values = array('true', 't', 'yes', 'y', 'on', '1');
		$false_values = array('false', 'f', 'no', 'n', 'off', '0');

		if ( in_array($value, $true_values) ) {
			$value = '1';
			return true;
		}
		elseif ( in_array($value, $false_values) ) {
			$value = '0';
			return true;
		}

		return false;
	}

	static public function is_array($value)
	{
		return is_array($value);
	}

	static public function is_string($value)
	{
		return is_string($value);
	}
}