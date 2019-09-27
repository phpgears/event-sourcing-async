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

namespace Gears\EventSourcing\Async\Tests\Serializer;

use Gears\Event\Async\Serializer\Exception\EventSerializationException;
use Gears\Event\Event;
use Gears\EventSourcing\Aggregate\AggregateVersion;
use Gears\EventSourcing\Async\Serializer\JsonEventSerializer;
use Gears\EventSourcing\Async\Tests\Stub\AggregateEventStub;
use Gears\Identity\UuidIdentity;
use PHPUnit\Framework\TestCase;

/**
 * JSON event serializer test.
 */
class JsonEventSerializerTest extends TestCase
{
    public function testInvalidEventSerialize(): void
    {
        $this->expectException(EventSerializationException::class);
        $this->expectExceptionMessageRegExp('/^Event class .+ does not implement .+\\\AggregateEvent$/');

        $event = $this->getMockBuilder(Event::class)->getMock();

        (new JsonEventSerializer())->serialize($event);
    }

    public function testSerialize(): void
    {
        $event = AggregateEventStub::instance(
            UuidIdentity::fromString('3247cb6e-e9c7-4f3a-9c6c-0dec26a0353f'),
            ['data' => 'value']
        );

        $serialized = (new JsonEventSerializer())->serialize($event);

        static::assertContains('"payload":{"data":"value"}', $serialized);
        static::assertContains('"createdAt":', $serialized);
        static::assertContains('"aggregateIdClass":"Gears\\\\Identity\\\\UuidIdentity"', $serialized);
        static::assertContains('"aggregateVersion":0', $serialized);
        static::assertContains('"metadata":[]', $serialized);
        static::assertContains('"aggregateId":"3247cb6e-e9c7-4f3a-9c6c-0dec26a0353f"', $serialized);
    }

    public function testDeserialize(): void
    {
        $event = AggregateEventStub::instance(
            UuidIdentity::fromString('3247cb6e-e9c7-4f3a-9c6c-0dec26a0353f'),
            ['data' => 'value']
        );
        $event = $event->withAddedMetadata(['meta' => 'data']);
        $event = AggregateEventStub::withVersion($event, new AggregateVersion(10));
        $eventDate = $event->getCreatedAt()->format('Y-m-d\TH:i:s.uP');

        $serialized = '{"class":"Gears\\\\EventSourcing\\\\Async\\\\Tests\\\\Stub\\\\AggregateEventStub",'
            . '"payload":{"data":"value"},'
            . '"createdAt":"' . $eventDate . '",'
            . '"attributes":{'
            . '"aggregateIdClass":"Gears\\\\Identity\\\\UuidIdentity",'
            . '"aggregateId":"3247cb6e-e9c7-4f3a-9c6c-0dec26a0353f",'
            . '"aggregateVersion":10,'
            . '"metadata":{"meta":"data"}'
            . '}}';

        $deserialized = (new JsonEventSerializer())->fromSerialized($serialized);

        static::assertEquals($event, $deserialized);
    }

    public function testMissingAggregateIdClassDeserialization(): void
    {
        $this->expectException(EventSerializationException::class);
        $this->expectExceptionMessage('Error reconstituting event');

        $serialized = '{"class":"Gears\\\\EventSourcing\\\\Async\\\\Tests\\\\Stub\\\\AggregateEventStub",'
            . '"payload":{"data":"value"},'
            . '"createdAt":"2019-01-01T00:00:00.000000+00:00",'
            . '"attributes":{'
            . '"aggregateId":"3247cb6e-e9c7-4f3a-9c6c-0dec26a0353f",'
            . '"aggregateVersion":0,'
            . '"metadata":{}'
            . '}}';

        (new JsonEventSerializer())->fromSerialized($serialized);
    }

    public function testInvalidAggregateIdClassDeserialization(): void
    {
        $this->expectException(EventSerializationException::class);
        $this->expectExceptionMessage('Error reconstituting event');

        $serialized = '{"class":"Gears\\\\EventSourcing\\\\Async\\\\Tests\\\\Stub\\\\AggregateEventStub",'
            . '"payload":{"data":"value"},'
            . '"createdAt":"2019-01-01T00:00:00.000000+00:00",'
            . '"attributes":{'
            . '"aggregateIdClass":"Gears\\\\Event\\\\Async\\\\Serializer\\\\JsonEventSerializer",'
            . '"aggregateId":"3247cb6e-e9c7-4f3a-9c6c-0dec26a0353f",'
            . '"aggregateVersion":0,'
            . '"metadata":{}'
            . '}}';

        (new JsonEventSerializer())->fromSerialized($serialized);
    }
}
