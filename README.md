# Composite Entity
[![Latest Stable Version](https://poser.pugx.org/compositephp/entity/v/stable)](https://packagist.org/packages/compositephp/entity)
[![Build Status](https://github.com/compositephp/entity/actions/workflows/main.yml/badge.svg)](https://github.com/compositephp/entity/actions)
[![Codecov](https://codecov.io/gh/compositephp/entity/branch/master/graph/badge.svg)](https://codecov.io/gh/compositephp/entity/)

Composite Entity is a PHP 8.1+ lightweight class designed for efficient and intelligent data handling. 
It specializes in the serialization and deserialization of data, making it highly beneficial for database management.

## Features
* Converts database rows to strictly typed objects and vice versa.
* Streamlines database interactions.

Overview:
* [Requirements](#requirements)
* [Installation](#installation)
* [Quick example](#quick-example)
* [Advanced usage](#advanced-usage)

## Requirements

* PHP 8.1 or higher.

## Installation

Install using Composer::

 ```shell
 $ composer require compositephp/entity
 ```

## Supported column types:

Composite Entity supports a wide range of data types:

* Basic types: String, Integer, Float, Bool, Array.
* Complex types: Object (stdClass), DateTime/DateTimeImmutable, Enum.
* Advanced types: Entity, Entity Lists or Maps, Collections (e.g., Doctrine Collection), Custom classes implementing Composite\DB\Entity\CastableInterface.

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

And that's it, no special getters or setters, no "behaviours" or extra code, Composite Entity casts everything automatically.

## Advanced usage

### Custom Hydration

For tailored performance, implement your own hydrator:

1. Create a class implementing `Composite\Entity\HydratorInterface`.
2. Add `Composite\Entity\Attributes\Hydrator` attribute to your entity class.

### Useful Attributes

* ####  Composite\Entity\Attributes\SkipSerialization

  Exclude properties from hydration.

* #### Composite\Entity\Attributes\ListOf

  Define lists of entities within a property.

    Example:
    
    ```php
    use Composite\Entity\AbstractEntity;
    use Composite\Entity\Attributes\ListOf;
    
    class User extends AbstractEntity
    {
        public readonly int $id;
        
        public function __construct(
            public string $name,
            #[ListOf(Setting::class, 'name')]
            public array $settings = [],
        ) {}
    }
    
    class Setting extends AbstractEntity
    {
        public function __construct(
            public readonly string $name,
            public bool $isActive,
        ) {}
    }
    ```

## License:

MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information. Maintained by Composite PHP.
