<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\AMQP;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

interface ConsumerInterface
{
    public function consume(AMQPMessage $message);

    /**
     * @return AMQPChannel
     */
    public function getChannel();
}
