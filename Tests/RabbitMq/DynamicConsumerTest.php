<?php

use OldSound\RabbitMqBundle\RabbitMq\DynamicConsumer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

test('queue options provider merges queue options', function () {
    $amqpConnection = $this->getMockBuilder(AMQPStreamConnection::class)->disableOriginalConstructor()->getMock();
    $amqpChannel    = $this->getMockBuilder(AMQPChannel::class)->disableOriginalConstructor()->getMock();

    $consumer = new DynamicConsumer($amqpConnection, $amqpChannel);
    $consumer->setContext('foo');

    $queueOptionsProvider = $this->getMockBuilder('\OldSound\RabbitMqBundle\Provider\QueueOptionsProviderInterface')->getMock();
    $queueOptionsProvider->expects($this->once())
        ->method('getQueueOptions')
        ->willReturn([
            'name'         => 'queue_foo',
            'routing_keys' => ['foo.*'],
        ]);

    $consumer->setQueueOptionsProvider($queueOptionsProvider);

    $reflectionClass  = new \ReflectionClass(DynamicConsumer::class);
    $reflectionMethod = $reflectionClass->getMethod('mergeQueueOptions');
    $reflectionMethod->setAccessible(true);
    $reflectionMethod->invoke($consumer);
});
