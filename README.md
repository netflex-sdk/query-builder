# Netflex QueryBuilder

A library for building complex search queries for the Netflex API using a fluent builder-pattern.

<a href="https://circleci.com/gh/netflex-sdk/query-builder"><img src="https://circleci.com/gh/netflex-sdk/query-builder.svg?style=shield" alt="CircleCI"></a>
<a href="https://packagist.org/packages/netflex/query-builder"><img src="https://img.shields.io/packagist/v/netflex/query-builder?label=stable" alt="Stable version"></a>
<a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/github/license/netflex-sdk/query-builder.svg" alt="License: MIT"></a>
<a href="https://packagist.org/packages/netflex/query-builder/stats"><img src="https://img.shields.io/packagist/dm/netflex/query-builder" alt="Downloads"></a>

## Installation

```bash
composer require netflex/query-builder
```

## Usage example

```php
<?php

use Netflex\Query\Builder;

$query = new Builder();
$query->relation('entry', 10000)
  ->where('id', '>=', 10100)
  ->where('author', '!=', null)
  ->orWhere(function ($query) {
    $query->where('id', '<', 10100)
      ->where('author', '=', 'John Doe');
  });

$items = $query->limit(100)
  ->fields(['id', 'name', 'author'])
  ->orderBy('name', 'desc')
  ->get();

$page = $query->paginate(25);
```

## Contributing

Thank you for considering contributing to the Netflex QueryBuilder! Please read the [contribution guide](CONTRIBUTING.md).

## Code of Conduct

In order to ensure that the community is welcoming to all, please review and abide by the [Code of Conduct](CODE_OF_CONDUCT.md).

## License

The Netflex QueryBuilder is open-sourced software licensed under the [MIT license](LICENSE.md).

<hr>

Copyright &copy; 2020 **[Apility AS](https://apility.no)**
