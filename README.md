# PHP API SDK

[![Latest Version](https://img.shields.io/github/release/programmatordev/php-api-sdk.svg?style=flat-square)](https://github.com/programmatordev/sportmonksfootball-php-api/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Tests](https://github.com/programmatordev/php-api-sdk/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/programmatordev/sportmonksfootball-php-api/actions/workflows/ci.yml?query=branch%3Amain)

A library for creating SDKs in PHP with support for:
- [PSR-18 HTTP clients](https://www.php-fig.org/psr/psr-18);
- [PSR-17 HTTP factories](https://www.php-fig.org/psr/psr-17);
- [PSR-6 caches](https://www.php-fig.org/psr/psr-6);
- [PSR-3 logs](https://www.php-fig.org/psr/psr-3);
- Authentication;
- Events;
- ...and more.

## Requirements

- PHP 8.1 or higher.

## Installation

You can install the library via [Composer](https://getcomposer.org/):

```bash
composer require programmatordev/php-api-sdk
```

To use the library, use Composer's [autoload](https://getcomposer.org/doc/01-basic-usage.md#autoloading):

```php
require_once 'vendor/autoload.php';
```

## Basic Usage

Simple usage looks like:

```php
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Method;

class PokeApi extends Api
{
    public function __construct() 
    {
        parent::__construct();
        
        $this->setBaseUrl('https://pokeapi.co/api/v2');
    }
    
    public function getPokemon(int|string $idOrName): string
    {
        return $this->request(
            method: Method::GET,
            path: $this->buildPath('/pokemon/{idOrName}', [
                'idOrName' => $idOrName
            ])
        );
    }
}
```

## Contributing

Any form of contribution to improve this library (including requests) will be welcome and appreciated.
Make sure to open a pull request or issue.

## License

This project is licensed under the MIT license.
Please see the [LICENSE](LICENSE) file distributed with this source code for further information regarding copyright and licensing.