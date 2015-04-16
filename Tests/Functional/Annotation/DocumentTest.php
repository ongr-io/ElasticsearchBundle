<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Annotation;

use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class DocumentTest extends AbstractElasticsearchTestCase
{
    /**
     * Test document mapping.
     */
    public function testDocumentMappingWithAllFieldSetToFalse()
    {
        $document = 'AcmeTestBundle:ColorDocument';
        $manager = $this->getManager();
        $mapping = $manager->getBundlesMapping([$document]);
        $result = $mapping[$document]->getFields();
        $expectedResult = [
            '_parent' => null,
            '_ttl' => null,
            'enabled' => null,
            '_all' => ['enabled' => false],
        ];
        $this->assertEquals($expectedResult, $result);
    }
}
