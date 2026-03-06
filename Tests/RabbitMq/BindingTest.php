<?php

use OldSound\RabbitMqBundle\RabbitMq\Binding;

test('queue bind delegates to channel with correct arguments', function () {
    $connection = $this->getMockBuilder('\PhpAmqpLib\Connection\AMQPStreamConnection')
        ->disableOriginalConstructor()
        ->getMock();

    $channel = $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
        ->disableOriginalConstructor()
        ->getMock();
    $channel->method('getChannelId')->willReturn('channel_id');

    $source      = 'example_source';
    $destination = 'example_destination';
    $key         = 'example_key';

    $channel->expects($this->once())
        ->method('queue_bind')
        ->with($destination, $source, $key, false, null);

    $binding = new Binding($connection, $channel);
    $binding->setExchange($source);
    $binding->setDestination($destination);
    $binding->setRoutingKey($key);
    $binding->setupFabric();
});

test('exchange bind delegates to channel with correct arguments', function () {
    $connection = $this->getMockBuilder('\PhpAmqpLib\Connection\AMQPStreamConnection')
        ->disableOriginalConstructor()
        ->getMock();

    $channel = $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
        ->disableOriginalConstructor()
        ->getMock();
    $channel->method('getChannelId')->willReturn('channel_id');

    $source      = 'example_source';
    $destination = 'example_destination';
    $key         = 'example_key';

    $channel->expects($this->once())
        ->method('exchange_bind')
        ->with($destination, $source, $key, false, null);

    $binding = new Binding($connection, $channel);
    $binding->setExchange($source);
    $binding->setDestination($destination);
    $binding->setRoutingKey($key);
    $binding->setDestinationIsExchange(true);
    $binding->setupFabric();
});
