<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Search\Event\EventProjectedToJSONLD;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Search\Place\PlaceProjectedToJSONLD;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\MockObject\MockObject;

abstract class AbstractReindexUDB3CoreTest extends AbstractOperationTestCase
{
    /**
     * @var EventBus|MockObject
     */
    private $eventBus;

    /**
     * @var array
     */
    private $logMessages;

    protected function setUp()
    {
        $this->eventBus = $this->createMock(EventBus::class);

        parent::setUp();

        $this->logger->expects($this->any())
            ->method('info')
            ->willReturnCallback(
                function ($message) {
                    $this->logMessages[] = ['info', $message];
                }
            );

        $this->logger->expects($this->any())
            ->method('warning')
            ->willReturnCallback(
                function ($message) {
                    $this->logMessages[] = ['warning', $message];
                }
            );

        $this->logger->expects($this->any())
            ->method('error')
            ->willReturnCallback(
                function ($message) {
                    $this->logMessages[] = ['error', $message];
                }
            );
    }

    /**
     * @return EventBus|MockObject
     */
    public function getEventBus()
    {
        return $this->eventBus;
    }

    /**
     * @test
     */
    public function it_scrolls_through_documents_in_the_index_and_fires_corresponding_events_to_trigger_reindexation()
    {
        $index = 'mock';

        $initialQuery = [
            'scroll' => '1m',
            'size' => 10,
            'index' => 'mock',
            'body' => [
                'query' => $this->operation->getQueryArray(),
                'sort' => [
                    '_doc',
                ],
            ],
        ];

        // @codingStandardsIgnoreStart
        $scrollQuery = [
            'scroll_id' => 'cXVlcnlUaGVuRmV0Y2g7NTsxOlhVSUxlOFQ2UXl1V1FfcWNSQmVabEE7MjpYVUlMZThUNlF5dVdRX3FjUkJlWmxBOzM6WFVJTGU4VDZReXVXUV9xY1JCZVpsQTs0OlhVSUxlOFQ2UXl1V1FfcWNSQmVabEE7NTpYVUlMZThUNlF5dVdRX3FjUkJlWmxBOzA7',
            'scroll' => '1m',
        ];
        // @codingStandardsIgnoreEnd

        $results = $this->getJsonDocumentAsElasticSearchResults(__DIR__ . '/data/udb3-core-scroll-1.json');

        $this->client->expects($this->once())
            ->method('search')
            ->with($initialQuery)
            ->willReturn($results);

        $this->client->expects($this->exactly(2))
            ->method('scroll')
            ->with($scrollQuery)
            ->willReturnOnConsecutiveCalls(
                $this->getJsonDocumentAsElasticSearchResults(__DIR__ . '/data/udb3-core-scroll-2.json'),
                $this->getJsonDocumentAsElasticSearchResults(__DIR__ . '/data/udb3-core-scroll-3.json')
            );

        // @codingStandardsIgnoreStart
        $expectedLogs = [
            ['info', 'Dispatching PlaceProjectedToJSONLD with id 39e6d5ee-c3d6-453a-bcb5-4e6e0eaf7054 and url http://udb-silex.dev/place/39e6d5ee-c3d6-453a-bcb5-4e6e0eaf7054.'],
            ['info', 'Dispatching EventProjectedToJSONLD with id 23017cb7-e515-47b4-87c4-780735acc942 and url http://udb-silex.dev/event/23017cb7-e515-47b4-87c4-780735acc942.'],
            ['info', 'Dispatching PlaceProjectedToJSONLD with id a1b3a9d8-ef08-46eb-8984-c7d3012bbb5a and url http://udb-silex.dev/place/a1b3a9d8-ef08-46eb-8984-c7d3012bbb5a.'],
            ['info', 'Dispatching PlaceProjectedToJSONLD with id fb43ae3d-d297-4c4a-8479-488fc028c8c8 and url http://udb-silex.dev/place/fb43ae3d-d297-4c4a-8479-488fc028c8c8.'],
            ['info', 'Dispatching OrganizerProjectedToJSONLD with id 5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83 and url http://udb-silex.dev/organizers/5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83.'],
            ['info', 'Dispatching PlaceProjectedToJSONLD with id a6b0ab58-67a5-496a-8ef8-ac2da14828c2 and url http://udb-silex.dev/place/a6b0ab58-67a5-496a-8ef8-ac2da14828c2.'],
            ['info', 'Dispatching PlaceProjectedToJSONLD with id 8cc3b0d4-bc97-4c17-b3d4-072ccc58b242 and url http://udb-silex.dev/place/8cc3b0d4-bc97-4c17-b3d4-072ccc58b242.'],
            ['info', 'Dispatching EventProjectedToJSONLD with id 02ca9526-f7d6-4338-80fe-88a346fdd118 and url http://udb-silex.dev/event/02ca9526-f7d6-4338-80fe-88a346fdd118.'],
            ['info', 'Dispatching PlaceProjectedToJSONLD with id 179c89c5-dba4-417b-ae96-62e7a12c2405 and url http://udb-silex.dev/place/179c89c5-dba4-417b-ae96-62e7a12c2405.'],
            ['info', 'Dispatching EventProjectedToJSONLD with id 441a5831-a65e-44fa-81ef-5c47e9c57a05 and url http://udb-silex.dev/event/441a5831-a65e-44fa-81ef-5c47e9c57a05.'],
            ['info', 'Cleaning up...'],
            ['info', 'Closed ElasticSearch scroll.'],
        ];
        // @codingStandardsIgnoreEnd

        $expectedEvents = [
            new PlaceProjectedToJSONLD(
                '39e6d5ee-c3d6-453a-bcb5-4e6e0eaf7054',
                'http://udb-silex.dev/place/39e6d5ee-c3d6-453a-bcb5-4e6e0eaf7054'
            ),
            new EventProjectedToJSONLD(
                '23017cb7-e515-47b4-87c4-780735acc942',
                'http://udb-silex.dev/event/23017cb7-e515-47b4-87c4-780735acc942'
            ),
            new PlaceProjectedToJSONLD(
                'a1b3a9d8-ef08-46eb-8984-c7d3012bbb5a',
                'http://udb-silex.dev/place/a1b3a9d8-ef08-46eb-8984-c7d3012bbb5a'
            ),
            new PlaceProjectedToJSONLD(
                'fb43ae3d-d297-4c4a-8479-488fc028c8c8',
                'http://udb-silex.dev/place/fb43ae3d-d297-4c4a-8479-488fc028c8c8'
            ),
            new OrganizerProjectedToJSONLD(
                '5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83',
                'http://udb-silex.dev/organizers/5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83'
            ),
            new PlaceProjectedToJSONLD(
                'a6b0ab58-67a5-496a-8ef8-ac2da14828c2',
                'http://udb-silex.dev/place/a6b0ab58-67a5-496a-8ef8-ac2da14828c2'
            ),
            new PlaceProjectedToJSONLD(
                '8cc3b0d4-bc97-4c17-b3d4-072ccc58b242',
                'http://udb-silex.dev/place/8cc3b0d4-bc97-4c17-b3d4-072ccc58b242'
            ),
            new EventProjectedToJSONLD(
                '02ca9526-f7d6-4338-80fe-88a346fdd118',
                'http://udb-silex.dev/event/02ca9526-f7d6-4338-80fe-88a346fdd118'
            ),
            new PlaceProjectedToJSONLD(
                '179c89c5-dba4-417b-ae96-62e7a12c2405',
                'http://udb-silex.dev/place/179c89c5-dba4-417b-ae96-62e7a12c2405'
            ),
            new EventProjectedToJSONLD(
                '441a5831-a65e-44fa-81ef-5c47e9c57a05',
                'http://udb-silex.dev/event/441a5831-a65e-44fa-81ef-5c47e9c57a05'
            ),
        ];

        $actualEvents = [];

        $this->eventBus->expects($this->exactly(10))
            ->method('publish')
            ->willReturnCallback(
                function (DomainEventStream $stream) use (&$actualEvents) {
                    $domainMessage = $stream->getIterator()[0];
                    $event = $domainMessage->getPayload();
                    $actualEvents[] = $event;
                }
            );

        $this->operation->run($index);

        $this->assertEquals($expectedLogs, $this->logMessages);
        $this->assertEquals($expectedEvents, $actualEvents);
    }

