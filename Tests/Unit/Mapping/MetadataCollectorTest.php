<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Mapping;

use Doctrine\Common\Cache\CacheProvider;
use ONGR\ElasticsearchBundle\Mapping\DocumentFinder;
use ONGR\ElasticsearchBundle\Mapping\DocumentParser;
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;

class MetadataCollectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MetadataCollector
     */
    private $metadataCollector;

    /**
     * @var DocumentFinder
     */
    private $docFinder;

    /**
     * @var DocumentParser
     */
    private $docParser;

    /**
     * @var CacheProvider
     */
    private $cache;

    /**
     * Initialize MetadataCollector.
     */
    public function setUp()
    {
        $this->docFinder = $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\DocumentFinder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->docParser = $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\DocumentParser')
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache = $this->getMockBuilder('Doctrine\Common\Cache\FilesystemCache')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataCollector = new MetadataCollector($this->docFinder, $this->docParser, $this->cache);
    }

    /**
     * Test bundle mapping parser when requesting non string bundle name.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage getBundleMapping() in the Metadata collector expects a string argument only!
     */
    public function testGetBundleMappingWithNotStringName()
    {
        $this->metadataCollector->getBundleMapping(1000);
    }

    /**
     * Test for getClientMapping() in case no mapping exists.
     */
    public function testGetClientMappingNull()
    {
        $this->assertNull($this->metadataCollector->getClientMapping([]));
    }
}
