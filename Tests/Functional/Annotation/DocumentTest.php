<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Annotation;

use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\DummyDocument;

class DocumentTest extends AbstractElasticsearchTestCase
{
    /**
     * Test document mapping.
     */
    public function testDocumentMapping()
    {
        $manager = $this->getManager();
        $repo = $manager->getRepository(DummyDocument::class);

        $type = $repo->getType();
        $mappings = $manager->getClient()->indices()->getMapping(['index' => $manager->getIndexName()]);

        $this->assertArrayHasKey($type, $mappings[$manager->getIndexName()]['mappings']);

        $managerMappings = $manager->getMetadataCollector()->getMapping(DummyDocument::class);

        $this->assertEquals(
            sort($managerMappings['properties']),
            sort($mappings[$manager->getIndexName()]['mappings'][$type])
        );
    }
}