    /**
     * @test
     */
    public function it_skips_hits_that_are_missing_a_property_or_have_an_unknown_type()
    {
        $index = 'mock';

        $initialQuery = [
            'scroll' => '1m',
            'size' => 10,
            'index' => 'mock',
            'body' => [
                'query' => $this->operation->getQueryArray(),
                'sort' => [
                    '_doc',
                ],
            ],
        ];

        // @codingStandardsIgnoreStart
        $scrollQuery = [
            'scroll_id' => 'cXVlcnlUaGVuRmV0Y2g7NTsxOlhVSUxlOFQ2UXl1V1FfcWNSQmVabEE7MjpYVUlMZThUNlF5dVdRX3FjUkJlWmxBOzM6WFVJTGU4VDZReXVXUV9xY1JCZVpsQTs0OlhVSUxlOFQ2UXl1V1FfcWNSQmVabEE7NTpYVUlMZThUNlF5dVdRX3FjUkJlWmxBOzA7',
            'scroll' => '1m',
        ];
        // @codingStandardsIgnoreEnd

        $results = $this->getJsonDocumentAsElasticSearchResults(__DIR__ . '/data/udb3-core-scroll-1-with-skips.json');

        $this->client->expects($this->once())
            ->method('search')
            ->with($initialQuery)
            ->willReturn($results);

        $this->client->expects($this->exactly(2))
            ->method('scroll')
            ->with($scrollQuery)
            ->willReturnOnConsecutiveCalls(
                $this->getJsonDocumentAsElasticSearchResults(__DIR__ . '/data/udb3-core-scroll-2-with-skips.json'),
                $this->getJsonDocumentAsElasticSearchResults(__DIR__ . '/data/udb3-core-scroll-3.json')
            );

        // @codingStandardsIgnoreStart
        $expectedLogs = [
            ['error', 'Skipping hit without _id property.'],
            ['info', 'Dispatching EventProjectedToJSONLD with id 23017cb7-e515-47b4-87c4-780735acc942 and url http://udb-silex.dev/event/23017cb7-e515-47b4-87c4-780735acc942.'],
            ['error', 'Skipping hit a1b3a9d8-ef08-46eb-8984-c7d3012bbb5a without _type property.'],
            ['info', 'Dispatching PlaceProjectedToJSONLD with id fb43ae3d-d297-4c4a-8479-488fc028c8c8 and url http://udb-silex.dev/place/fb43ae3d-d297-4c4a-8479-488fc028c8c8.'],
            ['info', 'Dispatching OrganizerProjectedToJSONLD with id 5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83 and url http://udb-silex.dev/organizers/5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83.'],
            ['error', 'Skipping hit 8cc3b0d4-bc97-4c17-b3d4-072ccc58b242 without _source property.'],
            ['error', 'Skipping hit 02ca9526-f7d6-4338-80fe-88a346fdd118 without @id property in _source.'],
            ['info', 'Dispatching PlaceProjectedToJSONLD with id 179c89c5-dba4-417b-ae96-62e7a12c2405 and url http://udb-silex.dev/place/179c89c5-dba4-417b-ae96-62e7a12c2405.'],
            ['error', 'Skipping hit 441a5831-a65e-44fa-81ef-5c47e9c57a05 with unknown document type mock-type.'],
            ['info', 'Cleaning up...'],
            ['info', 'Closed ElasticSearch scroll.'],
        ];
        // @codingStandardsIgnoreEnd

        $expectedEvents = [
            new EventProjectedToJSONLD(
                '23017cb7-e515-47b4-87c4-780735acc942',
                'http://udb-silex.dev/event/23017cb7-e515-47b4-87c4-780735acc942'
            ),
            new PlaceProjectedToJSONLD(
                'fb43ae3d-d297-4c4a-8479-488fc028c8c8',
                'http://udb-silex.dev/place/fb43ae3d-d297-4c4a-8479-488fc028c8c8'
            ),
            new OrganizerProjectedToJSONLD(
                '5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83',
                'http://udb-silex.dev/organizers/5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83'
            ),
            new PlaceProjectedToJSONLD(
                '179c89c5-dba4-417b-ae96-62e7a12c2405',
                'http://udb-silex.dev/place/179c89c5-dba4-417b-ae96-62e7a12c2405'
            ),
        ];

        $actualEvents = [];

        $this->eventBus->expects($this->exactly(4))
            ->method('publish')
            ->willReturnCallback(
                function (DomainEventStream $stream) use (&$actualEvents) {
                    $domainMessage = $stream->getIterator()[0];
                    $event = $domainMessage->getPayload();
                    $actualEvents[] = $event;
                }
            );

        $this->operation->run($index);

        $this->assertEquals($expectedLogs, $this->logMessages);
        $this->assertEquals($expectedEvents, $actualEvents);
    }

