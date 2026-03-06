<?php

use OldSound\RabbitMqBundle\RabbitMq\BaseConsumer;

beforeEach(function () {
    $amqpConnection = $this->getMockBuilder('\PhpAmqpLib\Connection\AMQPStreamConnection')
        ->disableOriginalConstructor()
        ->getMock();

    $this->consumer = new class ($amqpConnection) extends BaseConsumer {};
});

test('it extends BaseAmqp', function () {
    expect($this->consumer)->toBeInstanceOf('OldSound\RabbitMqBundle\RabbitMq\BaseAmqp');
});

test('it implements DequeuerInterface', function () {
    expect($this->consumer)->toBeInstanceOf('OldSound\RabbitMqBundle\RabbitMq\DequeuerInterface');
});

test('idle timeout is mutable', function () {
    expect($this->consumer->getIdleTimeout())->toBe(0);

    $this->consumer->setIdleTimeout(42);

    expect($this->consumer->getIdleTimeout())->toBe(42);
});

test('idle timeout exit code is mutable', function () {
    expect($this->consumer->getIdleTimeoutExitCode())->toBeNull();

    $this->consumer->setIdleTimeoutExitCode(43);

    expect($this->consumer->getIdleTimeoutExitCode())->toBe(43);
});
