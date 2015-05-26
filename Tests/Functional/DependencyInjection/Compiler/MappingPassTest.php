<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DependencyInjection\Compiler;

use ONGR\ElasticsearchBundle\Client\Connection;
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use ONGR\ElasticsearchBundle\ORM\Manager;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * Check if mapping is loaded as expected.
 */
class MappingPassTest extends AbstractElasticsearchTestCase
{
    /**
     * Tests if mapping is gathered correctly.
     *
     * Mapping is loaded from fixture bundle in Tests/app/fixture.
     */
    public function testMapping()
    {
        $container = $this->createClient()->getContainer();

        $this->assertArrayHasKey(
            'AcmeTestBundle',
            $container->getParameter('kernel.bundles'),
            'Test bundle is not loaded.'
        );

        /** @var MetadataCollector $mappingService */
        $mappingService = $container->get('es.metadata_collector');

        /** @var Connection $connection */
        $connection = $container->get('es.manager')->getConnection();
        $productMapping = $connection->getMapping('product');
        $expectedMapping = $mappingService->getMappingByNamespace(
            'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Product'
        );

        $this->assertEquals($expectedMapping['product']['properties'], $productMapping['properties']);
    }

    /**
     * Check if changed index name in manager is passed into repository services.
     */
    public function testConnectionIndexNameChange()
    {
        $container = $this->createClient()->getContainer();

        /** @var Manager $manager */
        $manager = $container->get('es.manager.default');
        $manager->getConnection()->setIndexName('new-index');

        /** @var Repository $repository */
        $repository = $container->get('es.manager.default.product');
        $this->assertEquals(
            'new-index',
            $repository->getManager()->getConnection()->getIndexName()
        );
    }
}
