<?php

use OldSound\RabbitMqBundle\Provider\ConnectionParametersProviderInterface;
use OldSound\RabbitMqBundle\RabbitMq\AMQPConnectionFactory;
use OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPConnection;
use OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPSocketConnection;
use OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPSSLConnection;

test('default connection values', function () {
    $factory = new AMQPConnectionFactory(AMQPConnection::class, []);

    /** @var AMQPConnection $instance */
    $instance = $factory->createConnection();

    expect($instance)->toBeInstanceOf(AMQPConnection::class);
    expect($instance->constructParams)->toEqual([
        'localhost', 5672, 'guest', 'guest', '/',
        false, 'AMQPLAIN', null, 'en_US',
        3, 3, null, false, 0, 0.0,
    ]);
});

test('socket connection default values', function () {
    $factory = new AMQPConnectionFactory(AMQPSocketConnection::class, []);

    /** @var AMQPSocketConnection $instance */
    $instance = $factory->createConnection();

    expect($instance)->toBeInstanceOf(AMQPSocketConnection::class);
    expect($instance->constructParams)->toEqual([
        'localhost', 5672, 'guest', 'guest', '/',
        false, 'AMQPLAIN', null, 'en_US',
        3, false, 3, 0, 0.0,
    ]);
});

test('socket connection with custom timeouts', function () {
    $factory = new AMQPConnectionFactory(AMQPSocketConnection::class, [
        'read_timeout' => 31,
        'write_timeout' => 32,
    ]);

    /** @var AMQPSocketConnection $instance */
    $instance = $factory->createConnection();

    expect($instance)->toBeInstanceOf(AMQPSocketConnection::class);
    expect($instance->constructParams)->toEqual([
        'localhost', 5672, 'guest', 'guest', '/',
        false, 'AMQPLAIN', null, 'en_US',
        31, false, 32, 0, 0.0,
    ]);
});

test('standard connection parameters', function () {
    $factory = new AMQPConnectionFactory(AMQPConnection::class, [
        'host'     => 'foo_host',
        'port'     => 123,
        'user'     => 'foo_user',
        'password' => 'foo_password',
        'vhost'    => '/vhost',
    ]);

    /** @var AMQPConnection $instance */
    $instance = $factory->createConnection();

    expect($instance)->toBeInstanceOf(AMQPConnection::class);
    expect($instance->constructParams)->toEqual([
        'foo_host', 123, 'foo_user', 'foo_password', '/vhost',
        false, 'AMQPLAIN', null, 'en_US',
        3, 3, null, false, 0, 0.0,
    ]);
});

test('URL parameters override individual parameters', function () {
    $factory = new AMQPConnectionFactory(AMQPConnection::class, [
        'url'      => 'amqp://bar_user:bar_password@bar_host:321/whost?keepalive=1&connection_timeout=6&read_write_timeout=6',
        'host'     => 'foo_host',
        'port'     => 123,
        'user'     => 'foo_user',
        'password' => 'foo_password',
        'vhost'    => '/vhost',
    ]);

    /** @var AMQPConnection $instance */
    $instance = $factory->createConnection();

    expect($instance)->toBeInstanceOf(AMQPConnection::class);
    expect($instance->constructParams)->toEqual([
        'bar_host', 321, 'bar_user', 'bar_password', 'whost',
        false, 'AMQPLAIN', null, 'en_US',
        6, 6, null, true, 0, 0.0,
    ]);
});

test('URL with percent-encoded values', function () {
    $factory = new AMQPConnectionFactory(AMQPConnection::class, [
        'url' => 'amqp://user%61:%61pass@ho%61st:10000/v%2fhost?keepalive=1&connection_timeout=6&read_write_timeout=6',
    ]);

    /** @var AMQPConnection $instance */
    $instance = $factory->createConnection();

    expect($instance)->toBeInstanceOf(AMQPConnection::class);
    expect($instance->constructParams)->toEqual([
        'hoast', 10000, 'usera', 'apass', 'v/host',
        false, 'AMQPLAIN', null, 'en_US',
        6, 6, null, true, 0, 0.0,
    ]);
});

