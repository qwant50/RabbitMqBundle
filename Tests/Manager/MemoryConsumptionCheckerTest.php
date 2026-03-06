<?php

use OldSound\RabbitMqBundle\MemoryChecker\MemoryConsumptionChecker;
use OldSound\RabbitMqBundle\MemoryChecker\NativeMemoryUsageProvider;

test('memory is not almost overloaded when usage is below threshold', function () {
    $provider = $this->getMockBuilder(NativeMemoryUsageProvider::class)->getMock();
    $provider->method('getMemoryUsage')->willReturn('7M');

    $checker = new MemoryConsumptionChecker($provider);

    expect($checker->isRamAlmostOverloaded('10M', '2M'))->toBeFalse();
});

test('memory is almost overloaded when usage is within threshold', function () {
    $provider = $this->getMockBuilder(NativeMemoryUsageProvider::class)->getMock();
    $provider->method('getMemoryUsage')->willReturn('9M');

    $checker = new MemoryConsumptionChecker($provider);

    expect($checker->isRamAlmostOverloaded('10M', '2M'))->toBeTrue();
});

test('memory is not almost overloaded without allowed buffer', function () {
    $provider = $this->getMockBuilder(NativeMemoryUsageProvider::class)->getMock();
    $provider->method('getMemoryUsage')->willReturn('7M');

    $checker = new MemoryConsumptionChecker($provider);

    expect($checker->isRamAlmostOverloaded('10M'))->toBeFalse();
});

test('memory is almost overloaded when usage exceeds max without allowed buffer', function () {
    $provider = $this->getMockBuilder(NativeMemoryUsageProvider::class)->getMock();
    $provider->method('getMemoryUsage')->willReturn('11M');

    $checker = new MemoryConsumptionChecker($provider);

    expect($checker->isRamAlmostOverloaded('10M'))->toBeTrue();
});
