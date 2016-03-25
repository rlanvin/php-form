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

///////////////////////////////////////////////////////////////////////////////
// Core type rules

/**
 * Test if the value is empty
 */
function is_empty($value)
{
	return $value === null || (\is_array($value) && empty($value)) || (\is_string($value) && \trim($value) === '');
}

/**
 * Test that the value is an array
 */
function is_array($value)
{
	return \is_array($value);
}

/**
 * Test that the value is a string
 */
function is_string($value)
{
	return \is_string($value);
}

///////////////////////////////////////////////////////////////////////////////
// Type rules

/**
 * Check that the input value is considered a boolean
 * and alter the value if necessary.
 *
 * The type of the value is preserved.
 * - strings (such as 'true' or 'y') will become '0' or '1'
 * - integers and bools are not modified
 *
 * This is made to accomodate PDO/MySQL that don't handle boolean directly.
 * A SELECT statement will return '0' or '1' as strings too, so this way
 * we're consistant accross the board. This is also made to avoid bugs with
 * in_array() and the like, due to PHP's type conversion.
 *
 * If you want to force the type to something else, use the second parameter.
 *
 * @param $value
 * @param $sanitize mixed - true  => alter value and keep type (default)
 *                        - 'bool', 'string' or 'int' => alter and cast to this type
 *                        - false => do not alter the value
 */
function bool(&$value, $sanitize = true)
{
	$true_values = array('true', 't', 'yes', 'y', 'on', '1', 1, true);
	$false_values = array('false', 'f', 'no', 'n', 'off', '0', 0, false);

	$ret = false;

	if ( $sanitize === true ) {
		$sanitize = \gettype($value);
	}

	// see http://stackoverflow.com/questions/13846769/php-in-array-0-value
	if ( \in_array($value, $true_values, true) ) {
		$value = $sanitize ? true : $value;
		$ret = true;
	}
	elseif ( \in_array($value, $false_values, true) ) {
		$value = $sanitize ? false : $value;
		$ret = true;
	}

	if ( $ret && $sanitize ) {
		switch ( $sanitize ) {
			case 'int':
			case 'integer':
				$value = (int) $value;
				break;

			case 'string':
				$value = $value ? '1' : '0';
				break;

			case 'bool':
			case 'boolean':
				$value = (bool) $value;
				break;

			default:
				throw new \InvalidArgumentException("Cannot cast the value to type $sanitize");
		}
	}

	return $ret;
}

/** 
 * Check that the input is a valid date, optionally of a given format
 *
 * @see http://www.php.net/strtotime
 */
function date($value, $format = 'Y-m-d')
{
	if ( ! \is_string($value) ) {
		return false;
	}

	if ( $format ) {
		$ret = \DateTime::createFromFormat($format, $value);
		if ( $ret ) {
			$errors = \DateTime::getLastErrors();
			if (!empty($errors['warning_count'])) {
				$ret = false;
			}
		}
	}
	else {
		// validate anything, not really recommended
		try {
			$ret = new \DateTime($value);
		} catch ( \Exception $e ) {
			$ret = false;
		}
	}

	return $ret !== false;
}

function datetime($value)
{
	return date($value, 'Y-m-d H:i:s');
}

function time($value)
{
	return date($value, 'H:i');
}

function numeric($value)
{
	return \is_numeric($value);
}

function integer($value)
{
	return \filter_var($value, FILTER_VALIDATE_INT) !== false;
}

function decimal($value)
{
	return \filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
}

function intl_decimal(&$value, $locale = null)
{
	if ( ! \class_exists('\Locale') ) {
		throw new \RuntimeException('intl extension is not installed');
	}

	if ( ! \is_string($value) && ! \is_int($value) && ! \is_float($value) ) {
		return false;
	}

	if ( $locale === null ) {
		$locale = \Locale::getDefault();
	}

	$fmt = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
	$ret = $fmt->parse($value);

	if ( $ret !== false ) {
		$value = $ret;
		return true;
	}

	return false;
}

function intl_integer(&$value, $locale)
{
	$original_value = $value;
	$ret = intl_decimal($value, $locale);
	if ( $ret == (int) $ret ) {
		$value = (int) $ret;
		return true;
	}
	return false;
}

/**
 * Check that the input is a valid email address.
 */
function email($value)
{
	return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

function url(&$value, $protocols = null)
{
	$ret = filter_var($value, FILTER_VALIDATE_URL);
	if ( $ret === false ) {
		return false;
	}

	if ( $protocols === null ) {
		return true;
	}

	if ( ! \is_array($protocols) ) {
		$protocols = array($protocols);
	}

	foreach ( $protocols as $proto ) {
		$proto .= '://';
		if ( substr($value, 0, strlen($proto)) == $proto ) {
			return true;
		}
	}

	return false;
}

function ip($value, $flags = null)
{
	return filter_var($value, FILTER_VALIDATE_IP, $flags) !== false;
}

function ipv4($value)
{
	return ip($value, FILTER_FLAG_IPV4);
}

function ipv6($value)
{
	return ip($value, FILTER_FLAG_IPV6);
}


///////////////////////////////////////////////////////////////////////////////
// Value rules

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


function between($value, $between)
{
	if ( ! is_array($between) || count($between) != 2 ) {
		throw new \InvalidArgumentException("'between' rule takes an array of exactly two values");
	}

	list($min,$max) = $between;
	if ( $min !== null ) {
		if ( ! min($value, $min) ) {
			return false;
		}
	}
	if ( $max !== null ) {
		if ( ! max($value, $max) ) {
			return false;
		}
	}

	return true;
}

function max($value, $param)
{
	return $value <= $param;
}

function min($value, $param)
{
	return $value >= $param;
}

function length($value, $between)
{
	if ( ! is_array($between) || count($between) != 2 ) {
		throw new \InvalidArgumentException("'length' rule takes an array of exactly two values");
	}

	list($min,$max) = $between;
	if ( $min !== null ) {
		if ( ! min_length($value, $min) ) {
			return false;
		}
	}
	if ( $max !== null ) {
		if ( ! max_length($value, $max) ) {
			return false;
		}
	}

	return true;
}

/**
 * Check that the value is a maximum length
 */
function max_length($value, $length)
{
	if ( ! \is_string($value) && ! \is_int($value) ) {
		return false;
	}
	if ( (! \is_int($length) && ! ctype_digit($length)) || $length < 0 ) {
		throw new \InvalidArgumentException('The length must be an positive integer');
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
	if ( (! \is_int($length) && ! ctype_digit($length)) || $length < 0 ) {
		throw new \InvalidArgumentException('The length must be an positive integer');
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


///////////////////////////////////////////////////////////////////////////////
// Special rules

/**
 * Check that the value is a string and trim it of unwanted character.
 *
 * @param $value mixed
 * @param $character_mask string The list of characters to be trimmed.
 * @see http://www.php.net/trim
 * @return bool
 */
function trim(&$value, $character_mask = null)
{
	// trim will trigger an error if called with something else than a string or an int
	if ( ! \is_string($value) && ! \is_int($value) && ! \is_float($value) ) {
		return true;
	}

	if ( $character_mask === null ) {
		$character_mask = " \t\n\r\0\x0B";
	}
	elseif ( ! \is_string($character_mask) ) {
		throw new \InvalidArgumentException("Character mask for 'trim' must be a string");
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
