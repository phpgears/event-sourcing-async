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

use Gears\Event\Async\Serializer\EventSerializer;
use Gears\Event\Async\Serializer\Exception\EventSerializationException;
use Gears\Event\Event;
use Gears\EventSourcing\Aggregate\AggregateVersion;
use Gears\EventSourcing\Event\AggregateEvent;
use Gears\Identity\Identity;

final class JsonEventSerializer implements EventSerializer
{
    /**
     * JSON encoding options.
     * Preserve float values and encode &, ', ", < and > characters in the resulting JSON.
     */
    private const JSON_ENCODE_OPTIONS = \JSON_UNESCAPED_UNICODE
        | \JSON_UNESCAPED_SLASHES
        | \JSON_PRESERVE_ZERO_FRACTION
        | \JSON_HEX_AMP
        | \JSON_HEX_APOS
        | \JSON_HEX_QUOT
        | \JSON_HEX_TAG;

    /**
     * JSON decoding options.
     * Decode large integers as string values.
     */
    private const JSON_DECODE_OPTIONS = \JSON_BIGINT_AS_STRING;

    /**
     * \DateTime::RFC3339_EXTENDED cannot handle microseconds on \DateTimeImmutable::createFromFormat.
     *
     * @see https://stackoverflow.com/a/48949373
     */
    private const DATE_RFC3339_EXTENDED = 'Y-m-d\TH:i:s.uP';

    /**
     * {@inheritdoc}
     */
    public function serialize(Event $event): string
    {
        if (!$event instanceof AggregateEvent) {
            throw new EventSerializationException(\sprintf(
                'Aggregate event class %s does not implement %s',
                \get_class($event),
                AggregateEvent::class
            ));
        }

        $serialized = \json_encode(
            [
                'class' => \get_class($event),
                'payload' => $event->getPayload(),
                'createdAt' => $event->getCreatedAt()->format(static::DATE_RFC3339_EXTENDED),
                'attributes' => $this->getSerializationAttributes($event),
            ],
            static::JSON_ENCODE_OPTIONS
        );

        // @codeCoverageIgnoreStart
        if ($serialized === false || \json_last_error() !== \JSON_ERROR_NONE) {
            throw new EventSerializationException(\sprintf(
                'Error serializing event %s due to %s',
                \get_class($event),
                \lcfirst(\json_last_error_msg())
            ));
        }
        // @codeCoverageIgnoreEnd

        return $serialized;
    }

    /**
     * Get serialization attributes.
     *
     * @param AggregateEvent $event
     *
     * @return array<string, mixed>
     */
    private function getSerializationAttributes(AggregateEvent $event): array
    {
        $aggregateId = $event->getAggregateId();

        return [
            'aggregateIdClass' => \get_class($aggregateId),
            'aggregateId' => $aggregateId->getValue(),
            'aggregateVersion' => $event->getAggregateVersion()->getValue(),
            'metadata' => $event->getMetadata(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fromSerialized(string $serialized): Event
    {
        ['class' => $eventClass, 'payload' => $payload, 'createdAt' => $createdAt, 'attributes' => $attributes] =
            $this->getEventDefinition($serialized);

        if (!\class_exists($eventClass)) {
            throw new EventSerializationException(\sprintf('Aggregate event class %s cannot be found', $eventClass));
        }

        if (!\in_array(AggregateEvent::class, \class_implements($eventClass), true)) {
            throw new EventSerializationException(\sprintf(
                'Aggregate event class must implement %s, %s given',
                AggregateEvent::class,
                $eventClass
            ));
        }

        $createdAt = \DateTimeImmutable::createFromFormat(self::DATE_RFC3339_EXTENDED, $createdAt);

        try {
            /* @var AggregateEvent $eventClass */
            return $eventClass::reconstitute($payload, $createdAt, $this->getDeserializationAttributes($attributes));
        } catch (\Exception $exception) {
            throw new EventSerializationException('Error reconstituting aggregate event', 0, $exception);
        }
    }

    /**
     * Get event definition from serialization.
     *
     * @param string $serialized
     *
     * @throws EventSerializationException
     *
     * @return array<string, mixed>
     */
    private function getEventDefinition(string $serialized): array
    {
        $definition = $this->getDeserializationDefinition($serialized);

        if (!isset($definition['class'], $definition['payload'], $definition['createdAt'], $definition['attributes'])
            || \count(\array_diff(\array_keys($definition), ['class', 'payload', 'createdAt', 'attributes'])) !== 0
            || !\is_string($definition['class'])
            || !\is_array($definition['payload'])
            || !\is_string($definition['createdAt'])
            || !\is_array($definition['attributes'])
        ) {
            throw new EventSerializationException('Malformed JSON serialized aggregate event');
        }

        return $definition;
    }

    /**
     * Get deserialization definition.
     *
     * @param string $serialized
     *
     * @return array<string, mixed>
     */
    private function getDeserializationDefinition(string $serialized): array
    {
        if (\trim($serialized) === '') {
            throw new EventSerializationException('Malformed JSON serialized aggregate event: empty string');
        }

        $definition = \json_decode($serialized, true, 512, static::JSON_DECODE_OPTIONS);

        // @codeCoverageIgnoreStart
        if ($definition === null || \json_last_error() !== \JSON_ERROR_NONE) {
            throw new EventSerializationException(\sprintf(
                'Event deserialization failed due to error %s: %s',
                \json_last_error(),
                \lcfirst(\json_last_error_msg())
            ));
        }
        // @codeCoverageIgnoreEnd

        return $definition;
    }

    /**
     * Get deserialization attributes.
     *
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    private function getDeserializationAttributes(array $attributes): array
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

        return [
            'aggregateId' => $identityClass::fromString($attributes['aggregateId']),
            'aggregateVersion' => new AggregateVersion($attributes['aggregateVersion']),
            'metadata' => $attributes['metadata'],
        ];
    }
}
