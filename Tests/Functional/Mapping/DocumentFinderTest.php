<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Functional\Mapping;

use Ongr\ElasticsearchBundle\Mapping\DocumentFinder;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DocumentFinderTest extends WebTestCase
{
    /**
     * Tests if document paths are returned for fixture bundle.
     */
    public function testGetBundleDocumentPaths()
    {
        $finder = new DocumentFinder($this->getContainer()->getParameter('kernel.bundles'));
        $this->assertGreaterThan(0, count($finder->getBundleDocumentPaths('AcmeTestBundle')));
    }

    /**
     * Tests if exception is thrown for unregistered bundle.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Bundle 'DemoBundle' does not exist.
     */
    public function testGetBundleClassException()
    {
        $finder = new DocumentFinder($this->getContainer()->getParameter('kernel.bundles'));
        $finder->getBundleClass('DemoBundle');
    }

    /**
     * Returns service container.
     *
     * @return object
     */
    public function getContainer()
    {
        return $this->createClient()->getContainer();
    }
}
