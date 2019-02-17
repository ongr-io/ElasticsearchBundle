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

use ONGR\ElasticsearchBundle\Mapping\DocumentFinder;

class DocumentFinderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Data provider for testGetNamespace().
     *
     * @return array
     */
    public function getTestGetNamespaceData()
    {
        return [
            [
                'ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\DummyDocument',
                'TestBundle:DummyDocument'
            ],
        ];
    }

    /**
     * Tests for getNamespace().
     *
     * @param string $expectedNamespace
     * @param string $className
     *
     * @dataProvider getTestGetNamespaceData()
     */
    public function testGetNamespace($expectedNamespace, $className)
    {
        $bundles = [
            'TestBundle' => 'ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\TestBundle'
        ];
        $finder = new DocumentFinder($bundles);

        $this->assertEquals($expectedNamespace, $finder->getNamespace($className));
    }

    /**
     * Test for getBundleDocumentClasses().
     */
    public function testGetBundleDocumentClasses()
    {
        $bundles = [
            'TestBundle' => 'ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\TestBundle'
        ];
        $finder = new DocumentFinder($bundles);

        $documents = $finder->getBundleDocumentClasses('TestBundle');

        $this->assertGreaterThan(0, count($documents));
        $this->assertContains('DummyDocument', $documents);
    }
}
