<?php

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\MultipleConsumer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

beforeEach(function () {
    $this->amqpConnection = $this->getMockBuilder(AMQPStreamConnection::class)->disableOriginalConstructor()->getMock();
    $this->amqpChannel    = $this->getMockBuilder(AMQPChannel::class)->disableOriginalConstructor()->getMock();
    $this->consumer       = new MultipleConsumer($this->amqpConnection, $this->amqpChannel);
});

dataset('multiple_consumer_process_flags', [
    'ack on null return'                        => [null,                              'basic_ack',    null],
    'ack on true return'                        => [true,                              'basic_ack',    null],
    'reject and requeue on false'               => [false,                             'basic_reject', true],
    'ack on MSG_ACK'                            => [ConsumerInterface::MSG_ACK,        'basic_ack',    null],
    'reject and requeue on MSG_REJECT_REQUEUE'  => [ConsumerInterface::MSG_REJECT_REQUEUE, 'basic_reject', true],
    'reject and drop on MSG_REJECT'             => [ConsumerInterface::MSG_REJECT,     'basic_reject', false],
]);

test('process queue message acks or rejects according to callback return value', function (mixed $processFlag, string $expectedMethod, ?bool $expectedRequeue) {
    $callback = static fn () => $processFlag;

    $this->consumer->setQueues([
        'test-1' => ['callback' => $callback],
        'test-2' => ['callback' => $callback],
    ]);

    $this->amqpChannel->method('basic_reject')
        ->willReturnCallback(function ($tag, $requeue) use ($expectedMethod, $expectedRequeue) {
            expect($expectedMethod)->toBe('basic_reject');
            expect($requeue)->toBe($expectedRequeue);
        });

    $this->amqpChannel->method('basic_ack')
        ->willReturnCallback(function () use ($expectedMethod) {
            expect($expectedMethod)->toBe('basic_ack');
        });

    $this->consumer->processQueueMessage('test-1', createMultipleConsumerTestMessage($this->amqpChannel));
    $this->consumer->processQueueMessage('test-2', createMultipleConsumerTestMessage($this->amqpChannel));
})->with('multiple_consumer_process_flags');

test('queues provider is used when set', function (mixed $processFlag, string $expectedMethod, ?bool $expectedRequeue) {
    $callback = static fn () => $processFlag;

    $queuesProvider = $this->getMockBuilder('\OldSound\RabbitMqBundle\Provider\QueuesProviderInterface')->getMock();
    $queuesProvider->expects($this->once())
        ->method('getQueues')
        ->willReturn([
            'test-1' => ['callback' => $callback],
            'test-2' => ['callback' => $callback],
        ]);

    $this->consumer->setQueuesProvider($queuesProvider);

    $reflectionClass  = new \ReflectionClass(MultipleConsumer::class);
    $reflectionMethod = $reflectionClass->getMethod('mergeQueues');
    $reflectionMethod->setAccessible(true);
    $reflectionMethod->invoke($this->consumer);

    $this->amqpChannel->method('basic_reject')
        ->willReturnCallback(function ($tag, $requeue) use ($expectedMethod, $expectedRequeue) {
            expect($expectedMethod)->toBe('basic_reject');
            expect($requeue)->toBe($expectedRequeue);
        });

    $this->amqpChannel->method('basic_ack')
        ->willReturnCallback(function () use ($expectedMethod) {
            expect($expectedMethod)->toBe('basic_ack');
        });

    $this->consumer->processQueueMessage('test-1', createMultipleConsumerTestMessage($this->amqpChannel));
    $this->consumer->processQueueMessage('test-2', createMultipleConsumerTestMessage($this->amqpChannel));
})->with('multiple_consumer_process_flags');

test('queues provider and static queues are merged together', function (mixed $processFlag, string $expectedMethod, ?bool $expectedRequeue) {
    $callback = static fn () => $processFlag;

    $this->consumer->setQueues([
        'test-1' => ['callback' => $callback],
        'test-2' => ['callback' => $callback],
    ]);

    $queuesProvider = $this->getMockBuilder('\OldSound\RabbitMqBundle\Provider\QueuesProviderInterface')->getMock();
    $queuesProvider->expects($this->once())
        ->method('getQueues')
        ->willReturn([
            'test-3' => ['callback' => $callback],
            'test-4' => ['callback' => $callback],
        ]);

    $this->consumer->setQueuesProvider($queuesProvider);

    $reflectionClass  = new \ReflectionClass(MultipleConsumer::class);
    $reflectionMethod = $reflectionClass->getMethod('mergeQueues');
    $reflectionMethod->setAccessible(true);
    $reflectionMethod->invoke($this->consumer);

    $this->amqpChannel->method('basic_reject')
        ->willReturnCallback(function ($tag, $requeue) use ($expectedMethod, $expectedRequeue) {
            expect($expectedMethod)->toBe('basic_reject');
            expect($requeue)->toBe($expectedRequeue);
        });

    $this->amqpChannel->method('basic_ack')
        ->willReturnCallback(function () use ($expectedMethod) {
            expect($expectedMethod)->toBe('basic_ack');
        });

    $this->consumer->processQueueMessage('test-1', createMultipleConsumerTestMessage($this->amqpChannel));
    $this->consumer->processQueueMessage('test-2', createMultipleConsumerTestMessage($this->amqpChannel));
    $this->consumer->processQueueMessage('test-3', createMultipleConsumerTestMessage($this->amqpChannel));
    $this->consumer->processQueueMessage('test-4', createMultipleConsumerTestMessage($this->amqpChannel));
})->with('multiple_consumer_process_flags');

test('queue declaration passes queue arguments to bind', function (array $routingKeysOption, string $expectedRoutingKey) {
    $queueName    = 'test-queue-name';
    $exchangeName = 'test-exchange-name';
    $expectedArgs = ['test-argument' => ['S', 'test-value']];

    $this->amqpChannel->method('getChannelId')->willReturn(0);
    $this->amqpChannel->method('queue_declare')->willReturn([$queueName, 5, 0]);

    $this->consumer->setExchangeOptions(['declare' => false, 'name' => $exchangeName, 'type' => 'topic']);
    $this->consumer->setQueues([
        $queueName => [
            'passive'      => true,
            'durable'      => true,
            'exclusive'    => true,
            'auto_delete'  => true,
            'nowait'       => true,
            'arguments'    => $expectedArgs,
            'ticket'       => null,
            'routing_keys' => $routingKeysOption,
        ],
    ]);
    $this->consumer->setRoutingKey('test-routing-key');

    $this->amqpChannel->expects($this->once())
        ->method('queue_bind')
        ->with($queueName, $exchangeName, $expectedRoutingKey, false, $expectedArgs);

    $this->consumer->setupFabric();
})->with([
    'uses consumer routing key when queue has none' => [[], 'test-routing-key'],
    'uses queue-specific routing key'               => [['test-routing-key-2'], 'test-routing-key-2'],
]);

function createMultipleConsumerTestMessage(mixed $channel): AMQPMessage
{
    $message = new AMQPMessage('foo body');
    $message->setChannel($channel);
    $message->setDeliveryTag(0);

    return $message;
}