    /**
     * @test
     */
    public function it_should_continue_when_encountering_an_exception_while_publishing_the_event_to_the_event_bus()
    {
        $index = 'mock';

        $initialQuery = [
            'scroll' => '1m',
            'size' => 10,
            'index' => 'mock',
            'body' => [
                'query' => $this->operation->getQueryArray(),
                'sort' => [
                    '_doc',
                ],
            ],
        ];

        // @codingStandardsIgnoreStart
        $scrollQuery = [
            'scroll_id' => 'cXVlcnlUaGVuRmV0Y2g7NTsxOlhVSUxlOFQ2UXl1V1FfcWNSQmVabEE7MjpYVUlMZThUNlF5dVdRX3FjUkJlWmxBOzM6WFVJTGU4VDZReXVXUV9xY1JCZVpsQTs0OlhVSUxlOFQ2UXl1V1FfcWNSQmVabEE7NTpYVUlMZThUNlF5dVdRX3FjUkJlWmxBOzA7',
            'scroll' => '1m',
        ];
        // @codingStandardsIgnoreEnd

        $results = $this->getJsonDocumentAsElasticSearchResults(__DIR__ . '/data/udb3-core-scroll-1.json');

        $this->client->expects($this->once())
            ->method('search')
            ->with($initialQuery)
            ->willReturn($results);

        $this->client->expects($this->exactly(2))
            ->method('scroll')
            ->with($scrollQuery)
            ->willReturnOnConsecutiveCalls(
                $this->getJsonDocumentAsElasticSearchResults(__DIR__ . '/data/udb3-core-scroll-2.json'),
                $this->getJsonDocumentAsElasticSearchResults(__DIR__ . '/data/udb3-core-scroll-3.json')
            );

        // @codingStandardsIgnoreStart
        $expectedLogs = [
            ['info', 'Dispatching PlaceProjectedToJSONLD with id 39e6d5ee-c3d6-453a-bcb5-4e6e0eaf7054 and url http://udb-silex.dev/place/39e6d5ee-c3d6-453a-bcb5-4e6e0eaf7054.'],
            ['info', 'Dispatching EventProjectedToJSONLD with id 23017cb7-e515-47b4-87c4-780735acc942 and url http://udb-silex.dev/event/23017cb7-e515-47b4-87c4-780735acc942.'],
            ['info', 'Dispatching PlaceProjectedToJSONLD with id a1b3a9d8-ef08-46eb-8984-c7d3012bbb5a and url http://udb-silex.dev/place/a1b3a9d8-ef08-46eb-8984-c7d3012bbb5a.'],
            ['warning', 'Could not process PlaceProjectedToJSONLD with id a1b3a9d8-ef08-46eb-8984-c7d3012bbb5a and url http://udb-silex.dev/place/a1b3a9d8-ef08-46eb-8984-c7d3012bbb5a. Client error: `GET http://udb-silex.dev/place/a1b3a9d8-ef08-46eb-8984-c7d3012bbb5a` resulted in a `410 Gone` response: {"type":"about:blank","status":410}'],
            ['info', 'Dispatching PlaceProjectedToJSONLD with id fb43ae3d-d297-4c4a-8479-488fc028c8c8 and url http://udb-silex.dev/place/fb43ae3d-d297-4c4a-8479-488fc028c8c8.'],
            ['info', 'Dispatching OrganizerProjectedToJSONLD with id 5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83 and url http://udb-silex.dev/organizers/5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83.'],
            ['info', 'Dispatching PlaceProjectedToJSONLD with id a6b0ab58-67a5-496a-8ef8-ac2da14828c2 and url http://udb-silex.dev/place/a6b0ab58-67a5-496a-8ef8-ac2da14828c2.'],
            ['info', 'Dispatching PlaceProjectedToJSONLD with id 8cc3b0d4-bc97-4c17-b3d4-072ccc58b242 and url http://udb-silex.dev/place/8cc3b0d4-bc97-4c17-b3d4-072ccc58b242.'],
            ['info', 'Dispatching EventProjectedToJSONLD with id 02ca9526-f7d6-4338-80fe-88a346fdd118 and url http://udb-silex.dev/event/02ca9526-f7d6-4338-80fe-88a346fdd118.'],
            ['info', 'Dispatching PlaceProjectedToJSONLD with id 179c89c5-dba4-417b-ae96-62e7a12c2405 and url http://udb-silex.dev/place/179c89c5-dba4-417b-ae96-62e7a12c2405.'],
            ['info', 'Dispatching EventProjectedToJSONLD with id 441a5831-a65e-44fa-81ef-5c47e9c57a05 and url http://udb-silex.dev/event/441a5831-a65e-44fa-81ef-5c47e9c57a05.'],
            ['info', 'Cleaning up...'],
            ['info', 'Closed ElasticSearch scroll.'],
        ];
        // @codingStandardsIgnoreEnd

        $expectedEvents = [
            new PlaceProjectedToJSONLD(
                '39e6d5ee-c3d6-453a-bcb5-4e6e0eaf7054',
                'http://udb-silex.dev/place/39e6d5ee-c3d6-453a-bcb5-4e6e0eaf7054'
            ),
            new EventProjectedToJSONLD(
                '23017cb7-e515-47b4-87c4-780735acc942',
                'http://udb-silex.dev/event/23017cb7-e515-47b4-87c4-780735acc942'
            ),
            new PlaceProjectedToJSONLD(
                'a1b3a9d8-ef08-46eb-8984-c7d3012bbb5a',
                'http://udb-silex.dev/place/a1b3a9d8-ef08-46eb-8984-c7d3012bbb5a'
            ),
            new PlaceProjectedToJSONLD(
                'fb43ae3d-d297-4c4a-8479-488fc028c8c8',
                'http://udb-silex.dev/place/fb43ae3d-d297-4c4a-8479-488fc028c8c8'
            ),
            new OrganizerProjectedToJSONLD(
                '5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83',
                'http://udb-silex.dev/organizers/5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83'
            ),
            new PlaceProjectedToJSONLD(
                'a6b0ab58-67a5-496a-8ef8-ac2da14828c2',
                'http://udb-silex.dev/place/a6b0ab58-67a5-496a-8ef8-ac2da14828c2'
            ),
            new PlaceProjectedToJSONLD(
                '8cc3b0d4-bc97-4c17-b3d4-072ccc58b242',
                'http://udb-silex.dev/place/8cc3b0d4-bc97-4c17-b3d4-072ccc58b242'
            ),
            new EventProjectedToJSONLD(
                '02ca9526-f7d6-4338-80fe-88a346fdd118',
                'http://udb-silex.dev/event/02ca9526-f7d6-4338-80fe-88a346fdd118'
            ),
            new PlaceProjectedToJSONLD(
                '179c89c5-dba4-417b-ae96-62e7a12c2405',
                'http://udb-silex.dev/place/179c89c5-dba4-417b-ae96-62e7a12c2405'
            ),
            new EventProjectedToJSONLD(
                '441a5831-a65e-44fa-81ef-5c47e9c57a05',
                'http://udb-silex.dev/event/441a5831-a65e-44fa-81ef-5c47e9c57a05'
            ),
        ];

        $actualEvents = [];

        $this->eventBus->expects($this->exactly(10))
            ->method('publish')
            ->willReturnCallback(
                function (DomainEventStream $stream) use (&$actualEvents) {
                    $domainMessage = $stream->getIterator()[0];
                    $event = $domainMessage->getPayload();
                    $actualEvents[] = $event;

                    // Throw one NOT FOUND exception to check that the
                    // operation continues to loop through the other results.
                    if ($event instanceof PlaceProjectedToJSONLD &&
                        $event->getItemId() == 'a1b3a9d8-ef08-46eb-8984-c7d3012bbb5a') {
                        // @codingStandardsIgnoreStart
                        throw new ClientException(
                            'Client error: `GET http://udb-silex.dev/place/a1b3a9d8-ef08-46eb-8984-c7d3012bbb5a` resulted in a `410 Gone` response: {"type":"about:blank","status":410}',
                            new Request('GET', 'http://udb-silex.dev/place/a1b3a9d8-ef08-46eb-8984-c7d3012bbb5a')
                        );
                        // @codingStandardsIgnoreEnd
                    }
                }
            );

        $this->operation->run($index);

        $this->assertEquals($expectedLogs, $this->logMessages);
        $this->assertEquals($expectedEvents, $actualEvents);
    }

    /**
     * @param string $filePath
     * @return array
     */
    private function getJsonDocumentAsElasticSearchResults($filePath)
    {
        $contents = file_get_contents($filePath);
        return Json::decodeAssociatively($contents);
    }
}
