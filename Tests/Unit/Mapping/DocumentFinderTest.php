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

class DocumentFinderTest extends \PHPUnit_Framework_TestCase
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
                'ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product',
                'TestBundle:Product'
            ],
            [
                'ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\User',
                'TestBundle:User'
            ],
        ];
    }

    /**
     * Data provider for testGetNamespaceWithSubDirInDocumentDirectory().
     *
     * @return array
     */
    public function getTestGetNamespaceDataWithSubDirInDocumentDir()
    {
        return [
            [
                'ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Store\Product',
                'TestBundle:Product',
                'Document\Store'
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
     * Tests for getNamespace() with a configured document directory.
     *
     * @param string $expectedNamespace
     * @param string $className
     * @param string $documentDir
     *
     * @dataProvider getTestGetNamespaceDataWithSubDirInDocumentDir()
     */
    public function testGetNamespaceWithSubDirInDocumentDirectory($expectedNamespace, $className, $documentDir)
    {
        $bundles = [
            'TestBundle' => 'ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\TestBundle'
        ];
        $finder = new DocumentFinder($bundles);

        $this->assertEquals($expectedNamespace, $finder->getNamespace($className, $documentDir));
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
        $this->assertContains('Product', $documents);
        $this->assertContains('User', $documents);
    }
}
