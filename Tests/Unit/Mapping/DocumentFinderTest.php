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
     * Data provider for testDocumentDir tests.
     *
     * @return array
     */
    public function getTestData()
    {
        $out = [];

        // Case #0.
        $out[] = [
            'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Product',
            'AcmeTestBundle:Product',
            true,
        ];

        // Case #1.
        $out[] = [
            'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Product',
            'AcmeTestBundle:Product',
        ];

        return $out;
    }

    /**
     * Tests if correct namespace is returned.
     *
     * @param string $expectedNamespace
     * @param string $document
     * @param bool   $testPath
     *
     * @dataProvider getTestData
     */
    public function testDocumentDir($expectedNamespace, $document, $testPath = false)
    {
        $finder = new DocumentFinder($this->getBundles());

        $this->assertEquals($expectedNamespace, $finder->getNamespace($document));
        if ($testPath) {
            $this->assertGreaterThan(0, count($finder->getBundleDocumentPaths('AcmeTestBundle')));
        }
    }

    /**
     * @return array
     */
    public function getBundles()
    {
        return ['AcmeTestBundle' => 'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\AcmeTestBundle'];
    }
}
