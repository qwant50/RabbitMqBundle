<?php

namespace OldSound\RabbitMqBundle\Command;

class RpcServerCommand extends BaseConsumerCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('rabbitmq:rpc-server')
            ->setDescription('Start an RPC server')
        ;
    }

    protected function getConsumerService()
    {
        return 'old_sound_rabbit_mq.%s_server';
    }
}
