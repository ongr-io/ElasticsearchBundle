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

/**
 * Property Test.
 */
class PropertyTest extends AbstractElasticsearchTestCase
{
    /**
     * Check if "ignore above" option is set as expected.
     */
    public function testDocumentMappingWithIgnoreAbove()
    {
        $manager = $this->getManager();
        $params = [
            'index' => $manager->getConnection()->getIndexName(),
            'type' => 'product',
            'field' => 'limited',
        ];
        $result = $manager->getConnection()->getClient()->indices()->getFieldMapping($params);
        $expectedMapping = [
            'limited' => [
                'ignore_above' => 20,
                'type' => 'string',
                'index' => 'not_analyzed',
            ],
        ];
        $this->assertEquals($expectedMapping, $result['ongr-esb-test']['mappings']['product']['limited']['mapping']);
    }
}

