<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Client;

use Elasticsearch\Client;
use ONGR\ElasticsearchBundle\Client\Connection;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests if right values are being taken out.
     */
    public function testGetters()
    {
        $config = [
            'index' => 'index_name',
            'body' => [
                'mappings' => [
                    'test_mapping' => [
                        'properties' => [],
                    ],
                ],
            ],
        ];

        $connection = new Connection(new Client(), $config);

        $this->assertEquals(
            'index_name',
            $connection->getIndexName(),
            'Recieved wrong index name'
        );
        $this->assertNull(
            $connection->getMapping('product'),
            'should not contain product mapping'
        );
        $this->assertArrayHasKey(
            'properties',
            $connection->getMapping('test_mapping'),
            'should contain test mapping'
        );
    }

    /**
     * Tests drop and create index behaviour.
     */
    public function testDropAndCreateIndex()
    {
        $indices = $this
            ->getMockBuilder('Elasticsearch\Namespaces\IndicesNamespace')
            ->disableOriginalConstructor()
            ->getMock();

        $indices
            ->expects($this->once())
            ->method('create')
            ->with(['index' => 'foo', 'body' => []]);

        $indices
            ->expects($this->once())
            ->method('delete')
            ->with(['index' => 'foo']);

        $client = $this
            ->getMockBuilder('Elasticsearch\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $client
            ->expects($this->exactly(2))
            ->method('indices')
            ->will($this->returnValue($indices));

        $connection = new Connection($client, ['index' => 'foo', 'body' => []]);
        $connection->dropAndCreateIndex();
    }

    /**
     * Tests if scroll request is made properly.
     */
    public function testScroll()
    {
        $client = $this->getMock('Elasticsearch\Client');
        $client->expects($this->once())
            ->method('scroll')
            ->with(['scroll_id' => 'test_id', 'scroll' => '5m'])
            ->willReturn('test');

        $connection = new Connection($client, ['index' => 'foo', 'body' => []]);
        $result = $connection->scroll('test_id', '5m');

        $this->assertEquals('test', $result);
    }

    /**
     * Tests if exception is thown when unknown operation is recieved.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testBulkException()
    {
        $client = $this
            ->getMockBuilder('Elasticsearch\Client')
            ->getMock();

        $connection = new Connection($client, []);
        $connection->bulk('unknownOperation', 'foo_type', []);
    }
}
