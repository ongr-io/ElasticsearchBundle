<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Mapping;

use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MetadataCollectorTest extends WebTestCase
{
    /**
     * @var MetadataCollector
     */
    private $metadataCollector;

    /**
     * Initialize MetadataCollector.
     */
    public function setUp()
    {
        $container = $this->createClient()->getContainer();
        $this->metadataCollector = $container->get('es.metadata_collector');
    }

    /**
     * Test if function throws exception if ES type names are not unique.
     *
     * @expectedException \LogicException
     */
    public function testGetBundleMappingWithTwoSameESTypes()
    {
        $this->metadataCollector->getMappings(['AcmeBarBundle', 'AcmeBarBundle']);
    }

    /**
     * Test mapping getter when there are no bundles loaded from parser.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Bundle 'acme' does not exist.
     */
    public function testGetBundleMappingWithNoBundlesLoaded()
    {
        $this->metadataCollector->getBundleMapping('acme');
    }

    /**
     * Test for getBundleMapping(). Make sure meta fields are excluded from mapping.
     */
    public function testGetBundleMapping()
    {
        $mapping = $this->metadataCollector->getBundleMapping('AcmeBarBundle');

        $properties = $mapping['product']['properties'];
        $this->assertArrayNotHasKey('_id', $properties);
//        $this->assertArrayNotHasKey('_ttl', $properties);

        $aliases = $mapping['product']['aliases'];
        $this->assertArrayHasKey('_id', $aliases);
//        $this->assertArrayHasKey('_ttl', $aliases);
        $this->assertArrayHasKey('_routing', $aliases);
    }

    /**
     * Test for getDocumentType() in case invalid class given.
     *
     * @expectedException \ONGR\ElasticsearchBundle\Mapping\Exception\MissingDocumentAnnotationException
     * @expectedExceptionMessage cannot be parsed as document because @Document annotation is missing
     */
    public function testGetDocumentTypeException()
    {
        $this->metadataCollector->getDocumentType('\StdClass');
    }
}
