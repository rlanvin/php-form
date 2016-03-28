# PHP Form

Lightweight form validation library for PHP, with a concise syntax and powerful use of closures. It can validate traditional form submissions as well as API requests.

[![Build Status](https://travis-ci.org/rlanvin/php-form.svg?branch=master)](https://travis-ci.org/rlanvin/php-form)
[![Latest Stable Version](https://poser.pugx.org/rlanvin/php-form/v/stable)](https://packagist.org/packages/rlanvin/php-form)

## Basic example

```php
// create the form with rules
$form = new Form\Validator([
    'name' => ['required', 'trim', 'max_length' => 255],
    'email' => ['required', 'email']
]);

if ( $form->validate($_POST) ) {
    // $_POST data is valid
    $form->getValues(); // returns an array of sanitized values
}
else {
   // $_POST data is not valid
   $form->getErrors(); // contains the errors
   $form->getValues(); // can be used to repopulate the form
}
```

Complete doc is available in [the wiki](https://github.com/rlanvin/php-form/wiki).

## Requirements

- PHP >= 5.4
- [`mbstring` extension](http://www.php.net/manual/en/book.mbstring.php)
- [`intl` extension](http://php.net/manual/en/book.intl.php) recommended to validate numbers in local format

If you are stuck with PHP 5.3, you may still use [version 1.1](https://github.com/rlanvin/php-form/releases/tag/v1.1.0).

## Installation

The recommended way is to install the lib [through Composer](http://getcomposer.org/).

Just add this to your `composer.json` file:

```JSON
{
    "require": {
        "rlanvin/php-form": "2.*"
    }
}
```

Then run `composer install` or `composer update`.

Now you can use the autoloader, and you will have access to the library:

```php
<?php
require 'vendor/autoload.php';
```

### Alternative method (not recommended)

- Download [the latest release](https://github.com/rlanvin/php-form/releases/latest)
- Put the files in a folder that is autoloaded, or `inclure` or `require` them

However be sure to come back regulary and check for updates.

## Documentation

Complete doc is available in [the wiki](https://github.com/rlanvin/php-form/wiki).

## Contribution

Feel free to contribute! Just create a new issue or a new pull request.

## License

This library is released under the MIT License.
