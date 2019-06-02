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

namespace Gears\EventSourcing\Async\Serializer;

use Gears\Event\Async\Serializer\Exception\EventSerializationException;
use Gears\Event\Async\Serializer\JsonEventSerializer as BaseJsonEventSerializer;
use Gears\Event\Event;
use Gears\EventSourcing\Aggregate\AggregateVersion;
use Gears\EventSourcing\Event\AggregateEvent;
use Gears\Identity\Identity;

class JsonEventSerializer extends BaseJsonEventSerializer
{
    /**
     * {@inheritdoc}
     *
     * @throws EventSerializationException
     */
    protected function getSerializationAttributes(Event $event): array
    {
        if (!$event instanceof AggregateEvent) {
            throw new EventSerializationException(\sprintf(
                'Event class %s does not implement %s',
                \get_class($event),
                AggregateEvent::class
            ));
        }

        $aggregateId = $event->getAggregateId();

        return [
            'aggregateIdClass' => \get_class($aggregateId),
            'aggregateId' => $aggregateId->getValue(),
            'aggregateVersion' => $event->getAggregateVersion()->getValue(),
            'createdAt' => $event->getCreatedAt()->format(self::DATE_RFC3339_EXTENDED),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @throws EventSerializationException
     */
    protected function getDeserializationAttributes(array $attributes): array
    {
        /* @var Identity $identityClass */
        $identityClass = $attributes['aggregateIdClass'] ?? null;

        if ($identityClass === null) {
            throw new EventSerializationException(
                'Malformed JSON serialized event: Aggregate event identity class is not defined'
            );
        }

        if (!\class_exists($identityClass)
            || !\in_array(Identity::class, \class_implements($identityClass), true)
        ) {
            throw new EventSerializationException(\sprintf(
                'Aggregate event identity class %s does not implement %s',
                $identityClass,
                Identity::class
            ));
        }

        /* @var string $aggregateId */

        return [
            'aggregateId' => $identityClass::fromString($attributes['aggregateId']),
            'aggregateVersion' => new AggregateVersion($attributes['aggregateVersion']),
            'createdAt' => \DateTimeImmutable::createFromFormat(self::DATE_RFC3339_EXTENDED, $attributes['createdAt']),
        ];
    }
}