test('URL without vhost', function () {
    $factory = new AMQPConnectionFactory(AMQPConnection::class, [
        'url' => 'amqp://user:pass@host:321/?keepalive=1&connection_timeout=6&read_write_timeout=6',
    ]);

    /** @var AMQPConnection $instance */
    $instance = $factory->createConnection();

    expect($instance)->toBeInstanceOf(AMQPConnection::class);
    expect($instance->constructParams)->toEqual([
        'host', 321, 'user', 'pass', '',
        false, 'AMQPLAIN', null, 'en_US',
        6, 6, null, true, 0, 0.0,
    ]);
});

test('SSL connection parameters', function () {
    $factory = new AMQPConnectionFactory(AMQPSSLConnection::class, [
        'host'        => 'ssl_host',
        'port'        => 123,
        'user'        => 'ssl_user',
        'password'    => 'ssl_password',
        'vhost'       => '/ssl',
        'ssl_context' => ['verify_peer' => false],
    ]);

    /** @var AMQPSSLConnection $instance */
    $instance = $factory->createConnection();

    expect($instance)->toBeInstanceOf(AMQPSSLConnection::class);
    expect($instance->constructParams)->toHaveKey(6);

    $options = $instance->constructParams[6];
    expect($options)->toHaveKey('ssl_context');
    expect($options)->toHaveKey('context');

    $context = $options['context'];
    $instance->constructParams[6]['ssl_context'] = null;
    $instance->constructParams[6]['context'] = null;

    $this->assertIsResource($context);
    expect(get_resource_type($context))->toBe('stream-context');
    expect(stream_context_get_options($context))->toEqual(['ssl' => ['verify_peer' => false]]);

    expect($instance->constructParams)->toEqual([
        'ssl_host', 123, 'ssl_user', 'ssl_password', '/ssl',
        [],
        [
            'url'                 => '',
            'host'                => 'ssl_host',
            'port'                => 123,
            'user'                => 'ssl_user',
            'password'            => 'ssl_password',
            'vhost'               => '/ssl',
            'connection_timeout'  => 3,
            'read_write_timeout'  => 3,
            'ssl_context'         => null,
            'context'             => null,
            'keepalive'           => false,
            'heartbeat'           => 0,
            'channel_rpc_timeout' => 0.0,
        ],
    ]);
});

test('cluster connection without root connection keys', function () {
    $factory = new AMQPConnectionFactory(AMQPConnection::class, [
        'hosts' => [
            ['host' => 'cluster_host', 'port' => 123, 'user' => 'cluster_user', 'password' => 'cluster_password', 'vhost' => '/cluster_vhost'],
            ['url' => 'amqp://user:pass@host:321/vhost'],
        ],
    ]);

    /** @var AMQPConnection $instance */
    $instance = $factory->createConnection();

    expect($instance)->toBeInstanceOf(AMQPConnection::class);
    expect($instance->constructParams)->toEqual([
        'cluster_host', 123, 'cluster_user', 'cluster_password', '/cluster_vhost',
        false, 'AMQPLAIN', null, 'en_US',
        3, 3, null, false, 0, 0.0,
    ]);

    expect($instance::$createConnectionParams)->toEqual([
        [
            ['host' => 'cluster_host', 'port' => 123, 'user' => 'cluster_user', 'password' => 'cluster_password', 'vhost' => '/cluster_vhost'],
            ['host' => 'host', 'port' => 321, 'user' => 'user', 'password' => 'pass', 'vhost' => 'vhost'],
        ],
        [
            'url' => '', 'host' => 'localhost', 'port' => 5672, 'user' => 'guest', 'password' => 'guest',
            'vhost' => '/', 'connection_timeout' => 3, 'read_write_timeout' => 3,
            'ssl_context' => null, 'keepalive' => false, 'heartbeat' => 0, 'channel_rpc_timeout' => 0.0,
        ],
    ]);
});

