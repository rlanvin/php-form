# PHP Form

Lightweight form validation library for PHP, with a concise syntax and powerful use of closures.

[![Build Status](https://travis-ci.org/rlanvin/php-form.svg?branch=master)](https://travis-ci.org/rlanvin/php-form)

## Basic example

```php
// create the form with rules
$form = new Form([
    'name' => ['required', 'trim', 'max_length' => 255],
    'email' => ['required', 'email']
]);

if ( $form->validate($_POST) ) {
    // $_POST data is valid
    save_to_db_or_something($form->getValues());
}
else {
   // $_POST data is not valid
   display_errors_or_something($form->getErrors());
   // $form->getValues() can be used to repopulate the form
}
```

Complete doc is available in [the wiki](https://github.com/rlanvin/php-form/wiki).

## Requirements

- PHP >= 5.3 (PHP >= 5.4 highly recommended)
- mbstring extension (http://www.php.net/manual/en/book.mbstring.php)

## Installation

**Caution** The current branch (1.*) is NOT namespaced and still compatible with PHP 5.3 for historical reasons. The code is made so that you can just add a namespace at the beginning of the file.

The recommended way is to install the lib [through Composer](http://getcomposer.org/).

Just add this to your `composer.json` file:

```JSON
{
    "require": {
        "rlanvin/php-form": "1.*"
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
