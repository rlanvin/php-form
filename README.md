# PHP Form

Lightweight form validation library for PHP

[![Build Status](https://travis-ci.org/rlanvin/php-form.svg?branch=master)](https://travis-ci.org/rlanvin/php-form)

## Requirements

- PHP >= 5.3
- mbstring extension (http://www.php.net/manual/en/book.mbstring.php)

## Installation

### Option 1

- Download the files in [src/](https://github.com/rlanvin/php-form/tree/master/src)
- (optional) Merge them into one
- Put them in a folder that is autoloaded, or `inclure` or `require` them
- Done

### Option 2

The recommended way is to install the lib [through Composer](http://getcomposer.org/).

Just add this to your `composer.json` file:

```JSON
{
    "require": {
        "rlanvin/php-form": "dev-master"
    }
}
```

Then run `composer install` or `composer update`.

Now you can use the autoloader, and you will have access to the library:

```php
<?php
require 'vendor/autoload.php';
```

## Documentation

Complete doc is available in [the wiki](https://github.com/rlanvin/php-form/wiki).

## License

This library is released under the MIT License.
