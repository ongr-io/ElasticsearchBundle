<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Client;

use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

/**
 * Functional tests for connection service.
 */
class ConnectionTest extends ElasticsearchTestCase
{
    /**
     * @return array
     */
    protected function getTestMapping()
    {
        return [
            'product' => [
                'properties' => [
                    'id' => [
                        'type' => 'string',
                        'index' => 'not_analyzed',
                    ],
                    'title' => ['type' => 'string'],
                ],
            ],
        ];
    }

    /**
     * Tests updateMapping with real data.
     */
    public function testUpdateMapping()
    {
        $manager = $this->getManager('bar');
        $connection = $manager->getConnection();

        // Using phpunit setExpectedException does not continue after exception is thrown.
        $thrown = false;
        try {
            $connection->updateMapping();
        } catch (\LogicException $e) {
            $thrown = true;
            // Continue.
        }
        $this->assertTrue($thrown, '\LogicException should be thrown');

        $connection = $this->getManager(
            'bar',
            false,
            $this->getTestMapping()
        )->getConnection();

        $status = $connection->updateMapping();
        $this->assertTrue($status, 'Mapping should be updated');
    }

    /**
     * Tests bulk operations.
     */
    public function testBulk()
    {
        $manager = $this->getManager();
        $connection = $manager->getConnection();
        $repository = $manager->getRepository('ONGRTestingBundle:Product');

        // CREATE.
        $connection->bulk('create', 'product', ['_id' => 'baz', 'title' => 'Awesomo']);
        $connection->commit();

        $product = $repository->find('baz');
        $this->assertEquals('Awesomo', $product->title, 'Document should be created.');

        // UPDATE.
        $connection->bulk('update', 'product', ['_id' => 'baz', 'title' => 'Improved awesomo']);
        $connection->commit();

        $product = $repository->find('baz');
        $this->assertEquals('Improved awesomo', $product->title, 'Document should be updated.');

        // INDEX.
        $connection->bulk('index', 'product', ['_id' => 'baz', 'title' => 'Indexed awesomo']);
        $connection->commit();

        $product = $repository->find('baz');
        $this->assertEquals('Indexed awesomo', $product->title, 'Document should be indexed.');

        // DELETE.
        $connection->bulk('delete', 'product', ['_id' => 'baz']);
        $connection->commit();

        $this->setExpectedException('Elasticsearch\Common\Exceptions\Missing404Exception');
        $product = $repository->find('baz');
    }
}
