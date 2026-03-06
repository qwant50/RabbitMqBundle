<?php

use OldSound\RabbitMqBundle\Event\OnIdleEvent;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;

beforeEach(function () {
    $this->consumer = new Consumer(
        $this->getMockBuilder('\PhpAmqpLib\Connection\AMQPStreamConnection')
            ->disableOriginalConstructor()
            ->getMock(),
        $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock()
    );
});

test('should allow get consumer set in constructor', function () {
    $event = new OnIdleEvent($this->consumer);

    expect($event->getConsumer())->toBe($this->consumer);
});

test('should set force stop to true in constructor', function () {
    $event = new OnIdleEvent($this->consumer);

    expect($event->isForceStop())->toBeTrue();
});

test('should return previously set force stop value', function () {
    $event = new OnIdleEvent($this->consumer);

    expect($event->isForceStop())->toBeTrue();

    $event->setForceStop(false);
    expect($event->isForceStop())->toBeFalse();
});
