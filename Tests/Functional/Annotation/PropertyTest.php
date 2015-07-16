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

use ONGR\ElasticsearchBundle\ORM\Manager;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\ElasticsearchBundle\Test\DelayedObjectWrapper;

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

    /**
     * Check if "store" and "indexName" options are set as expected.
     */
    public function testDocumentMappingWithStore()
    {
        $manager = DelayedObjectWrapper::wrap($this->getManager());
        $params = [
            'index' => $manager->getConnection()->getIndexName(),
            'type' => 'product',
            'field' => 'stored',
        ];
        $result = $manager->getConnection()->getClient()->indices()->getFieldMapping($params);
        $expectedMapping = [
            'stored' => [
                'store' => true,
                'index_name' => 'ongr-esb-test',
                'type' => 'string',
            ],
        ];
        $this->assertEquals($expectedMapping, $result['ongr-esb-test']['mappings']['product']['stored']['mapping']);
    }

    /**
     * Data provider for testDocumentMappingWithRawData.
     *
     * @return array
     */
    public function rawDataTestProvider()
    {
        return [
            // Case #0. Additional data.
            [
                'type' => 'Media',
                'field' => 'name',
                'expected' => [
                    'name' => [
                        'type' => 'string',
                        'index' => 'not_analyzed',
                        'null_value' => 'data',
                    ],
                ],
            ],
            // Case #1. Overridden data.
            [
                'type' => 'Media',
                'field' => 'title',
                'expected' => [
                    'title' => [
                        'type' => 'string',
                        'index' => 'no',
                    ],
                ],
            ],
            // Case #2. Additional and Overridden data.
            [
                'type' => 'Media',
                'field' => 'description',
                'expected' => [
                    'description' => [
                        'type' => 'string',
                        'index' => 'no',
                        'null_value' => 'data',
                    ],
                ],
            ],
        ];
    }

    /**
     * Check if "raw" data was merged into mapping as expected.
     *
     * @param string $type
     * @param string $field
     * @param array  $expected
     *
     * @dataProvider rawDataTestProvider
     */
    public function testDocumentMappingWithRawData($type, $field, $expected)
    {
        /** @var Manager $manager */
        $manager = DelayedObjectWrapper::wrap($this->getManager());
        $index = $manager->getConnection()->getIndexName();
        $params = [
            'index' => $index,
            'type' => $type,
            'field' => $field,
        ];
        $result = $manager->getConnection()->getClient()->indices()->getFieldMapping($params);
        $this->assertEquals($expected, $result[$index]['mappings'][$type][$field]['mapping']);
    }
}
