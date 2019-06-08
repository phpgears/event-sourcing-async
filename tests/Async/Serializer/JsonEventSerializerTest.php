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
    /**
     * @expectedException \Gears\Event\Async\Serializer\Exception\EventSerializationException
     * @expectedExceptionMessageRegExp /Event class .+ does not implement Gears\\EventSourcing\\Event\\AggregateEvent/
     */
    public function testInvalidEventSerialize(): void
    {
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

        $this->assertContains('"payload":{"data":"value"}', $serialized);
        $this->assertContains('"aggregateIdClass":"Gears\\\\Identity\\\\UuidIdentity"', $serialized);
        $this->assertContains('"aggregateVersion":0', $serialized);
        $this->assertContains('"metadata":[]', $serialized);
        $this->assertContains('"aggregateId":"3247cb6e-e9c7-4f3a-9c6c-0dec26a0353f"', $serialized);
    }

    public function testDeserialize(): void
    {
        $event = AggregateEventStub::instance(
            UuidIdentity::fromString('3247cb6e-e9c7-4f3a-9c6c-0dec26a0353f'),
            ['data' => 'value']
        );
        $event = $event->withMetadata(['meta' => 'data']);
        $event = $event->withAggregateVersion(new AggregateVersion(10));
        $eventDate = $event->getCreatedAt()->format('Y-m-d\TH:i:s.uP');

        $serialized = '{"class":"Gears\\\\EventSourcing\\\\Async\\\\Tests\\\\Stub\\\\AggregateEventStub",'
            . '"payload":{"data":"value"},'
            . '"attributes":{'
            . '"aggregateIdClass":"Gears\\\\Identity\\\\UuidIdentity",'
            . '"aggregateId":"3247cb6e-e9c7-4f3a-9c6c-0dec26a0353f",'
            . '"aggregateVersion":10,'
            . '"metadata":{"meta":"data"},'
            . '"createdAt":"' . $eventDate . '"'
            . '}}';

        $deserialized = (new JsonEventSerializer())->fromSerialized($serialized);

        $this->assertEquals($event, $deserialized);
    }

    /**
     * @expectedException \Gears\Event\Async\Serializer\Exception\EventSerializationException
     * @expectedExceptionMessage Error reconstituting event
     */
    public function testMissingAggregateIdClassDeserialization(): void
    {
        $serialized = '{"class":"Gears\\\\EventSourcing\\\\Async\\\\Tests\\\\Stub\\\\AggregateEventStub",'
            . '"payload":{"data":"value"},'
            . '"attributes":{'
            . '"aggregateId":"3247cb6e-e9c7-4f3a-9c6c-0dec26a0353f",'
            . '"aggregateVersion":0,'
            . '"metadata":{},'
            . '"createdAt":"2019-01-01T00:00:00.000000+00:00"'
            . '}}';

        (new JsonEventSerializer())->fromSerialized($serialized);
    }

    /**
     * @expectedException \Gears\Event\Async\Serializer\Exception\EventSerializationException
     * @expectedExceptionMessage Error reconstituting event
     */
    public function testInvalidAggregateIdClassDeserialization(): void
    {
        $serialized = '{"class":"Gears\\\\EventSourcing\\\\Async\\\\Tests\\\\Stub\\\\AggregateEventStub",'
            . '"payload":{"data":"value"},'
            . '"attributes":{'
            . '"aggregateIdClass":"Gears\\\\Event\\\\Async\\\\Serializer\\\\JsonEventSerializer",'
            . '"aggregateId":"3247cb6e-e9c7-4f3a-9c6c-0dec26a0353f",'
            . '"aggregateVersion":0,'
            . '"metadata":{},'
            . '"createdAt":"2019-01-01T00:00:00.000000+00:00"'
            . '}}';

        (new JsonEventSerializer())->fromSerialized($serialized);
    }
}
