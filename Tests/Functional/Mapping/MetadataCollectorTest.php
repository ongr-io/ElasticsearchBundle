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

use ONGR\ElasticsearchBundle\Exception\MissingDocumentAnnotationException;
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use ONGR\ElasticsearchBundle\Tests\WebTestCase;
use Symfony\Bridge\PhpUnit\SetUpTearDownTrait;

class MetadataCollectorTest extends WebTestCase
{
    use SetUpTearDownTrait;

    /**
     * @var MetadataCollector
     */
    private $metadataCollector;

    /**
     * Initialize MetadataCollector.
     */
    public function doSetUp()
    {
        $container = $this->createClient()->getContainer();
        $this->metadataCollector = $container->get('es.metadata_collector');
    }

    /**
     * Test if function throws exception if ES type names are not unique.
     */
    public function testGetBundleMappingWithTwoSameESTypes()
    {
        $this->expectException(\LogicException::class);

        $this->metadataCollector->getMappings(['TestBundle', 'TestBundle']);
    }

    /**
     * Test mapping getter when there are no bundles loaded from parser.
     */
    public function testGetBundleMappingWithNoBundlesLoaded()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Bundle \'acme\' does not exist.');

        $this->metadataCollector->getBundleMapping('acme');
    }

    /**
     * Test for getBundleMapping(). Make sure meta fields are excluded from mapping.
     */
    public function testGetBundleMapping()
    {
        $mapping = $this->metadataCollector->getBundleMapping('TestBundle');

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
     */
    public function testGetDocumentTypeException()
    {
        $this->expectException(MissingDocumentAnnotationException::class);
        $this->expectExceptionMessage('cannot be parsed as document because @Document annotation is missing');

        $this->metadataCollector->getDocumentType('\StdClass');
    }
}
