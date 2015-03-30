<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Unit\Mapping;

use Ongr\ElasticsearchBundle\Mapping\DocumentFinder;

class DocumentFinderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Data provider for testDocumentDir tests.
     *
     * @return array $out
     */
    public function getTestData()
    {
        $out = [];

        // Case #0 one level directory.
        $out[] = [
            'Document',
            'Ongr\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Product',
            'AcmeTestBundle:Product',
            true,
        ];

        // Case #1 two levels directory, `\` directory separator.
        $out[] = [
            'Document\Document',
            'Ongr\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Document\Product',
            'AcmeTestBundle:Product',
        ];

        // Case #2 two levels directory, `/` directory separator.
        $out[] = [
            'Document/Document',
            'Ongr\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Document\Product',
            'AcmeTestBundle:Product',
        ];

        // Case #3 two levels directory, `/` directory separator.
        $out[] = [
            'Document/Test',
            'Ongr\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Test\Item',
            'AcmeTestBundle:Item',
            true,
        ];

        // Case #4 two levels directory, `\` directory separator.
        $out[] = [
            'Document\Test',
            'Ongr\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Test\Item',
            'AcmeTestBundle:Item',
            true,
        ];

        return $out;
    }

    /**
     * Tests if correct namespace is returned.
     *
     * @param string $documentDir
     * @param string $expectedNamespace
     * @param string $document
     * @param bool   $testPath
     *
     * @dataProvider getTestData
     */
    public function testDocumentDir($documentDir, $expectedNamespace, $document, $testPath = false)
    {
        $finder = new DocumentFinder($this->getBundles());
        $finder->setDocumentDir($documentDir);

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
        return ['AcmeTestBundle' => 'Ongr\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\AcmeTestBundle'];
    }
}
