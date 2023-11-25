# Composite Entity
[![Latest Stable Version](https://poser.pugx.org/compositephp/entity/v/stable)](https://packagist.org/packages/compositephp/entity)
[![Build Status](https://github.com/compositephp/entity/actions/workflows/main.yml/badge.svg)](https://github.com/compositephp/entity/actions)
[![Codecov](https://codecov.io/gh/compositephp/entity/branch/master/graph/badge.svg)](https://codecov.io/gh/compositephp/entity/)

Composite Entity is a lightweight and intelligent PHP 8.1+ class that shines in its ability to be serialized and deserialized from an array. 
This smart feature makes it extremely useful when managing data from databases. 

It efficiently converts database rows into a strictly typed object and back into an array, enhancing your workflow and making your interaction with databases much smoother and more productive.

Overview:
* [Requirements](#requirements)
* [Installation](#installation)
* [Quick example](#quick-example)
* [Advanced usage](#advanced-usage)

## Requirements

* PHP 8.1+

## Installation

Install package via composer:

 ```shell
 $ composer require compositephp/entity
 ```

## Supported column types:

Composite Entity has the capability to automatically serialize and deserialize nearly all data types you might require.
- String
- Integer
- Float
- Bool
- Array
- Object (stdClass)
- DateTime and DateTimeImmutable
- Enum
- Another Entity
- List of map of Entities
- Collection (any ArrayAccess instance, e.g. Doctrine Collection)
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

And that's it, no special getters or setters, no "behaviours" or extra code, Composite Entity casts everything automatically.

## Advanced usage

### Custom Hydration

If you like to have a full control or best performance during the serialization and deserialization of the entity you can 
implement your own Hydrator. To do this, follow the simple steps:

1. Create class and implement `Composite\Entity\HydratorInterface`
2. Add attribute `Composite\Entity\Attributes\Hydrator` to your entity class.

### Usefull Attributes

* ####  Composite\Entity\Attributes\SkipSerialization

    Add this attribute to any entity property to remove it from the hydration process.

* #### Composite\Entity\Attributes\ListOf

    Can be added to array property, indicate the desired target entity class to store list of of this entity in your property.
    Use the second parameter `keyColumn` to store the associative array in your property, so you can easily search the entities 
    by the named key later.

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