test('cluster connection with root connection keys', function () {
    $factory = new AMQPConnectionFactory(AMQPConnection::class, [
        'host'     => 'host',
        'port'     => 123,
        'user'     => 'user',
        'password' => 'password',
        'vhost'    => '/vhost',
        'hosts'    => [
            ['host' => 'cluster_host', 'port' => 123, 'user' => 'cluster_user', 'password' => 'cluster_password', 'vhost' => '/vhost'],
        ],
    ]);

    /** @var AMQPConnection $instance */
    $instance = $factory->createConnection();

    expect($instance)->toBeInstanceOf(AMQPConnection::class);
    expect($instance->constructParams)->toEqual([
        'cluster_host', 123, 'cluster_user', 'cluster_password', '/vhost',
        false, 'AMQPLAIN', null, 'en_US',
        3, 3, null, false, 0, 0.0,
    ]);

    expect($instance::$createConnectionParams)->toEqual([
        [
            ['host' => 'cluster_host', 'port' => 123, 'user' => 'cluster_user', 'password' => 'cluster_password', 'vhost' => '/vhost'],
        ],
        [
            'url' => '', 'host' => 'host', 'port' => 123, 'user' => 'user', 'password' => 'password',
            'vhost' => '/vhost', 'connection_timeout' => 3, 'read_write_timeout' => 3,
            'ssl_context' => null, 'keepalive' => false, 'heartbeat' => 0, 'channel_rpc_timeout' => 0.0,
        ],
    ]);
});

test('SSL cluster connection parameters', function () {
    $factory = new AMQPConnectionFactory(AMQPSSLConnection::class, [
        'hosts' => [
            ['host' => 'ssl_cluster_host', 'port' => 123, 'user' => 'ssl_cluster_user', 'password' => 'ssl_cluster_password', 'vhost' => '/ssl_cluster_vhost'],
            ['url' => 'amqp://user:pass@host:321/vhost'],
        ],
        'ssl_context' => ['verify_peer' => false],
    ]);

    /** @var AMQPSSLConnection $instance */
    $instance = $factory->createConnection();

    expect($instance)->toBeInstanceOf(AMQPSSLConnection::class);

    expect($instance->constructParams)->toHaveKey(6);
    $options = $instance->constructParams[6];
    expect($options)->toHaveKey('ssl_context');
    expect($options)->toHaveKey('context');
    $context = $options['context'];
    $instance->constructParams[6]['ssl_context'] = null;
    $instance->constructParams[6]['context'] = null;

    $this->assertIsResource($context);
    expect(get_resource_type($context))->toBe('stream-context');
    expect(stream_context_get_options($context))->toEqual(['ssl' => ['verify_peer' => false]]);

    expect($instance::$createConnectionParams)->toHaveKey(1);
    $createConnectionOptions = $instance::$createConnectionParams[1];
    expect($createConnectionOptions)->toHaveKey('ssl_context');
    $createConnectionContext = $createConnectionOptions['context'];
    $instance::$createConnectionParams[1]['ssl_context'] = null;
    $instance::$createConnectionParams[1]['context'] = null;
    $this->assertIsResource($createConnectionContext);
    expect(get_resource_type($createConnectionContext))->toBe('stream-context');
    expect(stream_context_get_options($createConnectionContext))->toEqual(['ssl' => ['verify_peer' => false]]);

    expect($instance->constructParams)->toEqual([
        'ssl_cluster_host', 123, 'ssl_cluster_user', 'ssl_cluster_password', '/ssl_cluster_vhost',
        [],
        [
            'url' => '', 'host' => 'localhost', 'port' => 5672, 'user' => 'guest', 'password' => 'guest',
            'vhost' => '/', 'connection_timeout' => 3, 'read_write_timeout' => 3,
            'ssl_context' => null, 'context' => null,
            'keepalive' => false, 'heartbeat' => 0, 'channel_rpc_timeout' => 0.0,
        ],
    ]);

    expect($instance::$createConnectionParams)->toEqual([
        [
            ['host' => 'ssl_cluster_host', 'port' => 123, 'user' => 'ssl_cluster_user', 'password' => 'ssl_cluster_password', 'vhost' => '/ssl_cluster_vhost'],
            ['host' => 'host', 'port' => 321, 'user' => 'user', 'password' => 'pass', 'vhost' => 'vhost'],
        ],
        [
            'url' => '', 'host' => 'localhost', 'port' => 5672, 'user' => 'guest', 'password' => 'guest',
            'vhost' => '/', 'connection_timeout' => 3, 'read_write_timeout' => 3,
            'ssl_context' => null, 'context' => null,
            'keepalive' => false, 'heartbeat' => 0, 'channel_rpc_timeout' => 0.0,
        ],
    ]);
});

