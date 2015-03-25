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

use Elasticsearch\Common\Exceptions\ElasticsearchException;
use ONGR\ElasticsearchBundle\Client\Connection;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * Functional tests for connection service.
 */
class ConnectionTest extends AbstractElasticsearchTestCase
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
     * Tests if update mapping throws exception for read only manager.
     *
     * @expectedException \Elasticsearch\Common\Exceptions\Forbidden403Exception
     * @expectedExceptionMessage Manager is readonly! Update types operation not permitted.
     */
    public function testReadOnlyManagerUpdateMapping()
    {
        $connection = $this->getReadOnlyManager()->getConnection();

        $connection->updateTypes([]);
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
        try {
            $connection = $this->getManager()->getConnection();
            $this->assertTrue($connection->isOpen());
            $connection->close();
            $this->assertFalse($connection->isOpen());
            $connection->open();
            $this->assertTrue($connection->isOpen());
        } catch (ElasticsearchException $e) {
            $this->testOpenClose();
        }
    }

    /**
     * Tests if open index throws exception for read only manager.
     *
     * @expectedException \Elasticsearch\Common\Exceptions\Forbidden403Exception
     * @expectedExceptionMessage Manager is readonly! Open index operation not permitted.
     */
    public function testReadOnlyManagerOpenIndex()
    {
        $connection = null;

        try {
            $manager = $this->getManager();
            $manager->getConnection()->close();
            $connection = $this->getReadOnlyManager()->getConnection();
            $this->assertFalse($connection->isOpen());
        } catch (ElasticsearchException $e) {
            $this->testReadOnlyManagerOpenIndex();
        }

        if ($connection) {
            $connection->open();
        }
    }

    /**
     * Tests if close index throws exception for read only manager.
     *
     * @expectedException \Elasticsearch\Common\Exceptions\Forbidden403Exception
     * @expectedExceptionMessage Manager is readonly! Close index operation not permitted.
     */
    public function testReadOnlyManagerCloseIndex()
    {
        $connection = $this->getReadOnlyManager()->getConnection();

        $this->assertTrue($connection->isOpen());
        $connection->close();
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
        $connection->dropAndCreateIndex(true, false);

        $warmers = $connection->getClient()->indices()->getWarmer(
            [
                'index' => $connection->getIndexName(),
                'name' => '*',
            ]
        );

        $this->assertArrayHasKey('test_foo_warmer', $warmers[$connection->getIndexName()]['warmers']);
    }

    /**
     * Tests if delete warmer throws exception for read only manager.
     *
     * @expectedException \Elasticsearch\Common\Exceptions\Forbidden403Exception
     * @expectedExceptionMessage Manager is readonly! Warmer edit operation not permitted.
     */
    public function testReadOnlyManagerWarmerAction()
    {
        $this->getManager();

        /** @var Connection $connection */
        $connection = $this->getReadOnlyManager()->getConnection();

        $connection->deleteWarmers(['test_foo_warmer']);
    }

    /**
     * Tests if cache clear throws exception for read only manager.
     *
     * @expectedException \Elasticsearch\Common\Exceptions\Forbidden403Exception
     * @expectedExceptionMessage Manager is readonly! Clear cache operation not permitted.
     */
    public function testReadOnlyManagerCacheClear()
    {
        $this->getManager();

        /** @var Connection $connection */
        $connection = $this->getReadOnlyManager()->getConnection();
        $connection->clearCache();
    }

    /**
     * Tests if drop index throws exception for read only manager.
     */
    public function testReadOnlyManagerDropIndex()
    {
        $this->getManager();

        /** @var Connection $connection */
        $connection = $this->getReadOnlyManager()->getConnection();
        try {
            $connection->dropIndex();
        } catch (\Exception $e) {
            $this->assertInstanceOf('\Elasticsearch\Common\Exceptions\Forbidden403Exception', $e);
            $this->assertEquals('Manager is readonly! Drop index operation not permitted.', $e->getMessage());
        }
        $this->assertTrue($connection->indexExists($connection->getIndexName()));
    }

    /**
     * Tests if create index throws exception for read only manager.
     *
     * @expectedException \Elasticsearch\Common\Exceptions\Forbidden403Exception
     * @expectedExceptionMessage Manager is readonly! Create index operation not permitted.
     */
    public function testReadOnlyManagerCreateIndex()
    {
        $this->getManager('readonly');
    }

    /**
     * @return object
     */
    public function getReadOnlyManager()
    {
        $manager = $this->getContainer()->get('es.manager.readonly');

        return $manager;
    }
}
