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

use ONGR\ElasticsearchBundle\Mapping\DocumentFinder;
use ONGR\ElasticsearchBundle\Tests\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DocumentFinderTest extends WebTestCase
{
    /**
     * Tests if document paths are returned for fixture bundle.
     */
    public function testGetBundleDocumentClasses()
    {
        $finder = new DocumentFinder($this->getContainer()->getParameter('kernel.bundles'));
        $this->assertGreaterThan(0, count($finder->getBundleDocumentClasses('TestBundle')));
        $this->assertEquals(0, count($finder->getBundleDocumentClasses('FrameworkBundle')));
    }

    /**
     * Tests if exception is thrown for unregistered bundle.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Bundle 'NotExistingBundle' does not exist.
     */
    public function testGetBundleClassException()
    {
        $finder = new DocumentFinder($this->getContainer()->getParameter('kernel.bundles'));
        $finder->getBundleClass('NotExistingBundle');
    }

    /**
     * Returns service container.
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->createClient()->getContainer();
    }
}
