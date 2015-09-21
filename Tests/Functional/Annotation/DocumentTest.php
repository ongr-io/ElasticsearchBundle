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

class DocumentTest extends AbstractElasticsearchTestCase
{
    /**
     * Test document mapping.
     */
    public function testDocumentMapping()
    {
        $manager = $this->getManager();
        $repo = $manager->getRepository('AcmeBarBundle:ProductDocument');

        $type = $repo->getTypes()[0];
        $mappings = $manager->getClient()->indices()->getMapping(['index' => $manager->getIndexName()]);

        $this->assertArrayHasKey($type, $mappings[$manager->getIndexName()]['mappings']);

        $managerMappings = $manager->getMetadataCollector()->getMapping('AcmeBarBundle:ProductDocument');

        $this->assertEquals(
            sort($managerMappings['properties']),
            sort($mappings[$manager->getIndexName()]['mappings'][$type])
        );
    }
}