test('socket cluster connection parameters', function () {
    $factory = new AMQPConnectionFactory(AMQPSocketConnection::class, [
        'hosts' => [
            ['host' => 'cluster_host', 'port' => 123, 'user' => 'cluster_user', 'password' => 'cluster_password', 'vhost' => '/cluster_vhost'],
            ['url' => 'amqp://user:pass@host:321/vhost'],
        ],
    ]);

    /** @var AMQPSocketConnection $instance */
    $instance = $factory->createConnection();

    expect($instance)->toBeInstanceOf(AMQPSocketConnection::class);
    expect($instance->constructParams)->toEqual([
        'cluster_host', 123, 'cluster_user', 'cluster_password', '/cluster_vhost',
        false, 'AMQPLAIN', null, 'en_US',
        3, false, 3, 0, 0.0,
    ]);

    expect($instance::$createConnectionParams)->toEqual([
        [
            ['host' => 'cluster_host', 'port' => 123, 'user' => 'cluster_user', 'password' => 'cluster_password', 'vhost' => '/cluster_vhost'],
            ['host' => 'host', 'port' => 321, 'user' => 'user', 'password' => 'pass', 'vhost' => 'vhost'],
        ],
        [
            'url' => '', 'host' => 'localhost', 'port' => 5672, 'user' => 'guest', 'password' => 'guest',
            'vhost' => '/', 'connection_timeout' => 3, 'read_write_timeout' => 3,
            'ssl_context' => null, 'keepalive' => false, 'heartbeat' => 0,
            'read_timeout' => 3, 'write_timeout' => 3, 'channel_rpc_timeout' => 0.0,
        ],
    ]);
});

test('connection parameters provider with constructor args', function () {
    $provider = $this->getMockBuilder(ConnectionParametersProviderInterface::class)->getMock();
    $provider->expects($this->once())
        ->method('getConnectionParameters')
        ->willReturn(['constructor_args' => [1, 2, 3, 4]]);

    $factory = new AMQPConnectionFactory(AMQPConnection::class, [], $provider);

    /** @var AMQPConnection $instance */
    $instance = $factory->createConnection();

    expect($instance)->toBeInstanceOf(AMQPConnection::class);
    expect($instance->constructParams)->toEqual([1, 2, 3, 4]);
});

test('connection parameters provider with standard parameters', function () {
    $provider = $this->getMockBuilder(ConnectionParametersProviderInterface::class)->getMock();
    $provider->expects($this->once())
        ->method('getConnectionParameters')
        ->willReturn([
            'host'     => '1.2.3.4',
            'port'     => 5678,
            'user'     => 'admin',
            'password' => 'admin',
            'vhost'    => 'foo',
        ]);

    $factory = new AMQPConnectionFactory(AMQPConnection::class, [], $provider);

    /** @var AMQPConnection $instance */
    $instance = $factory->createConnection();

    expect($instance)->toBeInstanceOf(AMQPConnection::class);
    expect($instance->constructParams)->toEqual([
        '1.2.3.4', 5678, 'admin', 'admin', 'foo',
        false, 'AMQPLAIN', null, 'en_US',
        3, 3, null, false, 0, 0.0,
    ]);
});
