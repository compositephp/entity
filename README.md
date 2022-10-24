# Composite Entity

Composite Entity is lightweight and smart PHP 8.1+ Entity class with automatic Data Mapping and serialization. Very 
useful to work with database rows, just hydrate associative array into strict typed object and make your IDE happy.

Overview:
* [Requirements](#requirements)
* [Installation](#installation)
* [Quick example](#quick-example)

## Requirements

* PHP 8.1+

## Installation

Install package via composer:

 ```shell
 $ composer require compositephp/entity
 ```

## Supported column types:

Composite entity can automatically serialize and deserialize back almost all data types you may need:

- String
- Integer
- Float
- Bool
- Array
- Object (stdClass)
- DateTime and DateTimeImmutable
- Enum
- Another Entity
- Custom class that implements `Composite\DB\Entity\CastableInterface`

## Quick example

```php
use Composite\Entity\AbstractEntity;

class User extends AbstractEntity
{
    public function __construct(
        public readonly int $id,
        public string $email,
        public ?string $name = null,
        public bool $is_test = false,
        public array $languages = [],
        public Status $status = Status::ACTIVE,
        public readonly \DateTimeImmutable $created_at = new \DateTimeImmutable(),
    ) {}
}

enum Status
{
    case ACTIVE;
    case BLOCKED;
}
```

Example of serialization:

```php
$user = new User(
    id: 123,
    email: 'john@example.com',
    name: 'John',
    is_test: false,
    languages: ['en', 'fr'],
    status: Status::BLOCKED,
);

var_export($user->toArray());

//will output
array (
  'id' => 123,
  'email' => 'user@example.com',
  'name' => 'John',
  'is_test' => false,
  'languages' => '["en","fr"]',
  'status' => 'BLOCKED',
  'created_at' => '2022-01-01 11:22:33.000000',
)
```

You can also deserialize (hydrate) entity from array:

```php
$user = User::fromArray([
  'id' => 123,
  'email' => 'user@example.com',
  'name' => 'John',
  'is_test' => false,
  'languages' => '["en","fr"]',
  'status' => 'BLOCKED',
  'created_at' => '2022-01-01 11:22:33.000000',
]);
```

And that's it, no special getters or setters, no "behaviours" or extra code, Composite Entity casts everything 
automatically.

## License:

MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information. Maintained by Composite PHP.
