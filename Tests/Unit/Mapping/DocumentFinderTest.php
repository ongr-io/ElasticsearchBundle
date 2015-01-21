<?php

/*
 * This file is part of the ONGR package.
 *
 * Copyright (c) 2014-2015 NFQ Technologies UAB
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Mapping;

use ONGR\ElasticsearchBundle\Mapping\DocumentFinder;

class DocumentFinderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Data provider for getNamespace tests.
     *
     * @return array $out
     */
    public function getTestData()
    {
        $out = [];

        // Case #0 one level directory.
        $out[] = [
            'Document',
            'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Product'
        ];

        // Case #1 two levels directory, `\` directory separator.
        $out[] = [
            'Document\Document',
            'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Document\Product'
        ];

        // Case #2 two levels directory, `/` directory separator.
        $out[] = [
            'Document/Document',
            'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\Document\Product'
        ];

        return $out;
    }

    /**
     * Tests if correct namespace is returned.
     *
     * @param $documentDir
     * @param $expectedNamespace
     *
     * @dataProvider getTestData
     */
    public function testGetNamespace($documentDir, $expectedNamespace)
    {
        $finder = new DocumentFinder($this->getBundles());
        $finder->setDocumentDir($documentDir);

        $this->assertEquals($expectedNamespace, $finder->getNamespace('AcmeTestBundle:Product'));
    }

    /**
     * @return array
     */
    public function getBundles()
    {
        return ['AcmeTestBundle' => 'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\AcmeTestBundle'];
    }
}
