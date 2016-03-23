<?php

/**
 * Licensed under the MIT license.
 *
 * For the full copyright and license information, please view the LICENSE file.
 *
 * @author Rémi Lanvin <remi@cloudconnected.fr>
 * @link https://github.com/rlanvin/php-form
 */

namespace Form\Rule;

/**
 * Each rule is a method that returns a boolean indicating whether the
 * value provided is valid (true) or invalid (false).
 *
 * Rules can optionally alter the value provided, acting as a sanitizer.
 * 
 * Rules can take one additional parameter. To pass the parameter is the
 * Form context, use the following syntax:
 * ['trim']; // no option
 * ['trim' => '/']; // will trim "/"
 *
 */


function is_empty($value)
{
	return $value === null || (\is_array($value) && empty($value)) || (\is_string($value) && \trim($value) === '');
}

///////////////////////////////////////////////////////////////////////////////
// Type rules


/**
 * Check that the input value is considered a boolean
 * and cast the value to '0' or '1'.
 *
 * Note: we return '0' or '1' as *string*.
 * This is made to accomodate PDO/MySQL that don't handle boolean directly.
 * A SELECT statement will return '0' or '1' as strings too, so this way
 * we're consistant accross the board.
 * This is also made to avoid bugs with in_array() and the like.
 */
function bool(&$value)
{
	$true_values = array('true', 't', 'yes', 'y', 'on', '1', 1, true);
	$false_values = array('false', 'f', 'no', 'n', 'off', '0', 0, false);

	if ( \in_array($value, $true_values, true) ) {
		$value = '1';
		return true;
	}
	elseif ( \in_array($value, $false_values, true) ) {
		$value = '0';
		return true;
	}

	return false;
}

/** 
 * Check that the input is a valid date.
 * @see http://www.php.net/strtotime
 */
function date($value)
{
	if ( ! \is_string($value) ) {
		return false;
	}
	return strtotime($value) !== false;
}

/**
 * Check that the input is a valid email address.
 */
function email($value)
{
	return !! filter_var($value, FILTER_VALIDATE_EMAIL);
}

/**
 * Test that the value is an array
 */
function is_array($value)
{
	return \is_array($value);
}

function is_string($value)
{
	return \is_string($value);
}

function url(&$value)
{
	if ( ! \is_string($value) ) {
		return false;
	}

	return filter_var($value, FILTER_VALIDATE_URL) !== false;
}

function numeric($value)
{
	return \is_numeric($value);
}

///////////////////////////////////////////////////////////////////////////////
// Value validator

/**
 * Check that the value is in the param array
 * If value is an array, it'll compute array diff.
 */
function in($value, array $param)
{
	if ( \is_array($value) ) {
		$ret = array_diff($value, $param);
		return empty($ret);
	}

	return \in_array($value, $param);
}
/**
 * Check that value is a key of the param array.
 * If value is an array, it'll compute array diff.
 */
function in_keys($value, array $param)
{
	if ( \is_array($value) ) {
		$ret = array_diff($value, array_keys($param));
		return empty($ret);
	}
	
	if ( ! is_string($value) && ! is_int($value) ) {
		return false;
	}

	return array_key_exists($value, $param);
}

/**
 * Check that the value is a maximum length
 */
function max_length($value, $length)
{
	if ( ! \is_string($value) && ! \is_int($value) ) {
		return false;
	}
	if ( ! \is_int($length) && ! ctype_digit($length)) {
		throw new \InvalidArgumentException('The length must be an integer');
	}
	return mb_strlen($value) <= $length;
}

/**
 * Check that the value is a minimum length
 */
function min_length($value, $length)
{
	if ( ! \is_string($value) && ! \is_int($value) ) {
		return false;
	}
	if ( ! \is_int($length) && ! ctype_digit($length)) {
		throw new \InvalidArgumentException('The length must be an integer');
	}
	return mb_strlen($value) >= $length;
}

/**
 * Check the value against a regexp.
 *
 * @param $value mixed
 * @param $regexp string Regular expression
 * @return bool
 */
function regexp($value, $regexp)
{
	if ( ! \is_string($regexp) ) {
		throw new \InvalidArgumentException('The regular expression must be a string');
	}
	if ( ! $regexp ) {
		throw new \InvalidArgumentException('The regular expression cannot be empty');
	}

	return !! filter_var($value, FILTER_VALIDATE_REGEXP, array(
		'options' => array('regexp' => $regexp)
	));
}

/**
 * Validates the format: HH:MM
 */
function time($value)
{
	if ( ! \is_string($value) ) {
		return false;
	}

	$h = substr($value, 0, 2);
	$m = substr($value, 3, 2);

	return (isset($value[2]) && $value[2] == ':' && \is_numeric($h) && $h < 24 && \is_numeric($m) && $m < 60);
}

/**
 * Check that the value is a string and trim it of unwanted character.
 *
 * @param $value mixed
 * @param $character_mask string The list of characters to be trimmed.
 * @see http://www.php.net/trim
 * @return bool
 */
function trim(&$value, $character_mask = true)
{
	// trim will trigger an error if called with something else than a string
	if ( ! \is_string($value) && ! \is_int($value) ) {
		return false;
	}

	if ( $character_mask === true ) {
		$character_mask = " \t\n\r\0\x0B";
	}

	$value = \trim($value, $character_mask);
	return true;
}



// function date_max($value, $param)
// {
// 	return strtotime($value) <= $param;
// }

// function date_min($value, $param)
// {
// 	return strtotime($value) >= $param;
// }

function max($value, $param)
{
	return $value <= $param;
}

function min($value, $param)
{
	return $value >= $param;
}
