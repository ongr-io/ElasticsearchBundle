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
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Check if mapping is loaded as expected.
 */
class MappingPassTest extends WebTestCase
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
}
