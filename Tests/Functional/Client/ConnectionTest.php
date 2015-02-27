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
     * Tests updateMapping with real data.
     */
    public function testUpdateMapping()
    {
        $manager = $this->getManager('bar');
        $connection = $manager->getConnection();

        $this->assertEquals(-1, $connection->updateMapping(), 'Connection does not have any mapping loaded.');

        $connection = $this->getManager(
            'bar',
            false,
            $this->getTestMapping()
        )->getConnection();
        $this->assertEquals(1, $connection->updateMapping(), 'Mapping should be updated');

        $connection->forceMapping($this->getTestLessMapping());
        $this->assertEquals(1, $connection->updateMapping(), 'Mapping should be updated');

        $clientMapping = $connection->getClient()->indices()->getMapping(
            [
                'index' => $connection->getIndexName(),
                'type' => 'product',
            ]
        );

        $this->assertArrayNotHasKey('category', $clientMapping[$connection->getIndexName()]['mappings']);
    }

    /**
     * Check if ES version getter works as expected.
     */
    public function testGetVersionNumber()
    {
        $this->assertTrue(version_compare($this->getManager()->getConnection()->getVersionNumber(), '1.0.0', '>='));
    }

    /**
     * Check if open and close works as expected.
     */
    public function testOpenClose()
    {
        $connection = $this->getManager()->getConnection();
        $this->assertTrue($connection->isOpen());
        $connection->close();
        $this->assertFalse($connection->isOpen());
        $connection->open();
        $this->assertTrue($connection->isOpen());
    }

    /**
     * Tests bulk operations.
     */
    public function testBulk()
    {
        $manager = $this->getManager();
        $connection = $manager->getConnection();
        $repository = $manager->getRepository('AcmeTestBundle:Product');

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

        $this->assertNull($repository->find('baz'), 'Document should not be found.');
    }

    /**
     * @return array
     */
    private function getTestMapping()
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
            'category' => [
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
     * @return array
     */
    private function getTestLessMapping()
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
     * Tests if warmers are being loaded.
     */
    public function testWarmers()
    {
        $manager = $this->getManager('default', false);
        $connection = $manager->getConnection();
        $connection->dropAndCreateIndex(true);

        $warmers = $connection->getClient()->indices()->getWarmer(
            [
                'index' => $connection->getIndexName(),
                'name' => '*',
            ]
        );

        $this->assertArrayHasKey('test_foo_warmer', $warmers[$connection->getIndexName()]['warmers']);
    }
}
