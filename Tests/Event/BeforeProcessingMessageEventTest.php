<?php

use OldSound\RabbitMqBundle\Event\BeforeProcessingMessageEvent;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use PhpAmqpLib\Message\AMQPMessage;

test('event stores the correct message and consumer', function () {
    $consumer = new Consumer(
        $this->getMockBuilder('\PhpAmqpLib\Connection\AMQPStreamConnection')
            ->disableOriginalConstructor()
            ->getMock(),
        $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock()
    );

    $message = new AMQPMessage('body');
    $event = new BeforeProcessingMessageEvent($consumer, $message);

    expect($event->getAMQPMessage())->toBe($message);
    expect($event->getConsumer())->toBe($consumer);
});
