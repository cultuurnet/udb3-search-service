<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use CultuurNet\UDB3\Search\Creator;
use PHPUnit\Framework\TestCase;

final class ConsumerTest extends TestCase
{
    /**
     * @test
     */
    public function it_resolves_the_creator_of_a_client_consumer_to_the_client_id_with_clients_suffix(): void
    {
        // A consumer authenticated via "x-client-id: <clientId>" (no end-user) must produce the
        // creator "{clientId}@clients", mirroring the {azp}@clients value udb3-backend stamps on
        // client-created events. It must NOT resolve to the client's owner user UUID.
        $consumer = new Consumer('pjeOqgEYI0Y4gmr8DWMpUrpTMXrvjgpc', null);

        $this->assertEquals(
            new Creator('pjeOqgEYI0Y4gmr8DWMpUrpTMXrvjgpc@clients'),
            $consumer->getCreator()
        );
    }

    /**
     * @test
     */
    public function it_has_no_creator_when_the_consumer_has_no_id(): void
    {
        $consumer = new Consumer(null, null);

        $this->assertNull($consumer->getCreator());
    }
}
