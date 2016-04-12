# Changelog

## [Unreleased]

- `each` validator is now transparent in the error array

## [2.0.1] - 2016-04-11

- Fix a PHP fatal error when `is_array` is used with `each`

## [2.0] - 2016-03-28

- Drop support for PHP 5.3 (minimum version is now PHP 5.4)
- Namespacing: `Form` is now `Form\Validator`
- `Validator` static class is replaced by the namespace `Form\Rule`
- Using PSR-4 autoloader
- Adding syntax to access nested fields directly in `getRules`, `getErrors` and `getValues` (and similar `has*` methods)
- `setRules` now accepts another validator

### Rule changes

- The rule `bool` now takes an optionnal parameter to determine type conversion
- The rule `date` now takes an optionnal parameter to determine the date format
- The rule `trim` now always return true
- New rule: `datetime`
- New rule: `length`
- New rules: `integer`, `decimal`, `intl_integer` and `intl_decimal`
- New rules: `ip`, `ipv4`, `ipv6`
- New rules: `between`, `min`, `max`
- Remove rules: `min_value` (use `min`) and `max_value` (use `max`)

## [1.1.0] - 2016-03-25

- `each` now cast to array in all circumstances
- `each` now accepts a subform
- error array for `each` validator now includes the offset

## 1.0.0 - 2015-10-12

### Added

- First release, everything before that was unversioned (`dev-master` was used).

[Unreleased]: https://github.com/rlanvin/php-form/compare/v2.0.1...HEAD
[2.0.1]: https://github.com/rlanvin/php-form/compare/v2.0.0...v2.0.1
[2.0]: https://github.com/rlanvin/php-form/compare/v1.1.0...v2.0.0
[1.1.0]: https://github.com/rlanvin/php-form/compare/v1.0.0...v1.1.0