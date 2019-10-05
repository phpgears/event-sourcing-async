[![PHP version](https://img.shields.io/badge/PHP-%3E%3D7.1-8892BF.svg?style=flat-square)](http://php.net)
[![Latest Version](https://img.shields.io/packagist/v/phpgears/event-sourcing-async.svg?style=flat-square)](https://packagist.org/packages/phpgears/event-sourcing-async)
[![License](https://img.shields.io/github/license/phpgears/event-sourcing-async.svg?style=flat-square)](https://github.com/phpgears/event-sourcing-async/blob/master/LICENSE)

[![Build Status](https://img.shields.io/travis/phpgears/event-sourcing-async.svg?style=flat-square)](https://travis-ci.org/phpgears/event-sourcing-async)
[![Style Check](https://styleci.io/repos/172602060/shield)](https://styleci.io/repos/172602060)
[![Code Quality](https://img.shields.io/scrutinizer/g/phpgears/event-sourcing-async.svg?style=flat-square)](https://scrutinizer-ci.com/g/phpgears/event-sourcing-async)
[![Code Coverage](https://img.shields.io/coveralls/phpgears/event-sourcing-async.svg?style=flat-square)](https://coveralls.io/github/phpgears/event-sourcing-async)

[![Total Downloads](https://img.shields.io/packagist/dt/phpgears/event-sourcing-async.svg?style=flat-square)](https://packagist.org/packages/phpgears/event-sourcing-async/stats)
[![Monthly Downloads](https://img.shields.io/packagist/dm/phpgears/event-sourcing-async.svg?style=flat-square)](https://packagist.org/packages/phpgears/event-sourcing-async/stats)

# Event Sourcing Async

Async decorator for Event Sourcing events and Async Event Bus

## Installation

### Composer

```
composer require phpgears/event-sourcing-async
```

## Usage

Require composer autoload file

```php
require './vendor/autoload.php';
```

This package adds a new `Gears\EventSourcing\Async\Serializer\JsonEventSerializer` serializer, as a general serializer allowing maximum compatibility in case of events being handled by other systems, to allow `Gears\EventSourcing\Event\AggregateEvent` events to be used in Async event bus

```php
use Gears\Event\Async\AsyncEventBus;
use Gears\EventSourcing\Async\Serializer\JsonEventSerializer;
use Gears\Event\Async\Discriminator\ParameterEventDiscriminator;

/* @var \Gears\Event\EventBus $eventBus */

/* @var Gears\Event\Async\EventQueue $eventQueue */
$eventQueue = new CustomEventQueue(new JsonEventSerializer());

$asyncEventBus new AsyncEventBus(
    $eventBus,
    $eventQueue,
    new ParameterEventDiscriminator('async')
);

$asyncEvent = new CustomEvent(['async' => true]);

$asyncEventBus->dispatch($asyncEvent);
```

Refer to [phpgears/event-async](https://github.com/phpgears/event-async) for more information 

## Contributing

Found a bug or have a feature request? [Please open a new issue](https://github.com/phpgears/event-sourcing-async/issues). Have a look at existing issues before.

See file [CONTRIBUTING.md](https://github.com/phpgears/event-sourcing-async/blob/master/CONTRIBUTING.md)

## License

See file [LICENSE](https://github.com/phpgears/event-sourcing-async/blob/master/LICENSE) included with the source code for a copy of the license terms.
