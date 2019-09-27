<?php

/*
 * event-sourcing-async (https://github.com/phpgears/event-sourcing-async).
 * Async decorator for Event Sourcing events.
 *
 * @license MIT
 * @link https://github.com/phpgears/event-sourcing-async
 * @author Julián Gutiérrez <juliangut@gmail.com>
 */

declare(strict_types=1);

namespace Gears\EventSourcing\Async\Tests\Stub;

use Gears\EventSourcing\Aggregate\AggregateVersion;
use Gears\EventSourcing\Event\AbstractAggregateEvent;
use Gears\Identity\Identity;

/**
 * Abstract aggregate event stub class.
 */
class AggregateEventStub extends AbstractAggregateEvent
{
    /**
     * Instantiate event.
     *
     * @param Identity $aggregateId
     * @param array    $payload
     *
     * @return self
     */
    public static function instance(Identity $aggregateId, array $payload): self
    {
        return static::occurred($aggregateId, $payload);
    }

    /**
     * Copy event with new version.
     *
     * @param self             $event
     * @param AggregateVersion $version
     *
     * @return static
     */
    public static function withVersion(self $event, AggregateVersion $version): self
    {
        return $event::reconstitute(
            $event->getPayload(),
            $event->getCreatedAt(),
            [
                'aggregateId' => $event->getAggregateId(),
                'aggregateVersion' => $version,
                'metadata' => $event->getMetadata(),
            ]
        );
    }
}
