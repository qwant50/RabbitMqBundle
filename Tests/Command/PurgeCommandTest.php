<?php

use OldSound\RabbitMqBundle\Command\PurgeConsumerCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

beforeEach(function () {
    $this->application = $this->getMockBuilder(Application::class)->disableOriginalConstructor()->getMock();
    $this->definition  = $this->getMockBuilder(InputDefinition::class)->disableOriginalConstructor()->getMock();
    $this->helperSet   = $this->getMockBuilder(HelperSet::class)->getMock();

    $this->application->method('getDefinition')->willReturn($this->definition);
    $this->definition->method('getArguments')->willReturn([]);
    $this->definition->method('getOptions')->willReturn([
        new InputOption('--no-confirmation', null, InputOption::VALUE_NONE, 'Switches off confirmation mode.'),
    ]);

    $this->application->expects($this->once())->method('getHelperSet')->willReturn($this->helperSet);

    $this->command = new PurgeConsumerCommand();
    $this->command->setApplication($this->application);
});

test('purge command has the correct input definition', function () {
    $definition = $this->command->getDefinition();

    expect($definition->hasArgument('name'))->toBeTrue();
    expect($definition->getArgument('name')->isRequired())->toBeTrue();

    expect($definition->hasOption('no-confirmation'))->toBeTrue();
    expect($definition->getOption('no-confirmation')->acceptValue())->toBeFalse();
});
