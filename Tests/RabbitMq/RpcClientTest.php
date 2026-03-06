<?php

use OldSound\RabbitMqBundle\RabbitMq\RpcClient;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

test('process message uses custom unserializer when set', function () {
    // onlyMethods([]) keeps all real implementations so processMessage() runs actual code
    $client = $this->getMockBuilder(RpcClient::class)
        ->onlyMethods([])
        ->disableOriginalConstructor()
        ->getMock();

    $message = $this->getMockBuilder(AMQPMessage::class)
        ->onlyMethods(['get'])
        ->setConstructorArgs(['message'])
        ->getMock();

    $serializer = $this->getMockBuilder('\Symfony\Component\Serializer\SerializerInterface')->getMock();
    $serializer->expects($this->once())->method('deserialize')->with('message', 'json', null);

    $client->initClient(true);
    $client->setUnserializer(function ($data) use ($serializer) {
        $serializer->deserialize($data, 'json', '');
    });

    $client->processMessage($message);
});

test('process message calls notify callback with message body', function () {
    // onlyMethods([]) keeps all real implementations so processMessage() and notify() run actual code
    $client = $this->getMockBuilder(RpcClient::class)
        ->onlyMethods([])
        ->disableOriginalConstructor()
        ->getMock();

    $expectedBody = 'message';

    $message = $this->getMockBuilder(AMQPMessage::class)
        ->onlyMethods(['get'])
        ->setConstructorArgs([$expectedBody])
        ->getMock();

    $notified = false;
    $client->notify(function ($msg) use (&$notified) {
        $notified = $msg;
    });

    $client->initClient(false);
    $client->processMessage($message);

    expect($notified)->toBe($expectedBody);
});

test('notify throws when given a non-callable', function () {
    // onlyMethods([]) keeps all real implementations so notify() throws as expected
    $client = $this->getMockBuilder(RpcClient::class)
        ->onlyMethods([])
        ->disableOriginalConstructor()
        ->getMock();

    expect(fn () => $client->notify('not a callable'))->toThrow(\InvalidArgumentException::class);
});

test('channel is cancelled when getReplies throws an exception', function () {
    // onlyMethods([]) keeps all real implementations so getReplies() runs actual code
    $client = $this->getMockBuilder(RpcClient::class)
        ->onlyMethods([])
        ->disableOriginalConstructor()
        ->getMock();

    $channel = $this->createMock('\PhpAmqpLib\Channel\AMQPChannel');
    $channel->method('getChannelId')->willReturn('test');
    $channel->expects($this->once())->method('wait')->willThrowException(new AMQPTimeoutException());
    $channel->expects($this->once())->method('basic_cancel');

    $client->setChannel($channel);
    $client->addRequest('a', 'b', 'c');

    expect(fn () => $client->getReplies())->toThrow(AMQPTimeoutException::class);
});
