<?php

use OldSound\RabbitMqBundle\RabbitMq\RpcServer;
use PhpAmqpLib\Message\AMQPMessage;

test('process message uses custom serializer when set', function () {
    $server = $this->getMockBuilder(RpcServer::class)
        ->onlyMethods(['sendReply', 'maybeStopConsumer'])
        ->disableOriginalConstructor()
        ->getMock();

    $message = $this->getMockBuilder(AMQPMessage::class)
        ->onlyMethods(['get'])
        ->getMock();

    $channel = $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
        ->disableOriginalConstructor()
        ->getMock();

    $message->setChannel($channel);
    $message->setDeliveryTag(0);

    $server->setCallback(static fn() => 'message');

    $serializer = $this->getMockBuilder('\Symfony\Component\Serializer\SerializerInterface')->getMock();
    $serializer->expects($this->once())->method('serialize')->with('message', 'json');

    $server->setSerializer(function ($data) use ($serializer) {
        $serializer->serialize($data, 'json');
    });

    $server->processMessage($message);
});
