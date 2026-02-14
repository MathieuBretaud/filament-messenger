# This is my package filament-messenger

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mathieubretaud/filament-messenger.svg?style=flat-square)](https://packagist.org/packages/mathieubretaud/filament-messenger)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mathieubretaud/filament-messenger/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mathieubretaud/filament-messenger/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/mathieubretaud/filament-messenger/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/mathieubretaud/filament-messenger/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mathieubretaud/filament-messenger.svg?style=flat-square)](https://packagist.org/packages/mathieubretaud/filament-messenger)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require mathieubretaud/filament-messenger
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-messenger-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-messenger-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-messenger-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$filamentMessenger = new MathieuBretaud\FilamentMessenger();
echo $filamentMessenger->echoPhrase('Hello, MathieuBretaud!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [bretaudmathieu](https://github.com/MathieuBretaud)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
