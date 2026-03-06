<?php

use OldSound\RabbitMqBundle\Event\AMQPEvent;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;

test('lazy connection does not open channel on construction', function () {
    $connection = $this->getMockBuilder('PhpAmqpLib\Connection\AbstractConnection')
        ->disableOriginalConstructor()
        ->getMock();

    $connection->method('connectOnConstruct')->willReturn(false);
    $connection->expects(static::never())->method('channel');

    new Consumer($connection, null);
});

test('non-lazy connection opens channel on construction', function () {
    $connection = $this->getMockBuilder('PhpAmqpLib\Connection\AbstractConnection')
        ->disableOriginalConstructor()
        ->getMock();

    $connection->method('connectOnConstruct')->willReturn(true);
    $connection->expects(static::once())->method('channel');

    new Consumer($connection, null);
});

test('dispatch event delegates to event dispatcher', function () {
    $baseAmqpConsumer = $this->getMockBuilder('OldSound\RabbitMqBundle\RabbitMq\BaseAmqp')
        ->disableOriginalConstructor()
        ->getMock();

    $eventDispatcher = $this->getMockBuilder('Symfony\Contracts\EventDispatcher\EventDispatcherInterface')
        ->disableOriginalConstructor()
        ->getMock();

    $baseAmqpConsumer->method('getEventDispatcher')->willReturn($eventDispatcher);

    $eventDispatcher->expects($this->once())
        ->method('dispatch')
        ->with(new AMQPEvent(), AMQPEvent::ON_CONSUME)
        ->willReturn(new AMQPEvent());

    $class = new \ReflectionClass(get_class($baseAmqpConsumer));
    $method = $class->getMethod('dispatchEvent');
    $method->setAccessible(true);
    $method->invokeArgs($baseAmqpConsumer, [AMQPEvent::ON_CONSUME, new AMQPEvent()]);
});
